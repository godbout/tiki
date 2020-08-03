<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'wiki_syntax' => 'wikicontent',
            'fgal_list_ocr_state' => 'alpha',
        ],
        'staticKeyFiltersForArrays' => [
            'ocr_lang' => 'text',
        ]
    ],
];

$section = 'file_galleries';
require_once('tiki-setup.php');
$access->check_feature(['feature_file_galleries', 'feature_jquery_tooltips']);
$filegallib = TikiLib::lib('filegal');
$statslib = TikiLib::lib('stats');

if ($prefs['feature_categories'] == 'y') {
    $categlib = TikiLib::lib('categ');
}

$templateslib = TikiLib::lib('template');

if ($prefs['feature_groupalert'] == 'y') {
    $groupalertlib = TikiLib::lib('groupalert');
}

$auto_query_args = [
    'galleryId',
    'offset',
    'find',
    'find_creator',
    'find_categId',
    'sort_mode',
    'edit_mode',
    'page',
    'filegals_manager',
    'insertion_syntax',
    'maxRecords',
    'show_fgalexplorer',
    'dup_mode',
    'show_details',
    'view',
];
if (! empty($_REQUEST['find_other'])) {
    $info = $filegallib->get_file_info($_REQUEST['find_other']);
    if (! empty($info)) {
        $galleryId = $info['galleryId'];
        $smarty->assign('find_other_val', $_REQUEST['find_other']);
    }
}

if (isset($_REQUEST['galleryId']) && ! isset($galleryId)) {
    $galleryId = $_REQUEST['galleryId'];
}

if (empty($galleryId) && isset($_REQUEST['parentId'])) {
    // check perms on parent gallery
    $parent_gal_info = $filegallib->get_file_gallery($_REQUEST['parentId']);
    $tikilib->get_perm_object('', 'file gallery', $parent_gal_info);

    $galleryId = 0;

    // Initialize listing fields with default values (used for the main gallery listing)
    $gal_info = $filegallib->get_file_gallery();
    $gal_info['usedSize'] = 0;
    $gal_info['maxQuota'] = $filegallib->getQuota($_REQUEST['parentId'], true);

    if ($prefs['feature_use_fgal_for_user_files'] === 'y' &&
        $parent_gal_info['type'] === 'user' && $parent_gal_info['user'] === $user && $tiki_p_userfiles === 'y') {
        $gal_info['type'] = 'user';
        $gal_info['user'] = $user;
    }

    $old_gal_info = [];
} else {
    if (! isset($_REQUEST['galleryId'])) {
        $galleryId = $prefs['fgal_root_id'];
    }

    if ($gal_info = $filegallib->get_file_gallery($galleryId)) {
        $tikilib->get_perm_object($galleryId, 'file gallery', $gal_info);
        if ($userlib->object_has_one_permission($galleryId, 'file gallery')) {
            $smarty->assign('individual', 'y');
        }
        $podCastGallery = $filegallib->isPodCastGallery($galleryId, $gal_info);
    } else {
        Feedback::errorPage(tr('Non-existent gallery'));
    }
    if ($prefs['fgal_quota_per_fgal'] === 'y') {
        $gal_info['usedSize'] = $filegallib->getUsedSize($galleryId);
        $gal_info['maxQuota'] = $filegallib->getQuota($gal_info['parentId']);
        $gal_info['minQuota'] = $filegallib->getMaxQuotaDescendants($galleryId);
    } else {
        $gal_info['usedSize'] = 0;
        $gal_info['maxQuota'] = 0;
        $gal_info['minQuota'] = 0;
    }

    if ($galleryId == $prefs['fgal_root_user_id'] && $tiki_p_admin_file_galleries !== 'y') {
        include_once('tiki-sefurl.php');
        header(
            'Location: ' . filter_out_sefurl(
                'tiki-list_file_gallery.php?galleryId=' . $filegallib->get_user_file_gallery()
            )
        );
    }
}

if (($galleryId != 0 || $tiki_p_list_file_galleries != 'y') && ($galleryId == 0 || $tiki_p_view_file_gallery != 'y')) {
    Feedback::errorPage(['mes' => tr('You do not have permission to view this section'), 'errortype' => 401]);
}
if ($prefs['feature_use_fgal_for_user_files'] === 'y' && $gal_info['type'] === 'user' &&
    $gal_info['visible'] !== 'y' && $gal_info['user'] !== $user && $tiki_p_admin_file_galleries !== 'y') {
    Feedback::errorPage(['mes' => tr('You do not have permission to view this gallery'), 'errortype' => 401]);
}

// Init smarty variables to blank values
$smarty->assign('fname', '');
$smarty->assign('fdescription', '');
$smarty->assign('max_desc', 1024);
$smarty->assign('maxRows', $maxRecords);
$smarty->assign('edited', 'n');
$smarty->assign('edit_mode', 'n');
$smarty->assign('dup_mode', 'n');
$smarty->assign(
    'parentId',
    isset($_REQUEST['parentId']) ? (int)$_REQUEST['parentId'] : (isset($gal_info['parentId']) ? $gal_info['parentId'] : -1)
);
$smarty->assign('creator', $user);
$smarty->assign('sortorder', 'name');
$smarty->assign('sortdirection', 'asc');
if ($galleryId === "1") {
    $traname = tra($gal_info['name']);
    $smarty->assign_by_ref('name', $traname); //get_strings tra('File Galleries')
} else {
    $smarty->assign_by_ref('name', $gal_info['name']);
}
$smarty->assign_by_ref('galleryId', $galleryId);
$smarty->assign('reindex_file_id', -1);
if (isset($_REQUEST['view'])) {
    $view = $_REQUEST['view'];
} else {
    if (isset($_REQUEST['fileId'])) {
        $view = 'page';
    } else {
        $view = $gal_info['default_view'];
    }
}

// Execute batch actions

