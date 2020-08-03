<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_DataChannel extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = [
            'domain' => 'tiki://local',
            'groups' => [ 'Admins' ],
        ];

        $data = array_merge($defaults, $this->obj->getData());

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['name'], $data['profile'])) {
            return false;
        }
        if (! is_array($data['groups'])) {
            return false;
        }
        if (! is_string($data['domain'])) {
            return false;
        }

        return true;
    }

    public function _install()
    {
        global $tikilib, $prefs;
        $channels = Tiki_Profile_ChannelList::fromConfiguration($prefs['profile_channels']);

        $data = $this->getData();

        $this->replaceReferences($data);

        $channels->addChannel($data['name'], $data['domain'], $data['profile'], $data['groups']);
        $tikilib->set_preference('profile_channels', $channels->getConfiguration());

        return $data['name'];
    }
}
