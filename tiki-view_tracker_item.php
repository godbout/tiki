<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'trackers';
require_once('tiki-setup.php');

$access->check_feature('feature_trackers');

$trklib = TikiLib::lib('trk');
if ($prefs['feature_categories'] == 'y') {
    $categlib = TikiLib::lib('categ');
}
$filegallib = TikiLib::lib('filegal');
$notificationlib = TikiLib::lib('notification');
if ($prefs['feature_groupalert'] == 'y') {
    $groupalertlib = TikiLib::lib('groupalert');
}
$auto_query_args = [
    'offset',
    'trackerId',
    'reloff',
    'itemId',
    'maxRecords',
    'status',
    'sort_mode',
    'initial',
    'filterfield',
    'filtervalue',
    'view',
    'exactvalue'
];
if (isset($_REQUEST['trackerId'])) {
    $trackerId = $_REQUEST['trackerId'];
}
if (isset($_REQUEST['itemId'])) {
    $itemId = $_REQUEST['itemId'];
}
$special = false;
if (! isset($trackerId) && $prefs['userTracker'] == 'y' && ! isset($_REQUEST['user'])) {
    if (isset($_REQUEST['view']) and $_REQUEST['view'] == ' user') {
        if (empty($user)) {
            $smarty->assign('msg', tra("You are not logged in"));
            $smarty->assign('errortype', '402');
            $smarty->display("error.tpl");
            die;
        }
        $utid = $userlib->get_tracker_usergroup($user);
        if (isset($utid['usersTrackerId'])) {
            $trackerId = $utid['usersTrackerId'];
            $itemId = $trklib->get_item_id($trackerId, $utid['usersFieldId'], $user);
            if ($itemId == null) {
                $addit = [];
                $addit[] = [
                    'fieldId' => $utid['usersFieldId'],
                    'type' => 'u',
                    'value' => $user,
                ];
                $definition = Tracker_Definition::get($trackerId);
                if ($definition && $f = $definition->getUserField()) {
                    if ($f != $utid['usersFieldId']) {
                        $addit[] = [
                            'fieldId' => $f,
                            'type' => 'u',
                            'value' => $user,
                        ];
                    }
                }
                if ($definition && $f = $definition->getWriterGroupField()) {
                    $addit[] = [
                        'fieldId' => $f,
                        'type' => 'g',
                        'value' => $group,
                    ];
                }
                $itemId = $trklib->replace_item($trackerId, 0, ['data' => $addit], 'o');
            }
            $special = 'user';
        }
    } elseif (isset($_REQUEST["usertracker"]) and $tiki_p_admin == 'y') {
        $utid = $userlib->get_tracker_usergroup($_REQUEST['usertracker']);
        if (isset($utid['usersTrackerId'])) {
            $trackerId = $utid['usersTrackerId'];
            $itemId = $trklib->get_item_id($trackerId, $utid['usersFieldId'], $_REQUEST["usertracker"]);
        }
    }
}
if (! isset($trackerId) && $prefs['groupTracker'] == 'y') {
    if (isset($_REQUEST['view']) and $_REQUEST['view'] == ' group') {
        $gtid = $userlib->get_grouptrackerid($group);
        if (isset($gtid['groupTrackerId'])) {
            $trackerId = $gtid['groupTrackerId'];
            $itemId = $trklib->get_item_id($trackerId, $gtid['groupFieldId'], $group);
            if ($itemId == null) {
                $addit = ['data' => [
                    'fieldId' => $gtid['groupFieldId'],
                    'type' => 'g',
                    'value' => $group,
                ]];
                $itemId = $trklib->replace_item($trackerId, 0, $addit, 'o');
            }
            $special = 'group';
        }
    } elseif (isset($_REQUEST["grouptracker"]) and $tiki_p_admin == 'y') {
        $gtid = $userlib->get_grouptrackerid($_REQUEST["grouptracker"]);
        if (isset($gtid['groupTrackerId'])) {
            $trackerId = $gtid['groupTrackerId'];
            $itemId = $trklib->get_item_id($trackerId, $gtid['groupFieldId'], $_REQUEST["grouptracker"]);
        }
    }
}
$smarty->assign_by_ref('special', $special);
//url to a user user tracker tiki-view_tracker_item.php?user=yyyyy&view=+user or tiki-view_tracker_item.php?greoup=yyy&user=yyyyy&view=+user or tiki-view_tracker_item.php?trackerId=xxx&user=yyyyy&view=+user
if ($prefs['userTracker'] == 'y' && isset($_REQUEST['view']) && $_REQUEST['view'] = ' user' && ! empty($_REQUEST['user'])) {
    if (empty($trackerId)) {
        if (empty($_REQUEST['group'])) {
            $_REQUEST['group'] = $userlib->get_user_default_group($_REQUEST['user']);
        }
        if (! empty($_REQUEST['group'])) {
            $utid = $userlib->get_usertrackerid($_REQUEST['group']);
            if (! empty($utid['usersTrackerId']) && ! empty($utid['usersFieldId'])) {
                $trackerId = $utid['usersTrackerId'];
                $fieldId = $utid['usersFieldId'];
            }
        }
    }
    if (! empty($trackerId)) {
        if (empty($fieldId)) {
            $definition = Tracker_Definition::get($trackerId);
            if ($definition) {
                $fieldId = $definition->getUserField();
            }
        }
        if (! empty($fieldId)) {
            $itemId = $trklib->get_item_id($trackerId, $fieldId, $_REQUEST['user']);
            if (! $itemId) {
                $smarty->assign(
                    'msg',
                    tra("You don't have a personal tracker item yet. Click here to make one:") . '<br /><a href="tiki-view_tracker.php?trackerId=' . $trackerId . '&cookietab=2">' . tra('Create tracker item') . '</a>'
                );
                $smarty->display("error.tpl");
                die;
            }
        }
    }
}
if ((! isset($trackerId) || ! $trackerId) && isset($itemId)) {
    $item_info = $trklib->get_tracker_item($itemId);
    $trackerId = $item_info['trackerId'];
}
if (! isset($trackerId) || ! $trackerId) {
    $smarty->assign('msg', tra("No tracker indicated"));
    $smarty->display("error.tpl");
    die;
}
if (! isset($utid) and ! isset($gtid) and (! isset($itemId) or ! $itemId) and ! isset($_REQUEST["offset"])) {
    $smarty->assign('msg', tra("No item indicated"));
    $smarty->display("error.tpl");
    die;
}