if (isset($_REQUEST['movesel']) && $access->checkCsrf()) {
    $access->check_permission_either(['admin_file_galleries', 'remove_files']);
    $movegalInfo = $filegallib->get_file_gallery_info($_REQUEST['moveto']);
    $movegalPerms = $tikilib->get_perm_object($_REQUEST['moveto'], 'file gallery', $movegalInfo, false);

    if ($movegalPerms['tiki_p_upload_files'] === 'y') {
        if (isset($_REQUEST['file'])) {
            $failedFiles = $totalFiles = count($_REQUEST['file']);
            foreach (array_values($_REQUEST['file']) as $file) {
                $result = $filegallib->set_file_gallery($file, $_REQUEST['moveto']);
                if ($result && $result->numRows()) {
                    $failedFiles--;
                }
            }
        }
    }
    if ($tiki_p_admin_file_galleries == 'y' || $movegalPerms['tiki_p_admin_file_galleries'] === 'y') {
        if (isset($_REQUEST['subgal'])) {
            $failedGals = $totalGals = count($_REQUEST['subgal']);
            foreach (array_values($_REQUEST['subgal']) as $subgal) {
                $result2 = $filegallib->move_file_gallery($subgal, $_REQUEST['moveto']);
                if ($result2 && $result2->numRows()) {
                    $failedGals--;
                }
            }
        }
    }
    $totalRequested = $totalFiles + $totalGals;
    $totalFails = $failedFiles + $failedGals;
    if (! $totalFails) {
        Feedback::success($totalRequested === 1 ? tr('One item moved') : tr('%0 items moved', $totalRequested));
    } else {
        $totalMoved = $totalRequested - $totalFails;
        if (! $totalMoved) {
            Feedback::error(tr('No items moved'));
        } else {
            if ($totalMoved === 1) {
                $msg = $totalFails === 1 ? tr('One item moved and one item failed to move')
                    : tr('One item moved and %0 items failed to move', $totalFails);
            } else {
                $msg = $totalFails === 1 ? tr('%0 items moved and one item failed to move', $totalMoved)
                    : tr('%0 items moved and %0 items failed to move', $totalMoved, $totalFails);
            }
            Feedback::error($msg);
        }
    }
}

if (isset($_REQUEST['fgal_actions'])) {
    if ($_REQUEST['fgal_actions'] === 'delsel_x') {
        $access->check_permission_either(['admin_file_galleries', 'remove_files']);
        if (isset($_REQUEST['file']) && $access->checkCsrf(true)) {
            $failedFiles = $totalFiles = count($_REQUEST['file']);
            foreach (array_values($_REQUEST['file']) as $file) {
                if ($info = $filegallib->get_file_info($file)) {
                    $result = $filegallib->remove_file($info, $gal_info);
                    if ($result && $result->numRows()) {
                        $failedFiles--;
                    }
                }
            }
        }

        if (isset($_REQUEST['subgal']) && $tiki_p_admin_file_galleries == 'y'
            && $access->checkCsrf(true)) {
            $failedGals = $totalGals = count($_REQUEST['subgal']);
            foreach (array_values($_REQUEST['subgal']) as $subgal) {
                $subgalInfo = $filegallib->get_file_gallery_info($subgal);
                $subgalPerms = $tikilib->get_perm_object($subgal, 'file gallery', $subgalInfo, false);

                if ($subgalPerms['tiki_p_admin_file_galleries'] === 'y') {
                    $result = $filegallib->remove_file_gallery($subgal, $galleryId);
                    if ($result && $result->numRows()) {
                        $failedGals--;
                    }
                }
            }
        }
        $totalRequested = $totalFiles + $totalGals;
        $totalFails = $failedFiles + $failedGals;
        if (! $totalFails) {
            Feedback::success($totalRequested === 1 ? tr('One item deleted') : tr('%0 items deleted', $totalRequested));
        } else {
            $totalDeleted = $totalRequested - $totalFails;
            if (! $totalDeleted) {
                Feedback::error(tr('No items deleted'));
            } else {
                if ($totalDeleted === 1) {
                    $msg = $totalFails === 1 ? tr('One item deleted and one item failed to delete')
                        : tr('One item deleted and %0 items failed to delete', $totalFails);
                } else {
                    $msg = $totalFails === 1 ? tr('%0 items deleted and one item failed to delete', $totalDeleted)
                        : tr('%0 items moved and %0 items failed to delete', $totalDeleted, $totalFails);
                }
                Feedback::error($msg);
            }
        }
    }

    if ($_REQUEST['fgal_actions'] === 'defaultsel_x' && $access->checkCsrf(true)) {
        $access->check_permission('admin_file_galleries');
        $galCount = 0;
        if (! empty($_REQUEST['subgal'])) {
            $result = $filegallib->setDefault(array_values($_REQUEST['subgal']));
            $galCount = count($_REQUEST['subgal']);
        } else {
            if (! empty($galleryId)) {
                $result = $filegallib->setDefault([(int)$galleryId]);
                $galCount = 1;
            }
        }
        $totalChanged = $result && $result->numRows() ? $result->numRows() : 0;
        $failed = $galCount - $totalChanged;
        if (! $failed) {
            Feedback::success($totalChanged === 1 ? tr('List view settings reset for one gallery')
                : tr('List view settings reset for %0 galleries', $totalChanged));
        } else {
            if ($failed === $galCount) {
                $msg = $failed === 1 ? tr('List view settings not changed for the selected gallery')
                    : tr('List view settings not changed for the %0 selected galleries', $failed);
            } else {
                $msg = $failed === 1 ? tr(
                    'List view settings reset for %0 galleries and not changed for one gallery',
                    $totalChanged
                )
                    : tr(
                        'List view settings reset for %0 galleries and not changed for %0 galleries',
                        $totalChanged,
                        $failed
                    );
            }
            Feedback::error($msg);
        }
        $view = null;
    }

    if ($_REQUEST['fgal_actions'] === 'refresh_metadata_x' && $access->checkCsrf()) {
        $access->check_permission('admin_file_galleries');
        $failedFiles = $totalFiles = count($_REQUEST['file']);
        if (! $totalFiles) {
            Feedback::error(tr('No files selected'));
        } else {
            foreach (array_values($_REQUEST['file']) as $file) {
                $result = $filegallib->metadataAction($file, 'refresh');
                if ($result && $result->numRows()) {
                    $failedFiles--;
                }
            }
            if (! $failedFiles) {
                Feedback::success($totalFiles === 1 ? tr('Metadata refreshed for one file')
                    : tr('Metadata refreshed for %0 files', $totalFiles));
            } else {
                $totalRefreshed = $totalFiles - $failedFiles;
                if (! $totalRefreshed) {
                    Feedback::error(tr('Metadata not refreshed'));
                } else {
                    if ($totalRefreshed === 1) {
                        $msg = $failedFiles === 1 ? tr('Metadata refreshed for one file and not refreshed for one file')
                            : tr('Metadata refreshed for one file and not refreshed for %0 files', $failedFiles);
                    } else {
                        $msg = $failedFiles === 1 ? tr(
                            'Metadata refreshed for %0 files and not refreshed for one file',
                            $totalRefreshed
                        )
                            : tr(
                                'Metadata refreshed for %0 files and not refreshed for %0 files',
                                $totalRefreshed,
                                $failedFiles
                            );
                    }
                    Feedback::error($msg);
                }
            }
        }
    }
    if ($_REQUEST['fgal_actions'] === 'zipsel_x') {
        $access->check_permission('upload_files');
        $href = [];
        if (isset($_REQUEST['file'])) {
            foreach (array_values($_REQUEST['file']) as $file) {
                $href[] = "fileId[]=$file";
            }
        }
        if (isset($_REQUEST['subgal'])) {
            foreach (array_values($_REQUEST['subgal']) as $subgal) {
                $href[] = "galId[]=$subgal";
            }
        }
        header("Location: tiki-download_file.php?" . implode('&', $href));
        die;
    }
    if ($_REQUEST['fgal_actions'] === 'permsel_x') {
        $access->check_permission('assign_perm_file_gallery');
        $perms = $userlib->get_permissions(0, -1, 'permName_asc', '', 'file galleries');
        $smarty->assign_by_ref('perms', $perms['data']);
        $groups = $userlib->get_groups(0, -1, 'groupName_asc', '', '', 'n');
        $smarty->assign_by_ref('groups', $groups['data']);
    }
}


