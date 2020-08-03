<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 * DCSLib
 *
 * @uses TikiLib
 */
class DCSLib extends TikiLib
{
    /**
     * @param $result
     * @param null $lang
     * @return mixed
     */
    private function convert_results($result, $lang = null)
    {
        foreach ($result as &$row) {
            $row['page_name'] = '';

            if ($row['content_type'] == 'page' && substr($row['data'], 0, 5) == 'page:') {
                $row['page_name'] = substr($row['data'], 5);

                $row['data'] = $this->get_content_from_page($row['page_name'], $lang);
            }
        }

        return $result;
    }

    /**
     * @param $page
     * @param null $lang
     * @return string
     */
    private function get_content_from_page($page, $lang = null)
    {
        global $prefs;
        $info = $this->get_page_info($page);

        if ($prefs['feature_multilingual'] == 'y') {
            $multilinguallib = TikiLib::lib('multilingual');

            if ($lang && $info['lang'] && $lang != $info['lang']) {
                $bestLangPageId = $multilinguallib->selectLangObj('wiki page', $info['page_id'], $lang);

                if ($info['page_id'] != $bestLangPageId) {
                    $info = $this->get_page_info_from_id($bestLangPageId);
                }
            }
        }

        if ($info) {
            return TikiLib::htmldecode($info['data']);
        }
    }

    /**
     * @param $result
     * @param null $lang
     * @return mixed
     */
    private function first_data($result, $lang = null)
    {
        $result = $this->convert_results($result, $lang);
        if ($first = reset($result)) {
            return $first['data'];
        }
    }

    /**
     * @param $fieldvalue
     * @param null $lang
     * @return mixed
     */
    public function get_actual_content($fieldvalue, $lang = null)
    {
        $query = 'SELECT * FROM `tiki_programmed_content` WHERE `contentId`=? AND `publishDate`<=? ORDER BY `publishDate` DESC';
        $result = $this->fetchAll($query, [(int)$fieldvalue, $this->now]);

        return $this->first_data($result, $lang);
    }

    /**
     * @param $fieldvalue
     * @param null $lang
     * @return mixed
     */
    public function get_actual_content_by_label($fieldvalue, $lang = null)
    {
        $query = 'SELECT tpc.*'
            . ' FROM `tiki_programmed_content` AS tpc, `tiki_content` AS tc'
            . ' WHERE tpc.`contentId` = tc.`contentId` AND tc.`contentLabel`=? AND `publishDate`<=? ORDER BY `publishDate` DESC';
        $result = $this->fetchAll($query, [$fieldvalue, $this->now]);

        return $this->first_data($result, $lang);
    }

    /**
     * @param $contentId
     */
    public function remove_contents($contentId)
    {
        $query = "delete from `tiki_programmed_content` where `contentId`=?";

        $result = $this->query($query, [$contentId]);
        $query = "delete from `tiki_content` where `contentId`=?";
        $result = $this->query($query, [$contentId]);
    }

    /**
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_content($offset = 0, $maxRecords = -1, $sort_mode = 'contentId_desc', $find = '')
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " WHERE (`description` LIKE ? OR `contentLabel` LIKE ?)";
            $bindvars = [$findesc, $findesc];
        } else {
            $mid = '';
            $bindvars = [];
        }

        $query = 'SELECT `tc`.*, tpcd.`data`,'
                . ' COALESCE(`tpcf`.`future`,0) AS `future`,'
                . ' COALESCE(`tpca`.`actual`,?) AS `actual`,'
                . ' COALESCE(`tpcn`.`next`,?) AS `next`,'
                . ' GREATEST(0, COALESCE(`tpco`.`old`,0) - 1) AS `old`'
            . ' FROM (`tiki_content` AS `tc`'
                . ' LEFT JOIN ( SELECT `contentId`, count(*) AS `future` FROM `tiki_programmed_content` WHERE `publishDate`>? GROUP BY `contentId` ) AS `tpcf` ON ( `tc`.`contentId` = `tpcf`.`contentId` )'
                . ' LEFT JOIN ( SELECT `contentId`, max(`publishDate`) AS `actual` FROM `tiki_programmed_content` WHERE `publishDate`<=? GROUP BY `contentId` ) AS `tpca` ON ( `tc`.`contentId` = `tpca`.`contentId` )'
                . ' LEFT JOIN ( SELECT `contentId`, min(`publishDate`) AS `next` FROM `tiki_programmed_content` WHERE `publishDate`>=? GROUP BY `contentId` ) AS `tpcn` ON ( `tc`.`contentId` = `tpcn`.`contentId` )'
                . ' LEFT JOIN ( SELECT `contentId`, count(*) AS `old` FROM `tiki_programmed_content` WHERE `publishDate`<? GROUP BY `contentId` ) AS `tpco` ON ( `tc`.`contentId` = `tpco`.`contentId` )'
                . ' LEFT JOIN ( SELECT `contentId`, `data`, `publishDate` FROM `tiki_programmed_content` ) AS `tpcd` ON ( `tc`.`contentId` = `tpcd`.`contentId` AND `tpcd`.`publishDate` = `tpca`.`actual` ))'
            . " $mid ORDER BY " . $this->convertSortMode($sort_mode);

        $query_cant = "select count(*) from `tiki_content` $mid";

        $result = $this->query(
            $query,
            array_merge(
                [
                    $this->now,
                    $this->now,
                    $this->now,
                    $this->now,
                    $this->now,
                    $this->now
                ],
                $bindvars
            ),
            $maxRecords,
            $offset
        );

        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;

        return $retval;
    }

    /**
     * @param $contentId
     * @return mixed
     */
    public function get_actual_content_date($contentId)
    {
        $query = "select max(`publishDate`) from `tiki_programmed_content` where `contentId`=? and `publishDate`<=?";
        $res = $this->getOne($query, [$contentId, $this->now]);

        return $res;
    }

