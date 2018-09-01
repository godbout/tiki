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

	global $tikilib;

	if (! file_exists('vendor/xorti/mxgraph-editor/mxClient.min.js')) {
		Feedback::error(tr('To view diagrams Tiki needs the xorti/mxgraph-editor package. If you do not have permission to install this package, ask the site administrator.'));
		return;
	}

	$headerlib = $tikilib::lib('header');
	$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);

	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/mxClient.min.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/sanitizer/sanitizer.min.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Graph.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Format.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Shapes.js', true);

	$headerlib->add_css('.diagram hr {margin-top:0.5em;margin-bottom:0.5em}');

	$smarty = TikiLib::lib('smarty');

	$fileId = isset($params['fileId']) ? intval($params['fileId']) : 0;

	if ($fileId) {
		$fileGalleryLib = TikiLib::lib('filegal');
		$userLib = TikiLib::lib('user');
		$info = $fileGalleryLib->get_file($fileId);
		if (! $info || ! $userLib->user_has_perm_on_object($user, $info['fileId'], 'file', 'tiki_p_download_files')) {
			return;
		}

		$data = $info['data'];
	}

	$smarty->assign('graph_data', $data);
	$smarty->assign('graph_data', $data);
	$data = $smarty->fetch('wiki-plugins/wikiplugin_diagram.tpl');

	return $data;
}
