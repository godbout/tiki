<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

class Tracker_Field_ShowTikiOrg extends Tracker_Field_Abstract
{
    public static function getTypes()
    {
        return [
            'STO' => [
                'name' => tr('show.tiki.org'),
                'description' => tr('Create, display or manage show.tiki.org instances.'),
                'prefs' => ['trackerfield_showtikiorg'],
                'tags' => ['experimental'],
                'help' => 'show.tiki.org',
                'default' => 'n',
                'params' => [
                    'domain' => [
                        'name' => tr('Domain name of show server'),
                        'description' => tr('For example, show.tikiwiki.org'),
                        'filter' => 'text',
                        'legacy_index' => 0,
                    ],
                    'versions' => [
                        'name' => tr('Supported Versions'),
                        'description' => tr('List of Tiki versions for new instances, For example: "18.x,15.x,12.x" or "trunk,19.x"'),
                        'filter' => 'text',
                        'separator' => ',',
                    ],
                    'remoteShellUser' => [
                        'name' => tr('Shell username on remote server'),
                        'description' => tr('The shell username on the show server'),
                        'filter' => 'text',
                        'legacy_index' => 1,
                    ],
                    'publicKey' => [
                        'name' => tr('Public key file path'),
                        'description' => tr('System path to public key on local server. Only RSA keys are supported.'),
                        'filter' => 'text',
                        'legacy_index' => 2,
                    ],
                    'privateKey' => [
                        'name' => tr('Private key file path'),
                        'description' => tr('System path to private key on local server. Only RSA keys are supported.'),
                        'filter' => 'text',
                        'legacy_index' => 3,
                    ],
                    'debugMode' => [
                        'name' => tr('Show debugging information'),
                        'description' => tr('Show debugging info during testing'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'fixedUserId' => [
                        'name' => tr('Fixed user ID'),
                        'description' => tr('Set fixed user ID instead of using the user ID of the creator of the tracker item'),
                        'filter' => 'int',
                        'legacy_index' => 5,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        global $user;

        $ret = [
            'id' => 0,
            'userid' => 0,
            'status' => 'DISCO',
            'username' => '',
            'canDestroy' => false,
            'debugoutput' => '',
            'showurl' => '',
            'showlogurl' => '',
            'snapshoturl' => '',
            'value' => 'none', // this is required to show the field, otherwise it gets hidden if tracker is set to doNotShowEmptyField
            'versions' => $this->getOption('versions', ['18.x', '15.x', '12.x']),
        ];

        $id = $this->getItemId();
        if (! $id) {
            return $ret;
        }
        $ret['id'] = $id;
        

        // get cache to prevent too many hits to show.tiki.org
        $cachelib = TikiLib::lib('cache');

        $cacheKey = 'STO-' . $this->getOption('domain') . '-' . $this->getConfiguration('fieldId') . "-" . $id;
        if ($data = $cachelib->getSerialized($cacheKey)) {
            $creator = TikiLib::lib('tiki')->get_user_login($data['userid']);
            if (TikiLib::lib('user')->user_has_permission($user, 'tiki_p_admin') || $user == $creator) {
                $data['canDestroy'] = true;
            }

            return $data;
        }

        $item = TikiLib::lib('trk')->get_tracker_item($id);
        $creator = $item['createdBy'];
        if (! $creator) {
            $creator = reset(TikiLib::lib('trk')->get_item_creators($item['trackerId'], $id));
        }

        if ($this->getOption('fixedUserId') > 0) {
            $userid = $this->getOption('fixedUserId');
        } else {
            $userid = TikiLib::lib('tiki')->get_user_id($creator);
        }

        if (! $userid || ! $creator) {
            return $ret;
        }
        $ret['userid'] = $userid;
        

        if (ctype_alnum($creator)) {
            $ret['username'] = $creator;
        } else {
            $ret['username'] = 'user';
        }

        $conn = new SSH2($this->getOption('domain'));

        $password = new RSA();

        $publicKeyLoaded = $password->loadKey(file_get_contents($this->getOption('publicKey')));
        $privateKeyLoaded = $password->loadKey(file_get_contents($this->getOption('privateKey')));

        if (! $publicKeyLoaded || ! $privateKeyLoaded) {
            $ret['status'] = 'INVKEYS';

            return $ret;
        }

        $conntry = $conn->login($this->getOption('remoteShellUser'), $password);

        if (! $conntry) {
            $ret['status'] = 'DISCO';

            return $ret;
        }

        $infostring = "info -i $id -U $userid";
        $infooutput = $conn->exec($infostring);
        $ret['debugoutput'] = $infostring . " " . $infooutput;

        if (strpos($infooutput, 'MAINTENANCE: ') !== false) {
            $maintpos = strpos($infooutput, 'MAINTENANCE: ');
            $maintreason = substr($infooutput, $maintpos + 13);
            $maintreason = substr($maintreason, 0, strpos($maintreason, '"'));
            $ret['maintreason'] = $maintreason;
            $ret['status'] = 'MAINT';

            return $ret;
        }

        $versionpos = strpos($infooutput, 'VERSION: ');
        $version = substr($infooutput, $versionpos + 9);
        $version = substr($version, 0, strpos($version, PHP_EOL));
        $version = trim($version);
        $ret['version'] = $version;

        $statuspos = strpos($infooutput, 'STATUS: ');
        $status = substr($infooutput, $statuspos + 8, 5);
        $status = trim($status);
        if (! $status || $status == 'FAIL') {
            $ret['status'] = 'FAIL';
        } else {
            $ret['status'] = $status;
            $sitepos = strpos($infooutput, 'SITE: ');
            $site = substr($infooutput, $sitepos + 6);
            $site = substr($site, 0, strpos($site, ' '));
            $ret['showurl'] = $site;
            $ret['showlogurl'] = $site . '/info.txt';
            $ret['snapshoturl'] = $site . '/snapshots/';
            if ($site) {
                $ret['value'] = 'active ' . substr($site, 0, strpos($site, '.')); // the 'active' is useful for filtering on
            }
        }

        $cachelib->cacheItem($cacheKey, serialize($ret));

        // Note that one should never cache canDestroy = true
        if (TikiLib::lib('user')->user_has_permission($user, 'tiki_p_admin') || $user == $creator) {
            $ret['canDestroy'] = true;
        }

        return $ret;
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/showtikiorg.tpl', $context);
    }

    public function renderOutput($context = [])
    {
        return $this->renderTemplate('trackerinput/showtikiorg.tpl', $context);
    }
}
