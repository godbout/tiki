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
	 * @param $fileId
	 * @return null|string
	 * @throws \Exception
	 */
	public static function convertToPDF($fileId)
	{
		global $user, $tikidomain;

		$userLib = \TikiLib::lib('user');
		$file = \Tiki\FileGallery\File::id($fileId);
		if (! $file->exists() || ! $userLib->user_has_perm_on_object($user, $file->fileId, 'file', 'tiki_p_download_files')) {
			return null;
		}

		if (! self::canConvertToPDF($file->filetype)) {
			$error = 'File type not supported.';
			$message = sprintf("Failed to convert document %s (id: %s) to pdf. Error: %s", $file->filename, $fileId, $error);
			$logsLib = \TikiLib::lib('logs');
			$logsLib->add_log('File', $message);
			return null;
		}

		$sourceFile = $file->getWrapper()->getReadableFile();
		$targetFile = implode(DIRECTORY_SEPARATOR, ['temp', 'cache', $tikidomain, 'target_' . $fileId . '.pdf']);

		try {
			$convertFail = false;

			$unoconv = new UnoconvLib();
			$unoconv->convertFile($sourceFile, $targetFile);
		} catch (\Exception $e) {
			$convertFail = true;
			$message = sprintf("Failed to convert document %s (id: %s) to pdf. Error: %s", $file->filename, $fileId, $e->getMessage());
			$logsLib = \TikiLib::lib('logs');
			$previous = $e->getPrevious();
			while ($previous) {
				$logsLib->add_log('Unoconv', $previous->getMessage());
				$previous = $previous->getPrevious();
			}
			$logsLib->add_log('Unoconv', $message);
		}

		if ((empty($targetFile) && file_exists($targetFile)) || $convertFail) {
			$message = tr('Failed to convert document %0 to pdf.', $file->filename);

			$userlib = \TikiLib::lib('user');
			if ($userlib->user_has_permission($user, 'tiki_p_view_actionlog')) {
				$message .= ' ' . tr('Please check Action Log for more information.');
			} else {
				$message .= ' ' . tr('Please contact the administrator.');
			}

			$access = \TikiLib::lib('access');
			$access->display_error('', $message);
		}

		return $targetFile;
	}
}
