<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use Tiki\Lib\Unoconv\UnoconvLib;

class PDFHelper
{

	/**
	 * Checks if a given mimetype is able to be converted to PDF.
	 *
	 * @param $mimeType
	 * @return bool
	 */
	public static function canConvertToPDF($mimeType)
	{

		return strpos($mimeType, 'application/vnd.openxmlformats-officedocument') !== false ||
			strpos($mimeType, 'application/vnd.ms') !== false ||
			$mimeType == 'application/msword' ||
			strpos($mimeType, 'application/vnd.oasis.opendocument.') !== false;
	}

	/**
	 * Converts a tiki file to PDF
	 *
	 * @param $file
	 * @return null|string
	 */
	public static function convertToPDF($file)
	{

		global $user, $tikidomain;

		if (! self::canConvertToPDF($file['filetype'])) {
			$error = 'File type not supported.';
			$message = sprintf("Failed to convert document %s (id: %s) to pdf. Error: %s", $file['filename'], $file['filename'], $error);
			$logsLib = \TikiLib::lib('logs');
			$logsLib->add_log('File', $message);
			return null;
		}

		$sourceFile = implode(DIRECTORY_SEPARATOR, ['temp', 'cache', $tikidomain, 'source_' . $file['fileId']]);
		$targetFile = implode(DIRECTORY_SEPARATOR, ['temp', 'cache', $tikidomain, 'target_' . $file['fileId'] . '.pdf']);
		file_put_contents($sourceFile, $file['data']);

		try {
			$unoconv = new UnoconvLib();
			$unoconv->convertFile($sourceFile, $targetFile);
		} catch (\Exception $e) {
			$convertFail = true;
			$message = sprintf("Failed to convert document %s (id: %s) to pdf. Error: %s", $file['filename'], $file['filename'], $e->getMessage());
			$logsLib = \TikiLib::lib('logs');
			$logsLib->add_log('Unoconv', $message);
		}

		unlink($sourceFile);

		if ((empty($targetFile) && file_exists($targetFile)) || $convertFail) {
			$message = tr('Failed to convert document %0 to pdf.', $file['filename']);

			$userlib = \TikiLib::lib('user');
			if ($userlib->user_has_permission($user, 'tiki_p_view_actionlog')) {
				$message .= ' ' . tr('Please check Action Log for more information.');
			} else {
				$message .= ' ' . tr('Please contact the administrator.');
			}

			$access = \TikiLib::lib('access');
			$access->display_error('', $message);
		}

		$content = file_get_contents($targetFile);
		unlink($targetFile);

		return $content;
	}
}
