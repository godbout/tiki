<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 *
 */
class ContributionLib extends TikiLib
{
    /**
     * @param        $name
     * @param string $description
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function add_contribution($name, $description = '')
    {
        $query = 'insert into `tiki_contributions`(`name`, `description`) values(?, ?)';

        return $this->query($query, [$name, $description]);
    }

    /**
     * @param $contributionId
     * @return mixed
     */
    public function get_contribution($contributionId)
    {
        $query = 'select * from `tiki_contributions` where `contributionId`=?';
        $result = $this->query($query, [(int)$contributionId]);

        return $result->fetchRow();
    }

    /**
     * @param        $contributionId
     * @param        $name
     * @param string $description
     *
     * @return TikiDb_Pdo_Result
     */
    public function replace_contribution($contributionId, $name, $description = '')
    {
        $query = 'update `tiki_contributions` set `name`= ?, `description`=? where `contributionId`=?';

        return $this->query($query, [$name, $description, (int)$contributionId]);
    }

    /**
     * @param $contributionId
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_contribution($contributionId)
    {
        $query = 'delete from `tiki_contributions`where `contributionId`=?';

        return $this->query($query, [$contributionId]);
    }

    /**
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_contributions($offset = 0, $maxRecords = -1, $sort_mode = 'name_asc', $find = '')
    {
        $bindvars = [];

        if ($find) {
            $mid = ' where (`name` like ?)';
            $bindvars[] = "%$find%";
        } else {
            $mid = '';
        }

        $query = "select * from `tiki_contributions` $mid order by " . $this->convertSortMode($sort_mode);
        $result = $this->query($query, $bindvars, $maxRecords, $offset);

        $query_cant = "select count(*) from `tiki_contributions` $mid";
        $cant = $this->getOne($query_cant, $bindvars);

        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    /**
     * @param $contributions
     * @param $itemId
     * @param $objectType
     * @param string $description
     * @param string $name
     * @param string $href
     */
    public function assign_contributions($contributions, $itemId, $objectType, $description = '', $name = '', $href = '')
    {
        $objectlib = TikiLib::lib('object');

        if (($objectId = $objectlib->get_object_id($objectType, $itemId)) == 0) {
            $objectId = $objectlib->insert_object($objectType, $itemId, $description, $name, $href);
        } else {
            $query = 'delete from `tiki_contributions_assigned` where `objectId`=?';
            $this->query($query, [(int)$objectId]);
        }

        if (! empty($contributions)) {
            $query = 'insert `tiki_contributions_assigned` (`contributionId`, `objectId`) values(?,?)';
            foreach ($contributions as $contribution) {
                if ($contribution) {
                    $this->query($query, [(int)$contribution, (int)$objectId]);
                }
            }
        }
    }

    /**
     * @param $itemId
     * @param $objectType
     * @return array
     */
    public function get_assigned_contributions($itemId, $objectType)
    {
        $query = 'select tc.* from `tiki_contributions` tc, `tiki_contributions_assigned` tca, `tiki_objects` tob' .
                        ' where tob.`itemId`=? and tob.`type`=? and tca.`objectId`=tob.`objectId` and tca.`contributionId`= tc.`contributionId`' .
                        ' order by tob.`type`desc, tc.`name` asc';

        $result = $this->query($query, [$itemId, $objectType]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * @param $itemIdOld
     * @param $objectTypeOld
     * @param $itemIdNew
     * @param $objectTypeNew
     * @param $description
     * @param $name
     * @param $href
     */
    public function change_assigned_contributions($itemIdOld, $objectTypeOld, $itemIdNew, $objectTypeNew, $description, $name, $href)
    {
        if ($this->get_assigned_contributions($itemIdOld, $objectTypeOld)) {
            $objectlib = TikiLib::lib('object');
            if (($objectId = $objectlib->get_object_id($objectTypeNew, $itemIdNew)) == 0) { // create object
                $objectId = $objectlib->insert_object($objectTypeNew, $itemIdNew, $description, $name, $href);
            }

            $query = 'update `tiki_contributions_assigned` tca' .
                            ' left join `tiki_objects` tob on tob.`objectId`= tca.`objectId` set tca.`objectId`=?' .
                            ' where tob.`itemId`=? and tob.`type`=?';

            $this->query($query, [(int)$objectId, $itemIdOld, $objectTypeOld]);
        }
    }

    /**
     * @param $itemId
     * @param $objectType
     */
    public function remove_assigned_contributions($itemId, $objectType)
    {
        // works only if mysql> 4
        // $query = 'delete tca from `tiki_contributions_assigned` tca left join `tiki_objects`tob on tob.`objectId`=tca.`objectId` where tob.`itemId`= ? and tob.`type`= ?';

        $objectlib = TikiLib::lib('object');
        $objectId = $objectlib->get_object_id($objectType, $itemId);
        $query = 'delete from `tiki_contributions_assigned` where `objectId`= ?';
        $this->query($query, [$objectId]);
    }

    /**
     * @param $page
     */
    public function remove_page($page)
    {
        $objectlib = TikiLib::lib('object');
        $query = 'select * from `tiki_history` where `pageName` = ?';
        $result = $this->query($query, [$page]);

        while ($res = $result->fetchRow()) {
            $this->remove_history($res['historyId']);
        }

        $this->remove_assigned_contributions($page, 'wiki page');
    }

    /**
     * @param $historyId
     */
    public function remove_history($historyId)
    {
        //history object only created for contribution yet. You can remove object
        $objectlib = TikiLib::lib('object');

        $this->remove_assigned_contributions($historyId, 'history');
        $objectlib->delete_object('history', $historyId);
    }

    /**
     * @param $commentId
     */
    public function remove_comment($commentId)
    {
        //history object only created for contribution yet. You can remove object
        $objectlib = TikiLib::lib('object');

        $this->remove_assigned_contributions($commentId, 'comment');
        $objectlib->delete_object('comment', $commentId);
    }

    /**
     * @param $contributions
     * @return string
     */
    public function print_contributions($contributions)
    {
        $print = '';

        foreach ($contributions as $contribution) {
            if (! empty($print)) {
                $print .= ',';
            }

            $res = $this->get_contribution($contribution);
            $print .= $res['name'];
        }

        return $print;
    }

    /**
     * @param $action
     * @param $contributions
     * @param int $delay
     * @return bool
     */
    public function update($action, $contributions, $delay = 15)
    {
        $tikilib = TikiLib::lib('tiki');
        $logslib = TikiLib::lib('logs');

        if ($action['objectType'] == 'wiki page' && $action['action'] != 'Removed') {
            // try to find an history
            $query = 'select * from `tiki_history` where `pageName`=? and `lastModif` <=? and `lastModif` >= ? and `user`=?';

            $result = $tikilib->query(
                $query,
                [$action['object'], $action['lastModif'] + $delay, $action['lastModif'], $action['user']]
            );

            if (($nb = $result->numRows()) == 1) {
                $res = $result->fetchRow();
                $this->assign_contributions($contributions, $res['historyId'], 'history');
            } elseif ($nb == 0) {
                $info = $tikilib->get_page_info($action['object']);
                if ($info['lastModif'] <= $action['lastModif']) { //it is the page
                    $this->assign_contributions($contributions, $info['pageName'], 'wiki page');
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            if ($action['objectType'] == 'comment' || $action['objectType'] == 'forum') {
                if ($commentId = $logslib->get_comment_action($action)) {
                    $this->assign_contributions($contributions, $commentId, 'comment');
                } else {
                    return false;
                }
            } else {
                $this->assign_contributions($contributions, $action['object'], $action['objectType']);
            }
        }

        return true;
    }
}
