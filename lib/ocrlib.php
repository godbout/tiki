<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: blacklistlib.php 66101 2018-04-19 18:03:14Z luciash $

use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\FriendlyErrors;


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
	public $ocrIngNow;
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

	/** @var string The minimum version requirement of Tesseract that needs to be installed on the OS */
	private const TESSERACT_BINARY_VERSION = '3.5.1';


	/**
	 * Set default values
	 *
	 * ocr constructor.
	 */
	public function __construct()
	{

		$this->setOCRNow();
		$this->nextOCRfile();
	}

	/**
	 * Sets $ocrIngNow with the current file flagged as currently being processed.
	 */
	private function setOCRNow(){

		$query = 'SELECT `fileId` FROM `tiki_files` WHERE `ocr_state` = ' . self::OCR_STATUS_PROCESSING . ' LIMIT 1';
		$result = $this->query($query, []);
		$result = $result->fetchRow();
		$this->ocrIngNow = $result['fileId'];
	}

	/**
	 * Checks if a file  id can be processed or not.
	 *
	 * @throws Exception If the file is not suitable to be OCR'd, throw an exception
	 */
	public function checkFileGalID(){

		$query = 'SELECT 1 FROM `tiki_files` WHERE `fileId` = ' . $this->nextOCRFile . ' LIMIT 1';
		$result = $this->query($query, []);
		if (!$result->numRows()){
			throw new Exception('The File ID specified does not exist.');
		}

	}

	/**
	 * Sets $nextOCRFile with the fileid of the next file scheduled to be processed by the OCR engine.
	 */
	private function nextOCRfile(){

		$query = 'SELECT `fileId` FROM `tiki_files` WHERE `ocr_state` = ' . self::OCR_STATUS_PENDING . ' LIMIT 1';
		$result = $this->query($query, []);
		$result = $result->fetchRow();
		$this->nextOCRFile = $result['fileId'];
	}

	/**
	 * Sets the database and class vars to reflect that an OCR has finished.
	 */
	private function finishedOCR(){
		// first change the ocr state from processing to finished OCR'ing
		$query = 'UPDATE `tiki_files` SET `ocr_state` = ' . self::OCR_STATUS_FINISHED . ' WHERE `tiki_files`.`fileId` = '.$this->ocrIngNow.';';
		$this->query($query);
		$this->setOCRNow();             // now update the class var to reflect the change in state (remove processing flag)
	}
	/**
	 * Sets set the flag to reflect that it is no longer processing but, still needs to be ORR'd
	 */
	private function unsuccessfulOCR(){
		// first change the ocr state from processing to finished OCR'ing
		$query = 'UPDATE `tiki_files` SET `ocr_state` = ' . self::OCR_STATUS_PENDING . ' WHERE `tiki_files`.`fileId` = '.$this->ocrIngNow.';';
		$this->query($query);
	}
	/**
	 * Sets the database and class vars to reflect that the next file in the queue has begun
	 * @return bool True if there is a file started, false if no file started.
	 */

	private function startOCR() : bool
	{
		if (!$this->nextOCRFile){
			return false;
		}
		// first update the the next file in queue to reflect that it is now processing
		$query = 'UPDATE `tiki_files` SET `ocr_state` = ' . self::OCR_STATUS_PROCESSING . ' WHERE `tiki_files`.`fileId` = '.$this->nextOCRFile.';';
		$this->query($query);
		$this->nextOCRfile();            // now update the class var to reflect the change in state (set next file in queue)
		$this->setOCRNow();
		return true;
	}

	/**
	 *
	 * Retrieves the number of files marked as waiting to be processed.
	 *
	 * @return int The number of queued files
	 */

	public function getOCRQueueCount() :int
	{
		$query = 'SELECT COUNT(*) FROM `tiki_files` WHERE `ocr_state` = ' . self::OCR_STATUS_PENDING;
		$result = $this->query($query, []);
		$result = $result->fetchRow();
		return (int) reset($result);
	}

	/**
	 * Checks if all the dependencies for OCR have been satisfied.
	 *
	 * @return bool True if all dependencies have been satisfied, false otherwise.
	 */

	public function checkOCRDependencies() : bool
	{
		global $prefs;

		if ($prefs['fgal_ocr_enable'] !== 'y')
		{
			return false;
		}
		if (!class_exists ('thiagoalessio\TesseractOCR\TesseractOCR'))
		{
			return false;
		}
		if (!$this->checkTesseractVersion())
		{
			return false;
		}
		return true;
	}

	/**
	 * Check if Tesseract binary is installed.
	 *
	 * @return bool false if Tesseract not installed or true otherwise
	 */

	private function checkTesseractInstalled() : bool
	{

		if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR'))
		{
			return false;
		}

		$tesseract = new TesseractOCR();
		$errors = new FriendlyErrors();

		try {
			$errors::checkTesseractPresence($tesseract->command->executable);
		}catch (Exception $e){
			return false;
		}
		return true;
	}

	/**
	 * Gets the binary tesseract version.
	 *
	 * @return string version number upon success, or empty string otherwise.
	 */
	public function getTesseractVersion() : string
	{
		if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR'))
		{
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
	public function checkTesseractVersion() : bool
	{
		return version_compare($this->getTesseractVersion(),self::TESSERACT_BINARY_VERSION, '>=');
	}


	/**
	 * @return array 3 character language codes installed with Tesseract Binary
	 */

	public function getTesseractLangs() : array
	{

		if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR'))
		{
			return [];
		}
		$tesseract = new TesseractOCR();

		if (!$this->checkTesseractInstalled()){
			return [];
		}

		return $tesseract->command->getAvailableLanguages();
	}

	/**
	 *
	 * OCR's a file set by $ocrIngNow. Intended to be used by a CLI command, as OCRing a large file may cause timeouts.
	 *
	 * @return string	Message detailing action performed.
	 * @throws Exception
	 */

	public function OCRfile() : string
	{

		if (! $this->startOCR()) {
			return ('No files to OCR');
		}

		$file = TikiLib::lib('filegal')->get_file($this->ocrIngNow);

		/** @var $fileName string The path that the file can be read on the server. */
		if ($file['data']) {
			$fileName = writeTempFile($file['data']);
		} else {
			$fileName = $file['path'];
		}
		try {
			$OCRText = (new TesseractOCR($fileName))->run();
			$query = 'UPDATE `tiki_files` SET `ocr_data` = \'' . $OCRText
				. '\' WHERE `tiki_files`.`fileId` = ' . $this->ocrIngNow . ';';
			$this->query($query);
			$unifiedsearchlib = \TikiLib::lib('unifiedsearch');
			$unifiedsearchlib->invalidateObject('file', $this->ocrIngNow);
			$unifiedsearchlib->processUpdateQueue();
			$finished = $this->ocrIngNow;
			$this->finishedOCR();

		} catch (Exception $e) {
			$this->unsuccessfulOCR();
			if ($file['data'])
			{
				unlink($fileName);
			}
			throw new Exception($e);
		}

		// if we had to create a temp file to read, delete it.
		if ($file['data']) {
			unlink($fileName);
		}
		return ("Finished OCR of fgal id $finished");
	}
}
