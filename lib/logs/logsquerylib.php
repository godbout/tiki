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

class LogsQueryLib
{
    public $type = "";
    public $id = "";
    public $action = "";
    public $start = "";
    public $end = "";
    public $client = "";
    public $groupType = null;
    public $limit = null;
    public $desc = true;

    public static function type($type = "")
    {
        $me = new self();
        $me->type = $type;

        return $me;
    }

    public static function wikiPage($id = "")
    {
        return LogsQueryLib::type("wiki page")->id($id);
    }

    public static function wikiPagesFrom($user = "")
    {
        return LogsQueryLib::type("wiki page")->id($id);
    }

    public static function forum($id = "")
    {
        return LogsQueryLib::type("forum")->id($id);
    }

    public static function fileGallery($id = "")
    {
        return LogsQueryLib::type("file gallery")->id($id);
    }

    public static function imageGallery($id = "")
    {
        return LogsQueryLib::type("image gallery")->id($id);
    }

    public static function category($id = "")
    {
        return LogsQueryLib::type("category")->id($id);
    }

    public static function system($id = "")
    {
        return LogsQueryLib::type("system")->id($id);
    }

    public static function message($id = "")
    {
        return LogsQueryLib::type("message")->id($id);
    }

    public static function comment($id = "")
    {
        return LogsQueryLib::type("comment")->id($id);
    }

    public static function sheet($id = "")
    {
        return LogsQueryLib::type("sheet")->id($id);
    }

    public static function blog($id = "")
    {
        return LogsQueryLib::type("blog")->id($id);
    }

    public static function file($id = "")
    {
        return LogsQueryLib::type("file")->id($id);
    }

    public static function article($id = "")
    {
        return LogsQueryLib::type("article")->id($id);
    }

    public static function trackerItem($id = "")
    {
        return LogsQueryLib::type("trackeritem")->id($id);
    }

    public static function wikiPageAttachment($id = "")
    {
        return LogsQueryLib::type("wiki page attachment")->id($id);
    }

    public static function listTypes()
    {
        $tikilib = TikiLib::lib('tiki');
        $result = [];

        foreach ($tikilib->fetchAll("SELECT objectType FROM tiki_actionlog GROUP By objectType") as $row) {
            $result[] = $row['objectType'];
        }

        return $result;
    }

    public static function listActions()
    {
        $tikilib = TikiLib::lib('tiki');
        $result = [];

        foreach ($tikilib->fetchAll("SELECT action FROM tiki_actionlog GROUP By action") as $row) {
            $result[] = $row['action'];
        }

        return $result;
    }

    public static function url($id = "")
    {
        return LogsQueryLib::type("url")->id($id);
    }

    public function id($id = "")
    {
        $this->id = $id;

        return $this;
    }

    public function viewed()
    {
        return $this->action("viewed");
    }

    public function action($action)
    {
        $this->action = $action;

        return $this;
    }

    public function start($start)
    {
        $this->start = $start;

        return $this;
    }

    public function end($end)
    {
        $this->end = $end;

        return $this;
    }

    public function client($client)
    {
        $this->client = $client;

        return $this;
    }

    public function count()
    {
        $this->groupType = "count";

        return $this->fetchAll();
    }

    public function countByDate()
    {
        $this->groupType = "countByDate";

        return $this->fetchAll();
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function desc()
    {
        $this->desc = true;

        return $this;
    }

    public function asc()
    {
        $this->desc = false;

        return $this;
    }

    public function countByDateFilterId($ids = [])
    {
        $tikilib = TikiLib::lib('tiki');

        $this->countByDate();

        $result = [];

        foreach ($ids as $id) {
            foreach ($this->id($id)->fetchAll() as $log) {
                if (empty($result[$log['date']])) {
                    $result[$log['date']] = 0;
                }
                $result[$log['date']] += $log['count'];
            }
        }

        return $result;
    }

    public function countUsersFilterId($ids = [])
    {
        $tikilib = TikiLib::lib('tiki');

        $this->groupType = "";

        $result = [];

        foreach ($ids as $id) {
            foreach ($this->id($id)->fetchAll() as $log) {
                if (empty($result[$log['user']])) {
                    $result[$log['user']] = 0;
                }

                $result[$log['user']]++;
            }
        }

        return $result;
    }

    public function countUsersIPFilterId($ids = [])
    {
        $tikilib = TikiLib::lib('tiki');

        $this->groupType = "";

        $result = [];

        foreach ($ids as $id) {
            foreach ($this->id($id)->fetchAll() as $log) {
                $result[json_encode(["ip" => $log['ip'], "user" => $log['user']])]++;
            }
        }

        return $result;
    }

    public function fetchAll()
    {
        $tikilib = TikiLib::lib('tiki');

        if (empty($this->type)) {
            return [];
        }


        $query = "
			SELECT
				" . ($this->groupType == "count" ? " COUNT(actionId) as count " : "") . "
				" . ($this->groupType == "countByDate" ? " COUNT(actionId) AS count, DATE_FORMAT(FROM_UNIXTIME(lastModif), '%m/%d/%Y') as date " : "") . "
				" . (empty($this->groupType) ? " * " : "") . "
			FROM
				tiki_actionlog
			WHERE
				objectType = ?
				" . (
            ! empty($this->id) ? " AND object = ? " : ""
        ) . "
				" . (
                    ! empty($this->action) ? " AND action = ? " : ""
                ) . "
				" . (
                    ! empty($this->start) ? " AND lastModif > ? " : ""
                ) . "
				" . (
                    ! empty($this->end) ? " AND lastModif < ? " : ""
                ) . "
				" . (
                    ! empty($this->client) ? " AND client = ? " : ""
                ) . "

			" . ($this->groupType == "countByDate" ? " GROUP BY DATE_FORMAT(FROM_UNIXTIME(lastModif), '%Y%m%d') " : "") . "

			ORDER BY lastModif " . ($this->desc == true ? "DESC" : "ASC") . "

			" . (
                    ! empty($this->limit) ?
                " LIMIT " . $this->limit
                : ""
                ) . "
		";

        $params = [$this->type];

        if (! empty($this->id)) {
            $params[] = $this->id;
        }
        if (! empty($this->action)) {
            $params[] = $this->action;
        }
        if (! empty($this->start)) {
            $params[] = $this->start;
        }
        if (! empty($this->end)) {
            $params[] = $this->end;
        }
        if (! empty($this->client)) {
            $params[] = $this->client;
        }

        if ($this->groupType == "count") {
            return $tikilib->getOne($query, $params);
        }

        return $tikilib->fetchAll($query, $params);
    }
}
