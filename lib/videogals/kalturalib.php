<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Kaltura\Client\Client;
use Kaltura\Client\Configuration;
use Kaltura\Client\Enum\UiConfCreationMode;
use Kaltura\Client\Enum\UiConfObjType;
use Kaltura\Client\Type\FilterPager;
use Kaltura\Client\Type\MediaEntry;
use Kaltura\Client\Type\MediaEntryFilter;
use Kaltura\Client\Type\MixEntry;
use Kaltura\Client\Type\MixEntryFilter;
use Kaltura\Client\Type\UiConf;
use Kaltura\Client\Type\UiConfFilter;

class KalturaLib
{
    const CONFIGURATION_LIST = 'kaltura_configuration_list';
    const SESSION_ADMIN = 2;
    const SESSION_USER = 0;

    private $kconfig;
    private $client;
    private $sessionType;
    private $initialized = false;

    public function __construct($session_type)
    {
        $this->sessionType = $session_type;
    }

    public function getSessionKey()
    {
        if ($session = $this->storedKey()) {
            return $session;
        }

        if ($this->getClient()) {
            return $this->storedKey();
        }

        return '';
    }

    private function storedKey($key = null)
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');
        $session = "kaltura_session_{$this->sessionType}_$user";

        if (is_null($key)) {
            if (isset($_SESSION[$session]) && $_SESSION[$session]['expiry'] > $tikilib->now) {
                return $_SESSION[$session]['key'];
            }
        } else {
            $_SESSION[$session] = [
                'key' => $key,
                'expiry' => $tikilib->now + 1800, // Keep for half an hour
            ];
        }

