<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$section = 'wiki page';
$section_class = "tiki_wiki_page manage";	// This will be body class instead of $section
require_once('tiki-setup.php');

include_once('lib/rankings/ranklib.php');

$access->check_feature(['feature_wiki', 'feature_wiki_rankings']);
$access->check_permission('tiki_p_view');

if (! isset($_REQUEST["limit"])) {
    $limit = 10;
} else {
    $limit = $_REQUEST["limit"];
}

if (isset($_REQUEST["categId"]) && $_REQUEST["categId"] > 0) {
    $smarty->assign('categIdstr', $_REQUEST["categId"]);
    $categs = explode(",", $_REQUEST["categId"]);
} else {
    $categs = [];
}
$smarty->assign('categId', $categs);

$allrankings = [
    [
    'name' => tra('Top pages'),
    'value' => 'wiki_ranking_top_pages'
    ],
    [
    'name' => tra('Last pages'),
    'value' => 'wiki_ranking_last_pages'
    ],
    [
    'name' => tra('Most-relevant pages'),
    'value' => 'wiki_ranking_top_pagerank'
    ],
    [
    'name' => tra('Top authors'),
    'value' => 'wiki_ranking_top_authors'
    ]
];

$smarty->assign('allrankings', $allrankings);

if (! isset($_REQUEST["which"])) {
    $which = 'wiki_ranking_top_pages';
} else {
    $which = $_REQUEST["which"];
}

$smarty->assign('which', $which);

$smarty->assign_by_ref('limit', $limit);

// Rankings:
// Top Pages
// Last pages
// Top Authors
$rankings = [];

$rk = $ranklib->$which($limit, $categs, $prefs['language']);
$rank["data"] = $rk["data"];
$rank["title"] = $rk["title"];
$rank["y"] = $rk["y"];
$rank["type"] = $rk["type"];
$rankings[] = $rank;

$smarty->assign_by_ref('rankings', $rankings);
$smarty->assign('rpage', 'tiki-wiki_rankings.php');

include_once('tiki-section_options.php');

// Display the template
$smarty->assign('mid', 'tiki-ranking.tpl');
$smarty->display("tiki.tpl");
