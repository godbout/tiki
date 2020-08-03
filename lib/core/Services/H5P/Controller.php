<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_H5P_Controller
{
    public function setUp()
    {
        global $prefs;

        if ($prefs['h5p_enabled'] !== 'y') {
            throw new Services_Exception_Disabled(tr('h5p_enabled'));
        }
        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Services_Exception_Disabled('feature_file_galleries');
        }
        if (! function_exists('curl_init')) {
            throw new Services_Exception_NotAvailable(tr('H5P requires the CURL extension to be installed in PHP'));
        }
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'file_galleries';
    }

    public function action_embed($input)
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_button');

        $fileId = $input->fileId->int();
        $page = $input->page->pagename();
        $index = $input->index->int();

        $perms = Perms::get();

        if (empty($fileId)) {
            if ($perms->h5p_edit) {
                TikiLib::lib('header')->add_jq_onready(
                    '$(".create-h5p-content").click($.clickModal({title: "' . tr('Create H5P Content') . '", size: "modal-lg"}))'
                );

                return [
                    'html' => smarty_function_button([
                        'href' => TikiLib::lib('service')->getUrl([
                            'controller' => 'h5p',
                            'action' => 'edit',
                            'page' => $page,
                            'index' => $index,
                        ]),
                        '_text' => tra('Create H5P content'),
                        '_class' => 'create-h5p-content btn-sm',
                    ], $smarty->getEmptyInternalTemplate()),
                ];
            }

            throw new Services_Exception_NotAvailable(tr('H5P Embed:') . ' ' . tr('No fileID provided.'));
        }

        $content = TikiLib::lib('h5p')->loadContentFromFileId($fileId);

        if (! $content) {
            Feedback::error(tr('H5P Plugin:') . ' ' . tr('Cannot find H5P content with fileId: %0.', $fileId));

            return '';
        }

        if (is_string($content)) {
            // Return error message if the user has the correct cap
            return Perms::get()->h5p_edit ? $content : null;
        }

        // Log view
        new H5P_Event(
            'content',
            'embed',
            $content['id'],
            $content['title'],
            $content['library']['name'],
            $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
        );

        $html = TikiLib::lib('h5p')->addAssets($content);

        if ($perms->h5p_edit) {
            TikiLib::lib('header')->add_jq_onready(
                '$(".edit-h5p-content").click($.clickModal({title: "' . tr('Edit H5P Content') . '", size: "modal-lg"}))'
            );

            $html .= smarty_function_button([
                'href' => TikiLib::lib('service')->getUrl([
                    'controller' => 'h5p',
                    'action' => 'edit',
                    'fileId' => $fileId,
                    'page' => $page,
                    'index' => $index,
                ]),
                '_text' => tra('Edit'),
                '_class' => 'edit-h5p-content btn-sm',
            ], $smarty->getEmptyInternalTemplate());
        }

        return [
            'html' => $html,
            'h5p_title' => TikiLib::lib('filegal')->get_file_label($fileId),
        ];
    }

    public function action_edit($input)
    {
        // Check permission
        if (! Perms::get()->h5p_edit) {
            throw new Services_Exception_Denied(tr('H5P Edit:') . ' ' . tr('Permission denied.'));
        }

        // Load content
        $fileId = $input->fileId->int();
        if (! empty($fileId)) {
            // Retrieve existing content data

            $content = TikiLib::lib('h5p')->loadContentFromFileId($fileId);
            $content['title'] = TikiLib::lib('filegal')->get_file_label($fileId);
        } else {
            $content = [
                'disable' => H5PCore::DISABLE_NONE,
            ];
        }

        $page = $input->page->pagename();
        $index = $input->index->int();

        $util = new Services_Utilities();
        // Handle for submit
        if ($util->isConfirmPost()) {
            switch ($input->op->word()) {
                case 'Save':
                    // Create new content or update existing

                    $created = empty($fileId);

                    if ($fileId = TikiLib::lib('h5p')->saveContent($content, $input)) {
                        // Content updated, redirect to view
                        if ($created && $page) {
                            $result = TikiLib::lib('service')->internal(
                                'plugin',
                                'edit',
                                [
                                    'page' => $page,
                                    'type' => 'h5p',
                                    'index' => $index,
                                    'edit_icon' => $index,
                                    'params' => ['fileId' => $fileId],
                                ]
                            );

                            if (! empty($result['redirect'])) {
                                TikiLib::lib('access')->redirect($result['redirect']);
                            }
                        } else {
                            if ($page) {
                                TikiLib::lib('access')->redirect(TikiLib::lib('wiki')->sefurl($page));
                            } else {
                                return ['FORWARD' => [
                                    'controller' => 'h5p',
                                    'action' => 'embed',
                                    'fileId' => $fileId,
                                ]];
                            }
                        }
                    }

                    break;

                case 'Delete':
                    $filegallib = TikiLib::lib('filegal');
                    $fileInfo = $filegallib->get_file_info($fileId);
                    $filegallib->remove_file($fileInfo);

                    if ($page) {
                        $result = TikiLib::lib('service')->internal(
                            'plugin',
                            'edit',
                            [
                                'page' => $page,
                                'type' => 'h5p',
                                'index' => $index,
                                'edit_icon' => $index,
                                'params' => ['fileId' => ''],
                            ]
                        );

                        if (! empty($result['redirect'])) {
                            TikiLib::lib('access')->redirect($result['redirect']);
                        }
                    } else {
                        return [
                            'FORWARD' => [
                                'controller' => 'h5p',
                                'action' => 'edit',
                            ]];

                        break;
                    }
            }
        }

        if (! empty($content['id'])) {
            // Log editing of content
            new H5P_Event(
                'content',
                'edit',
                $content['id'],
                $input->title->text(),
                $content['library']['name'],
                $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
            );
        } else {
            // Log creation of new content (form opened)
            new H5P_Event('content', 'new');
        }

        // Load assets required for Editor
        TikiLib::lib('h5p')->addEditorAssets(empty($content['id']) ? null : $content['id']);

        // Prepare for template
        $core = \H5P_H5PTiki::get_h5p_instance('core');
        if (empty($content['library'])) {
            $library = empty($input->library->text()) ? 0 : $input->library->text();
        } else {
            $library = H5PCore::libraryToString($content['library']);
        }
        if (empty($content['params'])) {
            $parameters = empty($input->parameters->xss()) ? '{}' : $input->parameters->xss();
        } else {
            $parameters = $core->filterParameters($content);
        }

        return [
            'loading' => tr('Waiting for javascript...'),
            'fileId' => $fileId,
            'h5p_title' => empty($content['title']) ? '' : $content['title'],
            'library' => $library,
            'parameters' => $parameters,
            'page' => $page,
            'index' => $index,
        ];
    }

    public function action_libraries($input)
    {
        global $prefs;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST['libraries'] = [];
            foreach ($input->libraries as $library) {
                $_POST['libraries'][] = $library;
            }
        }

        $editor = \H5P_EditorTikiStorage::get_h5peditor_instance();

        $name = filter_input(INPUT_GET, 'machineName', FILTER_SANITIZE_STRING);
        $major_version = filter_input(INPUT_GET, 'majorVersion', FILTER_SANITIZE_NUMBER_INT);
        $minor_version = filter_input(INPUT_GET, 'minorVersion', FILTER_SANITIZE_NUMBER_INT);

        header('Cache-Control: no-cache');
        header('Content-type: application/json');

        if ($name) {
            $out = $editor->getLibraryData($name, $major_version, $minor_version, substr($prefs['language'], 0, 2), '');

            // Log library load
            new H5P_Event(
                'library',
                null,
                null,
                null,
                $name,
                $major_version . '.' . $minor_version
            );
        } else {
            $out = $editor->getLibraries();
        }

        return json_decode(json_encode($out), true);
    }

    public function action_list_libraries($input)
    {
        global $prefs;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST['libraries'] = [];
            foreach ($input->libraries as $library) {
                $_POST['libraries'][] = $library;
            }
        }

        $editor = \H5P_EditorTikiStorage::get_h5peditor_instance();

        $name = $input->machineName->text();
        $majorVersion = $input->majorVersion->int();
        $minorVersion = $input->minorVersion->int();

        if ($name) {
            $results = $editor->getLibraryData($name, $majorVersion, $minorVersion, substr($prefs['language'], 0, 2), '');
            $results = json_decode($results, true);

            $results['name'] = $name;
            $results['majorVersion'] = $majorVersion;
            $results['minorVersion'] = $minorVersion;

            $results['libraries'] = $editor->findEditorLibraries($name, $majorVersion, $minorVersion);

            // Log library load
            new H5P_Event(
                'library',
                null,
                null,
                null,
                $name,
                $majorVersion . '.' . $minorVersion
            );
        } else {
            $results = $editor->getLibraries();
            $results = json_decode(json_encode($results), true);
        }

        return [
            'title' => tr('H5P Content Libraries'),
            'results' => $results,
        ];
    }

    public function action_library_install($input)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            //$token = func_get_arg(1);
            //if (! $this->isValidEditorToken($token)) return;

            $editor = \H5P_EditorTikiStorage::get_h5peditor_instance();
            $editor->ajax->action(H5PEditorEndpoints::LIBRARY_INSTALL, '', $input->id->text());
            exit;
        }
    }

    public function action_files($input)
    {
        $files_directory = \H5P_H5PTiki::$h5p_path;

        // Get Content ID for upload
        $contentId = $input->contentId->int();

        $file = new \H5peditorFile(\H5P_H5PTiki::get_h5p_instance('interface'));
        if (! $file->isLoaded()) {
            H5PCore::ajaxError(tr('File not found on server. Check file upload settings.'));
            exit;
        }

        // Make sure file is valid
        if ($file->validate()) {
            $core = \H5P_H5PTiki::get_h5p_instance('core');

            // Save the valid file
            $file_id = $core->fs->saveFile($file, $contentId);

            // Keep track of temporary files so they can be cleaned up later.
            TikiDb::get()->table('tiki_h5p_tmpfiles')->insert([
                'path' => $file_id,
                'created_at' => time(),
            ]);
        }

        header('Cache-Control: no-cache');
        $file->printResult();
        exit;
    }

    /**
     * Handle user results reported by the H5P content.
     * @param JitFilter $input
     * @throws Services_Exception_NotAvailable
     * @return array
     */
    public function action_results($input)
    {
        global $user;

        $contentId = $input->contentId->int();

        if (! $contentId) {
            throw new Services_Exception_NotAvailable(tr('H5P Results:') . ' ' . tr('No contentId provided.'));
        }

        $user_id = TikiLib::lib('user')->get_user_id($user);

        $tiki_h5p_results = TikiDb::get()->table('tiki_h5p_results');
        $result_id = $tiki_h5p_results->fetchOne(
            'id',
            [
                'user_id' => $user_id,
                'content_id' => $contentId,
            ]
        );

        $data = [
            'score' => $input->score->int(),
            'max_score' => $input->maxScore->int(),
            'opened' => $input->opened->int(),
            'finished' => $input->finished->int(),
            'time' => $input->finished->int() - $input->opened->int(),    // is this right?
        ];

        if (! $result_id) {
            // Insert new results
            $data['user_id'] = $user_id;
            $data['content_id'] = $contentId;
            $tiki_h5p_results->insert($data);
        } else {
            // Update existing results
            $tiki_h5p_results->update($data, ['id' => $result_id]);
        }

        // Get content info for log
        $H5PTiki = new H5P_H5PTiki();
        $content = $H5PTiki->loadContent($contentId);

        // Log view
        new H5P_Event(
            'results',
            'set',
            $contentId,
            $content->title,
            $content->name,
            $content->major_version . '.' . $content->minor_version
        );

        return [];
    }

    /**
     * @param JitFilter $input
     * @throws Services_Exception_NotAvailable
     * @return array
     */
    public function action_userdata($input)
    {
        global $user;

        $contentId = $input->contentId->int();

        if (! $contentId) {
            throw new Services_Exception_NotAvailable(tr('H5P User Data:') . ' ' . tr('No contentId provided.'));
        }

        $data = [
            'dataType' => $input->dataType->word(),
            'data' => json_decode($input->data->text(), true),
            'subContentId' => $input->subContentId->int(),
            'preload' => $input->preload->int(),
            'invalidate' => $input->invalidate->int(),
        ]
        ;
        TikiLib::lib('tiki')->set_user_preference($user, "h5p_content_$contentId", json_encode($data));

        return ['data' => $data];
    }

    public function action_list_results($input)
    {
        // tiki_p_admin required for now
        \Services_Exception_Denied::checkGlobal('admin');

        $results = TikiDb::get()->query('SELECT r.*, c.`title`, c.`file_id`, u.`login`
FROM `tiki_h5p_results` AS r
LEFT JOIN `tiki_h5p_contents` AS c ON r.`content_id` = c.`id`
LEFT JOIN `users_users` AS u ON u.`userId` = r.`user_id`');

        return [
            'title' => tr('H5P User Results'),
            'results' => $results->result,
        ];
    }

    public function action_cron($input)
    {
        global $prefs;

        ignore_user_abort(true);

        // Verify token to prevent unauthorized use
        if (! isset($prefs['h5p_cron_token']) || $prefs['h5p_cron_token'] !== $input->token->word()) {
            return 'Invalid token'; // Invalid token
        }

        // Register run time
        TikiLib::lib('tiki')->set_preference('h5p_cron_last_run', time());

        // Clean up old temporary files
        TikiLib::lib('h5p')->removeOldTmpFiles();

        // update libs from hub if set
        $H5PTiki = new H5P_H5PTiki();
        $H5PTiki->getLibraryUpdates();

        // Check for metadata updates
        $core = \H5P_H5PTiki::get_h5p_instance('core');
        $core->fetchLibrariesMetadata();

        return '';
    }

    /**
     * Called as content-type-cache from H5PEditorEndpoints::CONTENT_TYPE_CACHE ("-"s replaced for "_"s in editor.js)
     *
     * @param JitFilter $input
     *
     * @return null
     */
    public function action_content_type_cache($input)
    {
        $token = $input->token->text();

        $editor = \H5P_EditorTikiStorage::get_h5peditor_instance();
        $editor->ajax->action(H5PEditorEndpoints::CONTENT_TYPE_CACHE, $token);
        exit;
    }
}
