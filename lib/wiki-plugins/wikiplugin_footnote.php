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
	$allFootnotes = &$context->footnotes;
	$smarty = TikiLib::lib('smarty');

	if (! isset($allFootnotes['lists'])) {   // if this is the first time the script has run, initialise
		$allFootnotes['count'] = 0;
		$allFootnotes['lists'] = [];    // data for general footnotes
	}

	$data = trim($data);
	if (empty($data)) {
		return '<sup>' . tra('Error: Empty footnote') . '</sup>';
	}

	$allFootnotes['count']++;                      // keep a record of how many times footones is called to generate unique id's

	// Create an array of classes to be applied
	$classes = (isset($params['class'])) ? explode(' ', trim($params["class"])) : [];

	//set the current list to create
	$list = '.def.';                            // Set the default to illegal class name to prevent conflicts
	foreach ($classes as $class) {
		if (isset($allFootnotes['lists'][$class])) {
			$list = $class;                         // set list the the first occurrence, if there happens to be multiplies.
			break;
		}
	}

	// wow, thats a mouth full, lets make it a little more pleasing to the eyes.
	$classFootnotes = &$allFootnotes['lists'][$list];

	// set the current number of list entries
	$listNum = count($classFootnotes) + 1;

	$classFootnotes[$listNum]['unique'] = $allFootnotes['count'];
	$classFootnotes[$listNum]['class'] = implode(' ', $classes);

	$classFootnotes[$listNum]['data'] = TikiLib::lib('parser')->parse_data_plugin($data, true);


	$smarty->assign('uniqueId', $classFootnotes[$listNum]['unique']);
	$smarty->assign('listNum', $listNum);
	$smarty->assign('class', $classFootnotes[$listNum]['class']);
	return $smarty->fetch('templates/wiki-plugins/wikiplugin_footnote.tpl');
}
