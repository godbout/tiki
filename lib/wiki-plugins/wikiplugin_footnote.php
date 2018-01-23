<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_footnote_info()
{
	return [
		'name' => tra('Footnote'),
		'documentation' => 'PluginFootnote',
		'description' => tra('Create automatically numbered footnotes (together with PluginFootnoteArea)'),
		'prefs' => ['wikiplugin_footnote'],
		'body' => tra('The footnote'),
		'iconname' => 'superscript',
		'filter' => 'wikicontent',
		'format' => 'html',
		'introduced' => 3,
		'params' => [
			'class' => [
				'required' => false,
				'name' => tra('Class'),
				'description' => tra('Add class to footnotearea'),
				'since' => '14.0',
				'default' => '',
				'filter' => 'alnumspace',
				'accepted' => tra('Valid CSS class'),
			],
		]
	];
}

/**
 * @param string $data
 * @param $params
 * @param int $offset
 * @param WikiParser_Parsable $context
 * @return string
 * @throws Exception
 * 
 * @see wikiplugin_footnotearea()
 */
function wikiplugin_footnote($data, $params, $offset, $context)
{
	/** @var int $globalId Globally unique number of the next footnote, used for intra-document (anchor) links */
	static $globalId = 1;
	
	$footnotes = &$context->footnotes;
	$smarty = TikiLib::lib('smarty');

	$data = trim($data);
	if (empty($data)) {
		return '<sup>' . tra('Error: Empty footnote') . '</sup>';
	}

	// Create an array of classes to be applied
	$classes = (isset($params['class'])) ? explode(' ', trim($params["class"])) : [];

	// set the current number of list entries
	$footnote = ['displayed' => false];
	$footnote['class'] = implode(' ', $classes);
	$footnote['data'] = TikiLib::lib('parser')->parse_data_plugin($data, true);
	$footnote['globalId'] = $globalId;
	$globalId++;
	
	$footnotes[] = $footnote;

	$smarty->assign('listNum', count($footnotes));
	$smarty->assign('footnote', $footnote);
	return $smarty->fetch('templates/wiki-plugins/wikiplugin_footnote.tpl');
}
