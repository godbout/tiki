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
 *
 */
class HotwordsLib extends TikiLib
{

    /**
     * @param int $offset
     * @param $maxRecords
     * @param string $sort_mode
     * @param string $find
     * @return array
     */
    public function list_hotwords($offset = 0, $maxRecords = -1, $sort_mode = 'word_desc', $find = '')
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `word` like ?";
            $bindvars = [$findesc];
        } else {
            $mid = '';
            $bindvars = [];
        }

        $query = "select * from `tiki_hotwords` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_hotwords` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;

        return $retval;
    }

    /**
     * @param $word
     * @param $url
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function add_hotword($word, $url)
    {
        //		$word = addslashes($word);

        $url = addslashes($url);
        $query = "delete from `tiki_hotwords` where `word`=?";
        $this->query($query, [$word]);
        $query = "insert into `tiki_hotwords`(`word`,`url`) values(?,?)";

        return $this->query($query, [$word, $url]);
    }

    /**
     * @param $word
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_hotword($word)
    {
        $query = "delete from `tiki_hotwords` where `word`=?";

        return $this->query($query, [$word]);
    }
}
$hotwordlib = new HotwordsLib;
