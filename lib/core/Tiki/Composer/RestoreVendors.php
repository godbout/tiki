<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Composer;

use Composer\Script\Event;

class RestoreVendors
{

	public static function restore(Event $event)
	{
		$composer = $event->getComposer();
		$vendors = $composer->getConfig()->get('vendor-dir');

		if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
			$vendors .= DIRECTORY_SEPARATOR;
		}

		$repoManager = $composer->getRepositoryManager()->getLocalRepository();
		$package = $repoManager->findPackages('plotly/plotly.js');

		if (!file_exists($vendors . 'plotly/plotly.js/dist/plotly-basic.min.js') && !empty($package[0])) {
			$repoManager->removePackage($package[0]);
		}
	}
}
