<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Composer;

use Composer\Script\Event;

class Autoload
{
	public static function change(Event $event)
	{
		$composer = $event->getComposer();
		$vendors = $composer->getConfig()->get('vendor-dir');

		if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
			$vendors .= DIRECTORY_SEPARATOR;
		}

		$repoManager = $composer->getRepositoryManager()->getLocalRepository();
		$package = $repoManager->findPackages('blueimp/jquery-file-upload');

		if (! empty($package[0])) {
			$autoload = $package[0]->getAutoload();
			if (! empty($autoload['classmap'])) {
				foreach ($autoload['classmap'] as $key => $path) {
					if ($path == 'server/php/UploadHandler.php') {
						unset($autoload['classmap'][$key]);
						break;
					}
				}
				$package[0]->setAutoload($autoload);
			}
		}

		// Write changes to local repository file vendor_bundled/vendor/composer/installed.json
		$repoManager->write();
	}
}