if (isset($_REQUEST['permsel']) && isset($_REQUEST['subgal']) && $access->checkCsrf()) {
    $access->check_permission('assign_perm_file_gallery');
    $fails = [];
    foreach ($_REQUEST['subgal'] as $id) {
        foreach ($_REQUEST['perms'] as $perm) {
            if (empty($_REQUEST['groups']) && empty($perm)) {
                $result = $userlib->assign_object_permission('', $id, 'file gallery', '');
                if ($result !== true) {
                    $fails[] = $id;
                }

                continue;
            }
            foreach ($_REQUEST['groups'] as $group) {
                $result = $userlib->assign_object_permission($group, $id, 'file gallery', $perm);
                if ($result !== true) {
                    $fails[] = $id;
                }
            }
        }
    }
    if (count($fails)) {
        Feedback::error('There was an error in assigning permissions');
    } else {
        Feedback::success('Permissions assigned');
    }
}

// Lock a file
if (isset($_REQUEST['lock']) && isset($_REQUEST['fileId']) && $_REQUEST['fileId'] > 0) {
    if (! $fileInfo = $filegallib->get_file_info($_REQUEST['fileId'])) {
        $error_msg = tr('Incorrect file ID');
    } else {
        $error_msg = '';
        if ($_REQUEST['lock'] == 'n' && ! empty($fileInfo['lockedby'])) {
            if ($fileInfo['lockedby'] != $user && $tiki_p_admin_file_galleries != 'y') {
                $error_msg = sprintf(tr('The file is already locked by %s'), $fileInfo['lockedby']);
            } else {
                if ($fileInfo['lockedby'] != $user
                    && $access->checkCsrf(true)) {
                    $filegallib->unlock_file($_REQUEST['fileId']);
                } elseif ($access->checkCsrf()) {
                    $result = $filegallib->unlock_file($_REQUEST['fileId']);
                    if ($result && $result->numRows()) {
                        Feedback::success(tr('File unlocked'));
                    } else {
                        Feedback::error(tr('File not unlocked'));
                    }
                }
            }
        } elseif ($_REQUEST['lock'] == 'y') {
            if (! empty($fileInfo['lockedby']) && $fileInfo['lockedby'] != $user) {
                $error_msg = sprintf(tr('The file is already locked by %s'), $fileInfo['lockedby']);
            } elseif ($gal_info['lockable'] != 'y') {
                $smarty->assign('errortype', 401);
                $error_msg = tr('Files in this gallery are not lockable');
            } elseif ($access->checkCsrf()) {
                $result = $filegallib->lock_file($_REQUEST['fileId'], $user);
                if ($result && $result->numRows()) {
                    Feedback::success(tr('File locked'));
                } else {
                    Feedback::error(tr('File not locked'));
                }
            }
        }
        if ($error_msg != '') {
            Feedback::error($error_msg);
        }
    }
}

// Validate a draft
if (! empty($_REQUEST['validate']) && $prefs['feature_file_galleries_save_draft'] == 'y') {
    // To validate a draft the user must be the owner or the file or the gallery or admin
    if ($tiki_p_admin_file_galleries != 'y' && (! $user || $user != $gal_info['user'])) {
        if ($user != $info['user']) {
            Feedback::error(tr('You don\'t have permission to validate files from this gallery'));
        }
    } elseif (! $info = $filegallib->get_file_info($_REQUEST['validate'])) {
        Feedback::error(tr('Error retrieving file'));
    } elseif ($access->checkCsrf(true)) {
        $result = $filegallib->validate_draft($info['fileId']);
        if ($result && $result->numRows()) {
            Feedback::success(tr('Draft validated'));
        } else {
            Feedback::error(tr('Validation failed'));
        }
    }
}

if (! empty($_REQUEST['remove']) && $access->checkCsrf(true)) {
    $result = $filegallib->actionHandler(
        'removeFile',
        [
            'fileId' => $_REQUEST['remove'],
            'draft' => (! empty($_REQUEST['draft'])),
        ]
    );
    if ($result && $result->numRows()) {
        Feedback::success(tr('File deleted'));
    } else {
        Feedback::error(tr('File not deleted'));
    }
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'refresh_metadata'
    //popup confirm used here because request may be GET
    && $access->checkCsrf(true)) {
    $result = $filegallib->metadataAction($_REQUEST['fileId'], 'refresh');
    if ($result && $result->numRows()) {
        Feedback::success(tr('Metadata refreshed'));
    } else {
        Feedback::error(tr('Metadata not refreshed'));
    }
}

