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
 * PollLibShared
 *
 * @uses TikiLib
 */
class PollLibShared extends TikiLib
{

    /**
     * @param $pollId
     * @return bool
     */
    public function get_poll($pollId)
    {
        $query = "select * from `tiki_polls` where `pollId`=?";
        $result = $this->query($query, [(int)$pollId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();

        return $res;
    }

    /**
     * @param $optionId
     * @return array
     */
    public function get_poll_voters($optionId)
    {
        $query = "select user from `tiki_user_votings` where `optionId`=?";
        $result = $this->query($query, [(int)$optionId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * @param $pollId
     * @param int $from
     * @param int $to
     * @return array
     */
    public function list_poll_options($pollId, $from = 0, $to = 0)
    {
        if (empty($from) && empty($to)) {
            $query = 'select * from `tiki_poll_options` where `pollId`=?';
            $bindVars = [(int) $pollId];
        } else {
            $query = 'select tpo.`pollId`, tpo.`optionId`, tpo.`title`, tpo.`position`, count(tuv.`id`) as votes' .
                            ' from `tiki_poll_options` tpo' .
                            ' left join `tiki_user_votings` tuv on (tpo.`optionId` = tuv.`optionId`)' .
                            ' where `pollId`=? and ((tuv.`time` >= ? and tuv.`time` <= ?) or tuv.`time` = ?)' .
                            ' group by `votes`, tpo.`pollId`, tpo.`optionId`, tpo.`title`, tpo.`position` ';
            $bindVars = [(int)$pollId, (int)$from, (int)$to, 0];
        }

        $query .= ' order by `position`';
        $result = $this->query($query, $bindVars);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * @param string $active
     * @return int
     */
    public function get_random_poll($active = "a")
    {
        $bindvars = [(int)$this->now, $active];

        if ($active == "a") {
            $bindvars[] = "c"; // current;
            $mid = "or `active`=?";
        }

        $result = $this->query("select `pollId` from `tiki_polls` where `publishDate`<=? and (`active`=? $mid) ", $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        if (count($res) == 0) {
            return 0;
        } elseif (count($ret) == 1) {
            return $ret[0]['pollId'];
        }
        $bid = mt_rand(0, count($ret) - 1);

        return $ret[$bid]['pollId'];
    }

    /**
     * @param string $type
     * @param int $datestart
     * @param string $dateend
     * @param string $find
     * @return array
     */
    public function get_polls($type = 'a', $datestart = 0, $dateend = '', $find = '')
    {
        if (! $dateend) {
            $dateend = date('U');
        }
        $bindvars = [$type, (int)$datestart, (int)$dateend];

        if ($find) {
            $mid = 'and `title`=?';
            $bindvars[] = '%' . $find . '%';
        } else {
            $mid = '';
        }

        $query = "select * from `tiki_polls` where `active`=? and `publishDate`>=? and `publishDate`<=? $mid";
        $query_cant = "select count(*) from `tiki_polls` where `active`=? and `publishDate`>=? and `publishDate`<=? $mid";
        $result = $this->query($query, $bindvars);
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
     * @param $user
     * @param $pollId
     * @param $optionId
     * @param $previous_vote
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result|bool
     */
    public function poll_vote($user, $pollId, $optionId, $previous_vote)
    {
        if (! $previous_vote || $previous_vote == 0) {
            $query = "update `tiki_polls` set `votes`=`votes`+1 where `pollId`=?";
            $this->query($query, [(int) $pollId]);
            $query = "update `tiki_poll_options` set `votes`=`votes`+1 where `optionId`=?";

            return $this->query($query, [(int) $optionId]);
        } elseif ($previous_vote != $optionId) {
            $query = "update `tiki_poll_options` set `votes`=`votes`-1 where `optionId`=?";
            $this->query($query, [(int) $previous_vote]);
            $query = "update `tiki_poll_options` set `votes`=`votes`+1 where `optionId`=?";

            return $this->query($query, [(int) $optionId]);
        }

        return true;
    }

    /**
     * @param $cat_type
     * @param $cat_objid
     * @param null $user
     * @return array
     */
    public function get_ratings($cat_type, $cat_objid, $user = null)
    {
        global $tikilib, $prefs;

        $out = [];

        $result = $this->fetchAll(
            "select `pollId` from `tiki_poll_objects`" .
            " INNER JOIN `tiki_objects` ON `tiki_objects`.`objectId` = `tiki_poll_objects`.`catObjectId`" .
            " WHERE `tiki_objects`.`type`=? and `tiki_objects`.`itemId`=?",
            [$cat_type, $cat_objid]
        );

        foreach ($result as $row) {
            $poll = [];
            $poll['info'] = $this->get_poll($row['pollId']);

            if ($cat_type == 'wiki page') {
                $poll['info'] = $this->pollnameclean($poll['info'], $cat_objid);
            }

            $poll['options'] = $this->list_poll_options($row['pollId']);
            $poll['title'] = $poll['info']['title'];

            if ($user) {
                $poll['vote'] = $tikilib->get_user_vote('poll' . $row['pollId'], $user);
            } else {
                $poll['vote'] = false;
            }

            $out[] = $poll;

            // Unless multiple polls per object is enabled, end after the first
            if ($prefs['poll_multiple_per_object'] != 'y') {
                break;
            }
        }

        return $out;
    }

    /**
     * @param $s
     * @param $page
     * @return mixed
     */
    private function pollnameclean($s, $page)
    {
        if (isset($s['title'])) {
            $s['title'] = substr($s['title'], strlen($page) + 2);
        }

        return $s;
    }

    /**
     * @param $pollId
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function remove_poll($pollId)
    {
        $query = "delete from `tiki_poll_objects` where `pollId`=?";
        $this->query($query, [(int) $pollId]);
        $query = "delete from `tiki_polls` where `pollId`=?";
        $result = $this->query($query, [(int) $pollId]);
        $query = "delete from `tiki_poll_options` where `pollId`=?";
        $this->query($query, [(int) $pollId]);
        $this->remove_object('poll', $pollId);

        $query = 'delete from `tiki_user_votings` where `id`=?';
        $this->query($query, ['poll' . (int)$pollId]);

        return $result;
    }

    /**
     * @param $cat_type
     * @param $cat_objid
     * @return mixed
     */
    public function get_catObjectId($cat_type, $cat_objid)
    {
        return $this->getOne("select `objectId` from `tiki_objects` where `type`=? and `itemId`=?", [$cat_type, $cat_objid]);
    }

    /**
     * @param $catObjectId
     * @return mixed
     */
    public function has_object_polls($catObjectId)
    {
        $query = "select count(*) from `tiki_poll_objects` where `catObjectId`=?";

        return $this->getOne($query, [(int) $catObjectId]);
    }

    /**
     * @param $cat_type
     * @param $cat_objid
     * @param null $pollId
     * @return bool
     */
    public function remove_object_poll($cat_type, $cat_objid, $pollId = null)
    {
        $catObjectId = $this->get_catObjectId($cat_type, $cat_objid);
        $query = "delete from `tiki_poll_objects` where `catObjectId`=?";
        $bindvars = [(int)$catObjectId];

        if ($pollId) {
            $query .= ' AND `pollId` = ?';
            $bindvars[] = $pollId;
        }

        $this->query($query, $bindvars);

        return true;
    }

    /**
     * @param $template_id
     * @param $title
     * @return mixed
     */
    public function create_poll($template_id, $title)
    {
        $pollid = $this->replace_poll(0, $title, "o", date('U'));
        $options = $this->list_poll_options($template_id);

        foreach ($options as $op) {
            $this->replace_poll_option($pollid, 0, $op['title'], $op['position']);
        }

        return $pollid;
    }

    /**
     * @param $pollId
     * @param $optionId
     * @param $title
     * @param $position
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function replace_poll_option($pollId, $optionId, $title, $position)
    {
        if ($optionId) {
            $query = "update `tiki_poll_options` set `title`=?,`position`=? where `optionId`=?";
            $result = $this->query($query, [$title, (int) $position, (int) $optionId]);
        } else {
            $query = "insert into `tiki_poll_options`(`pollId`,`title`,`position`,`votes`) values(?,?,?,?)";
            $result = $this->query($query, [(int) $pollId, $title, (int) $position, 0]);
        }

        return $result;
    }

    /**
     * @param $pollId
     * @param $title
     * @param $active
     * @param $publishDate
     * @param int $voteConsiderationSpan
     * @return mixed
     */
    public function replace_poll($pollId, $title, $active, $publishDate, $voteConsiderationSpan = 0)
    {
        if ($pollId) {
            $query = "update `tiki_polls` set `title`=?,`active`=?,`publishDate`=?, `voteConsiderationSpan`=? where `pollId`=?";
            $result = $this->query($query, [$title, $active, $publishDate, $voteConsiderationSpan, $pollId]);
        } else {
            $query = "insert into tiki_polls(`title`,`active`,`publishDate`,`votes`, `voteConsiderationSpan`) values(?,?,?,?,?)";
            $result = $this->query($query, [$title, $active, $publishDate, 0, $voteConsiderationSpan]);

            $pollId = $this->getOne(
                "select max(`pollId`) from `tiki_polls` where `title`=? and `publishDate`=?",
                [$title, $publishDate]
            );
        }

        return $pollId;
    }

    /**
     * @param        $catObjectId
     * @param        $pollId
     * @param string $title
     *
     * @return TikiDb_Pdo_Result
     */
    public function poll_categorize($catObjectId, $pollId, $title = '')
    {
        $query = "delete from `tiki_poll_objects` where `catObjectId`=? and `pollId`=?";
        $this->query($query, [(int) $catObjectId, (int) $pollId], -1, -1, false);
        $query = "insert into `tiki_poll_objects`(`catObjectId`,`pollId`,`title`) values(?,?,?)";

        return $this->query($query, [(int) $catObjectId, (int) $pollId, $title]);
    }

    /**
     * @param $pollId
     * @return array
     */
    public function get_poll_categories($pollId)
    {
        $categlib = TikiLib::lib('categ');

        $query = "select tco.`categId`, tc.`name`" .
                            " from `tiki_poll_objects` tpo, `tiki_category_objects` tco, `tiki_categories` tc" .
                            "  where tpo.`pollId`=? and tpo.`catObjectId`=tco.`catObjectId` and tco.`categId`=tc.`categId`";
        $result = $this->query($query, [(int)$pollId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * @param $pollId
     * @return array
     */
    public function get_poll_objects($pollId)
    {
        $query = "select tob.* from `tiki_objects` tob, `tiki_poll_objects` tpo where tpo.`pollId`=? and tpo.`catObjectId`=tob.`objectId`";
        $result = $this->query($query, [(int) $pollId]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }


    /**
     * @param $pollId
     * @return bool
     */
    public function clone_poll($pollId)
    {
        $poll = $this->get_poll($pollId);
        if (! is_array($poll)) {
            return false;
        }

        /* copy the poll */
        $poll['pollId'] = 0;
        $poll['publishDate'] = $this->now;
        $pollId_new = $this->replace_poll(0, $poll['title'], $poll['active'], $poll['publishDate']);

        /* copy the poll options */
        $options = $this->list_poll_options($pollId);
        foreach ($options as $option) {
            $this->replace_poll_option($pollId_new, 0, $option['title'], $option['position']);
        }

        return $pollId_new;
    }

    /**
     *  compute percent of each option and nb of votes and pondarated total of poll
     *
     * @param mixed $poll_info
     * @param mixed $options
     */
    public function options_percent(&$poll_info, &$options)
    {
        global $prefs;
        if (! empty($prefs['poll_percent_decimals'])) {
            $percent_decimals = 2;
        } else {
            $percent_decimals = $prefs['poll_percent_decimals'];
        }
        $poll_info['votes'] = 0;
        $total = 0;
        $isNum = true; // try to find if it is a numeric poll with a title like +1, -2, 1 point...

        foreach ($options as $i => $option) {
            $poll_info['votes'] += $option['votes']; // nb of votes
        }

        foreach ($options as $i => $option) {
            if ($option['votes'] == 0) {
                $percent = 0;
            } else {
                $percent = number_format($option['votes'] * 100 / $poll_info['votes'], $percent_decimals);
                if ($isNum) {
                    if (preg_match('/^([+-]?[0-9]+).*/', $option['title'], $matches)) {
                        $total += $option['votes'] * $matches[1];
                    } else {
                        $isNum = false; // it is not a numeric poll
                    }
                }
            }
            $options[$i]['percent'] = $percent;
            $options[$i]['width'] = $percent;
        }

        if ($isNum) {
            $poll_info['total'] = $total; // ponderated total
        }
    }

    /**
     * @param $pollId
     * @param $user
     * @param $ip
     * @param $optionId
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function delete_vote($pollId, $user, $ip, $optionId)
    {
        $query = 'delete from `tiki_user_votings` where `id`=? and `optionId`=? and ';
        $bindvars = ['poll' . $pollId, $optionId];

        if (! empty($user)) {
            $query .= '`user`=?';
            $bindvars[] = $user;
        } else {
            $query .= '`ip`=?';
            $bindvars[] = $ip;
        }

        $result = $this->query($query, $bindvars);
        $query = 'update `tiki_poll_options` set `votes` = `votes`- 1 where `pollId`=? and `optionId`=?';
        $this->query($query, [$pollId, $optionId]);
        $query = 'update `tiki_polls` set `votes`=`votes`-1 where `pollId`=?';
        $this->query($query, [$pollId]);
        $_SESSION['votes'] = array_diff($_SESSION['votes'], ['poll' . $pollId]);

        return $result;
    }
}
$polllib = new PollLibShared;
