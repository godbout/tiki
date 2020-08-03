<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Perms_Context
{
    private static $permissionList = [];

    private $previousUser;
    private $previousGroupList;

    private $user;
    private $groupList = [];

    public static function setPermissionList($allperms)
    {
        $permissionList = array_keys($allperms);

        $shortPermList = array_map(function ($name) {
            return substr($name, 7);
        }, $permissionList);

        self::$permissionList = $shortPermList;
    }

    public function __construct($user, $activate = true)
    {
        $tikilib = TikiLib::lib('tiki');
        $this->user = $user;
        $this->groupList = $tikilib->get_user_groups($user);

        if ($activate) {
            $this->activate();
        }
    }

    public function overrideGroups(array $groupList)
    {
        $this->groupList = $groupList;
    }

    public function activate($globalize = false)
    {
        global $user, $globalperms;
        $perms = Perms::getInstance();
        $this->previousUser = $user;
        $this->previousGroupList = $perms->getGroups();
        $smarty = TikiLib::lib('smarty');
        $user = $this->user;
        $perms->setGroups($this->groupList);

        $globalperms = Perms::get();
        $globalperms->globalize(self::$permissionList, $smarty, false);

        if (is_object($smarty)) {
            $smarty->assign('globalperms', $globalperms);
        }
    }

    public function activatePermanently()
    {
        global $user, $globalperms;
        $perms = Perms::getInstance();
        $smarty = TikiLib::lib('smarty');
        $user = $this->user;
        $perms->setGroups($this->groupList);

        $globalperms = Perms::get();
        $globalperms->globalize(self::$permissionList, $smarty, false);

        if (is_object($smarty)) {
            $smarty->assign('globalperms', $globalperms);
        }

        $this->previousUser = $user;
        $this->previousGroupList = $perms->getGroups();
    }

    public function __destruct()
    {
        global $user, $globalperms;
        $user = $this->previousUser;

        $perms = Perms::getInstance();
        $perms->setGroups($this->previousGroupList);
        $globalperms = Perms::get();
    }
}
