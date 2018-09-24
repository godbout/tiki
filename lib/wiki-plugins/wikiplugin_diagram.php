<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_diagram_info()
{
	$info = [
		'name' => tr('Diagram'),
		'documentation' => 'PluginDiagram',
		'description' => tr('Display mxGraph/Draw.io diagrams'),
		'prefs' => ['wikiplugin_diagram'],
		'iconname' => 'sitemap',
		'tags' => ['basic'],
		'introduced' => 19,
		'packages_required' => ['xorti/mxgraph-editor' => 'vendor/xorti/mxgraph-editor/mxClient.js'],
		'params' => [
			'fileId' => [
				'required' => false,
				'name' => tr('fileId'),
				'description' => tr('Id of the file in the file gallery. A xml file containing the graph model.'),
				'since' => '19.0',
				'filter' => 'int',
			],
		],
	];

	return $info;
}

function wikiplugin_diagram($data, $params)
{

	global $tikilib, $user, $page, $wikiplugin_included_page;

	if (! file_exists('vendor/xorti/mxgraph-editor/mxClient.min.js')) {
		Feedback::error(tr('To view diagrams Tiki needs the xorti/mxgraph-editor package. If you do not have permission to install this package, ask the site administrator.'));
		return;
	}

	$headerlib = $tikilib::lib('header');
	$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);

	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/mxClient.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/sanitizer/sanitizer.min.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Graph.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Format.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Shapes.js', true);

	$headerlib->add_css('.diagram hr {margin-top:0.5em;margin-bottom:0.5em}');

	$fileId = isset($params['fileId']) ? intval($params['fileId']) : 0;

	if ($fileId) {
		$fileGalleryLib = TikiLib::lib('filegal');
		$userLib = TikiLib::lib('user');
		$info = $fileGalleryLib->get_file($fileId);
		$data = $fileGalleryLib->getFileData($info);

		if ($data === false) {
			return;
		}
	}

	if (function_exists('simplexml_load_string')) {
		$doc = simplexml_load_string($data);
		if (empty($data) || $doc === false || $doc->getName() != 'mxGraphModel') {
			Feedback::error(tr("Tiki wasn't able to parse the Diagram. Please check the diagram XML data and structure."));
			return;
		}
	}

	static $id = 0;
	$id++;

	//checking if user can see edit button
	if (! empty($wikiplugin_included_page)) {
		$sourcepage = $wikiplugin_included_page;
	} else {
		$sourcepage = $page;
	}

	$smarty = TikiLib::lib('smarty');
	$smarty->assign('index', $id);
	$smarty->assign('graph_data', $data);
	$smarty->assign('graph_data_base64', base64_encode($data));
	$smarty->assign('sourcepage', $sourcepage);
	return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_diagram.tpl') . '~/np~';
}