if (isset($itemId)) {
    $item_info = $trklib->get_tracker_item($itemId);

    TikiLib::events()->trigger(
        'tiki.trackeritem.view',
        [
            'type' => 'trackeritem',
            'object' => $itemId,
            'owner' => $item_info['createdBy'],
            'user' => $GLOBALS['user'],
        ]
    );
}

$definition = Tracker_Definition::get($trackerId);
$fieldDefinitions = $definition->getFields();
$smarty->assign('tracker_is_multilingual', $prefs['feature_multilingual'] == 'y' && $definition->getLanguageField());

if ($prefs['feature_groupalert'] == 'y') {
    $groupforalert = $groupalertlib->GetGroup('tracker', $trackerId);
    if ($groupforalert != "") {
        $showeachuser = $groupalertlib->GetShowEachUser('tracker', $trackerId, $groupforalert);
        $listusertoalert = $userlib->get_users(0, -1, 'login_asc', '', '', false, $groupforalert, '');
        $smarty->assign_by_ref('listusertoalert', $listusertoalert['data']);
    }
    $smarty->assign_by_ref('groupforalert', $groupforalert);
    $smarty->assign_by_ref('showeachuser', $showeachuser);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
// ************* previous/next **************
foreach ([
    'status',
    'filterfield',
    'filtervalue',
    'initial',
    'exactvalue',
    'reloff'
] as $reqfld) {
    $trynam = 'try' . $reqfld;
    if (isset($_REQUEST[$reqfld])) {
        $$trynam = $_REQUEST[$reqfld];
    } else {
        $$trynam = '';
    }
}
if (isset($_REQUEST['filterfield'])) {
    if (is_array($_REQUEST['filtervalue']) and isset($_REQUEST['filtervalue'][$tryfilterfield])) {
        $tryfiltervalue = $_REQUEST['filtervalue'][$tryfilterfield];
    } else {
        $tryfilterfield = preg_split('/\s*:\s*/', $_REQUEST['filterfield']);
        $tryfiltervalue = preg_split('/\s*:\s*/', $_REQUEST['filtervalue']);
        $tryexactvalue = preg_split('/\s*:\s*/', $_REQUEST['exactvalue']);
    }
}

//*********** handle prev/next links *****************
if (isset($_REQUEST['reloff'])) {
    if (isset($_REQUEST['move'])) {
        switch ($_REQUEST['move']) {
            case 'prev':
                $tryreloff += - 1;

                break;

            case 'next':
                $tryreloff += 1;

                break;

            default:
                $tryreloff = (int)$_REQUEST['move'];
        }
    }
    $cant = 0;
    $listfields = [];
    if (substr($sort_mode, 0, 2) == 'f_') { //look at the field in case the field needs some processing to find the sort
        list($a, $i, $o) = explode('_', $sort_mode);
        foreach ($fieldDefinitions as $f) {
            if ($f['fieldId'] == $i) {
                $listfields = [
                    $i => $f
                ];

                break;
            }
        }
    }
    if (isset($_REQUEST['cant'])) {
        $cant = $_REQUEST['cant'];
    } else {
        if (is_array($tryfiltervalue)) {
            $tryfiltervalue = array_values($tryfiltervalue);
        }
        $trymove = $trklib->list_items($trackerId, $offset + $tryreloff, 1, $sort_mode, $listfields, $tryfilterfield, $tryfiltervalue, $trystatus, $tryinitial, $tryexactvalue);
        if (isset($trymove['data'][0]['itemId'])) {
            // Autodetect itemId if not specified
            if (! isset($itemId)) {
                $itemId = $trymove['data'][0]['itemId'];
                unset($item_info);
            }
            $cant = $trymove['cant'];
        }
    }
    $smarty->assign('cant', $cant);
}
//*********** that's all for prev/next *****************
$smarty->assign('itemId', $itemId);
if (! isset($item_info)) {
    $item_info = $trklib->get_tracker_item($itemId);
    if (empty($item_info)) {
        $smarty->assign('msg', tra("No item indicated"));
        $smarty->display("error.tpl");
        die;
    }
}
$item_info['logs'] = ['cant' => $trklib->item_has_history($item_info['itemId'])];	// only used to show history links, no need to load everything
$smarty->assign_by_ref('item_info', $item_info);
$smarty->assign('item', ['itemId' => $itemId, 'trackerId' => $trackerId]);
$cat_objid = $itemId;
$cat_type = 'trackeritem';

$tracker_info = $definition->getInformation();
$itemObject = Tracker_Item::fromInfo($item_info);

if (! isset($tracker_info["writerCanModify"]) or (isset($utid) and ($trackerId != $utid['usersTrackerId']))) {
    $tracker_info["writerCanModify"] = 'n';
}
if (! isset($tracker_info["userCanSeeOwn"]) or (isset($utid) and ($trackerId != $utid['usersTrackerId']))) {
    $tracker_info["userCanSeeOwn"] = 'n';
}
if (! isset($tracker_info["writerGroupCanModify"]) or (isset($gtid) and ($trackerId != $gtid['groupTrackerId']))) {
    $tracker_info["writerGroupCanModify"] = 'n';
}
if (! isset($tracker_info["groupCanSeeOwn"]) or (isset($gtid) and ($trackerId != $gtid['groupTrackerId']))) {
    $tracker_info["groupCanSeeOwn"] = 'n';
}
$tikilib->get_perm_object($trackerId, 'tracker', $tracker_info);
if (! $itemObject->canView()) {
    $smarty->assign('errortype', 403);
    $smarty->assign('msg', tra("Permission denied"));
    $smarty->display("error.tpl");
    die;
}
if (isset($tracker_info['adminOnlyViewEditItem']) && $tracker_info['adminOnlyViewEditItem'] === 'y') {
    $access->check_permission('tiki_p_admin_trackers', tra('This tracker restricts access to the built-in tracker interfaces. You need admin tracker permission'), 'tracker', $tracker_info['trackerId']);
}

include_once('tiki-sefurl.php');

if (isset($_REQUEST["remove"]) && $itemObject->canRemove()) {
    $access->check_authenticity(tr('Are you sure you want to permanently delete this item?'));
    $trklib->remove_tracker_item($_REQUEST["remove"]);
    $access->redirect(filter_out_sefurl('tiki-view_tracker.php?trackerId=' . $trackerId));
}
if (! empty($_REQUEST['moveto'])) {
    $perms = Perms::get('tracker', $_REQUEST['moveto']);
    if ($tiki_p_admin_trackers == 'y' && $perms->create_tracker_items) {
        // Move item to another tracker. This assumes certain similarities between the 2 trackers.
        $trklib->move_item($trackerId, $itemId, $_REQUEST['moveto']);
        header('Location: ' . filter_out_sefurl('tiki-view_tracker_item.php?itemId=' . $itemId));
        exit;
    }
    $smarty->assign('errortype', 403);
    $smarty->assign('msg', tra("Permission denied"));
    $smarty->display("error.tpl");
    die;
}
if (isset($_REQUEST["removeattach"])) {
    $owner = $trklib->get_item_attachment_owner($_REQUEST["removeattach"]);
    if (($user && ($owner == $user)) || ($tiki_p_admin_trackers == 'y')) {
        $access->check_authenticity(tra('Are you sure you want to remove this attachment?'));
        $trklib->remove_item_attachment($_REQUEST["removeattach"]);
    }
}


$status_types = $trklib->status_types();
$smarty->assign('status_types', $status_types);
$fields = [];
$ins_fields = [];
$usecategs = false;
$cookietab = 1;
$itemUsers = $trklib->get_item_creators($trackerId, $itemId);
$smarty->assign_by_ref('itemUsers', $itemUsers);
$plugins_loaded = false;

if (empty($tracker_info)) {
    $item_info = [];
}
$fieldFactory = $definition->getFieldFactory();

$rateFieldId = $definition->getRateField();
if (isset($tracker_info['useRatings']) and $tracker_info['useRatings'] == 'y' and $tiki_p_tracker_vote_ratings == 'y') {
    if ($user and $tiki_p_tracker_vote_ratings == 'y' and isset($rateFieldId) and isset($_REQUEST['ins_' . $rateFieldId])) {
        $trklib->replace_rating($trackerId, $itemId, $rateFieldId, $user, $_REQUEST['ins_' . $rateFieldId]);
        header('Location: tiki-view_tracker_item.php?trackerId=' . $trackerId . '&itemId=' . $itemId);
        die;
    }
}

// Why do we need to define these array elements? Can users not just consult fieldId? Chealer 2017-05-23
foreach ($fieldDefinitions as &$fieldDefinition) {
    $fid = $fieldDefinition["fieldId"];

    $fieldDefinition["ins_id"] = 'ins_' . $fid;
    $fieldDefinition["filter_id"] = 'filter_' . $fid;
}
unset($fieldDefinition);

if (isset($_REQUEST["save"]) || isset($_REQUEST["save_return"]) || isset($_REQUEST['save_and_comment'])) {
    if ($prefs['feature_warn_on_edit'] == 'y') {
        TikiLib::lib('service')->internal('semaphore', 'unset', ['object_id' => $itemId, 'object_type' => 'trackeritem']);
    }
    foreach ($fieldDefinitions as $i => $current_field) {
        $fid = $current_field["fieldId"];
        $fieldIsVisible = $itemObject->canViewField($fid);
        $fieldIsEditable = $itemObject->canModifyField($fid);

        $current_field_ins = null;
        if ($fieldIsVisible || $fieldIsEditable) {
            $current_field_ins = $current_field;

            $handler = $fieldFactory->getHandler($current_field, $item_info);

            if ($handler) {
                $insert_values = $handler->getFieldData($_REQUEST);

                if ($insert_values) {
                    $current_field_ins = array_merge($current_field_ins, $insert_values);
                }
            }
        }

        if (! empty($current_field_ins)) {
            if ($fieldIsEditable) {
                $ins_fields['data'][$i] = $current_field_ins;
            }
            if ($fieldIsVisible) {
                $fields['data'][$i] = $current_field_ins;
            }
        }
    }

    if ($itemObject->canModify()) {
        $captchalib = TikiLib::lib('captcha');
        if (empty($user) && $prefs['feature_antibot'] == 'y' && ! $captchalib->validate()) {
            $smarty->assign('msg', $captchalib->getErrors());
            $smarty->assign('errortype', 'no_redirect_login');
            $smarty->display("error.tpl");
            die;
        }
        // Check field values for each type and presence of mandatory ones
        $mandatory_missing = [];
        $err_fields = [];
        $categorized_fields = $definition->getCategorizedFields();
        $field_errors = $trklib->check_field_values($ins_fields, $categorized_fields, $trackerId, empty($itemId) ? '' : $itemId);
        $smarty->assign('err_mandatory', $field_errors['err_mandatory']);
        $smarty->assign('err_value', $field_errors['err_value']);
        // values are OK, then lets save the item
        if (count($field_errors['err_mandatory']) == 0 && count($field_errors['err_value']) == 0) {
            $smarty->assign('input_err', '0'); // no warning to display
            if ($prefs['feature_groupalert'] == 'y') {
                $groupalertlib->Notify(isset($_REQUEST['listtoalert']) ? $_REQUEST['listtoalert'] : '', "tiki-view_tracker_item.php?itemId=" . $itemId);
            }
            check_ticket('view-trackers-items');
            if (! isset($_REQUEST["edstatus"]) or ($tracker_info["showStatus"] != 'y' and $tiki_p_admin_trackers != 'y')) {
                $_REQUEST["edstatus"] = $tracker_info["modItemStatus"];
            }
            $trklib->replace_item($trackerId, $itemId, $ins_fields, $_REQUEST["edstatus"]);
            if (isset($rateFieldId) && isset($_REQUEST["ins_$rateFieldId"])) {
                $trklib->replace_rating($trackerId, $itemId, $rateFieldId, $user, $_REQUEST["ins_$rateFieldId"]);
            }
            $_REQUEST['show'] = 'view';

            // What is this loop for? The value is overriden later for editable fields. Chealer 2017-05-25
            foreach ($fields["data"] as $i => $array) {
                if (isset($fields["data"][$i])) {
                    $ins_fields["data"][$i]["value"] = '';
                }
            }

            $item_info = $trklib->get_tracker_item($itemId);
            $item_info['logs'] = $trklib->get_item_history($item_info, 0, '', 0, 1);
            $smarty->assign('item_info', $item_info);
        } else {
            $error = $ins_fields;
            $cookietab = "2";
            if ($tracker_info['useAttachments'] == 'y') {
                ++$cookietab;
            }
            if ($tracker_info['useComments'] == 'y') {
                ++$cookietab;
            }
            $smarty->assign('input_err', '1'); // warning to display
            // can't go back if there are errors
            if (isset($_REQUEST['save_return'])) {
                $_REQUEST['save'] = 'save';
                unset($_REQUEST['save_return']);
            }
            if (isset($_REQUEST['save_and_comment'])) {
                $_REQUEST['save'] = 'save';
                unset($_REQUEST['save_and_comment']);
            }
        }
        if (isset($_REQUEST['save_return']) && isset($_REQUEST['from'])) {
            $fromUrl = filter_out_sefurl('tiki-index.php?page=' . urlencode($_REQUEST['from']));
            header("Location: {$fromUrl}");
            exit;
        }

        if (isset($_REQUEST['save_and_comment'])) {
            $version = $trklib->last_log_version($itemId);
            $smarty->assign('version', $version);

            $modalUrl = TikiLib::lib('service')->getUrl([
                'controller' => 'comment',
                'action' => 'post',
                'type' => 'trackeritem',
                'objectId' => $itemId,
                'parentId' => 0,
                'version' => $version,
                'return_url' => '',
                'title' => tr('Comment for edit #%0', $version),
                ]);

            $headerlib->add_jq_onready('$.openModal({ remote:"' . $modalUrl . '"});');
        }
    }
}
// remove image from an image field
if (isset($_REQUEST["removeImage"])) {
    $img_field = [
        'data' => []
    ];
    $img_field['data'][] = [
        'fieldId' => $_REQUEST["fieldId"],
        'type' => 'i',
        'name' => $_REQUEST["fieldName"],
        'value' => 'blank'
    ];
    $trklib->replace_item($trackerId, $itemId, $img_field);
    $_REQUEST['show'] = "mod";
}
// ************* return to list ***************************
if (isset($_REQUEST["returntracker"]) || isset($_REQUEST["save_return"])) {
    require_once('lib/smarty_tiki/block.self_link.php');
    header(
        'Location: ' . smarty_block_self_link(
            [
                '_script' => 'tiki-view_tracker.php',
                '_tag' => 'n',
                '_urlencode' => 'n',
                'itemId' => 'NULL',
                'trackerId' => $trackerId
            ],
            '',
            $smarty
        )
    );
    die;
}
// ********************************************************
$info = $trklib->get_tracker_item($itemId);
$itemObject = Tracker_Item::fromInfo($info);
if (! isset($info['trackerId'])) {
    $info['trackerId'] = $trackerId;
}
if (! $itemObject->canView()) {
    $smarty->assign('errortype', 403);
    $smarty->assign('msg', tra('Permission denied'));
    $smarty->display('error.tpl');
    die;
}

$fieldFactory = $definition->getFieldFactory();

foreach ($fieldDefinitions as $i => $current_field) {
    $current_field_ins = null;
    $fid = $current_field['fieldId'];

    $handler = $fieldFactory->getHandler($current_field, $info);

    $fieldIsVisible = $itemObject->canViewField($fid);
    $fieldIsEditable = $itemObject->canModifyField($fid);

    if ($fieldIsVisible || $fieldIsEditable) {
        $current_field_ins = $current_field;

        if ($handler) {
            $insert_values = $handler->getFieldData();

            if ($insert_values) {
                $current_field_ins = array_merge($current_field_ins, $insert_values);
            }
        }
    }

    if (! empty($current_field_ins)) {
        if ($fieldIsVisible) {
            $fields['data'][$i] = $current_field_ins;
        }
        if ($fieldIsEditable) {
            $ins_fields['data'][$i] = $current_field_ins;
        }
    }
}
$smarty->assign('tracker_item_main_value', $trklib->get_isMain_value($trackerId, $itemId));

//restore types values if there is an error
if (isset($error)) {
    foreach ($ins_fields["data"] as $i => $current_field) {
        if (isset($error["data"][$i]["value"])) {
            $ins_fields["data"][$i]["value"] = $error["data"][$i]["value"];
        }
    }
}

$authorfield = $definition->getAuthorField();
if ($authorfield) {
    $tracker_info['authorindiv'] = $trklib->get_item_value($trackerId, $itemId, $authorfield);
}

// Pull realname for user.
$info["createdByReal"] = $tikilib->get_user_preference($info["createdBy"], 'realName', '');
$info["lastModifByReal"] = $tikilib->get_user_preference($info["lastModifBy"], 'realName', '');

if ($tracker_info['doNotShowEmptyField'] == 'y') {
    $trackerlib = TikiLib::lib('trk');
    $fields['data'] = $trackerlib->mark_fields_as_empty($fields['data']);
}

$smarty->assign('trackerId', $trackerId);
$smarty->assign('tracker_info', $tracker_info);
$smarty->assign_by_ref('info', $info);
$smarty->assign_by_ref('fields', $fields["data"]);
$smarty->assign_by_ref('ins_fields', $ins_fields["data"]);
if ($prefs['feature_user_watches'] == 'y' and $tiki_p_watch_trackers == 'y') {
    if ($user and isset($_REQUEST['watch'])) {
        check_ticket('view-trackers-items');
        if ($_REQUEST['watch'] == 'add') {
            $tikilib->add_user_watch($user, 'tracker_item_modified', $itemId, 'tracker ' . $trackerId, $tracker_info['name'], "tiki-view_tracker_item.php?trackerId=" . $trackerId . "&amp;itemId=" . $itemId);
        } else {
            $remove_watch_tracker_type = 'tracker ' . $trackerId;
            $tikilib->remove_user_watch($user, 'tracker_item_modified', $itemId, $remove_watch_tracker_type);
        }
    }
    $smarty->assign('user_watching_tracker', 'n');
    $it = $tikilib->user_watches($user, 'tracker_item_modified', $itemId, 'tracker ' . $trackerId);
    if ($user and $tikilib->user_watches($user, 'tracker_item_modified', $itemId, 'tracker ' . $trackerId)) {
        $smarty->assign('user_watching_tracker', 'y');
    }
    // Check, if the user is watching this trackers' item by a category.
    if ($prefs['feature_categories'] == 'y') {
        $watching_categories_temp = $categlib->get_watching_categories($trackerId, 'tracker', $user);
        $smarty->assign('category_watched', 'n');
        if (count($watching_categories_temp) > 0) {
            $smarty->assign('category_watched', 'y');
            $watching_categories = [];
            foreach ($watching_categories_temp as $wct) {
                $watching_categories[] = [
                    "categId" => $wct,
                    "name" => $categlib->get_category_name($wct)
                ];
            }
            $smarty->assign('watching_categories', $watching_categories);
        }
    }
}

if ($tracker_info['useComments'] == 'y') {
    $comCount = $trklib->get_item_nb_comments($itemId);
    $smarty->assign("comCount", $comCount);
    $smarty->assign("canViewCommentsAsItemOwner", $itemObject->canViewComments());

    $saveAndComment = $definition->getConfiguration('saveAndComment');
    if ($saveAndComment !== 'n') {
        if (! $itemObject->canPostComments()) {
            $saveAndComment = 'n';
        }
    }
    $smarty->assign("saveAndComment", $saveAndComment);
}

if ($tracker_info["useAttachments"] == 'y') {
    if (isset($_REQUEST["removeattach"])) {
        $_REQUEST["show"] = "att";
    }
    if (isset($_REQUEST["editattach"])) {
        $att = $trklib->get_item_attachment($_REQUEST["editattach"]);
        $smarty->assign("attach_comment", $att['comment']);
        $smarty->assign("attach_version", $att['version']);
        $smarty->assign("attach_longdesc", $att['longdesc']);
        $smarty->assign("attach_file", $att["filename"]);
        $smarty->assign("attId", $att["attId"]);
        $_REQUEST["show"] = "att";
    }
    if (isset($_REQUEST['attach']) && $tiki_p_attach_trackers == 'y' && isset($_FILES['userfile1'])) {
        // Process an attachment here
        if (is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
            $fp = fopen($_FILES['userfile1']['tmp_name'], "rb");
            $data = '';
            $fhash = '';
            if ($prefs['t_use_db'] == 'n') {
                $fhash = md5($_FILES['userfile1']['name'] . $tikilib->now);
                $fw = fopen($prefs['t_use_dir'] . $fhash, "wb");
                if (! $fw) {
                    $smarty->assign('msg', tra('Cannot write to this file:') . $fhash);
                    $smarty->display("error.tpl");
                    die;
                }
            }
            while (! feof($fp)) {
                if ($prefs['t_use_db'] == 'n') {
                    $data = fread($fp, 8192 * 16);
                    fwrite($fw, $data);
                } else {
                    $data .= fread($fp, 8192 * 16);
                }
            }
            fclose($fp);
            if ($prefs['t_use_db'] == 'n') {
                fclose($fw);
                $data = '';
            }

            try {
                if ($prefs['t_use_db'] == 'n') {
                    $filegallib->assertUploadedFileIsSafe($prefs['t_use_dir'] . $fhash, $_FILES['userfile1']['name']);
                } else {
                    $filegallib->assertUploadedContentIsSafe($data, $_FILES['userfile1']['name']);
                }
            } catch (Exception $e) {
                $smarty->assign('msg', $_FILES['userfile1']['name'] . ': ' . $e->getMessage());
                $smarty->display("error.tpl");
                die;
            }
            $size = $_FILES['userfile1']['size'];
            $name = $_FILES['userfile1']['name'];
            $type = $_FILES['userfile1']['type'];
        } else {
            $smarty->assign('msg', $_FILES['userfile1']['name'] . ': ' . tra('Upload was not successful') . ': ' . $tikilib->uploaded_file_error($_FILES['userfile1']['error']));
            $smarty->display("error.tpl");
            die;
        }
        $trklib->replace_item_attachment($_REQUEST["attId"], $name, $type, $size, $data, $_REQUEST["attach_comment"], $user, $fhash, $_REQUEST["attach_version"], $_REQUEST["attach_longdesc"], $trackerId, $itemId, $tracker_info);
        $_REQUEST["attId"] = 0;
        $_REQUEST['show'] = "att";
    }
    // If anything below here is changed, please change lib/wiki-plugins/wikiplugin_attach.php as well.
    $attextra = 'n';
    if (strstr($tracker_info["orderAttachments"], '|')) {
        $attextra = 'y';
    }
    $attfields = explode(',', strtok($tracker_info["orderAttachments"], '|'));
    $atts = $trklib->list_item_attachments($itemId, 0, -1, 'comment_asc', '');
    $smarty->assign('atts', $atts["data"]);
    $smarty->assign('attCount', $atts["cant"]);
    $smarty->assign('attfields', $attfields);
    $smarty->assign('attextra', $attextra);
}
if (isset($_REQUEST['moveto']) && empty($_REQUEST['moveto'])) {
    $trackers = $trklib->list_trackers();
    $smarty->assign_by_ref('trackers', $trackers['data']);
    $_REQUEST['show'] = 'mod';
}
if (isset($_REQUEST['show'])) {
    if ($_REQUEST['show'] == 'view') {
        $cookietab = 1;
        // for legacy edit mode after saving
        setCookieSection('tabs_view_tracker_item', '', 'tabs');
    } elseif ($tracker_info["useComments"] == 'y' and $_REQUEST['show'] == 'com') {
        $cookietab = 2;
    } elseif ($_REQUEST['show'] == "mod") {
        $cookietab = 2;
        if ($tracker_info["useAttachments"] == 'y') {
            $cookietab++;
        }
        if ($tracker_info["useComments"] == 'y' && $tiki_p_tracker_view_comments == 'y') {
            $cookietab++;
        }
    } elseif ($_REQUEST['show'] == "att") {
        $cookietab = 2;
        if ($tracker_info["useComments"] == 'y' && $tiki_p_tracker_view_comments == 'y') {
            $cookietab = 3;
        }
    }
}
if (isset($_REQUEST['from'])) {
    $from = $_REQUEST['from'];
} else {
    $from = false;
}
$smarty->assign('from', $from);
if (isset($_REQUEST['status'])) {
    $smarty->assign_by_ref('status', $_REQUEST['status']);
}
include_once('tiki-section_options.php');

ask_ticket('view-trackers-items');
if ($prefs['feature_actionlog'] == 'y') {
    $logslib = TikiLib::lib('logs');
    $logslib->add_action('Viewed', $itemId, 'trackeritem');
}

// Generate validation js
if ($prefs['feature_jquery'] == 'y' && $prefs['feature_jquery_validation'] == 'y') {
    $validatorslib = TikiLib::lib('validators');
    $validationjs = $validatorslib->generateTrackerValidateJS($fields['data']);
    $smarty->assign('validationjs', $validationjs);
}

if ($itemObject->canRemove()) {
    $smarty->assign('editTitle', tr('Edit/Delete'));
} else {
    $smarty->assign('editTitle', tr('Edit'));
}
if (! empty($path)) {
    $smarty->assign('formAction', $path);
}
$smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
$smarty->assign('canView', $itemObject->canView());
$smarty->assign('canModify', $itemObject->canModify());
$smarty->assign('canRemove', $itemObject->canRemove());
$smarty->assign('conflictoverride', !empty($_REQUEST['conflictoverride']));

if ($itemObject->canModify() && $prefs['tracker_legacy_insert'] == 'y' && $prefs['feature_warn_on_edit'] == 'y' && empty($_REQUEST['conflictoverride'])) {
    $otherUser = TikiLib::lib('service')->internal(
        'semaphore',
        'get_user',
        [
            'object_id' => $itemId,
            'object_type' => 'trackeritem',
            'check' => 1,
        ]
    );
    if ($otherUser && $otherUser !== $user) {
        $smarty->loadPlugin('smarty_modifier_username');
        $msg = tr("This tracker item is being edited by user %0. Please check with the user before editing, otherwise conflicts will occur and data might be lost.", smarty_modifier_username($otherUser));
        $msg .= '<br /><br /><a href="' . filter_out_sefurl('tiki-view_tracker_item.php?itemId=' . $itemId . '&conflictoverride=y') . '">' . tra('Override lock and carry on with edit') . '</a>';
        $smarty->assign('msg', $msg);
        $smarty->assign('errortitle', tra('Item is currently being edited'));
        $smarty->display("error.tpl");
        die;
    }
    TikiLib::lib('service')->internal('semaphore', 'set', ['object_id' => $itemId, 'object_type' => 'trackeritem']);
}

// Add view/edit template. Override an optional template defined in the tracker by a template passed via request
// Note: Override is only allowed if a default template was set already in the tracker.

// View
$viewItemPretty = [
        'override' => false,
        'value' => $tracker_info['viewItemPretty'],
        'type' => 'wiki'
];
if (! empty($tracker_info['viewItemPretty'])) {
    if (isset($_REQUEST['vi_tpl'])) {
        $viewItemPretty['override'] = true;
        $viewItemPretty['value'] = $_REQUEST['vi_tpl'];
    }
    // Need to check wether this is a wiki: or tpl: template, bc the smarty template needs to take care of this
    if (strpos(strtolower($viewItemPretty['value']), 'wiki:') === false) {
        $viewItemPretty['type'] = 'tpl';
    }
}
$smarty->assign('viewItemPretty', $viewItemPretty);

// Edit
$editItemPretty = [
    'override' => false,
    'value' => $tracker_info['editItemPretty'],
    'type' => 'wiki'
];
if (! empty($tracker_info['editItemPretty'])) {
    if (isset($_REQUEST['ei_tpl'])) {
        $editItemPretty['override'] = true;
        $editItemPretty['value'] = $_REQUEST['ei_tpl'];
    }
    if (strpos(strtolower($editItemPretty['value']), 'wiki:') === false) {
        $editItemPretty['type'] = 'tpl';
    }
}
$smarty->assign('editItemPretty', $editItemPretty);

// add referer url to setup the back button in tpl
// check wether we have been called from a different page than ourselfs to save a link to the referer for a back buttom.
// this can be a wikipage with the trackerlist item and and view item temlate set using vi_tpl=wiki:mytemplate
// if we do anything on the current page (i.e. adding a comment) we need to keep that saved link.
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$temp = strtolower($referer);
if (strpos($temp, 'vi_tpl=') || strpos($temp, 'ei_tpl=')) {
    $referer = $_SESSION['item_tpl_referer'];
} else {
    $_SESSION['item_tpl_referer'] = $referer;
}
unset($temp);
$smarty->assign('referer', $referer);

// Display the template
$smarty->assign('mid', 'tiki-view_tracker_item.tpl');

try {
    if (isset($_REQUEST['print'])) {
        $smarty->assign('print_page', 'y');
        $smarty->display('tiki-print.tpl');
    } elseif (isset($_REQUEST['pdf'])) {
        $smarty->assign('print_page', 'y');
        $trackerData = $smarty->fetch('tiki-print.tpl');
        //getting comments associated with tracker item
        $broker = TikiLib::lib('service')->getBroker();
        $comments = $broker->internalRender("comment", "list", $jitRequest = new JitFilter(["controller" => "comment", "action" => "list", "type" => "trackeritem", "objectId" => $itemId]));
        require_once 'lib/pdflib.php';
        $generator = new PdfGenerator();
        if (! empty($generator->error)) {
            Feedback::error($generator->error);
            $access->redirect($page);
        } else {
            $pdf = $generator->getPdf('tiki-print.php', ['page' => $tracker_info['name']], str_ireplace("</dl>", "</dl><h3>Comments</h3>" . $comments . "<br />", $trackerData));
            $length = strlen($pdf);
            header('Cache-Control: private, must-revalidate');
            header('Pragma: private');
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename="' . TikiLib::lib('tiki')->remove_non_word_characters_and_accents($trklib->get_isMain_value($trackerId, $itemId)) . '.pdf"');
            header("Content-Type: application/pdf");
            header("Content-Transfer-Encoding: binary");
            header('Content-Length: ' . $length);
            echo $pdf;
        }
        //end of pdf
    } else {
        $smarty->display('tiki.tpl');
    }
} catch (SmartyException $e) {
    $message = tr('The requested element cannot be displayed. One of the view/edit templates is missing or has errors: %0', $e->getMessage());
    trigger_error($e->getMessage(), E_USER_ERROR);
    $smarty->loadPlugin('smarty_modifier_sefurl');
    $access->redirect(smarty_modifier_sefurl($info['trackerId'], 'tracker'), $message, 302, 'error');
}