$smarty->assign('url', $tikilib->httpPrefix() . parse_url($_SERVER['REQUEST_URI'])['path']);
// Edit mode
if (isset($_REQUEST['edit_mode']) and $_REQUEST['edit_mode']) {
    $smarty->assign('edit_mode', 'y');
    $smarty->assign('edited', 'y');
    if ($prefs['feature_categories'] == 'y') {
        $cat_type = 'file gallery';
        $cat_objid = $galleryId;
        include_once('categorize_list.php');
    }

    if ($prefs['feature_groupalert'] == 'y') {
        $smarty->assign('groupforAlert', isset($_REQUEST['groupforAlert']) ? $_REQUEST['groupforAlert'] : '');
        $all_groups = $userlib->list_all_groups();
        $groupselected = $groupalertlib->GetGroup('file gallery', $galleryId);
        if (is_array($all_groups)) {
            foreach ($all_groups as $g) {
                $groupforAlertList[$g] = ($g == $groupselected) ? 'selected' : '';
            }
        }
        $smarty->assign_by_ref('groupforAlert', $groupselected);
        $showeachuser = $groupalertlib->GetShowEachUser('file gallery', $galleryId, $groupselected);
        $smarty->assign_by_ref('showeachuser', $showeachuser);
        $smarty->assign_by_ref('groupforAlertList', $groupforAlertList);
    }

    // ocr controls
    if (! empty($prefs['ocr_enable']) && $prefs['ocr_enable'] === 'y') {
        if (empty($prefs['ocr_limit_languages'])) {
            $ocr = TikiLib::lib('ocr');
            $langs = $ocr->getTesseractLangs();
        } else {
            $langs = $prefs['ocr_limit_languages'];
        }
        $selectedLangs = json_decode($gal_info['ocr_lang']);
        // lets remove the language codes from the unselected list if they are already selected
        foreach ($selectedLangs as $lang) {
            unset($langs[array_search($lang, $langs)]);
        }
        $langLib = TikiLib::lib('language');

        if (! empty($selectedLangs)) {
            $smarty->assign('selectedLanguages', $langLib->findLanguageNames($selectedLangs));
        }
        $smarty->assign('languages', $langLib->findLanguageNames($langs));
    }
    // Edit a file
    if (isset($_REQUEST['fileId']) && $_REQUEST['fileId'] > 0) {
        if ($tiki_p_edit_gallery_file != 'y') {
            Feedback::errorPage(['mes' => tr('Permission denied'), 'errortype' => 401]);
        }
        $info = $filegallib->get_file_info($_REQUEST['fileId']);
        $smarty->assign('fileId', $_REQUEST['fileId']);
        $smarty->assign_by_ref('filename', $info['filename']);
        $smarty->assign_by_ref('fname', $info['name']);
        $smarty->assign_by_ref('fdescription', $info['description']);
    } elseif ($galleryId > 0) {
        // Edit a gallery
        $smarty->assign_by_ref('maxRows', $gal_info['maxRows']);
        $smarty->assign_by_ref('parentId', $gal_info['parentId']);
        $smarty->assign_by_ref('creator', $gal_info['user']);
        $smarty->assign('max_desc', $gal_info['max_desc']);


        if (isset($gal_info['sort_mode']) && preg_match('/(.*)_(asc|desc)/', $gal_info['sort_mode'], $matches)) {
            $smarty->assign('sortorder', $matches[1]);
            $smarty->assign('sortdirection', $matches[2]);
        } else {
            $smarty->assign('sortorder', 'created');
            $smarty->assign('sortdirection', 'desc');
        }
    } elseif ($tiki_p_create_file_galleries != 'y') {
        Feedback::errorPage(['mes' => tr('Permission denied'), 'errortype' => 401]);
    }
    // Duplicate mode
} elseif (! empty($_REQUEST['dup_mode'])) {
    $smarty->assign('dup_mode', 'y');
}

