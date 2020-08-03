<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('tiki-setup.php');

/***
 * @var \TikiAccessLib  $access
 * @var \Smarty_Tiki    $smarty
 *
 */

$access->check_feature('wiki_keywords');
$access->check_permission('tiki_p_admin_wiki');

/**
 * @param        $page
 * @param string $keywords
 *
 * @throws Exception
 * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
 */
function set_keywords($page, $keywords = "")
{
    global $tikilib;

    $query = "UPDATE `tiki_pages` SET `keywords`=? WHERE `pageName`=? LIMIT 1";
    $bindvars = [ $keywords, $page ];
    $result = $tikilib->query($query, $bindvars);

    if ($result && $result->numRows()) {
        /***
        *
        * @var \UnifiedSearchLib $searchlib
        */
        $searchlib = TikiLib::lib('unifiedsearch');
        $searchlib->invalidateObject('wiki page', $page);
        $searchlib->processUpdateQueue();
    }

    return $result;
}

/**
 * @param $page
 * @return bool
 */
function get_keywords($page)
{
    global $tikilib;

    return $tikilib->get_page_info($page);
}

/**
 * @param int $limit
 * @param int $offset
 * @param string $page
 * @return array
 */
function get_all_keywords($limit = 0, $offset = 0, $page = "")
{
    global $tikilib;
    $query = "FROM `tiki_pages` WHERE `keywords` IS NOT NULL and `keywords` <> '' ";
    if ($page) {
        $query .= " and `pageName` LIKE ?";
        $bindvars = [ "%$page%" ];
    } else {
        $bindvars = [];
    }

    $ret = [
        'pages' => $tikilib->fetchAll("SELECT `keywords`, `pageName` as page " . $query, $bindvars, $limit, $offset),
        'cant' => $tikilib->getOne('SELECT COUNT(*) ' . $query, $bindvars),
    ];

    return $ret;
}

//Init variables for limit and offset
$limit = $prefs['maxRecords'];
$offset = 0;

//Check for offset, see if it's a multiple of the limit
//This is done to stop arbitrary offsets being entered
$offset = (int)$_REQUEST['offset'];

if ((isset($_REQUEST['save_keywords']) && isset($_REQUEST['new_keywords']) && isset($_REQUEST['page']) && $access->checkCsrf())
    || (isset($_REQUEST['remove_keywords']) && isset($_REQUEST['page']) && $access->checkCsrf(true))
    ) {
    //Set page and new_keywords var for both remove_keywords and
    //save_keywords actions at the same time
    (isset($_REQUEST['page'])) ? $page = $_REQUEST['page'] : $page = $_REQUEST['page'];
    (isset($_REQUEST['new_keywords'])) ? $new_keywords = $_REQUEST['new_keywords'] : $new_keywords = "";

    $result = set_keywords($page, $new_keywords);

    if ($result && $result->numRows()) {
        $msg = isset($_REQUEST['save_keywords'])
            ? tr('Keywords for page "%0" saved', htmlspecialchars($_REQUEST['page']))
            : tr('Keywords for page "%0" removed', htmlspecialchars($_REQUEST['page']));
        Feedback::success($msg);
    } else {
        Feedback::error(tr('Keywords were not updated'));
    }
}

if (isset($_REQUEST['page']) && ! $_REQUEST['remove_keywords']) {
    $page_keywords = get_keywords($_REQUEST['page']);

    $smarty->assign('edit_keywords', $page_keywords['keywords']);
    $smarty->assign('edit_keywords_page', $page_keywords['pageName']);
    $smarty->assign('edit_on', 'y');
}

if (isset($_REQUEST['q']) && ! $_REQUEST['remove_keywords'] && ! $_REQUEST['save_keywords']) {
    $existing_keywords = get_all_keywords($limit, $offset, $_REQUEST['q']);
    $smarty->assign('search_on', 'y');
    $smarty->assign('search_cant', $existing_keywords['cant']);
}

if (! isset($existing_keywords['cant'])) {
    $existing_keywords = get_all_keywords($limit, $offset);
}

if ($existing_keywords['cant'] > 0) {
    $smarty->assign('existing_keywords', $existing_keywords['pages']);

    $pages_cant = ceil($existing_keywords['cant'] / $limit);
    $smarty->assign('pages_cant', $pages_cant);
    $smarty->assign('offset', $offset);
}

$smarty->assign('mid', 'tiki-admin_keywords.tpl');
$smarty->display('tiki.tpl');
