<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\File\PDFHelper;

class Tracker_Field_Files extends Tracker_Field_Abstract implements Tracker_Field_Exportable
{
    public static function getTypes()
    {
        global $prefs;

        $options = [
            'FG' => [
                'name' => tr('Files'),
                'description' => tr('Attached and upload files stored in the file galleries to the tracker item.'),
                'prefs' => ['trackerfield_files', 'feature_file_galleries'],
                'tags' => ['advanced'],
                'help' => 'Files Tracker Field',
                'default' => 'y',
                'params' => [
                    'galleryId' => [
                        'name' => tr('Gallery ID'),
                        'description' => tr('File gallery to upload new files into.'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'file_gallery',
                    ],
                    'filter' => [
                        'name' => tr('MIME Type Filter'),
                        'description' => tr('Mask for accepted MIME types in the field'),
                        'filter' => 'text',
                        'legacy_index' => 1,
                    ],
                    'count' => [
                        'name' => tr('File Count'),
                        'description' => tr('Maximum number of files to be attached on the field.'),
                        'filter' => 'int',
                        'legacy_index' => 2,
                    ],
                    'displayMode' => [
                        'name' => tr('Display Mode'),
                        'description' => tr('Show files as object links or via a wiki plugin (img so far)'),
                        'filter' => 'word',
                        'options' => [
                            '' => tr('Links'),
                            'barelink' => tr('Bare Links'),
                            'table' => tr('Table'),
                            'img' => tr('Images'),
                            'vimeo' => tr('Vimeo'),
                            'googleviewer' => tr('Google Viewer'),
                            'moodlescorm' => tr('Moodle Scorm Viewer'),
                        ],
                    ],
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters used such as in the {img} plugin, for example,.') . ' "max=400&desc=namedesc&stylebox=block"',
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters used such as in the {img} plugin, for example,.') . ' "thumb=box&max=60"',
                        'filter' => 'text',
                    ],
                    'displayOrder' => [
                        'name' => tr('Display Order'),
                        'description' => tr('Sort order for the files'),
                        'filter' => 'word',
                        'options' => [
                            '' => tr('Default (order added to tracker item)'),
                            'name_asc' => tr('Name (A - Z)'),
                            'name_desc' => tr('Name (Z - A)'),
                            'filename_asc' => tr('Filename (A - Z)'),
                            'filename_desc' => tr('Filename (Z - A)'),
                            'created_asc' => tr('Created date (old - new)'),
                            'created_desc' => tr('Created date (new - old)'),
                            'lastModif_asc' => tr('Last modified date (old - new)'),
                            'lastModif_desc' => tr('Last modified date (new - old)'),
                            'filesize_asc' => tr('File size (small - large)'),
                            'filesize_desc' => tr('File size (large - small)'),
                            'hits_asc' => tr('Hits (low - high)'),
                            'hits_desc' => tr('Hits (high - low)'),
                        ],
                    ],
                    'deepGallerySearch' => [
                        'name' => tr('Include Child Galleries'),
                        'description' => tr('Use files from child galleries as well.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'replace' => [
                        'name' => tr('Replace Existing File'),
                        'description' => tr('Replace the existing file, if any, instead of uploading a new one.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'browseGalleryId' => [
                        'name' => tr('Browse Gallery ID'),
                        'description' => tr('File gallery browse files. Use 0 for root file gallery. (requires elFinder feature - experimental)') . '. ' . tr('Restrict permissions to view the file gallery to hide the button.') ,
                        'filter' => 'int',
                        'profile_reference' => 'file_gallery',
                    ],
                    'duplicateGalleryId' => [
                        'name' => tr('Duplicate Gallery ID'),
                        'description' => tr('File gallery to duplicate files into when copying the tracker item. 0 or empty means do not duplicate (default).'),
                        'filter' => 'int',
                        'profile_reference' => 'file_gallery',
                    ],
                    'indexGeometry' => [
                        'name' => tr('Index As Map Layer'),
                        'description' => tr('Index the files in a specific format for use in map searchlayers to display trails and features.'),
                        'filter' => 'text',
                        'default' => '',
                        'options' => [
                            '' => tr('No'),
                            'geojson' => tr('GeoJSON'),
                            'gpx' => tr('GPX'),
                        ],
                    ],
                    'uploadInModal' => [
                        'name' => tr('Upload In Modal'),
                        'description' => tr('Upload files in a new modal window.'),
                        'filter' => 'alpha',
                        'default' => 'y',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'image_x' => [
                        'name' => tr('Maximum image width'),
                        'description' => tr('Leave blank to use selected gallery default setting or enter value in pixels to override gallery settings'),
                        'filter' => 'text',
                        'default' => '',
                    ],
                    'image_y' => [
                        'name' => tr('Maximum image height'),
                        'description' => tr('Leave blank to use selected gallery default settings or enter value in pixels to override gallery settings'),
                        'filter' => 'text',
                    ],
                    'addDecriptionOnUpload' => [
                        'name' => tr('Add Descriptions'),
                        'description' => tr('Add descriptions on uploaded files.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'requireTitle' => [
                        'name' => tr('Require file title'),
                        'description' => tr('Require a file title which will be saved as the name of the file in the file gallery in addition to the filename. Upload In Modal required.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ]
                ],
            ],
        ];
        if (isset($prefs['vimeo_upload']) && $prefs['vimeo_upload'] === 'y') {
            $options['FG']['params']['displayMode']['description'] = tr('Show files as object links or via a wiki plugin (img, Vimeo)');
            $options['FG']['params']['displayMode']['options']['vimeo'] = tr('Vimeo');
        }

        return $options;
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;
        $filegallib = TikiLib::lib('filegal');

        $galleryId = (int) $this->getOption('galleryId');
        $count = (int) $this->getOption('count');
        $deepGallerySearch = (boolean) $this->getOption('deepGallerySearch');

        // to use the user's userfiles gallery enter the fgal_root_user_id which is often (but not always) 2
        $galleryId = $filegallib->check_user_file_gallery($galleryId);

        $value = '';
        $ins_id = $this->getInsertId();
        if (isset($requestData[$ins_id])) {
            // Incoming data from form

            // Get the list of selected file IDs from the text field
            $value = $requestData[$ins_id];
            $fileIds = explode(',', $value);

            // Remove missed uploads
            $fileIds = array_filter($fileIds);

            // Obtain the info for display and filter by type if specified
            $fileInfo = $this->getFileInfo($fileIds);
            $fileInfo = array_filter($fileInfo, [$this, 'filterFile']);

            // Rebuild the database value, but preserve the order the files have been attached to the item
            foreach ($fileIds as & $fileId) {
                if (! isset($fileInfo[$fileId])) {
                    $fileId = 0;
                }
            }

            // Keep only the last files if a limit is applied
            if ($count) {
                $fileIds = array_filter($fileIds);
                $fileIds = array_slice($fileIds, -$count);
                $value = implode(',', $fileIds);
            } else {
                $value = implode(',', array_filter($fileIds));
            }
        } else {
            $value = $this->getValue();

            // Obtain the information from the database for display
            $fileIds = array_filter(explode(',', $value));
            $fileInfo = $this->getFileInfo($fileIds);
        }

        if ($deepGallerySearch) {
            $gallery_list = null;
            $filegallib->getGalleryIds($gallery_list, $galleryId, 'list');
            $gallery_list = implode(' or ', $gallery_list);
        } else {
            $gallery_list = $galleryId;
        }

        if ($this->getOption('displayMode') == 'img' && $fileIds) {
            $firstfile = $fileIds[0];
        } else {
            $firstfile = 0;
        }

        $galinfo = $filegallib->get_file_gallery($galleryId);
        if (!$galinfo) {
            Feedback::error(tr('Files field: Gallery #%0 not found', $galleryId));

            return [];
        }
        if ($prefs['feature_use_fgal_for_user_files'] !== 'y' || $galinfo['type'] !== 'user') {
            $perms = Perms::get('file gallery', $galleryId);
            $canUpload = $perms->upload_files;
        } else {
            global $user;
            $perms = TikiLib::lib('tiki')->get_local_perms($user, $galleryId, 'file gallery', $galinfo, false);		//get_perm_object($galleryId, 'file gallery', $galinfo);
            $canUpload = $perms['tiki_p_upload_files'] === 'y';
        }

        $image_x = $this->getOption('image_x');
        $image_y = $this->getOption('image_y');

        //checking if image_x and image_y are set
        if (! $image_x) {
            $image_x = $galinfo['image_max_size_x'];
        }

        if (! $image_y) {
            $image_y = $galinfo['image_max_size_y'];
        }



        return [
            'galleryId' => $galleryId,
            'canUpload' => $canUpload,
            'limit' => $count,
            'files' => $fileInfo,
            'firstfile' => $firstfile,
            'value' => $value,
            'filter' => $this->getOption('filter'),
            'image_x' => $image_x,
            'image_y' => $image_y,
            'gallerySearch' => $gallery_list,
            'requireTitle' => $this->getOption('requireTitle'),
        ];
    }

    public function renderInput($context = [])
    {
        global $prefs;

        $context['canBrowse'] = false;

        if ($prefs['fgal_tracker_existing_search'] == 'y') {
            if ($this->getOption('browseGalleryId')) {
                $defaultGalleryId = $this->getOption('browseGalleryId');
            } elseif ($this->getOption('galleryId')) {
                $defaultGalleryId = $this->getOption('galleryId');
            } else {
                $defaultGalleryId = 0;
            }
            $deepGallerySearch = $this->getOption('deepGallerySearch');
            //in case $deepGallerySearch is false
            $deepGallerySearch = $deepGallerySearch == 1 ? 1 : 0;
            $image_x = $this->getOption('image_x');
            $image_y = $this->getOption('image_y');

            if ($prefs['fgal_elfinder_feature'] == 'y') {
                $smarty = TikiLib::lib('smarty');
                $smarty->loadPlugin('smarty_function_ticket');
                $context['onclick'] = 'return openElFinderDialog(this, {
	defaultGalleryId:' . $defaultGalleryId . ',
	deepGallerySearch: ' . $deepGallerySearch . ',
	ticket: \'' . smarty_function_ticket(['mode' => 'get'], $smarty) . '\',
	getFileCallback: function(file,elfinder){ window.handleFinderFile(file,elfinder); },
	eventOrigin:this
});';
            }
            $context['galleryId'] = $defaultGalleryId;
            $context['canBrowse'] = Perms::get(['type' => 'file gallery', 'object' => $defaultGalleryId])->view_file_gallery;
        }

        return $this->renderTemplate('trackerinput/files.tpl', $context, [
            'replaceFile' => 'y' == $this->getOption('replace', 'n'),
            'addDecriptionOnUpload' => $this->getOption('addDecriptionOnUpload') === 'y' ? 1 : 0,
        ]);
    }

    public function renderOutput($context = [])
    {
        global $prefs;
        global $mimetypes;
        include('lib/mime/mimetypes.php');
        $galleryId = (int)$this->getOption('galleryId');

        if (! isset($context['list_mode'])) {
            $context['list_mode'] = 'n';
        }
        if (! $this->getOption('displayOrder')) {
            $value = $this->getValue();
        } else {
            $value = $this->getConfiguration('files');
            $value = implode(',', array_keys($value));
        }

        if ($context['list_mode'] === 'csv') {
            return $value;
        }

        if ($context['list_mode'] === 'text') {
            return implode(
                "\n",
                array_map(
                    function ($file) {
                        return $file['name'];
                    },
                    $this->getConfiguration('files')
                )
            );
        }

        $ret = '';
        if (empty($value)) {
            $ret = '&nbsp;';
        } else {
            if ($this->getOption('displayMode')) { // images etc
                $params = [
                    'fileId' => $value,
                ];
                if ($context['list_mode'] === 'y') {
                    $otherParams = $this->getOption('displayParamsForLists');
                } else {
                    $otherParams = $this->getOption('displayParams');
                }
                if ($otherParams) {
                    parse_str($otherParams, $otherParams);
                    $params = array_merge($params, $otherParams);
                }
                $params['fromFieldId'] = $this->getConfiguration('fieldId');
                $params['fromItemId'] = $this->getItemId();
                $item = Tracker_Item::fromInfo($this->getItemData());
                $params['checkItemPerms'] = $item->canModify() ? 'n' : 'y';

                if ($this->getOption('displayMode') == 'img') { // img
                    if ($context['list_mode'] === 'y') {
                        $params['thumb'] = $context['list_mode'];
                        $params['rel'] = 'box[' . $this->getInsertId() . ']';
                    }
                    include_once('lib/wiki-plugins/wikiplugin_img.php');
                    $ret = wikiplugin_img('', $params);
                } elseif ($this->getOption('displayMode') == 'vimeo') {	// Vimeo videos stored as filegal REMOTEs
                    include_once('lib/wiki-plugins/wikiplugin_vimeo.php');
                    $ret = wikiplugin_vimeo('', $params);
                } elseif ($this->getOption('displayMode') == 'moodlescorm') {
                    include_once('lib/wiki-plugins/wikiplugin_playscorm.php');
                    foreach ($this->getConfiguration('files') as $fileId => $file) {
                        $params['fileId'] = $fileId;
                        $ret .= wikiplugin_playscorm('', $params);
                    }
                } elseif ($this->getOption('displayMode') == 'googleviewer') {
                    if ($prefs['auth_token_access'] != 'y') {
                        $ret = tra('Token access needs to be enabled for Google viewer to be used');
                    } else {
                        $files = [];
                        foreach ($this->getConfiguration('files') as $fileId => $file) {
                            global $base_url, $tikiroot, $https_mode;
                            if ($https_mode) {
                                $scheme = 'https';
                            } else {
                                $scheme = 'http';
                            }
                            $googleurl = $scheme . "://docs.google.com/viewer?url=";
                            if ($prefs['feature_sefurl'] === 'y') {
                                $fileurl = urlencode($base_url . "dl" . $fileId);
                            } else {
                                $fileurl = urlencode($base_url . "tiki-download_file.php?fileId=" . $fileId);
                            }
                            require_once 'lib/auth/tokens.php';
                            $tokenlib = AuthTokens::build($prefs);
                            if ($prefs['feature_sefurl'] === 'y') {
                                $token = $tokenlib->createToken(
                                    $tikiroot . "dl" . $fileId,
                                    [],
                                    ['Registered'],
                                    ['timeout' => 600, 'hits' => 6]
                                );
                                $fileurl .= urlencode("?TOKEN=" . $token);
                            } else {
                                $token = $tokenlib->createToken(
                                    $tikiroot . "tiki-download_file.php",
                                    ['fileId' => $fileId],
                                    ['Registered'],
                                    ['timeout' => 600, 'hits' => 6]
                                );
                                $fileurl .= urlencode("&TOKEN=" . $token);
                            }
                            $url = $googleurl . $fileurl . '&embedded=true';
                            $title = $file['name'];
                            $files[] = ['url' => $url, 'title' => $title, 'id' => $fileId];
                        }
                        $smarty = TikiLib::lib('smarty');
                        $smarty->assign('files', $files);
                        $ret = $smarty->fetch('trackeroutput/files_googleviewer.tpl');
                    }
                } elseif ($this->getOption('displayMode') == 'barelink') {
                    $smarty = TikiLib::lib('smarty');
                    $smarty->loadPlugin('smarty_function_object_link');
                    $smarty->loadPlugin('smarty_modifier_sefurl');
                    foreach ($this->getConfiguration('files') as $fileId => $file) {
                        $ret .= smarty_modifier_sefurl($file['fileId'], 'file');
                    }
                } elseif ($this->getOption('displayMode') == 'table') {
                    $ret = $this->renderTemplate('trackeroutput/files_table.tpl', $context, [
                        'files' => $this->getConfiguration('files')
                    ]);
                }
                $ret = preg_replace('/~\/?np~/', '', $ret);
            } else {
                $smarty = TikiLib::lib('smarty');
                $smarty->loadPlugin('smarty_function_object_link');
                $smarty->loadPlugin('smarty_modifier_iconify');
                $ret = '<ol class="tracker-item-files">';

                foreach ($this->getConfiguration('files') as $fileId => $file) {
                    $ret .= '<li class="m-1">';
                    if ($prefs['vimeo_upload'] == 'y' && $this->getOption('displayMode') == 'vimeo') {
                        $ret .= smarty_function_icon(['name' => 'vimeo'], $smarty->getEmptyInternalTemplate());
                    } else {
                        $ret .= smarty_modifier_iconify('tiki-download_file.php?fileId=' . $fileId, $file['filetype'], $fileId, 2);
                    }

                    $ret .= smarty_function_object_link(['type' => 'file', 'id' => $fileId, 'title' => $file['name']], $smarty->getEmptyInternalTemplate());

                    $globalperms = Perms::get([ 'type' => 'file gallery', 'object' => $galleryId ]);

                    if ($prefs['feature_draw'] == 'y' &&
                        $globalperms->upload_files == 'y' &&
                        ($file['filetype'] == $mimetypes["svg"] ||
                        $file['filetype'] == $mimetypes["gif"] ||
                        $file['filetype'] == $mimetypes["jpg"] ||
                        $file['filetype'] == $mimetypes["png"] ||
                        $file['filetype'] == $mimetypes["tiff"])
                    ) {
                        $smarty->loadPlugin('smarty_function_icon');
                        $editicon = smarty_function_icon(['name' => 'edit'], $smarty->getEmptyInternalTemplate());
                        $ret .= " <a href='tiki-edit_draw.php?fileId=" . $file['fileId']
                            . "' onclick='return $(this).ajaxEditDraw();' class='tips' title='Edit: " . $file['name']
                            . "' data-fileid='" . $file['fileId'] . "' data-galleryid='" . $galleryId . "'>
							$editicon
						</a>";
                    }

                    $smarty->loadPlugin('smarty_function_icon');
                    $viewicon = smarty_function_icon(['name' => 'view'], $smarty->getEmptyInternalTemplate());

                    if ($prefs['fgal_pdfjs_feature'] == 'y' &&
                        ($file['filetype'] == $mimetypes["pdf"] || (PDFHelper::canConvertToPDF($file['filetype']) && $prefs['fgal_convert_documents_pdf'] == 'y'))
                    ) {
                        $ret .= " <a href='tiki-display.php?fileId=" . $file['fileId']
                            . "' target='_blank' class='tips' title='Preview: " . $file['filename'] . "'>
							$viewicon
						</a>";
                    } else {
                        $dataAttributes = [];

                        if ($file['filetype'] === 'text/plain') {
                            $dataAttributes[] = 'data-is-text="1"';
                        }

                        $src = smarty_modifier_sefurl($file['fileId'], 'display');
                        $ret .= " <a href='" . $src . "' target='_blank' class='tips' title='Preview: " . $file['filename'] . "' data-box='box-" . $this->getConfiguration('fieldId') . "' " . implode($dataAttributes, ' ') . ">
							$viewicon
						</a>";
                    }

                    $ret .= '</li>';
                }
                $ret .= '</ol>';
            }
        }

        return $ret;
    }

    public function handleSave($value, $oldValue)
    {
        $new = array_diff(explode(',', $value), explode(',', $oldValue));
        $remove = array_diff(explode(',', $oldValue), explode(',', $value));

        $itemId = $this->getItemId();

        if ($itemId) {
            $relationlib = TikiLib::lib('relation');
            $relations = $relationlib->get_relations_from('trackeritem', $itemId, 'tiki.file.attach');
            foreach ($relations as $existing) {
                if ($existing['type'] != 'file') {
                    continue;
                }

                if (in_array($existing['itemId'], $remove)) {
                    $relationlib->remove_relation($existing['relationId']);
                }
            }

            foreach ($new as $fileId) {
                if (! empty($fileId)) {
                    $relationlib->add_relation('tiki.file.attach', 'trackeritem', $itemId, 'file', $fileId);
                }
            }
        }

        return [
            'value' => $value,
        ];
    }

    /**
     * called from action_clone_item and duplicates the related files if option duplicateGalleryID is set
     * @param mixed $strict
     */
    public function handleClone($strict = false)
    {
        global $prefs;

        $oldValue = $this->getValue();
        if ($galleryId = $this->getOption('duplicateGalleryId')) {
            $filegallib = TikiLib::lib('filegal');

            // to use the user's userfiles gallery enter the fgal_root_user_id which is often (but not always) 2
            $galleryId = $filegallib->check_user_file_gallery($galleryId);

            $newIds = [];

            foreach (array_filter(explode(',', $oldValue)) as $fileId) {
                $newIds[] = $filegallib->duplicate_file($fileId, $galleryId);
            }

            return $this->handleSave(implode(',', $newIds), $oldValue);
        }

        return [
            'value' => $oldValue,
        ];
    }

    public function watchCompare($old, $new)
    {
        $name = $this->getConfiguration('name');
        $isVisible = $this->getConfiguration('isHidden', 'n') == 'n';

        if (! $isVisible) {
            return;
        }

        $filegallib = TikiLib::lib('filegal');

        $oldFileIds = explode(',', $old);
        $newFileIds = explode(',', $new);

        $oldFileInfos = empty($oldFileIds) ? [] : $filegallib->get_files_info(null, $oldFileIds);
        $newFileInfos = empty($newFileIds) ? [] : $filegallib->get_files_info(null, $newFileIds);

        $oldValueLines = '';
        foreach ($oldFileInfos as $info) {
            $oldValueLines .= '> ' . $info['filename'];
        }
        $newValueLines = '';
        foreach ($newFileInfos as $info) {
            $newValueLines .= '> ' . $info['filename'];
        }

        return "[-[$name]-]:\n--[Old]--:\n$oldValueLines\n\n*-[New]-*:\n$newValueLines";
    }

    public function renderDiff($context = [])
    {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_modifier_sefurl');
        $smarty->loadPlugin('smarty_modifier_escape');
        $smarty->loadPlugin('smarty_modifier_iconify');

        if ($context['oldValue']) {
            $old = $context['oldValue'];
        } else {
            $old = '';
        }
        if ($context['value']) {
            $new = $context['value'];
        } else {
            $new = $this->getValue('');
        }
        if (empty($context['diff_style'])) {
            $context['diff_style'] = 'sidediff';
        }

        $filegallib = TikiLib::lib('filegal');

        $oldFileIds = explode(',', $old);
        $newFileIds = explode(',', $new);

        $filesRemoved = array_diff($oldFileIds, $newFileIds);
        $filesAdded = array_diff($newFileIds, $oldFileIds);

        $addedFileInfos = empty($filesAdded) ? [] : $filegallib->get_files_info(null, $filesAdded);
        $removedFileInfos = empty($filesRemoved) ? [] : $filegallib->get_files_info(null, $filesRemoved);

        $result = '<table class="table"><tr><td class="diffdeleted">-</td><td class="diffdeleted"><del class="diffchar deleted">';

        foreach ($removedFileInfos as $file) {
            $url = smarty_modifier_sefurl($file['fileId'], 'file');
            $result .= smarty_modifier_iconify($url, $file['filetype'], $file['fileId'], 1);
            $result .= ' <a href="' . $url . '">' . smarty_modifier_escape($file['name']) . '</a><br>';
        }

        $result .= '</del></td><td class="diffadded">+</td><td class="diffadded"><ins class="diffchar inserted">';

        foreach ($addedFileInfos as $file) {
            $url = smarty_modifier_sefurl($file['fileId'], 'file');
            $result .= smarty_modifier_iconify($url, $file['filetype'], $file['fileId'], 1);
            $result .= ' <a href="' . $url . '">' . smarty_modifier_escape($file['name']) . '</a><br>';
        }

        $result .= '</ins></td></tr></table>';

        return $result;
    }

    public function filterFile($info)
    {
        $filter = $this->getOption('filter');

        if (! $filter) {
            return true;
        }

        $parts = explode('*', $filter);
        $parts = array_map('preg_quote', $parts, array_fill(0, count($parts), '/'));

        $body = implode('[\w-]*', $parts);

        // Force begin, ignore end which may contain charsets or other attributes
        return preg_match("/^$body/", $info['filetype']);
    }

    private function getFileInfo($ids)
    {
        $db = TikiDb::get();
        $table = $db->table('tiki_files');

        $sortOrder = $this->getOption('displayOrder');

        $data = $table->fetchAll(
            [
                'fileId',
                'name',
                'filename',
                'filetype',
                'archiveId',
                'lastModif',
                'description'
            ],
            [
                'fileId' => $table->in($ids),
            ],
            -1,
            -1,
            $table->sortMode($sortOrder)
        );

        $out = [];
        foreach ($data as $info) {
            $out[$info['fileId']] = $info;
        }

        if (! $sortOrder) {	// re-order result into order they were attached
            $out2 = [];
            foreach ($ids as $id) {
                if (isset($out[$id])) {
                    $out2["$id"] = $out[$id];
                } else {
                    $itemId = $this->getItemId();
                    if ($itemId) {
                        $smarty = TikiLib::lib('smarty');
                        $smarty->loadPlugin('smarty_function_object_link');

                        Feedback::warning(
                            tr(
                                'File #%0 missing (was attached to trackerfield "%1" on item %2)',
                                $id,
                                $this->getConfiguration('permName'),
                                smarty_function_object_link(
                                    [
                                        'id' => $itemId,
                                        'type' => 'trackeritem',
                                    ],
                                    $smarty
                                )
                            )
                        );
                    }
                }
            }
            $out = $out2;
        } elseif (strstr($sortOrder, 'name')) {
            $sep = strrpos($sortOrder, '_');
            $field = substr($sortOrder, 0, $sep);
            $dir = substr($sortOrder, $sep + 1);
            $sortArray = array_map(function ($file) use ($field) {
                return isset($file[$field]) ? $file[$field] : '';
            }, $out);
            natsort($sortArray);
            if ($dir == 'desc') {
                $sortArray = array_reverse($sortArray, true);
            }
            $sorted = [];
            foreach ($sortArray as $key => $_) {
                $sorted[$key] = $out[$key];
            }
            $out = $sorted;
        }

        return $out;
    }

    private function handleUpload($galleryId, $file)
    {
        if (empty($file['tmp_name'])) {
            // Not an actual file upload attempt, just skip
            return false;
        }

        if (! is_uploaded_file($file['tmp_name'])) {
            Feedback::error(tr('Problem with uploaded file: "%0"', $file['name']));

            return false;
        }

        $filegallib = TikiLib::lib('filegal');
        $gal_info = $filegallib->get_file_gallery_info($galleryId);

        if (! $gal_info) {
            Feedback::error(tr('No gallery for uploaded file, galleryId=%0', $galleryId));

            return false;
        }

        $perms = Perms::get('file gallery', $galleryId);
        if (! $perms->upload_files) {
            Feedback::error(tr('You don\'t have permission to upload a file to gallery "%0"', $gal_info['name']));

            return false;
        }

        $fileIds = $this->getConfiguration('files');

        if ($this->getOption('displayMode') == 'img' && is_array($fileIds) && count($fileIds) > 0) {
            return $filegallib->update_single_file($gal_info, $file['name'], $file['size'], $file['type'], file_get_contents($file['tmp_name']), $fileIds[0]);
        }

        return $filegallib->upload_single_file($gal_info, $file['name'], $file['size'], $file['type'], file_get_contents($file['tmp_name']));
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            TikiLib::lib('smarty')->loadPlugin('smarty_modifier_sefurl');
            $urls = [];

            foreach (explode(',', $this->getValue()) as $value) {
                $urls[] = smarty_modifier_sefurl($value, 'file');
            }

            return [
                'geo_located' => $typeFactory->identifier('y'),
                'geo_file' => $typeFactory->identifier(implode(',', $urls)),
                'geo_file_format' => $typeFactory->identifier($this->getOption('indexGeometry')),
            ];
        }

        return parent::getDocumentPart($typeFactory);
    }

    public function getProvidedFields()
    {
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            return ['geo_located', 'geo_file', 'geo_file_format'];
        }

        return parent::getProvidedFields();
    }

    public function getGlobalFields()
    {
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            return [];
        }

        return parent::getGlobalFields();
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (& $info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }
}
