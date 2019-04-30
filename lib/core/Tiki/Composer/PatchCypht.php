<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Composer;

use Composer\Script\Event;

class PatchCypht
{
	public static function setup(Event $event)
	{

		$cypht = __DIR__ . '/../../../../cypht/';
		$vendors = $event->getComposer()->getConfig()->get('vendor-dir');

		if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
			$vendors .= DIRECTORY_SEPARATOR;
		}

		// setup stock version with missing files and symlinks
		copy($cypht.'hm3.ini', $vendors.'jason-munro/cypht/hm3.ini');
		chdir($vendors.'jason-munro/cypht/modules');
		if (! file_exists('tiki')) {
			symlink('../../../../../cypht/modules/tiki', 'tiki');
		}
		chdir('../');
		if (! file_exists('vendor')) {
			symlink('../../../../vendor_bundled/vendor', 'vendor');
		}
		chdir($cypht.'../');

		// generate storage dirs
		if (! is_dir('temp/cypht')) {
			umask(0);
			mkdir('temp/cypht', 0777);
			mkdir('temp/cypht/app_data', 0777);
			mkdir('temp/cypht/attachments', 0777);
			mkdir('temp/cypht/users', 0777);
		}

		// generate Cypht config
		`cd {$vendors}jason-munro/cypht; php scripts/config_gen.php`;

		// copy site.js and site.css
		copy($vendors.'jason-munro/cypht/site/site.js', $cypht.'site.js');
		copy($vendors.'jason-munro/cypht/site/site.css', $cypht.'site.css');

		// js custom pacthes
		$js = file_get_contents($cypht.'site.js');
		$js = str_replace("url: ''", "url: 'cypht/ajax.php'", $js);
		$js = str_replace("xhr.open('POST', window.location.href)", "xhr.open('POST', 'cypht/ajax.php')", $js);
		$js = preg_replace("#^.*/\* swipe event handler \*/#s", "", $js);
		file_put_contents($cypht.'site.js', $js);

		// copy stock assets
		`cp -rp {$vendors}jason-munro/cypht/modules/smtp/assets {$cypht}modules/smtp/`;
		`cp -rp {$vendors}jason-munro/cypht/modules/themes/assets {$cypht}modules/themes/`;
	}
}
