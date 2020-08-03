<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Suite_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('suite_jitsi_provision');
    }

    public static function getJitsiUrl()
    {
        $url = TikiLib::lib('service')->getUrl([
            'controller' => 'suite',
            'action' => 'jitsi',
        ]);

        return TikiLib::tikiUrl($url) . '&username=${username}&password=${password}';
    }

    public function action_jitsi($input)
    {
        global $prefs;
        $config = $prefs['suite_jitsi_configuration'];
        $config = str_replace(['${username}', '${password}'], [
            $input->username->none(),
            $input->password->none(),
        ], $config);

        return [
            'configuration' => $config,
        ];
    }
}
