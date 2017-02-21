<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: wikiplugin_googleanalytics.php 57962 2016-03-17 20:02:39Z jonnybradley $

function wikiplugin_h5p_info()
{
	return array(
		'name' => tra('H5P'),
		'documentation' => 'PluginH5P',
		'description' => tra(''),
		'prefs' => array('wikiplugin_h5p'),
		'iconname' => 'html',
		'format' => 'html',
		'introduced' => 16,
		'params' => array(
			'fileId' => array(
				'required' => true,
				'name' => tra('File ID'),
				'description' => tr('The H5P file in a file gallery'),
				'since' => '17.0',
				'filter' => 'digits',
				'default' => '',
			),
		),
	);
}

function wikiplugin_h5p($data, $params)
{
	global $prefs;

	if (empty($params['fileId'])) {
		Feedback::error(tr('H5P Plugin:') . ' ' . tr('No fileID provided.'));
		return '';
	} else {
		$fileId = $params['fileId'];
	}

	$tiki_h5p_contents = TikiDb::get()->table('tiki_h5p_contents');

	$row = $tiki_h5p_contents->fetchFullRow(
		['file_id' => $fileId]
	);

	if (! isset($row['id'])) {
		Feedback::error(tr('H5P Plugin:') . ' ' . tr('Cannot find H5P content with fileId: %0.', $fileId));
		return '';
	}

	// Try to find content with $id.
	$core = \H5P_H5PTiki::get_h5p_instance('core');
	$content = $core->loadContent($row['id']);

	if (! $content) {
		Feedback::error(tr('H5P Plugin:') . ' ' . tr('Cannot find H5P content with id: %0.', $row['id']));
		return '';
	}

	if (is_string($content)) {
		// Return error message if the user has the correct cap
		return Perms::get()->h5p_edit ? $content : NULL;
	}

	$content['language'] = $prefs['language'];

	// Log view
	new H5P_Event('content', 'plugin',
		$content['id'],
		$content['title'],
		$content['library']['name'],
		$content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

	return TikiLib::lib('h5p')->addAssets($content);
}