// Process the insertion or modification request
if (isset($_REQUEST['edit']) && $access->checkCsrf()) {
    // Saving information
    // Handle files
    if (isset($_REQUEST['fileId'])) {
        if ($tiki_p_admin_file_galleries != 'y') {
            // Check file upload rights
            if ($tiki_p_upload_files != 'y') {
                Feedback::errorPage(['mes' => tr('You need upload permission to edit files'), 'errortype' => 401]);
            }
            // Check THIS file edit rights
            if ($_REQUEST['fileId'] > 0) {
                $info = $filegallib->get_file_info($_REQUEST["fileId"]);
                if (! $user || $info['user'] != $user) {
                    Feedback::errorPage(['mes' => tr('You do not have permission to edit this file'),
                                         'errortype' => 401]);
                }
            }
        }
    } else {
        // Handle galleries
        if ($tiki_p_admin_file_galleries != 'y') {
            // Check gallery creation rights
            if ($tiki_p_create_file_galleries != 'y') {
                Feedback::errorPage(['mes' => tr('You need permission to create galleries to edit them'),
                                     'errortype' => 401]);
            }
            // Check THIS gallery modification rights
            if ($galleryId > 0) {
                if (! $user || $gal_info['user'] != $user) {
                    Feedback::errorPage(['mes' => tr('You do not have permission to edit this gallery'),
                                         'errortype' => 401]);
                }
            }
        }
    }
    // Everything is ok so we proceed to edit the file or gallery
    $request_vars = [
        'name',
        'fname',
        'description',
        'fdescription',
        'max_desc',
        'fgal_type',
        'maxRows',
        'rowImages',
        'thumbSizeX',
        'thumbSizeY',
        'parentId',
        'creator',
        'quota',
        'image_max_size_x',
        'image_max_size_y',
        'wiki_syntax',
        'icon_fileId',
        'ocr_langs',
    ];
    foreach ($request_vars as $v) {
        if (isset($_REQUEST[$v])) {
            $smarty->assign_by_ref($v, $_REQUEST[$v]);
        }
    }
    $request_toggles = ['visible', 'public', 'lockable'];
    foreach ($request_toggles as $t) {
        $$t = (isset($_REQUEST[$t]) && $_REQUEST[$t] == 'on') ? 'y' : 'n';
        $smarty->assign($t, $$t);
    }
    $_REQUEST['archives'] = isset($_REQUEST['archives']) ? $_REQUEST['archives'] : 0;
    $_REQUEST['user'] = isset($_REQUEST['user']) ? $_REQUEST['user'] : (isset($gal_info['user']) ? $gal_info['user'] : $user);
    $_REQUEST['sortorder'] = isset($_REQUEST['sortorder']) ? $_REQUEST['sortorder'] : 'created';
    $_REQUEST['sortdirection'] = isset($_REQUEST['sortdirection']) && $_REQUEST['sortdirection'] == 'asc' ? 'asc' : 'desc';
    if (isset($_REQUEST['fileId'])) {
        $infoOverride = $filegallib->get_file_info($_REQUEST['fileId']);

        $_REQUEST['fname'] = (isset($_REQUEST['fname']) ? $_REQUEST['fname'] : $infoOverride['name']);
        $_REQUEST['fdescription'] = (isset($_REQUEST['fdescription']) ? $_REQUEST['fdescription'] : $infoOverride['description']);
        $info['data'] = (isset($_REQUEST['data']) ? $_REQUEST['data'] : $info['data']);

        $file = Tiki\FileGallery\File::id($_REQUEST['fileId']);
        $file->setParam('description', $_REQUEST['fdescription']);
        $fid = $file->replace($info['data'], $info['filetype'], $_REQUEST['fname'], $info['filename']);
        if ($fid) {
            Feedback::success(tr('File properties for %0 edited' . htmlspecialchars($_REQUEST['fname'])));
        } else {
            Feedback::error(tr('File properties for %0 not changed' . htmlspecialchars($_REQUEST['fname'])));
        }
        $smarty->assign('edit_mode', 'n');
    } else {
        if ($prefs['fgal_quota_per_fgal'] != 'y') {
            $_REQUEST['quota'] = 0;
        }

        if ($test = $filegallib->checkQuotaSetting($_REQUEST['quota'], $galleryId, $_REQUEST['parentId'])) {
            Feedback::errorPage(($test > 0) ? tr('Quota too big') : tr('Quota too small'));
        }
        $old_gal_info = $filegallib->get_file_gallery_info($galleryId);

        // first validate length of OCR languages, then save if they conform.
        $ocrLangs = json_encode($_POST['ocr_lang']);
        if (strlen($ocrLangs) > 255) {
            Feedback::error(tr('You may not use that many OCR languages. Use fewer languages.'));
            $ocrLangs = $old_gal_info['ocr_lang'];
        }

        $gal_info = [
            'galleryId' => $galleryId,
            'name' => $_REQUEST['name'],
            'description' => $_REQUEST['description'],
            'user' => $_REQUEST['user'],
            'maxRows' => $_REQUEST['maxRows'],
            'public' => $public,
            'visible' => $visible,
            'show_id' => $_REQUEST['fgal_list_id'],
            'show_icon' => $_REQUEST['fgal_list_type'],
            'show_name' => $_REQUEST['fgal_list_name'],
            'show_size' => $_REQUEST['fgal_list_size'],
            'show_description' => $_REQUEST['fgal_list_description'],
            'show_created' => $_REQUEST['fgal_list_created'],
            'show_hits' => $_REQUEST['fgal_list_hits'],
            'show_lastDownload' => $_REQUEST['fgal_list_lastDownload'],
            'max_desc' => $_REQUEST['max_desc'],
            'type' => $_REQUEST['fgal_type'],
            'parentId' => empty($_REQUEST['parentId']) ? $old_gal_info['parentId'] : $_REQUEST['parentId'],
            'lockable' => $lockable,
            'show_lockedby' => $_REQUEST['fgal_list_lockedby'],
            'archives' => $_REQUEST['archives'],
            'sort_mode' => $_REQUEST['sortorder'] . '_' . $_REQUEST['sortdirection'],
            'show_modified' => $_REQUEST['fgal_list_lastModif'],
            'show_creator' => $_REQUEST['fgal_list_creator'],
            'show_deleteAfter' => $_REQUEST['fgal_list_deleteAfter'],
            'show_checked' => $_REQUEST['fgal_show_checked'],
            'show_share' => $_REQUEST['fgal_list_share'],
            'show_author' => $_REQUEST['fgal_list_author'],
            'subgal_conf' => $_REQUEST['subgal_conf'],
            'show_last_user' => $_REQUEST['fgal_list_last_user'],
            'show_comment' => $_REQUEST['fgal_list_comment'],
            'show_files' => $_REQUEST['fgal_list_files'],
            'show_explorer' => (isset($_REQUEST['fgal_show_explorer']) ? 'y' : 'n'),
            'show_path' => (isset($_REQUEST['fgal_show_path']) ? 'y' : 'n'),
            'show_slideshow' => (isset($_REQUEST['fgal_show_slideshow']) ? 'y' : 'n'),
            'show_ocr_state' => $_POST['fgal_list_ocr_state'],
            'default_view' => $_REQUEST['fgal_default_view'],
            'quota' => $_REQUEST['quota'],
            'image_max_size_x' => $_REQUEST['image_max_size_x'],
            'image_max_size_y' => $_REQUEST['image_max_size_y'],
            'backlinkPerms' => isset($_REQUEST['backlinkPerms']) ? 'y' : 'n',
            'show_backlinks' => $_REQUEST['fgal_list_backlinks'],
            'wiki_syntax' => $_REQUEST['wiki_syntax'],
            'show_source' => $_REQUEST['fgal_list_source'],
            'icon_fileId' => ! empty($_REQUEST['fgal_icon_fileId']) ? $_REQUEST['fgal_icon_fileId'] : null,
            'ocr_lang' => $ocrLangs,
        ];

        if ($prefs['feature_file_galleries_templates'] == 'y' && isset($_REQUEST['fgal_template']) && ! empty($_REQUEST['fgal_template'])) {
            // Override with template parameters
            $template = $templateslib->get_parsed_template($_REQUEST['fgal_template']);

            if ($template) {
                $gal_info = array_merge($gal_info, $template['content']);
                $gal_info['template'] = $_REQUEST['fgal_template'];
            }
        }

        if ($prefs['fgal_show_slideshow'] != 'y') {
            $gal_info['show_slideshow'] = $old_gal_info['show_slideshow'];
        }

        if ($prefs['fgal_show_explorer'] != 'y') {
            $gal_info['show_explorer'] = $old_gal_info['show_explorer'];
        }

        if ($prefs['fgal_show_path'] != 'y') {
            $gal_info['show_path'] = $old_gal_info['show_path'];
        }

        if ($prefs['fgal_checked'] != 'y') {
            $gal_info['show_checked'] = $old_gal_info['show_checked'];
        }

        $fgal_diff = array_diff_assoc($gal_info, $old_gal_info);
        unset($fgal_diff['created']);
        unset($fgal_diff['lastModif']);
        unset($fgal_diff['votes']);
        unset($fgal_diff['points']);
        unset($fgal_diff['hits']);
        $smarty->assign('fgal_diff', $fgal_diff);

        $fgid = $filegallib->replace_file_gallery($gal_info);
        if ($fgid) {
            Feedback::success(tr('Gallery %0 created or modified', htmlspecialchars($_REQUEST['name'])));
        } else {
            Feedback::error(tr('Gallery %0 not created or modified', htmlspecialchars($_REQUEST['name'])));
        }
        if ($prefs['feature_groupalert'] == 'y') {
            $groupalertlib->AddGroup('file gallery', $galleryId, $_REQUEST['groupforAlert'], $_REQUEST['showeachuser']);
        }

        if ($prefs['feature_categories'] == 'y') {
            $cat_type = 'file gallery';
            $cat_objid = $fgid;
            $cat_desc = substr($_REQUEST['description'], 0, $_REQUEST['max_desc']);
            $cat_name = $_REQUEST['name'];
            $cat_href = 'tiki-list_file_gallery.php?galleryId=' . $cat_objid;
            include_once('categorize.php');
        }

        if (isset($_REQUEST['viewitem'])) {
            header(
                'Location: tiki-list_file_gallery.php?galleryId=' . $fgid
                . (! empty($_REQUEST['filegals_manager']) ? '&filegals_manager=' . $_REQUEST['filegals_manager'] : '')
                . (! empty($_REQUEST['insertion_syntax']) ? '&insertion_syntax=' . $_REQUEST['insertion_syntax'] : '')
            );
            die;
        }
        $smarty->assign('edit_mode', 'y');
    }
}

