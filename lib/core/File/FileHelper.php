<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use TikiLib;

/**
 * Class FileHelper
 * Generic FileHelper which includes logic
 * @package Tiki\File
 */
class FileHelper
{
	const FILE_DISPLAY_TEMPLATE_FOLDER = 'file_displays/';

	/**
	 * Function used to retrieve the file template display based on the file type
	 * Data is also passed by reference to parse its information if needed
	 *
	 * @param $file
	 * @param $data
	 * @param bool $injectDependencies If we want to retrieve only the file name, we can prevent injecting the Javascript files
	 * @return string
	 * @throws \Exception
	 */
	public static function getDisplayTemplate($file, &$data, $injectDependencies = false)
	{
		$accesslib = TikiLib::lib('access');
		$headerlib = Tikilib::lib('header');
		$template = '';

		if (DiagramHelper::isDiagram($file['fileId'])) {
			if ($injectDependencies) {
				if (! DiagramHelper::isPackageInstalled()) {
					$accesslib->display_error('tiki-display.php', tr('To view diagrams Tiki needs the xorti/mxgraph-editor package. If you do not have permission to install this package, ask the site administrator.'));
				}

				$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);
				$headerlib->add_jsfile('vendor/xorti/mxgraph-editor/drawio/webapp/js/app.min.js', true);
			}

			$data = DiagramHelper::parseData($data);
			$template = 'diagram.tpl';
		}

		return self::FILE_DISPLAY_TEMPLATE_FOLDER . $template;
	}
}
