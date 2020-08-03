<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

include_once "vendor_bundled/vendor/studio-42/elfinder/php/elFinderConnector.class.php";
include_once "vendor_bundled/vendor/studio-42/elfinder/php/elFinder.class.php";
include_once "vendor_bundled/vendor/studio-42/elfinder/php/elFinderVolumeDriver.class.php";

include_once 'lib/jquery_tiki/elfinder/elFinderVolumeTikiFiles.class.php';
include_once 'lib/jquery_tiki/elfinder/tikiElFinder.php';

class Services_File_FinderController
{
    private $fileController;

    private $parentIds;

    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Services_Exception_Disabled('feature_file_galleries');
        }
        if ($prefs['fgal_elfinder_feature'] != 'y') {
            throw new Services_Exception_Disabled('fgal_elfinder_feature');
        }
        $this->fileController = new Services_File_Controller();
        $this->fileController->setUp();

        $this->parentIds = null;
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'file_galleries';
    }

    /**********************
     * elFinder functions *
     *********************/

    /***
     * Main "connector" to handle all requests from elfinder
     *
     * @param $input
     *
     * @return array
     * @throws Exception
     */

    public function action_finder($input)
    {
        global $prefs, $user;


        if ($this->parentIds === null) {
            $ids = TikiLib::lib('filegal')->getGalleriesParentIds();
            $this->parentIds = [ 'galleries' => [], 'files' => [] ];
            foreach ($ids as $id) {
                if ($id['parentId'] > 0) {
                    $this->parentIds['galleries'][(int) $id['galleryId']] = (int) $id['parentId'];
                }
            }
            $tiki_files = TikiDb::get()->table('tiki_files');
            $this->parentIds['files'] = array_map('intval', $tiki_files->fetchMap('fileId', 'galleryId', []));
        }

        // turn off some elfinder commands here too (stops the back-end methods being accessible)
        $disabled = ['mkfile', 'edit', 'archive', 'resize'];
        // done so far: 'rename', 'rm', 'duplicate', 'upload', 'copy', 'cut', 'paste', 'mkdir', 'extract',

        // check for a "userfiles" gallery - currently although elFinder can support more than one root, it always starts in the first one
        $opts = [
            'debug' => ($prefs['fgal_elfinder_debug'] === 'y'),
            'roots' => [],
            'bind' => [
                //check csrf prior to executing state-changing actions
                'duplicate.pre mkdir.pre paste.pre rename.pre rm.pre upload.pre' => [
                    [$this, 'csrfCheck']
                ]
            ]
        ];

        $rootDefaults = [
            'driver' => 'TikiFiles', // driver for accessing file system (REQUIRED)
//			'path' => $rootId, // tiki root filegal - path to files (REQUIRED) - to be filled in later
            'disabled' => $disabled,
//			'URL'           => 									// URL to files (seems not to be REQUIRED)
            'accessControl' => [$this, 'elFinderAccess'], // obey tiki perms
            'uploadMaxSize' => ini_get('upload_max_filesize'),
            'accessControlData' => [
                'deepGallerySearch' => $input->deepGallerySearch->int(),
                'parentIds' => $this->parentIds,
            ],
            'alias' => tr('Default Root Gallery'),	// just in case
        ];

        // gallery to start in
        $startGallery = $input->defaultGalleryId->int();

        if ($startGallery) {
            $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($startGallery);
            if (! $gal_info) {
                Feedback::error(tr('Gallery ID %0 not found', $startGallery));
                $startGallery = $prefs['fgal_root_id'];
            }
        }

        // 'startPath' not functioning with multiple roots as yet (https://github.com/Studio-42/elFinder/issues/351)
        // so work around it for now with startRoot

        $opts['roots'][] = array_merge(
            // normal file gals
            $rootDefaults,
            [
                'path' => $prefs['fgal_root_id'],		// should be a function?
                'alias' => tr('File Galleries'),
            ]
        );
        $startRoot = 0;

        if (! empty($user) && $prefs['feature_userfiles'] == 'y' && $prefs['feature_use_fgal_for_user_files'] == 'y') {
            if ($startGallery && $startGallery == $prefs['fgal_root_user_id'] && ! Perms::get('file gallery', $startGallery)->admin_file_galleries) {
                $startGallery = (int) TikiLib::lib('filegal')->get_user_file_gallery();
            }
            $userRootId = $prefs['fgal_root_user_id'];

            if ($startGallery != $userRootId) {
                $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($startGallery);
                if ($gal_info['type'] == 'user') {
                    $startRoot = count($opts['roots']);
                }
            } else {
                $startRoot = count($opts['roots']);
            }
            $opts['roots'][] = array_merge(
                $rootDefaults,
                [
                    'path' => $userRootId,		// should be $prefs['fgal_root_id']?
                    'alias' => tr('Users File Galleries'),
                ]
            );
        }

        if ($prefs['feature_wiki_attachments'] == 'y' && $prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
            if ($startGallery && $startGallery == $prefs['fgal_root_wiki_attachments_id']) {
                $startRoot = count($opts['roots']);
            }
            $opts['roots'][] = array_merge(
                $rootDefaults,
                [
                    'path' => $prefs['fgal_root_wiki_attachments_id'],		// should be $prefs['fgal_root_id']?
                    'alias' => tr('Wiki Attachments'),
                ]
            );
        }

        if ($startGallery) {
            $opts['startRoot'] = $startRoot;
            $d = $opts['roots'][$startRoot]['path'] == $startGallery ? '' : 'd_';	// needs to be the cached name in elfinder (with 'd_' in front) unless it's the root id
            $opts['roots'][$startRoot]['startPath'] = $d . $startGallery;
        }

        /* thumb size not working due to css issues - tried this in setup/javascript.php but needs extensive css overhaul to get looking right
                if ($prefs['fgal_elfinder_feature'] === 'y') {
                    $tmbSize = (int) $prefs['fgal_thumb_max_size'] / 2;
                    TikiLib::lib('header')->add_css(".elfinder-cwd-icon {width:{$tmbSize}px; height:{$tmbSize}px;}");	// def 48
                    $tmbSize += 4;	// def 52
                    TikiLib::lib('header')->add_css(".elfinder-cwd-view-icons .elfinder-cwd-file-wrapper {width:{$tmbSize}px; height:{$tmbSize}px;}");
                    $tmbSize += 28; $tmbSizeW = $tmbSize + 40;	// def 120 x 80
                    TikiLib::lib('header')->add_css(".elfinder-cwd-view-icons .elfinder-cwd-file {width: {$tmbSizeW}px;height: {$tmbSize}px;}");
                }
        */
        // run elFinder

        session_write_close();

        $elFinder = new tikiElFinder($opts);
        $connector = new elFinderConnector($elFinder);

        $filegallib = TikiLib::lib('filegal');
        if ($input->cmd->text() === 'tikiFileFromHash') {	// intercept tiki only commands
            $fileId = $elFinder->realpath($input->hash->text());
            if (strpos($fileId, 'f_') !== false) {
                $info = $filegallib->get_file(str_replace('f_', '', $fileId));
            } else {
                $info = $filegallib->get_file_gallery(str_replace('d_', '', $fileId));
            }
            $params = [];
            if ($input->filegals_manager->text()) {
                $params['filegals_manager'] = $input->filegals_manager->text();
            }
            if ($input->insertion_syntax->text()) {
                $params['insertion_syntax'] = $input->insertion_syntax->text();
            }
            $info['wiki_syntax'] = $filegallib->getWikiSyntax($info['galleryId'], empty($info['fileId']) ? [] : $info, $params);
            $info['data'] = '';	// binary data makes JSON fall over

            return $info;
        } elseif ($input->cmd->text() === 'file') {
            // intercept download command and use tiki-download_file so the mime type and extension is correct
            $fileId = $elFinder->realpath($input->target->text());
            if (strpos($fileId, 'f_') !== false) {
                global $base_url;

                $fileId = str_replace('f_', '', $fileId);
                $display = '';

                $url = $base_url . 'tiki-download_file.php?fileId=' . $fileId;

                if (! $input->download->int()) {	// images can be displayed
                    $info = $filegallib->get_file($fileId);

                    if (strpos($info['filetype'], 'image/') !== false) {
                        $url .= '&display';
                    }
                }

                TikiLib::lib('access')->redirect($url);

                return [];
            }
        }

        // elfinder needs "raw" $_GET or $_POST
        if ($_SERVER["REQUEST_METHOD"] == 'POST') {
            $_POST = $input->asArray();
        } else {
            $_GET = $input->asArray();
            TikiLib::lib('access')->setTicket();
        }

        $connector->run();
        // deals with response

        return [];
    }

    /**
     * elFinderAccess "accessControl" callback.
     *
     * @param  string $attr attribute name (read|write|locked|hidden)
     * @param  string $path file path relative to volume root directory started with directory separator
     * @param         $data
     * @param         $volume
     *
     * @throws Exception
     * @return bool|null
     */
    public function elFinderAccess($attr, $path, $data, $volume)
    {
        global $prefs;

        $ar = explode('_', $path);
        $visible = true;		// for now
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $id = $ar[1];
            if ($isgal) {
                $visible = $this->isVisible($id, $data, $isgal);
            } else {
                $visible = $this->isVisible($this->parentIds['files'][$id], $data, $isgal);
            }
        } else {
            $isgal = true;
            $id = $path;
        }

        if ($isgal) {
            $perms = TikiLib::lib('tiki')->get_perm_object($id, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($id));
        } else {
            $perms = TikiLib::lib('tiki')->get_perm_object($id, 'file', TikiLib::lib('filegal')->get_file($id));
        }

        $perms = array_merge([
            'tiki_p_admin_file_galleries' => 'n',
            'tiki_p_download_files' => 'n',
            'tiki_p_upload_files' => 'n',
            'tiki_p_view_file_gallery' => 'n',
            'tiki_p_remove_files' => 'n',
            'tiki_p_create_file_galleries' => 'n',
            'tiki_p_edit_gallery_file' => 'n',
            'tiki_p_list_file_galleries' => 'n',
            'tiki_p_assign_perm_file_gallery' => 'n',
            'tiki_p_batch_upload_file_dir' => 'n',
            'tiki_p_batch_upload_files' => 'n',
            'tiki_p_view_fgal_explorer' => 'n',
            'tiki_p_view_fgal_path' => 'n',
            'tiki_p_upload_javascript' => 'n',
            'tiki_p_upload_svg' => 'n',
        ], $perms);

        switch ($attr) {
            case 'read':
                if ($isgal) {
                    return $visible && ($perms['tiki_p_view_file_gallery'] === 'y' || $id == $prefs['fgal_root_id']);
                }

                    return $visible && $perms['tiki_p_download_files'] === 'y';
                
            case 'write':
                if ($isgal) {
                    return $visible && ($perms['tiki_p_admin_file_galleries'] === 'y' || $perms['tiki_p_upload_files'] === 'y');
                }

                    return $visible && ($perms['tiki_p_edit_gallery_file'] === 'y' || $perms['tiki_p_remove_files'] === 'y');
                
            case 'locked':
            case 'hidden':
                return ! $visible;
            default:
                return false;
        }
    }

    private function isVisible($id, $data, $isgal)
    {
        $visible = true;

        if (! empty($data['startPath'])) {
            if ($data['startPath'] == $id) { // is startPath
                $visible = true;

                return $visible;
            }
            $isParentOf = $this->isParentOf($id, $data['startPath'], $this->parentIds['galleries']);

            if (isset($data['deepGallerySearch']) && $data['deepGallerySearch'] == 0) { // not startPath and not deep
                if ($isParentOf && $isgal) {
                    $visible = true;

                    return $visible;
                }
                $visible = false;

                return $visible;
            }
            if ($isParentOf && $isgal) {
                $visible = true;
            } else {
                $visible = false;
            }
            $pid = $this->parentIds['galleries'][$id];
            while ($pid) {
                if ($pid == $data['startPath']) {
                    $visible = true;

                    break;
                }
                $pid = $this->parentIds['galleries'][$pid];
            }

            return $visible;
        }

        return $visible;
    }

    private function isParentOf($id, $child, $parentIds)
    {
        if (! isset($parentIds[$child])) {
            return false;
        } elseif ($parentIds[$child] == $id) {
            return true;
        }

        return $this->isParentOf($child, $parentIds[$child], $parentIds);
    }

    /**
     * Anti-CSRF check. To be run pre-execution of commands that change the database
     *
     * @param $cmd
     * @param $args
     * @param $elfinder tikiElFinder
     * @param $volume   elFinderVolumeTikiFiles
     *
     * @throws Services_Exception
     * @return mixed $results array
     */
    public function csrfCheck($cmd, &$args, $elfinder, $volume)
    {
        $access = TikiLib::lib('access');
        //don't unset ticket since multiple actions may be performed without refreshing the page
        if ($access->checkCsrf(null, null, null, false, null, 'none')) {
            $access->setTicket();
            $elfinder->setCustomData('ticket', $access->getTicket());
        } else {
            return  [
                'preventexec' => true,
                'results' => [
                    'error' => tr('Potential cross-site request forgery (CSRF) detected. Operation blocked. Reloading the page may help.')
                ]
            ];
        }
    }
}