    /**
     * @param int $contentId
     * @param null $lang
     * @return string
     */
    public function get_random_content($contentId = 0, $lang = null)
    {
        $where = ' WHERE `publishDate`<=?';
        $bindvars = [$this->now];

        if ((int)$contentId > 0) {
            $bindvars[] = $contentId;
            $where .= ' AND `contentId`=?';
        }

        $querycant = 'SELECT count(*) FROM `tiki_programmed_content`' . $where;
        $cant = $this->getOne($querycant, $bindvars);

        if (! $cant) {
            return '';
        }

        $x = mt_rand(0, $cant - 1);
        $query = 'SELECT * FROM `tiki_programmed_content`' . $where;
        $result = $this->fetchAll($query, $bindvars, 1, $x);

        return $this->first_data($result, $lang);
    }

    /**
     * @param $contentId
     * @return mixed
     */
    public function get_next_content($contentId)
    {
        $query = "select min(`publishDate`) from `tiki_programmed_content` where `contentId`=? and `publishDate`>?";
        $res = $this->getOne($query, [$contentId, $this->now]);

        return $res;
    }

    /**
     * @param $contentId
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_programmed_content($contentId, $offset = 0, $maxRecords = -1, $sort_mode = 'publishDate_desc', $find = '')
    {
        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " where `contentId`=? and (`data` like ?) ";
            $bindvars = [$contentId, $findesc];
        } else {
            $mid = " where `contentId`=?";
            $bindvars = [$contentId];
        }

        $query = "select * from `tiki_programmed_content` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_programmed_content` $mid";
        $result = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        $retval = [];
        $retval["data"] = $this->convert_results($result);
        $retval["cant"] = $cant;

        return $retval;
    }

    /**
     * @param $pId
     * @param $contentId
     * @param $publishDate
     * @param $data
     * @param string $content_type
     * @return mixed
     */
    public function replace_programmed_content($pId, $contentId, $publishDate, $data, $content_type = 'static')
    {
        if (! $pId) {
            // was replace into ...
            $query = "insert into `tiki_programmed_content`(`contentId`,`publishDate`,`data`,`content_type`) values(?,?,?,?)";

            $result = $this->query($query, [$contentId, $publishDate, $data, $content_type]);
            $query = "select max(`pId`) from `tiki_programmed_content` where `publishDate`=? and `data`=?";
            $id = $this->getOne($query, [$publishDate, $data]);
        } else {
            $query
                = "update `tiki_programmed_content` set `contentId`=?, `publishDate`=?, `data`=?, `content_type`=? where `pId`=?";

            $result = $this->query($query, [$contentId, $publishDate, $data, $content_type, $pId]);
            $id = $pId;
        }

        return $id;
    }

    /**
     * @param $id
     * @return bool
     */
    public function remove_programmed_content($id)
    {
        $query = "delete from `tiki_programmed_content` where `pId`=?";

        $result = $this->query($query, [$id]);

        return true;
    }

    /**
     * @param $fieldvalue
     * @param string $fieldname
     * @return bool|mixed
     */
    public function get_content($fieldvalue, $fieldname = 'contentId')
    {
        if ($fieldname != 'contentId' && $fieldname != 'contentLabel') {
            return false;
        }

        $query = 'select * from `tiki_content` where `' . $fieldname . '`=?';
        $result = $this->fetchAll($query, [$fieldvalue]);
        $result = $this->convert_results($result);

        return reset($result);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_programmed_content($id)
    {
        $query = "select * from `tiki_programmed_content` where `pId`=?";

        $result = $this->fetchAll($query, [$id]);
        $result = $this->convert_results($result);

        return reset($result);
    }

    /**
     * @param $contentId
     * @param $description
     * @param null $label
     * @return mixed
     */
    public function replace_content($contentId, $description, $label = null)
    {
        $bindvars = [$description];
        if ($label !== null) {
            $bindvars[] = $label;
        }

        if ($contentId > 0) {
            $query = 'update `tiki_content` set `description`=?'
                . ($label === null ? '' : ',`contentLabel`=?')
                . ' where `contentId`=?';

            $bindvars[] = $contentId;
            $result = $this->query($query, $bindvars);
        } else {
            $query = 'insert into `tiki_content` (`description`'
                . ($label === null ? '' : ',`contentLabel`')
                . ') values(?'
                . ($label === null ? '' : ',?')
                . ')';

            $result = $this->query($query, $bindvars);
            $contentId = $this->getOne(
                'select max(`contentId`) from `tiki_content` where `description` = ? and `contentLabel` = ?',
                $bindvars
            );
        }

        return $contentId;
    }
}
