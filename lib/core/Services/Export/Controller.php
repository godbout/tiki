<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Yaml\Yaml;

class Services_Export_Controller
{
    /**
     * Get all content that could be synced with remote Tiki.
     * @param mixed $input
     * @return array
     */
    public function action_sync_content($input)
    {
        global $user;

        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($input->user->text(), $input->password->text());
        
        if (! $isvalid) {
            return [
                'error' => 'Specified user credentials are invalid.'
            ];
        }

        // enforce permissions of the incoming user and their groups
        $_permissionContext = new Perms_Context($user);

        $perms = Perms::get();
        if (! $perms->admin) {
            return [
                'error' => 'Specified user is not an administrator.'
            ];
        }

        return [
            'data' => $this->dumpContent(),
            'status' => 'ok'
        ];
    }

    /**
     * Currently available: wiki pages and tracker definitions + preferences/configuration.
     * @return string
     */
    public function dumpContent()
    {
        global $prefs;
        
        $data = "Preferences:\n";
        $data .= Yaml::dump($prefs, 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        $writer = new \Tiki_Profile_Writer("profiles", "temporary_export");
        \Tiki_Profile_InstallHandler_WikiPage::export($writer, '', true);
        \Tiki_Profile_InstallHandler_Tracker::export($writer, '', true);

        $data .= $writer->dump();
        
        foreach ($writer->getExternalWriter()->getFiles() as $file => $content) {
            $data .= "\n\n$file\n$content";
        }

        return $data;
    }
}
