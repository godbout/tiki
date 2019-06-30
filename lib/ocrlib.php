<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\FriendlyErrors;
use Tiki\Lib\Alchemy;


/**
 *
 * A Group of functions related to OCR processing, indexing & accounting
 *
 * Class ocr
 */
class ocrLib extends TikiLib
{

	/**
	 * @var int the fileid of the file currently being OCR'd
	 */
	private $ocrIngNow;
	/**
	 * @var int fileid of the next file flagged to be processed by the OCR engine.
	 */
	public $nextOCRFile;

	/** @var int The file has been placed in a queue to be OCR'd */
	public const OCR_STATUS_PENDING = 3;
	/** @var int The file is marked as currently being OCR'd */
	public const OCR_STATUS_PROCESSING = 2;
	/** @var int The file has been OCR'd and no further action is required */
	public const OCR_STATUS_FINISHED = 1;
	/** @var null This file will not be OCR'd */
	public const OCR_STATUS_SKIP = null;

	/** @var array The mime types natively supported by Tesseract */
	public const OCR_MIME_NATIVE = ['image/jpeg', 'image/png', 'image/bmp', 'image/tiff', 'image/x-portable-anymap'];

	/** @var array image types that can be handled with Tiki image handling */
	public const OCR_MIME_CONVERT = ['image/gif'];

	/** @var array Extra file types that alchemy brings to process */
	public const ALCHEMY_MIME = ['application/pdf'];

	/** @var array All file types that will be available for OCRing */
	public $ocrMime = self::OCR_MIME_NATIVE;

	/** @var string The minimum version requirement of Tesseract that needs to be installed on the OS */
	private const TESSERACT_BINARY_VERSION = '3.5.1';



	public function __construct()
	{

		// if alchemy is available, update ocrMime with new file types.
		if (class_exists('MediaAlchemyst\Alchemyst')) {
			$this->ocrMime = array_merge(self::OCR_MIME_NATIVE, self::ALCHEMY_MIME,self::OCR_MIME_CONVERT);
		}

	}

	/**
	 * Checks if a file  id can be processed or not.
	 *
	 * @throws Exception If the file is not suitable to be OCR'd, throw an exception
	 */
	public function checkFileGalID()
	{

		$query = 'SELECT 1 FROM `tiki_files` WHERE `fileId` = '
			. $this->nextOCRFile . ' LIMIT 1';
		$result = $this->query($query, []);
		if (! $result->numRows()) {
			throw new Exception('The File ID specified does not exist.');
		}

	}

	/**
	 * Checks if all the dependencies for OCR have been satisfied.
	 *
	 * @return bool True if all dependencies have been satisfied, false otherwise.
	 */

