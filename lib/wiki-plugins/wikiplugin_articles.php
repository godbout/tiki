<?php
// $Id: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_articles.php,v 1.27.2.1 2007-12-27 21:46:42 pkdille Exp $
// Includes articles listing in a wiki page
// Usage:
// {ARTICLES(max=>3,topic=>topicId)}{ARTICLES}
//

function wikiplugin_articles_help() {
        $help = tra("Includes articles listing into a wiki page");
        $help .= "<br />";
        $help .= tra("~np~{ARTICLES(max=>3, topic=>topicName, topicId=>id, type=>type, categId=>Category parent ID, lang=>en, sort=>columnName_asc|columnName_desc), quiet=>y|n}{ARTICLES}~/np~");

        return $help;
}

function wikiplugin_articles_info() {
	return array(
		'name' => tra('Article List'),
		'documentation' => 'PluginArticles',
		'description' => tra('Includes a list of articles within the page.'),
		'prefs' => array( 'feature_articles', 'wikiplugin_articles' ),
		'params' => array(
			'max' => array(
				'required' => false,
				'name' => tra('Articles displayed'),
				'description' => tra('The amount of articles to display in the list.'),
				'filter' => 'digits',
			),
			'topic' => array(
				'required' => false,
				'name' => tra('Topic name'),
				'description' => tra('The name of the topic.'),
				'filter' => 'topicname',
			),
			'topicId' => array(
				'required' => false,
				'name' => tra('Topic ID'),
				'description' => tra('The ID of the topic.'),
				'filter' => 'digits',
			),
			'type' => array(
				'required' => false,
				'name' => tra('Type'),
				'description' => tra('?'),
			),
			'categId' => array(
				'required' => false,
				'name' => tra('Category ID'),
				'description' => tra('The ID of the category to list from.'),
				'filter' => 'digits',
			),
			'lang' => array(
				'required' => false,
				'name' => tra('Language'),
				'description' => tra('The article language to list.'),
				'filter' => 'lang',
			),
			'sort' => array(
				'required' => false,
				'name' => tra('Sort order'),
				'description' => tra('The column and order of the sort in columnName_asc or columnName_desc format.'),
				'filter' => 'word',
			),
			'quiet' => array(
				'required' => false,
				'name' => tra('Quiet'),
				'description' => tra('?'),
			),
		),
	);
}

function wikiplugin_articles($data,$params) {
	global $smarty, $tikilib, $prefs, $tiki_p_read_article, $tiki_p_articles_read_heading, $dbTiki, $pageLang;

	extract($params,EXTR_SKIP);
	if (($prefs['feature_articles'] !=  'y') || (($tiki_p_read_article != 'y') && ($tiki_p_articles_read_heading != 'y'))) {
		//	the feature is disabled or the user can't read articles, not even article headings
		return("");
	}
	if(!isset($max)) {$max='3';}
	if(!isset($start)) {$start='0';}

	// Addes filtering by topic if topic is passed
	if(isset($topic)) {
		$topicId = $tikilib->fetchtopicId($topic);
	}
	if(!isset($topicId))
		$topicId='';
	if(!isset($topic))
		$topic='';

	if (!isset($sort))
		$sort = 'publishDate_desc';

	// Adds filtering by type if type is passed
	if(!isset($type)) 
		$type='';

	if (!isset($categId))
		$categId = '';

	if (!isset($lang))
		$lang = '';

	if (!isset($quiet))
		$quiet = 'n';
	$smarty->assign_by_ref('quiet', $quiet);

	include_once("lib/commentslib.php");
	$commentslib = new Comments($dbTiki);
	
	$listpages = $tikilib->list_articles($start, $max, 'publishDate_desc', '', 0, $tikilib->now, 'admin', $type, $topicId, 'y', $topic, $categId, '', '', $lang);
 	if ($prefs['feature_multilingual'] == 'y') {
		global $multilinguallib;
		include_once("lib/multilingual/multilinguallib.php");
		$listpages['data'] = $multilinguallib->selectLangList('article', $listpages['data'], $pageLang);
	}

	for ($i = 0; $i < count($listpages["data"]); $i++) {
		$listpages["data"][$i]["parsed_heading"] = $tikilib->parse_data($listpages["data"][$i]["heading"]);
		$comments_prefix_var='article:';
		$comments_object_var=$listpages["data"][$i]["articleId"];
		$comments_objectId = $comments_prefix_var.$comments_object_var;
		$listpages["data"][$i]["comments_cant"] = $commentslib->count_comments($comments_objectId);
		//print_r($listpages["data"][$i]['title']);
	}
	global $artlib; require_once ('lib/articles/artlib.php');

// Unsure of reasoning, but Ive added a isset around here for when Articles plugin is called
// multiple times on a page. - Damian aka Damosoft
	If (isset($artlib)) {
        $topics = $artlib->list_topics();
        $smarty->assign_by_ref('topics', $topics);}

	If (isset($artlib)) {
        $type = $artlib->list_types();
        $smarty->assign_by_ref('type', $type);}		
		
	// If there're more records then assign next_offset
	$smarty->assign_by_ref('listpages', $listpages["data"]);

	return "~np~ ".$smarty->fetch('tiki-view_articles.tpl')." ~/np~";
	//return str_replace("\n","",$smarty->fetch('tiki-view_articles.tpl')); // this considers the hour in the header like a link
}
?>
