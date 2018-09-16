<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_wysiwyg_list()
{

	return [
		// TODO: Replace feature_wysiwyg and wysiwyg_optional with a single tri-state preference (allowing either just normal editor (default), just WYSIWYG or both) to clarify and avoid misinterpretation
		'wysiwyg_optional' => [
			'name' => tra('Full WYSIWYG editor is optional'),
			'type' => 'flag',
			'description' => tra('If WYSIWYG is optional, the wiki text editor is also available. Otherwise only the WYSIWYG editor is used.'),
			'dependencies' => [
				'feature_wysiwyg',
			],
			'warning' => tra('Switching between HTML and wiki formats can cause problems for some pages.'),
			'default' => 'y',
		],

		'wysiwyg_default' => [
			'name' => tra('Full WYSIWYG editor is displayed by default'),
			'description' => tra('If both the WYSIWYG editor and the text editor are available, the WYSIWYG editor is used by default, for example, when creating new pages'),
			'type' => 'flag',
			'dependencies' => [
				'wysiwyg_optional',
			],
			'default' => 'y',
		],
		'wysiwyg_memo' => [
			'name' => tra('Reopen with the same editor'),
			'type' => 'flag',
			'description' => tra('Ensures the editor last used to edit a page or item is used for the next edit as the default.'),
			'dependencies' => [
				'feature_wysiwyg',
			],
			'default' => 'y',
		],

		// FIXME: This is not actually a WYSIWYG preference. See https://sourceforge.net/p/tikiwiki/mailman/tikiwiki-devel/thread/F2DE8896807BF045932776107E2E783D3505E26D%40CT20SEXCHP02.FONCIERQC.INTRA/#msg36170373
		'wysiwyg_wiki_parsed' => [
			'name' => tra("Support Tiki's \"wiki syntax\" in HTML pages"),
			'description' => tra('This allows a mixture of wiki syntax and HTML in the code of wiki pages where HTML is allowed.'),
			'type' => 'flag',
			'dependencies' => [
				'feature_wiki_allowhtml',
			],
			'default' => 'y',
		],

		// This preference is called "htmltowiki" because it involves conversion of the HTML code CKeditor handles to "wiki syntax" (Tiki's syntax)... although it equally involves the opposite conversion.
		'wysiwyg_htmltowiki' => [
			'name' => tra('Use Wiki syntax in WYSIWYG'),
			'description' => tra('Causes parsed text areas based on wiki syntax when not in WYSIWYG mode to keep using Tiki syntax, instead of HTML as the WYSIWYG editor uses by default. Sometimes referred to as a "visual wiki".'),
			'hint' => tra('Using wiki syntax in WYSIWYG mode will limit toolbar to wiki tools'),
			'type' => 'flag',
			'dependencies' => [
				'feature_wysiwyg',
			],
			// Should probably be (re)flagged as experimental. Chealer 2018-01-04
			'warning' => tra('Existing wiki pages remain in HTML, unless they are converted to non-WYSIWYG and back to WYSIWYG (one by one).') .
				' ' . tra('CKeditor offers possibilities which may not be expressible in Tiki syntax.') . ' See issue #6518 for example',
			'default' => 'y',
		],
		'wysiwyg_toolbar_skin' => [
			'name' => tra('Full WYSIWYG editor skin'),
			'type' => 'list',
			'help' => 'http://ckeditor.com/addons/skins/all',
			'options' => [
				'moono' => tra('Moono (Default)'),
				'kama' => tra('Kama'),
				'bootstrapck' => tra('Bootstrap CK'),
				'minimalist' => tra('Minimalist'),
				'office2013' => tra('Office 2013'),
			],
			'default' => 'moono',
		],
		'wysiwyg_fonts' => [
			'name' => tra('Typefaces'),
			'description' => tra('List of font names separated by semi-colons (";")'),
			'type' => 'textarea',
			'size' => '3',
			'default' => 'sans serif;serif;monospace;Arial;Century Gothic;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana',
		],
		'wysiwyg_inline_editing' => [
			'name' => tra('Inline WYSIWYG editor'),
			'description' => tra('Seamless inline editing. Uses CKEditor 4. Inline editing enables editing pages without a context switch. The editor is embedded in the wiki page. When used on pages in wiki format, a conversion from HTML to wiki format is required'),
			'help' => 'Wiki Inline Editing',
			'type' => 'flag',
			'default' => 'n',
			'dependencies' => [
				'feature_wysiwyg',
			],
			'tags' => ['experimental'],
		],
		'wysiwyg_extra_plugins' => [
			'name' => tra('Extra plugins'),
			'hint' => tra('List of plugin names (separated by,)'),
			'description' => tra('In Tiki, CKEditor uses the "standard" package in which some plugins are disabled by default that are available in the "full" package.<br>See http://ckeditor.com/presets for a comparison of which plugins are enabled as standard.'),
			'type' => 'textarea',
			'size' => '1',
			'default' => 'bidi,colorbutton,divarea,find,font,justify,pagebreak,showblocks,smiley',
		],
	];
}