	public function checkOCRDependencies(): bool
	{
		global $prefs;

		if ($prefs['fgal_ocr_enable'] !== 'y') {
			return false;
		}
		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			return false;
		}
		if (! $this->checkTesseractVersion()) {
			return false;
		}
		return true;
	}

	/**
	 * Check if Tesseract binary is installed.
	 *
	 * @return bool false if Tesseract not installed or true otherwise
	 */

	private function checkTesseractInstalled(): bool
	{

		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			return false;
		}

		$tesseract = new TesseractOCR();
		$errors = new FriendlyErrors();

		try {
			$errors::checkTesseractPresence($tesseract->command->executable);
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Gets the binary tesseract version.
	 *
	 * @return string version number upon success, or empty string otherwise.
	 */
	public function getTesseractVersion(): string
	{
		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			return '';
		}
		$tesseract = new TesseractOCR();
		if ($this->checkTesseractInstalled()) {
			return $tesseract->command->getTesseractVersion();
		}
		return '';
	}

	/**
	 * Checks if the binary tesseract version is sufficient.
	 *
	 * @return bool True if version is sufficient, false otherwise
	 */
	public function checkTesseractVersion(): bool
	{
		return version_compare(
			$this->getTesseractVersion(), self::TESSERACT_BINARY_VERSION, '>='
		);
	}


	/**
	 * @return array 3 character language codes installed with Tesseract Binary
	 */

	public function getTesseractLangs(): array
	{

		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			return [];
		}
		$tesseract = new TesseractOCR();

		if (! $this->checkTesseractInstalled()) {
			return [];
		}

		return $tesseract->command->getAvailableLanguages();
	}

	/**
	 * Change processing flags back to pending.
	 *
	 * @return int Number of files changed from processing to pending.
	 */

	public function releaseAllProcessing(): int
	{
		$changes = $this->table('tiki_files')->updateMultiple(
			['ocr_state' => self::OCR_STATUS_PENDING],
			['ocr_state' => self::OCR_STATUS_PROCESSING]
		);

		return $changes->numrows;
	}

	/**
	 *
	 * OCR's a file set by $ocrIngNow. Intended to be used by a CLI command, as OCRing a large file may cause timeouts.
	 *
	 * @return string    Message detailing action performed.
	 * @throws Exception
	 */

	public function OCRfile()
	{

		if (! $this->nextOCRFile) {
			throw Exception('No files to OCR');
		}

		// Set the database state to reflect that the next file in the queue has begun
		$this->table('tiki_files')->update(
			['ocr_state' => self::OCR_STATUS_PROCESSING],
			['fileId' => $this->nextOCRFile]
		);
		// Set $nextOCRFile with the fileid of the next file scheduled to be processed by the OCR engine.
		$this->nextOCRFile = $this->table('tiki_files')->fetchOne(
			'fileId', ['ocr_state' => self::OCR_STATUS_PENDING]
		);
		// Sets $ocrIngNow with the current file flagged as currently being processed.
		$this->ocrIngNow = $this->table('tiki_files')->fetchOne(
			'fileId', ['ocr_state' => self::OCR_STATUS_PROCESSING]
		);

		$file = TikiLib::lib('filegal')->get_file($this->ocrIngNow);

		if ($file['data']) {
			/** @var tempFile string The file path of a temp file for processing */
			$tempFile = writeTempFile($file['data']);;
		}else {
			$tempFile = writeTempFile(file_get_contents($file['path']));
		}

		if (in_array($file['filetype'], self::OCR_MIME_CONVERT)) {
			/** @var fileName string The path that the file can be read on the server in a format readable to Tesseract. */
			$fileName = writeTempFile('');
			unlink($fileName);
			if (! is_callable('imagepng')) {
				@unlink($tempFile);
				throw new Exception('Install GD to convert.');
			}
			imagepng(imagecreatefromstring(file_get_contents($tempFile)), $fileName);
		}elseif (in_array($file['filetype'], self::OCR_MIME_NATIVE)){
			$fileName = $tempFile;
			$tempFile = null;								// we zero this out so the file is not deleted later.
		}else {												// fall back onto media alchemist if the file type is not otherwise convertible.
			try {
				if (! class_exists('MediaAlchemyst\Alchemyst')) {
					throw new Exception('Install Media Alchemist to convert.');
				}
				$alchemy = new Alchemy\AlchemyLib();
				// We create a empty temp file and then delete it, so we know its writable before passing to alchemy
				$fileName = writeTempFile('');
				unlink($fileName);
				if ($alchemy->convertToImage($tempFile, $fileName) === null) {
					throw new Exception('\'Media Alchemist unable to convert file.');
				}

			} catch (Exception $e) {						// if media alchemist is not installed;
				$this->table('tiki_files')->update(
					['ocr_state' => self::OCR_STATUS_SKIP],
					['fileId' => $this->ocrIngNow]
				);
				@unlink($fileName);
				@unlink($tempFile);
				throw new Exception('failed');
			}
		}
		@unlink($tempFile);									// now that we are done with the temp file, lets delete it.

		try {
			$OCRText = (new TesseractOCR($fileName))->run();
			$OCRText = TikiFilter::get('striptags')->filter($OCRText);
			$this->table('tiki_files')->update(
				['ocr_data' => $OCRText], ['fileId' => $this->ocrIngNow]
			);
			$unifiedsearchlib = TikiLib::lib('unifiedsearch');
			$unifiedsearchlib->invalidateObject('file', $this->ocrIngNow);
			$unifiedsearchlib->processUpdateQueue();
			// change the ocr state from processing to finished OCR'ing
			$this->ocrIngNow = $this->table('tiki_files')->update(
				['ocr_state' => self::OCR_STATUS_FINISHED],
				['fileId' => $this->ocrIngNow]
			);

		} catch (Throwable $e) {
			// Set the database flag to reflect that it is no longer processing but, still needs to be OCR'd
			$this->table('tiki_files')->update(
				['ocr_state' => self::OCR_STATUS_PENDING],
				['fileId' => $this->ocrIngNow]
			);
			if ($file['data']) {
				@unlink($fileName);
			}
			throw new Exception($e);
		}

		// if we had to create a temp file to read, delete it.
		if ($file['data']) {
			unlink($fileName);
		}
	}
}
