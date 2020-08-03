<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'newsletters';
require_once('tiki-setup.php');
$access->check_feature('feature_newsletters');

global $nllib;
include_once('lib/newsletters/nllib.php');
$auto_query_args = [
    'nlId',
    'offset',
    'sort_mode',
    'find'
];
if (! isset($_REQUEST["nlId"])) {
    $_REQUEST["nlId"] = 0;
}
$smarty->assign('nlId', $_REQUEST["nlId"]);
$perms = Perms::get(['type' => 'newsletter', 'object' => $_REQUEST['nlId']]);

if ($perms->admin_newsletters != 'y') {
    Feedback::errorPage(['mes' => tr('You do not have the permission that is needed to use this feature'),
                         'errortype' => 401]);
}
$defaultArticleClipRange = 3600 * 24; // one day
if ($_REQUEST["nlId"]) {
    $info = $nllib->get_newsletter($_REQUEST["nlId"]);
    if (empty($info)) {
        Feedback::errorPage(tr('Newsletter does not exist'));
    }
    $update = "";
    $info["articleClipTypes"] = unserialize($info["articleClipTypes"]);
    $info["articleClipRangeDays"] = $info["articleClipRange"] / 3600 / 24;
} else {
    $info = [
        'nlId' => 0,
        'name' => '',
        'description' => '',
        'allowUserSub' => 'y',
        'allowAnySub' => 'n',
        'unsubMsg' => 'y',
        'validateAddr' => 'y',
        'allowTxt' => 'n',
        'allowArticleClip' => 'n',
        'autoArticleClip' => 'n',
        'emptyClipBlocksSend' => 'n',
        'articleClipRange' => $defaultArticleClipRange,
        'articleClipRangeDays' => $defaultArticleClipRange / 3600 / 24,
        'articleClipTypes' => []
    ];
    $update = "y";
}
$smarty->assign('info', $info);
if (isset($_REQUEST["remove"]) && $access->checkCsrf(true)) {
    $result = $nllib->remove_newsletter($_REQUEST["remove"]);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Newsletter removed'));
    } else {
        Feedback::error(tr('Newsletter not removed'));
    }
}
if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    if (isset($_REQUEST["allowUserSub"]) && $_REQUEST["allowUserSub"] == 'on') {
        $_REQUEST["allowUserSub"] = 'y';
    } else {
        $_REQUEST["allowUserSub"] = 'n';
    }
    if (isset($_REQUEST["allowAnySub"]) && $_REQUEST["allowAnySub"] == 'on') {
        $_REQUEST["allowAnySub"] = 'y';
    } else {
        $_REQUEST["allowAnySub"] = 'n';
    }
    if (isset($_REQUEST["unsubMsg"]) && $_REQUEST["unsubMsg"] == 'on') {
        $_REQUEST["unsubMsg"] = 'y';
    } else {
        $_REQUEST["unsubMsg"] = 'n';
    }
    if (isset($_REQUEST["validateAddr"]) && $_REQUEST["validateAddr"] == 'on') {
        $_REQUEST["validateAddr"] = 'y';
    } else {
        $_REQUEST["validateAddr"] = 'n';
    }
    if (isset($_REQUEST["allowTxt"]) && $_REQUEST["allowTxt"] == 'on') {
        $_REQUEST["allowTxt"] = 'y';
    } else {
        $_REQUEST["allowTxt"] = 'n';
    }
    if (isset($_REQUEST["allowArticleClip"]) && $_REQUEST["allowArticleClip"] == 'on') {
        $_REQUEST["allowArticleClip"] = 'y';
    } else {
        $_REQUEST["allowArticleClip"] = 'n';
    }
    if (isset($_REQUEST["autoArticleClip"]) && $_REQUEST["autoArticleClip"] == 'on') {
        $_REQUEST["autoArticleClip"] = 'y';
    } else {
        $_REQUEST["autoArticleClip"] = 'n';
    }
    if (isset($_REQUEST["emptyClipBlocksSend"]) && $_REQUEST["emptyClipBlocksSend"] == 'on') {
        $_REQUEST["emptyClipBlocksSend"] = 'y';
    } else {
        $_REQUEST["emptyClipBlocksSend"] = 'n';
    }
    if (isset($_REQUEST["articleClipRangeDays"]) && $_REQUEST["articleClipRangeDays"]) {
        $articleClipRange = 3600 * 24 * $_REQUEST["articleClipRangeDays"];
    } else {
        $articleClipRange = $defaultArticleClipRange; // default to 1 day
    }
    if (! empty($_REQUEST["articleClipTypes"])) {
        $articleClipTypes = serialize($_REQUEST["articleClipTypes"]);
    } else {
        $articleClipTypes = '';
    }
    if (! isset($_REQUEST['frequency'])) {
        $_REQUEST['frequency'] = 0;
    }
    $sid = $nllib->replace_newsletter(
        $_REQUEST["nlId"],
        $_REQUEST["name"],
        $_REQUEST["description"],
        $_REQUEST["allowUserSub"],
        $_REQUEST["allowAnySub"],
        $_REQUEST["unsubMsg"],
        $_REQUEST["validateAddr"],
        $_REQUEST["allowTxt"],
        $_REQUEST["frequency"],
        $_REQUEST["author"],
        $_REQUEST["allowArticleClip"],
        $_REQUEST["autoArticleClip"],
        $articleClipRange,
        $articleClipTypes,
        $_REQUEST["emptyClipBlocksSend"]
    );

    if ($sid) {
        Feedback::success(tr('Newsletter created or modified'));
    } else {
        Feedback::error(tr('Newsletter not created or modified'));
    }

    $info = [
        'nlId' => 0,
        'name' => '',
        'description' => '',
        'allowUserSub' => 'y',
        'allowAnySub' => 'n',
        'unsubMsg' => 'y',
        'validateAddr' => 'y',
        'allowTxt' => 'n'
    ];
    $smarty->assign('nlId', 0);
    $smarty->assign('info', $info);
    $cookietab = 1;
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
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
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $nllib->list_newsletters(
    $offset,
    $maxRecords,
    $sort_mode,
    $find,
    $update,
    [
        'tiki_p_admin_newsletters'
    ]
);

// get Article types for clippings feature
$articleTypes = [];
if ($prefs["feature_articles"] == 'y') {
    $artlib = TikiLib::lib('art');
    $allTypes = $artlib->list_types();
    foreach ($allTypes as $t) {
        $articleTypes[] = $t["type"];
    }
}
$smarty->assign('articleTypes', $articleTypes);

$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_newsletters.tpl');
$smarty->display("tiki.tpl");