        return $key;
    }

    private function getConfig()
    {
        if (! $this->kconfig) {
            global $prefs;
            $this->kconfig = new Configuration($prefs['kaltura_partnerId']);
            $this->kconfig->setServiceUrl($prefs['kaltura_kServiceUrl']);
        }

        return $this->kconfig;
    }

    private function getClient()
    {
        if (! $this->initialized && ! $this->client) {
            $this->initialized = true;

            try {
                $client = new Client($this->getConfig());
                if ($session = $this->storedKey()) {
                    $client->setKs($session);
                    $this->client = $client;
                } elseif ($session = $this->initializeClient($client)) {
                    $client->setKs($session);
                    $this->client = $client;
                    $this->storedKey($session);
                }
            } catch (Exception $e) {
                Feedback::error($e->getMessage());
            }
        }

        return $this->client;
    }

    public function getMediaUrl($entryId, $playerId)
    {
        global $prefs;
        $config = $this->getConfig();

        return $config->getServiceUrl() . "kwidget/wid/_{$prefs['kaltura_partnerId']}/uiconf_id/$playerId/entry_id/$entryId";
    }

    public function getPlaylist($entryId)
    {
        return $this->getClient()->playlist->get($entryId);
    }

    public function testSetup()
    {
        global $prefs;
        if (empty($prefs['kaltura_partnerId']) || ! is_numeric($prefs['kaltura_partnerId']) || empty($prefs['kaltura_secret']) || empty($prefs['kaltura_adminSecret'])) {
            return false;
        }

        return true;
    }

    private function initializeClient($client)
    {
        global $prefs, $user;

        if (! $this->testSetup()) {
            return false;
        }

        if ($user) {
            $kuser = $user;
        } else {
            $kuser = 'Anonymous';
        }

        if ($this->sessionType == self::SESSION_ADMIN) {
            $session = $client->session->start($prefs['kaltura_adminSecret'], $kuser, self::SESSION_ADMIN, $prefs['kaltura_partnerId'], 86400, 'edit:*');
        } else {
            $session = $client->session->start($prefs['kaltura_secret'], $kuser, self::SESSION_USER, $prefs['kaltura_partnerId'], 86400, 'edit:*');
        }

        return $session;
    }

    private function _getPlayersUiConfs()
    {
        if ($client = $this->getClient()) {
            $filter = new UiConfFilter();
            $filter->objTypeEqual = 1; // 1 denotes Players
            $filter->orderBy = '-createdAt';
            $uiConfs = $client->uiConf->listAction($filter);

            if (is_null($client->error)) {
                return $uiConfs;
            }
        }

        $uiConfs = new stdClass();
        $uiConfs->objects = [];

        return $uiConfs;
    }

    public function getPlayersUiConfs()
    {
        $cachelib = TikiLib::lib('cache');

        if (! $configurations = $cachelib->getSerialized(self::CONFIGURATION_LIST)) {
            try {
                $obj = $this->_getPlayersUiConfs()->objects;
            } catch (Exception $e) {
                Feedback::error($e->getMessage());

                return [];
            }
            $configurations = [];
            foreach ($obj as $o) {
                $configurations[] = get_object_vars($o);
            }

            $cachelib->cacheItem(self::CONFIGURATION_LIST, serialize($configurations));
        }

        return $configurations;
    }

    public function getPlayersUiConf($playerId)
    {
        // Ontaining full list, because it is cached
        $confs = $this->getPlayersUiConfs();

        foreach ($confs as $config) {
            if ($config['id'] == $playerId) {
                return $config;
            }
        }
    }

    public function updateStandardTikiKcw()
    {
        if ($client = $this->getClient()) {
            // first check if there is an existing one
            $pager = null;
            $filter = new UiConfFilter();
            $filter->nameLike = 'Tiki.org Standard 2013';
            $filter->objTypeEqual = UiConfObjType::CONTRIBUTION_WIZARD;
            $existing = $client->uiConf->listAction($filter, $pager);
            if (count($existing->objects) > 0) {
                $current_obj = array_pop($existing->objects);
                $current = $current_obj->id;
            } else {
                $current = '';
            }

            global $tikipath;
            $uiConf = new UiConf();
            $uiConf->name = 'Tiki.org Standard 2013';
            $uiConf->objType = UiConfObjType::CONTRIBUTION_WIZARD;
            $filename = $tikipath . "lib/videogals/standardTikiKcw.xml";
            $fh = fopen($filename, 'r');
            $confXML = fread($fh, filesize($filename));
            $uiConf->confFile = $confXML;
            $uiConf->useCdn = 1;
            $uiConf->swfUrl = '/flash/kcw/v2.1.4/ContributionWizard.swf';
            $uiConf->tags = 'autodeploy, content_v3.2.5, content_upload';

            // first try to update
            if ($current) {
                try {
                    $results = $client->uiConf->update($current, $uiConf);
                    if (isset($results->id)) {
                        return $results->id;
                    }
                } catch (Exception $e) {
                    Feedback::error($e->getMessage());
                }
            } else {
                try {
                    // create if updating failed or not updating
                    $uiConf->creationMode = UiConfCreationMode::ADVANCED;
                    $results = $client->uiConf->add($uiConf);
                    if (isset($results->id)) {
                        return $results->id;
                    }

                    return '';
                } catch (Exception $e) {
                    Feedback::error($e->getMessage());
                }
            }
        }

        return '';
    }

    public function cloneMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->cloneAction($entryId);
        }
    }

    public function deleteMedia($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->media->delete($entryId);
        }
    }

    public function deleteMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->delete($entryId);
        }
    }

    public function flattenVideo($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->requestFlattening($entryId, 'flv');	// FIXME this method is no longer supported
        }
    }

    public function getMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->get($entryId);
        }
    }

    public function updateMix($entryId, array $data)
    {
        if ($client = $this->getClient()) {
            $kentry = new MixEntry();
            $kentry->name = $data['name'];
            $kentry->description = $data['description'];
            $kentry->tags = $data['tags'];
            $kentry->editorType = $data['editorType'];
            $kentry->adminTags = $data['adminTags'];

            return $client->mixing->update($entryId, $kentry);
        }
    }

    public function getMedia($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->media->get($entryId);
        }
    }

    public function updateMedia($entryId, array $data)
    {
        if ($client = $this->getClient()) {
            $kentry = new MediaEntry();
            $kentry->name = $data['name'];
            $kentry->description = $data['description'];
            $kentry->tags = $data['tags'];
            $kentry->adminTags = $data['adminTags'];

            return $client->media->update($entryId, $kentry);
        }
    }

    public function listMix($sort_mode, $page, $page_size, $find)
    {
        if ($client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = $page;
            $kpager->pageSize = $page_size;

            $kfilter = new MixEntryFilter();
            $kfilter->orderBy = $sort_mode;
            $kfilter->nameMultiLikeOr = $find;

            return $client->mixing->listAction($kfilter, $kpager);
        }
    }

    public function listMedia($sort_mode, $page, $page_size, $find)
    {
        if ($client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = $page;
            $kpager->pageSize = $page_size;

            $kfilter = new MediaEntryFilter();
            $kfilter->orderBy = $sort_mode;
            $kfilter->nameMultiLikeOr = $find;
            $kfilter->statusIn = '-1,-2,0,1,2';

            return $client->media->listAction($kfilter, $kpager);
        }
    }

    public function getMovieList(array $movies)
    {
        if (count($movies) && $client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = 0;
            $kpager->pageSize = count($movies);

            $kfilter = new MediaEntryFilter();
            $kfilter->idIn = implode(',', $movies);

            $mediaList = [];
            foreach ($client->media->listAction($kfilter, $kpager)->objects as $media) {
                $mediaList[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                ];
            }

            return $mediaList;
        }

        return [];
    }
}
