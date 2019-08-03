<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// This is about improving wikiplugin_footnote, no relation to the "footnote" feature
function prefs_footnote_list()
{
	return [
		'footnote_popovers' => [
			'name' => tra('Display footnote content in popover'),
			'description' => tra('When the mouse is over the footnote reference, show footnote content in a popover window.'),
			'type' => 'flag',
			'default' => 'y',
			'dependencies' => [
				'wikiplugin_footnote',
				'feature_jquery_ui'
			],
		],
	];
}