// Process duplication of a gallery
if (! empty($_REQUEST['duplicate']) && ! empty($_REQUEST['name']) && ! empty($galleryId) && $access->checkCsrf()) {
    if ($tiki_p_create_file_galleries != 'y' || $gal_info['type'] == 'user') {
        Feedback::errorPage(tr('You do not have permission to duplicate this gallery'));
    }
    $newGalleryId = $filegallib->duplicate_file_gallery(
        $galleryId,
        $_REQUEST['name'],
        isset($_REQUEST['description']) ? $_REQUEST['description'] : ''
    );

    if (isset($_REQUEST['dupCateg']) && $_REQUEST['dupCateg'] == 'on' && $prefs['feature_categories'] == 'y') {
        $categlib = TikiLib::lib('categ');
        $cats = $categlib->get_object_categories('file gallery', $galleryId);
        $catObjectId = $categlib->add_categorized_object(
            'file gallery',
            $newGalleryId,
            (isset($_REQUEST['description']) ? $_REQUEST['description'] : ''),
            $_REQUEST['name'],
            'tiki-list_file_gallery.php?galleryId=' . $newGalleryId
        );
        foreach ($cats as $cat) {
            $categlib->categorize($catObjectId, $cat);
        }
    }
    if (isset($_REQUEST['dupPerms']) && $_REQUEST['dupPerms'] == 'on') {
        $userlib->copy_object_permissions($galleryId, $newGalleryId, 'file gallery');
    }
    if ($newGalleryId) {
        Feedback::success(tr('Gallery duplicated'));
        header('Location: tiki-list_file_gallery.php?galleryId=' . $newGalleryId);
        die;
    }
    Feedback::error(tr('Gallery not duplicated'));
}

// Process removal of a gallery
if (! empty($_REQUEST['removegal']) && $access->checkCsrf(true)) {
    if (! ($gal_info = $filegallib->get_file_gallery_info($_REQUEST['removegal']))) {
        Feedback::errorPage(tr('Incorrect gallery ID'));
    }

    $mygal_to_delete = ! empty($user) && $gal_info['type'] === 'user' && $gal_info['user'] !== $user && $tiki_p_userfiles === 'y' && $gal_info['parentId'] !== $prefs['fgal_root_user_id'];

    if ($tiki_p_admin_file_galleries != 'y' && ! $mygal_to_delete) {
        Feedback::errorPage(['mes' => tr('You do not have permission to remove this gallery'),
                             'errortype' => 401]);
    }
    $result = $filegallib->remove_file_gallery($_REQUEST['removegal'], $_REQUEST['removegal']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Gallery %0 deleted', $gal_info['name']));
    } else {
        Feedback::error(tr('Gallery %0 not deleted', $gal_info['name']));
    }
}

// Update a file comment
if (isset($_REQUEST['comment']) && $_REQUEST['comment'] != '' && isset($_REQUEST['fileId']) && $_REQUEST['fileId'] > 0
    && $access->checkCsrf()) {
    $msg = '';
    if (! $fileInfo = $filegallib->get_file_info($_REQUEST['fileId'])) {
        $msg = tra('Incorrect param');
    } elseif ($galleryId != $fileInfo['galleryId']) {
        $msg = tra('Could not find the file requested');
    } elseif ((! empty($fileInfo['lockedby']) && $fileInfo['lockedby'] != $user && $tiki_p_admin_file_galleries != 'y') || $tiki_p_edit_gallery_file != 'y') {
        $msg = tra('You do not have permission to do that');
    } else {
        $result = $filegallib->update_file($fileInfo['fileId'], [
            'name' => $fileInfo['name'],
            'description' => $fileInfo['description'],
            'lastModifUser' => $user,
            'comment' => $_REQUEST['comment']
        ]);
        if ($result && $result->numRows()) {
            Feedback::success(tr('File %0 updated', $fileInfo['name']));
        } else {
            Feedback::error(tr('File %0 not updated', $fileInfo['name']));
        }
    }
    if ($msg != '') {
        Feedback::error($msg);
    }
}

// Set display config
if (! isset($_REQUEST['maxRecords']) || $_REQUEST['maxRecords'] <= 0) {
    if ($view == 'page' && empty($_REQUEST['fileId'])) {
        $_REQUEST['maxRecords'] = 1;
    } elseif (isset($gal_info['maxRows']) && $gal_info['maxRows'] > 0) {
        $_REQUEST['maxRecords'] = $gal_info['maxRows'];
    } else {
        $_REQUEST['maxRecords'] = $prefs['maxRecords'];
    }
}

$smarty->assign_by_ref('maxRecords', $_REQUEST['maxRecords']);
if (! isset($_REQUEST['offset'])) {
    $_REQUEST['offset'] = 0;
}
$smarty->assign_by_ref('offset', $_REQUEST['offset']);

if (empty($_REQUEST['sort_mode'])) {
    if ($gal_info['sort_mode'] == 'name_asc' && $gal_info['show_name'] == 'f') {
        $_REQUEST['sort_mode'] = 'filename_asc';
    } else {
        $_REQUEST['sort_mode'] = $gal_info['sort_mode'];
    }
}

$smarty->assign_by_ref('sort_mode', $_REQUEST['sort_mode']);

$find = [];
if (! isset($_REQUEST['find_creator'])) {
    $smarty->assign('find_creator', '');
} else {
    $find['creator'] = $_REQUEST['find_creator'];
    $smarty->assign('find_creator', $_REQUEST['find_creator']);
}
if (! empty($_REQUEST['find_lastModif']) && ! empty($_REQUEST['find_lastModif_unit'])) {
    $find['lastModif'] = $tikilib->now - ($_REQUEST['find_lastModif'] * $_REQUEST['find_lastModif_unit']);
}
if (! empty($_REQUEST['find_lastDownload']) && ! empty($_REQUEST['find_lastDownload_unit'])) {
    $find['lastDownload'] = $tikilib->now - ($_REQUEST['find_lastDownload'] * $_REQUEST['find_lastDownload_unit']);
}
if (! empty($_REQUEST['find_fileType']) && ! empty($_REQUEST['find_fileType'])) {
    include_once('lib/mime/mimetypes.php');
    global $mimetypes;
    $find['fileType'] = $mimetypes[$_REQUEST['find_fileType']];
}

if (! isset($_REQUEST['find'])) {
    $_REQUEST['find'] = '';
}
$smarty->assign_by_ref('find', $_REQUEST['find']);

