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
class SearchStatsLib extends TikiLib
{
    public function clear_search_stats()
    {
        $query = "delete from tiki_search_stats";
        $result = $this->query($query, []);
    }

    public function register_term_hit($term)
    {
        $term = trim($term);

        $table = $this->table('tiki_search_stats');
        $table->insertOrUpdate(
            [
                'hits' => $table->increment(1),
            ],
            [
                'term' => $term,
                'hits' => 1,
            ]
        );
    }

    /**
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_search_stats($offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $mid = " where (`term` like ?)";
            $bindvars = ["%$find%"];
        } else {
            $mid = "";
            $bindvars = [];
        }

        $query = "select * from `tiki_search_stats` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_search_stats` $mid";
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
}
