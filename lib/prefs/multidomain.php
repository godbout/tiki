<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_multidomain_list()
{
	return [
		'multidomain_active' => [
			'name' => tra('Multi-domain'),
			'description' => tra('Enable domain names to be mapped to perspectives and simulate multiple domains hosted with the same Tiki installation.'),
			'perspective' => false,
			'help' => 'Multi-Domain',
			'type' => 'flag',
			'dependencies' => [
				'feature_perspective',
			],
			'default' => 'n',
		],
		'multidomain_config' => [
			'name' => tra('Multi-domain Configuration'),
			'description' => tra('Comma-separated values mapping the domain name to the perspective ID.'),
			'perspective' => false,
			'type' => 'textarea',
			'size' => 10,
			'hint' => tra('One domain per line with a comma separating it from the perspective ID. For example: tiki.org,1'),
			'default' => '',
		],
		'multidomain_switchdomain' => [
			'name' => tra('Switch domain when switching perspective'),
			'description' => tra('Important: Different domains have different login sessions and, even in the case of subdomains, an understanding of session cookies is needed to make it work'),
			'tags' => ['advanced'],
			'type' => 'flag',
			'dependencies' => [
				'feature_perspective', 'multidomain_active'
			],
			'default' => 'n',
		],
		'multidomain_default_not_categorized' => [
			'name' => tra('Default domain for non categorized content'),
			'description' => tra('The domain (hostname only) to be used when redirecting the user if he tries to read content that is not bound to the category/perspective configured for the current domain.'),
			'perspective' => false,
			'type' => 'text',
			'size' => 255,
			'default' => '',
		],
	];
}
