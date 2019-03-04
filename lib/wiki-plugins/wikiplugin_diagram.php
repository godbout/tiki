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
				'description' => tr('Id of the file in the file gallery. A xml file containing the graph model. Leave empty for more options.'),
				'since' => '19.0',
				'filter' => 'int',
			],
		],
	];

	return $info;
}

function wikiplugin_diagram($data, $params)
{

	global $tikilib, $user, $page, $wikiplugin_included_page, $tiki_p_upload_files;

	$filegallib = TikiLib::lib('filegal');

	if (! file_exists('vendor/xorti/mxgraph-editor/mxClient.min.js')) {
		Feedback::error(tr('To view diagrams Tiki needs the xorti/mxgraph-editor package. If you do not have permission to install this package, ask the site administrator.'));
		return;
	}

	$headerlib = $tikilib::lib('header');
	$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/deflate/pako.min.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/deflate/base64.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/jscolor/jscolor.js');
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/sanitizer/sanitizer.min.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/mxClient.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Graph.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Format.js', true);
	$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/grapheditor/js/Shapes.js', true);

	$headerlib->add_css('.diagram hr {margin-top:0.5em;margin-bottom:0.5em}');

	$fileId = isset($params['fileId']) ? intval($params['fileId']) : 0;

	if ($fileId) {
		$fileGalleryLib = TikiLib::lib('filegal');
		$userLib = TikiLib::lib('user');
		$file = \Tiki\FileGallery\File::id($fileId);
		$data = $file->getContents();

		if ($data === false) {
			return;
		}
	}

	$data = preg_replace('/\s+/', ' ', $data);
	static $diagramIndex = 0;
	++$diagramIndex;

	if (function_exists('simplexml_load_string')) {
		$doc = simplexml_load_string($data);
		if ($doc !== false && ($doc->getName() != 'mxGraphModel' && $doc->getName() != 'mxfile')) {
			Feedback::error(tr("Tiki wasn't able to parse the Diagram. Please check the diagram XML data and structure."));
			return;
		} elseif (empty($data) || $doc === false) {
			if ($tiki_p_upload_files != 'y') {
				return;
			}

			$label = tra('Create New Diagram');
			$page = htmlentities($page);
			$in = tr(" in ");

			$gals = $filegallib->list_file_galleries(0, -1, 'name_desc', $user);

			$galHtml = "<option value='0'>" . tr('Page (inline)') . "</option>";
			usort($gals['data'], function ($a, $b) {
				return strcmp(strtolower($a['name']), strtolower($b['name']));
			});
			foreach ($gals['data'] as $gal) {
				if ($gal['name'] != "Wiki Attachments" && $gal['name'] != "Users File Galleries") {
					$galHtml .= "<option value='" . $gal['id'] . "'>" . $gal['name'] . "</option>";
				}
			}

			return <<<EOF
		~np~
		<form id="newDiagram$diagramIndex" method="post" action="tiki-editdiagram.php">
			<p>
				<input type="submit" class="btn btn-primary btn-sm" name="label" value="$label" class="newSvgButton" />$in
				<select name="galleryId">
					$galHtml
				</select>
				<input type="hidden" name="newDiagram" value="1"/>
				<input type="hidden" name="page" value="$page"/>
				<input type="hidden" name="index" value="$diagramIndex"/>
			</p>
		</form>
		~/np~
EOF;
		}
	}

	//checking if user can see edit button
	if (! empty($wikiplugin_included_page)) {
		$sourcepage = $wikiplugin_included_page;
	} else {
		$sourcepage = $page;
	}

	//checking if user has edit permissions on the wiki page/file using the current permission library to obey global/categ/object perms
	if ($fileId) {
		$objectperms = Perms::get(['type' => 'file', 'object' => $fileId]);
	} else {
		$objectperms = Perms::get([ 'type' => 'wiki page', 'object' => $sourcepage ]);
	}

	if ($objectperms->edit) {
		$allowEdit = true;
	} else {
		$allowEdit = false;
	}

	$smarty = TikiLib::lib('smarty');
	$smarty->assign('index', $diagramIndex);
	$smarty->assign('graph_data', $data);
	$smarty->assign('graph_data_base64', base64_encode($data));
	$smarty->assign('sourcepage', $sourcepage);
	$smarty->assign('allow_edit', $allowEdit);
	$smarty->assign('file_id', $fileId);
	$smarty->assign('file_name', $file->name);

	return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_diagram.tpl') . '~/np~';
}