if (isset($_REQUEST['fileId'])) {
    if (! is_numeric($_REQUEST['fileId'])) {
        Feedback::error(tr('Invalid %0 parameter', 'fileId'));
    } else {
        $fileId = (int)$_REQUEST['fileId'];
        $smarty->assign('fileId', $fileId);
    }
}
if ($prefs['feature_categories'] == 'y') {
    if (! empty($_REQUEST['cat_categories'])) {
        if (count($_REQUEST['cat_categories']) > 1) {
            unset($_REQUEST['categId']);
        } else {
            $_REQUEST['categId'] = $_REQUEST['cat_categories'][0];
        }
    } else {
        $_REQUEST['cat_categories'] = [];
    }
    $selectedCategories = $_REQUEST['cat_categories'];
    $find['categId'] = $_REQUEST['cat_categories'];
    $smarty->assign('findSelectedCategoriesNumber', count($_REQUEST['cat_categories']));
    if (! empty($_REQUEST['categId'])) {
        $find['categId'] = $_REQUEST['categId'];
        $selectedCategories = [(int)$find['categId']];
        $smarty->assign('find_categId', $find['categId']);
    } else {
        $smarty->assign('find_categId', '');
    }

    // load categories for find
    if ($prefs['feature_categories'] == 'y' && ! isset($_REQUEST['edit_mode'])) {
        $categlib = TikiLib::lib('categ');
        $categories = $categlib->getCategories();
        $smarty->assign_by_ref('categories', $categories);
        $smarty->assign('cat_tree', $categlib->generate_cat_tree($categories, true, $selectedCategories));
    }
}

if (! empty($_REQUEST['find_orphans']) && ($_REQUEST['find_orphans'] == 'on' || $_REQUEST['find_orphans'] == 'y')) {
    $find['orphan'] = 'y';
    $smarty->assign('find_orphans', 'y');
}
if (! empty($_REQUEST['find_sub']) && ($_REQUEST['find_sub'] == 'on' || $_REQUEST['find_sub'] == 'y')) {
    $find_sub = true;
    $smarty->assign('find_sub', 'y');
} else {
    $find_sub = false;
}

if (isset($_GET['slideshow'])) {
    $_REQUEST['maxRecords'] = $maxRecords = -1;
    $offset = 0;
    $files = $filegallib->get_files(
        0,
        -1,
        $_REQUEST['sort_mode'],
        $_REQUEST['find'],
        $galleryId,
        false,
        false,
        false,
        true,
        false,
        false,
        false,
        true,
        '',
        false,
        false,
        false,
        $find
    );
    $smarty->assign('cant', $files['cant']);
    $smarty->assign_by_ref('files', $files['data']);

    $smarty->assign('show_find', 'n');
    $smarty->assign('direct_pagination', 'y');
    if (isset($_REQUEST['slideshow_noclose'])) {
        $smarty->assign('slideshow_noclose', 'y');
    }
    if (isset($_REQUEST['caption'])) {
        $smarty->assign('caption', $_REQUEST['caption']);
    }
    if (isset($_REQUEST['windowtitle'])) {
        $smarty->assign('sswindowtitle', $_REQUEST['windowtitle']);
    }
    $smarty->display('file_gallery_slideshow.tpl');
    die();
}
    if (! isset($_REQUEST["edit_mode"]) && ! isset($_REQUEST["edit"])) {
        $recursive = $view == 'admin' || $find_sub;
        $with_subgals = ! (in_array($view, ['admin', 'page']) || $find_sub);
        if (! empty($_REQUEST['filegals_manager'])) {    // get wiki syntax if needed
            $syntax = $filegallib->getWikiSyntax($galleryId);
        } else {
            $syntax = '';
        }
        $with_archive = (isset($gal_info['archives']) && $gal_info['archives'] == '-1') ? false : true;

        if ($view == 'page' && isset($_REQUEST['fileId'])) {
            try {
                $file = $filegallib->get_file_additional($fileId);
                $gal_info = $filegallib->get_file_gallery($file['parentId']);
                $gal_info['show_parentId'] = 'y';
            } catch (Exception $e) {
                Feedback::errorPage(['mes' => tr('File %0 not found', $fileId), 'errortype' => 404]);
            }
            $smarty->assign('cant', 1);
            if ($prefs['ocr_enable'] === 'y') {
                $info = $filegallib->get_file_info($fileId);
                if ($info['ocr_state'] === '1') {
                    $smarty->assign('ocrdata', $info['ocr_data'] ? $info['ocr_data'] : tr('OCR produced no results.'));
                }
                $ocrLangs = TikiLib::lib('ocr')->listFileLanguages($fileId);
                $ocrLangs = Tikilib::lib('language')->findLanguageNames($ocrLangs, 'translated');
                $ocrLangs = implode(', ', $ocrLangs);

                $smarty->assign('ocrlangs', $ocrLangs);
            }
        } else {
            // Get list of files in the gallery
            $files = $filegallib->get_files(
                $_REQUEST['offset'],
                $_REQUEST['maxRecords'],
                $_REQUEST['sort_mode'],
                $_REQUEST['find'],
                $galleryId,
                $with_archive,
                $with_subgals,
                ($view === 'list' && $gal_info['show_size'] !== 'n'),
                true,
                false,
                false,
                ($view === 'list' && $gal_info['show_files'] !== 'n'),
                $recursive,
                '',
                true,
                false,
                ($gal_info['show_backlinks'] != 'n'),
                $find,
                $syntax
            );
            $smarty->assign('cant', $files['cant']);
            if ($view == 'page') {
                $file = $files['data'][0];
            }
        }
        if ($view == 'page') {
            $smarty->assign('maxWidth', isset($_REQUEST['maxWidth']) ? $_REQUEST['maxWidth'] : '300px');
            //need to convert fileId to an offset to bring up a specific file for page view
            $smarty->assign('maxRecords', 1);
            $smarty->assign(
                'metarray',
                isset($file['metadata']) ?
                    json_decode($file['metadata'], true) : null
            );
            $smarty->assign_by_ref('file', $file);
        } else {
            $smarty->assign_by_ref('files', $files['data']);

            $subs = 0;
            if ($with_subgals) {
                foreach ($files['data'] as $f) {
                    $subs = $subs + $f['isgal'];
                }
            }
            $smarty->assign('filescount', $files['cant'] - $subs);
        }
    }
    //for page view to get offset in pagination right since subgalleries are not included
    $subgals = $filegallib->getSubGalleries($galleryId);
    $smarty->assign('subcount', count($subgals) - 1);

    $smarty->assign('mid', 'tiki-list_file_gallery.tpl');


// Browse view
$smarty->assign('thumbnail_size', $prefs['fgal_thumb_max_size']);

if (isset($_REQUEST['show_details'])) {
    $show_details = $_REQUEST['show_details'] === 'y' ? 'y' : 'n';
    setCookieSection('show_details', $show_details);
} else {
    $show_details = getCookie('show_details', null, 'n');
}
$smarty->assign('show_details', $show_details);

$options_sortorder = [
    tra('Creation Date') => 'created'
,
    tra('Name') => 'name'
,
    tra('Last modification date') => 'lastModif'
,
    tra('Hits') => 'hits'
,
    tra('Owner') => 'user'
,
    tra('Description') => 'description'
,
    tra('ID') => 'id',
];
$smarty->assign_by_ref('options_sortorder', $options_sortorder);
// Set section config
include_once('tiki-section_options.php');

