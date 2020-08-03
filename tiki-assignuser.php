<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// This script is used to assign groups to a particular user
// ASSIGN USER TO GROUPS
require_once('tiki-setup.php');

$auto_query_args = ['sort_mode', 'offset', 'find', 'assign_user', 'group', 'maxRecords'];

$access->check_permission_either(['tiki_p_admin_users', 'tiki_p_subscribe_groups']);

if (! isset($_REQUEST["assign_user"]) || ($tiki_p_admin != 'y' && $tiki_p_admin_users != 'y')) {
    $_REQUEST['assign_user'] = $user;
    $userChoice = 'y';
    $smarty->assign_by_ref('userChoice', $userChoice);
} else {
    if (! $userlib->user_exists($_REQUEST['assign_user'])) {
        $smarty->assign('msg', tra("User doesn't exist"));
        $smarty->display("error.tpl");
        die;
    }
    $userChoice = '';
    $smarty->assign_by_ref('assign_user', $_REQUEST['assign_user']);
}

$assign_user = $_REQUEST["assign_user"];

if (isset($_REQUEST["action"])) {
    if (! isset($_REQUEST["group"])) {
        Feedback::error(tr('A group must be indicated'));
    } else {
        if ($userChoice == 'y') {
            $gps = $userlib->get_groups(0, -1, 'groupName_asc', '', '', '', '', $userChoice);
            $groups = [];
            foreach ($gps['data'] as $g) {
                $groups[$g['groupName']] = $g;
            }
        } elseif ($tiki_p_admin != 'y') {
            $groups = $userlib->get_user_groups_inclusion($user);
        }
        if ($_REQUEST["action"] == 'assign' && $access->checkCsrf()) {
            if (! $userlib->group_exists($_REQUEST["group"])) {
                Feedback::error(tr('Invalid group'));
            } elseif ($tiki_p_admin_users == 'y'
                || ($tiki_p_admin_users == 'y' && array_key_exists($_REQUEST["group"], $groups))
            ) {
                $result = $userlib->assign_user_to_group($_REQUEST["assign_user"], $_REQUEST["group"]);
                if ($result && $result->numRows()) {
                    Feedback::success(
                        tr(
                        'Assigned user %0 to group %1',
                        htmlspecialchars($_REQUEST["assign_user"]),
                        htmlspecialchars($_REQUEST["group"])
                    )
                    );
                    $logslib->add_log('perms', sprintf("Assigned %s in group %s", $_REQUEST["assign_user"], $_REQUEST["group"]));
                } else {
                    Feedback::error(
                        tr(
                        'User %0 not assigned to group %1',
                        htmlspecialchars($_REQUEST["assign_user"]),
                        htmlspecialchars($_REQUEST["group"])
                    )
                    );
                }
            }
        } elseif ($_REQUEST["action"] == 'removegroup' && ($tiki_p_admin == 'y' && $access->checkCsrf()
                || ($tiki_p_admin_users == 'y' && array_key_exists($_REQUEST["group"], $groups) && $access->checkCsrf()))) {
            $result = $userlib->remove_user_from_group($_REQUEST["assign_user"], $_REQUEST["group"]);
            if ($result && $result->numRows()) {
                Feedback::success(
                    tr(
                    'Removed user %0 from group %1',
                    htmlspecialchars($_REQUEST["assign_user"]),
                    htmlspecialchars($_REQUEST["group"])
                )
                );
                $logslib->add_log('perms', sprintf("Removed %s from group %s", $_REQUEST["assign_user"], $_REQUEST["group"]));
            } else {
                Feedback::error(
                    tr(
                    'User %0 not removed from group %1',
                    htmlspecialchars($_REQUEST["assign_user"]),
                    htmlspecialchars($_REQUEST["group"])
                )
                );
            }
        }
    }
}

if (isset($_REQUEST['set_default']) && $access->checkCsrf()) {
    $result = $userlib->set_default_group($_REQUEST['login'], $_REQUEST['defaultgroup']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Default group set'));
    } else {
        Feedback::error(tr('Default group not set'));
    }
}

$user_info = $userlib->get_user_info($assign_user, true);
$smarty->assign_by_ref('user_info', $user_info);
if (! empty($_REQUEST['save']) && $access->checkCsrf()) {
    foreach ($_REQUEST as $r => $v) {
        if (strpos($r, 'new_') === 0) {
            $g = substr($r, 4);
            if ($_REQUEST['new_' . $g] != $_REQUEST['old_' . $g]) {
                $t = strtotime($_REQUEST['new_' . $g]);
                $t = $tikilib->make_time(date('H', $t), date('i', $t), 0, date('m', $t), date('d', $t), date('Y', $t));
                if ($t !== false) {
                    $g_info = $userlib->get_groupId_info($g);
                    $result = $userlib->extend_membership($assign_user, $g_info['groupName'], 0, $t);
                    if ($result && $result->numRows()) {
                        Feedback::success(tr('Default group set'));
                    } else {
                        Feedback::error(tr('Default group not set'));
                    }
                }
            }
        }
    }
}
$dates = $userlib->get_user_groups_date($user_info['userId']);
$smarty->assign_by_ref('dates', $dates);

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'groupName_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}

$smarty->assign_by_ref('sort_mode', $sort_mode);

// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
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

if (isset($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
}

if ($tiki_p_admin != 'y' && $userChoice != 'y') {
    $ingroups = $userlib->get_user_groups_inclusion($user);
    foreach ($user_info['groups'] as $grp => $i) {
        if (! isset($ingroups[$grp])) {
            unset($user_info['groups'][$grp]);
        }
    }
} else {
    $ingroups = '';
}
$users = $userlib->get_groups($offset, $maxRecords, $sort_mode, $find, '', 'y', $ingroups, $userChoice);

foreach ($users['data'] as $key => $gr) {
    if (isset($user_info['groups'][$gr['groupName']])) {
        $users['data'][$key]['what'] = $user_info['groups'][$gr['groupName']];
    }
}

$smarty->assign_by_ref('cant_pages', $users["cant"]);

// Get users (list of users)
$smarty->assign_by_ref('users', $users["data"]);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-assignuser.tpl');
$smarty->display("tiki.tpl");
