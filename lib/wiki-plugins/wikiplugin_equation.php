<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\VendorHelper;

function wikiplugin_equation_info()
{
	return [
		'name' => tra('Equation'),
		'documentation' => 'PluginEquation',
		'description' => tra('Render an equation written in LaTeX syntax as an image'),
		'prefs' => ['wikiplugin_equation'],
		'body' => tra('equation'),
		'iconname' => 'superscript',
		'introduced' => 2,
		'packages_required' => ['mathjax/mathjax' => VendorHelper::getAvailableVendorPath('mathjax', 'mathjax/mathjax/MathJax.js')],
	];
}

function wikiplugin_equation($data)
{
	$mathJaxJsFile = VendorHelper::getAvailableVendorPath('mathjax', 'mathjax/mathjax/MathJax.js');

	if (! $mathJaxJsFile) {
		Feedback::error(tr('To view equations Tiki needs the mathjax/mathjax package. If you do not have permission to install this package, ask the site administrator.'));
		return;
	}

	if (empty($data)) {
		return '';
	}

	$headerlib = TikiLib::lib('header');
	$headerlib->add_jsfile($mathJaxJsFile . '?config=TeX-AMS-MML_HTMLorMML', true);

	return '~np~' . $data . '~/np~';
}