// Theme control
if ($prefs['feature_theme_control'] == 'y') {
    $cat_type = 'file gallery';
    $cat_objid = $galleryId;
    include('tiki-tc.php');
}

// Watches
if ($prefs['feature_user_watches'] == 'y') {
    if (! isset($_REQUEST['fileId'])) {
        if ($user && isset($_REQUEST['watch_event'])) {
            if ($_REQUEST['watch_action'] == 'add' && $access->checkCsrf()) {
                $result = $tikilib->add_user_watch(
                    $user,
                    $_REQUEST['watch_event'],
                    $_REQUEST['watch_object'],
                    'File Gallery',
                    (isset($_REQUEST['galleryName']) ? $_REQUEST['galleryName'] : ''),
                    "tiki-list_file_gallery.php?galleryId=$galleryId"
                );
                if ($result) {
                    Feedback::success(tr('User watch added'));
                } else {
                    Feedback::error(tr('User watch not added'));
                }
            } elseif ($_REQUEST['watch_action'] == 'remove' && $access->checkCsrf()) {
                $result = $tikilib->remove_user_watch($user, $_REQUEST['watch_event'], $_REQUEST['watch_object'], 'File Gallery');
                if ($result && $result->numRows()) {
                    Feedback::success(tr('User watch removed'));
                } else {
                    Feedback::error(tr('User watch not removed'));
                }
            }
        }
        $smarty->assign('user_watching_file_gallery', 'n');
        if ($user && $tikilib->user_watches($user, 'file_gallery_changed', $galleryId, 'File Gallery')) {
            $smarty->assign('user_watching_file_gallery', 'y');
        }

        // Check, if the user is watching this file gallery by a category.
        if ($prefs['feature_categories'] == 'y') {
            $watching_categories_temp = $categlib->get_watching_categories($galleryId, 'file gallery', $user);
            $smarty->assign('category_watched', 'n');
            if (count($watching_categories_temp) > 0) {
                $smarty->assign('category_watched', 'y');
                $watching_categories = [];
                foreach ($watching_categories_temp as $wct) {
                    $watching_categories[] = ['categId' => $wct, 'name' => $categlib->get_category_name($wct)];
                }
                $smarty->assign('watching_categories', $watching_categories);
            }
        }
    }
}

if ($prefs['feature_file_galleries_templates'] == 'y') {
    $all_templates = $templateslib->list_templates('file_galleries', 0, -1, 'name_asc', '');
    $templates = [];
    foreach ($all_templates['data'] as $template) {
        $templates[] = ['label' => $template['name'], 'id' => $template['templateId']];
    }
    sort($templates);
    $smarty->assign_by_ref('all_templates', $templates);
}

$subGalleries = $filegallib->getSubGalleries(
    (isset($_REQUEST['parentId']) && $galleryId == 0) ? $_REQUEST['parentId'] : $galleryId
);
$smarty->assign('treeRootId', $subGalleries['parentId']);

if ($prefs['fgal_show_explorer'] == 'y' || $prefs['fgal_show_path'] == 'y'
    || $_REQUEST['fgal_actions'] === 'movesel_x' || isset($_REQUEST["edit_mode"]) || isset($_REQUEST['dup_mode'])) {
    $gals = [];
    foreach ($subGalleries['data'] as $gal) {
        $gals[] = [
            'label' => $gal['parentName'] . ' > ' . $gal['name'],
            'id' => $gal['id'],
            'perms' => $gal['perms'],
            'public' => $gal['public'],
            'user' => $gal['user'],
        ];
    }
    sort($gals);
    $smarty->assign_by_ref('all_galleries', $gals);

    if ($prefs['fgal_show_path'] == 'y') {
        $path = $filegallib->getPath($galleryId);
        $smarty->assign('gallery_path', $path['HTML']);
    }
    $smarty->assign('tree', $filegallib->getTreeHTML($galleryId));
    if (! empty($_REQUEST['fgal_actions']) && $_REQUEST['fgal_actions'] === 'movesel_x') {
        $smarty->assign('movesel_x', 'y');
    }
}

if (isset($files['data']) and in_array($view, ['browse', 'page'])) {
    foreach ($files['data'] as $file) {
        $_SESSION['allowed'][$file['fileId']] = true;
    }
}

if ($galleryId == 0) {
    $smarty->assign(
        'download_path',
        ((isset($podCastGallery) && $podCastGallery) ? $prefs['fgal_podcast_dir'] : $prefs['fgal_use_dir'])
    );
    // Add a file hit
    $statslib->stats_hit($gal_info['name'], 'file gallery', $galleryId);
    if ($prefs['feature_actionlog'] == 'y') {
        $logslib->add_action('Viewed', $galleryId, 'file gallery');
    }
} else {
    if (! isset($_REQUEST['fileId'])) {
        // Add a gallery hit
        $filegallib->add_file_gallery_hit($galleryId);
    }
}

// Get listing display config
include_once('fgal_listing_conf.php');
if (! isset($view)) {
    $view = $fgal_options['default_view']['value'];
}

$find_durations = [];
if ($view == 'admin') {
    $find_durations[] = [
        'label' => tra('Not modified for')
    ,
        'prefix' => 'find_lastModif'
    ,
        'default' => empty($_REQUEST['find_lastModif']) ? '' : $_REQUEST['find_lastModif']
    ,
        'default_unit' => empty($_REQUEST['find_lastModif_unit']) ? 'week' : $_REQUEST['find_lastModif_unit'],
    ];
    $find_durations[] = [
        'label' => tra('Not downloaded for')
    ,
        'prefix' => 'find_lastDownload'
    ,
        'default' => empty($_REQUEST['find_lastDownload']) ? '' : $_REQUEST['find_lastDownload']
    ,
        'default_unit' => empty($_REQUEST['find_lastDownload_unit']) ? 'week' : $_REQUEST['find_lastDownload_unit'],
    ];
    foreach ($fgal_listing_conf as $k => $v) {
        if ($k == 'type') {
            $show_k = 'icon';
        } elseif ($k == 'lastModif') {
            $show_k = 'modified';
        } else {
            $show_k = $k;
        }
        if (isset($prefs['fgal_list_' . $k . '_admin'])) {
            $gal_info['show_' . $show_k] = $prefs['fgal_list_' . $k . '_admin'];
        }
    }
    $smarty->assign('show_find_orphans', 'y');
}
$smarty->assign_by_ref('find_durations', $find_durations);
$smarty->assign_by_ref('gal_info', $gal_info);

$smarty->assign('view', $view);

// Display the template
if (! empty($_REQUEST['filegals_manager'])) {
    $smarty->assign('filegals_manager', $_REQUEST['filegals_manager']);
    $smarty->assign('insertion_syntax', $_REQUEST['insertion_syntax']);
    $smarty->display('tiki_full.tpl');
} else {
    $smarty->display('tiki.tpl');
}
