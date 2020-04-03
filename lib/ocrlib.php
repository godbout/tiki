<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\FriendlyErrors;
use Tiki\Lib\Alchemy;
use Symfony\Component\Filesystem\Filesystem;

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

	/** @var int An attempt to OCR the file has been made, but was not successful */
	public const OCR_STATUS_STALLED = 4;
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
	public const PDF_MIME = ['application/pdf'];

	/** @var array All file types that will be available for OCRing */
	public $ocrMime = [];

	/** @var string The minimum version requirement of Tesseract that needs to be installed on the OS */
	private const TESSERACT_BINARY_VERSION = '3.5.1';

	public function setMimeTypes()
	{
		global $prefs;
		if (empty($prefs['ocr_enable']) || $prefs['ocr_enable'] === 'n') {
			return [];
		}
		$this->ocrMime = self::OCR_MIME_NATIVE;
		if (is_callable('imagepng')) {
			$this->ocrMime = array_merge(self::OCR_MIME_CONVERT, $this->ocrMime);
		}
		exec($prefs['ocr_pdfimages_path'] . ' -v', $output, $return);
		if ($return === 0) {
			$this->ocrMime = array_merge(self::PDF_MIME, $this->ocrMime);
		}
	}

	/**
	 * Produces the absolute file path of any command. Unix and Windows safe.
	 * @param $executable string	The file name you want to find the absolute path of
	 *
	 * @return string|null		The absolute file path or null on no command found
	 * @throws Exception		If no suitable command was found
	 * todo 			Find the correct exit code on Windows if the "where" does not find the command.
	 */

	public function whereIsExecutable(string $executable) : ?string
	{
		if (! is_callable('exec')) {
			throw new Exception('exec() is not enabled. Could not execute command.');
		}
		$executable = escapeshellarg($executable);
		$return = 1;
		if (function_exists('exec')) {
			exec('type -p ' . $executable . ' 2>&1', $output, $return);
		}
		if ($return === 1) {				// if "type" did not find the command on the system
			return null;
		} elseif ($return !== 0) {
			unset($output);
			exec('where ' . $executable . ' 2>&1', $output, $return); // windows command
		} elseif ($return !== 0) {
			unset($output);
			exec('which ' . $executable . ' 2>&1', $output, $return); // alternative unix command but relies on $PATH
			if ($return === 1) {			// if "which" did not find the command on the system
				return null;
			}
		} elseif ($return !== 0) {
			throw new Exception('There was no suitable system command found. Could not execute command');
		}
		if (empty($output[0])) {                // if for some reason there was no output, return null
			return null;
		}
		return $output[0];
	}

	/**
	 * Checks if a file  id can be processed or not.
	 *
	 * @throws Exception If the file is not suitable to be OCR'd, throw an exception
	 */
	public function checkFileGalID()
	{
		if (! $this->table('tiki_files')->fetchBool(['fileId' => $this->nextOCRFile])) {
			throw new Exception('The File ID specified does not exist.');
		}
	}

	/**
	 * Checks if all the dependencies for OCR have been satisfied.
	 *
	 * @throws Exception if one of the dependencies are not satisfied;
	 */

	public function checkOCRDependencies()
	{
		global $prefs;

		if ($prefs['ocr_enable'] !== 'y') {
			throw new Exception('Feature Disabled');
		}
		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			throw new Exception('Tesseract not installed in Packages.');
		}
		if (! $this->checkTesseractVersion()) {
			throw new Exception('Tesseract binary not found.');
		}
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

		$tesseract = $this->newTesseract();
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
		$tesseract = $this->newTesseract();
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
		return version_compare($this->getTesseractVersion(), self::TESSERACT_BINARY_VERSION, '>=');
	}


	/**
	 * @return array 3 character language codes installed with Tesseract Binary
	 */

	public function getTesseractLangs(): array
	{

		if (! class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
			return [];
		}
		$tesseract = $this->newTesseract();

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
	 * Change stalled flags back to pending.
	 *
	 * @return int Number of files changed from stalled to pending.
	 */

	public function releaseAllStalled(): int
	{
		$changes = $this->table('tiki_files')->updateMultiple(
			['ocr_state' => self::OCR_STATUS_PENDING],
			['ocr_state' => self::OCR_STATUS_STALLED]
		);

		return $changes->numrows;
	}

	/**
	 * Set $nextOCRFile with the fileId of the next file scheduled to be processed by the OCR engine.
	 */

	public function setNextOCRFile()
	{
		$db = $this->table('tiki_files');
		$conditions = ['ocr_state' => self::OCR_STATUS_PENDING];
		if ($this->nextOCRFile) {											// we always take a greater file id to avoid infinite loops
			$conditions['fileId'] = $db->GreaterThan($this->nextOCRFile);
		}

		$this->nextOCRFile = $db->fetchOne('fileId', $conditions, ['fileId' => 'ASC']);
	}

	/**
	 * Creates a new tesseract instance.
	 *
	 * @param null|string $fileName File path of file to OCR. Null if no file.
	 *
	 * @return TesseractOCR		A instance with all Tiki preferences applied.
	 */

	private function newTesseract(?string $fileName = null)
	{
		global $prefs;

		$tesseract = new TesseractOCR($fileName);
		if (! empty($prefs['ocr_tesseract_path'])) {
			$tesseract->executable($prefs['ocr_tesseract_path']);
		}
		return $tesseract;
	}

	/**
	 * Finds the languages that a file will/has been processed with.
	 *
	 * @param null|int $fileId null defaults to the current file being worked on, otherwise it uses the passed fileid.
	 *
	 * @return array List of file specific languages
	 */

	public function listFileLanguages(?int $fileId = null): array
	{
		global $prefs;
		if (! $fileId) {
			$fileId = $this->ocrIngNow;
		}
		$db = $this->table('tiki_files');
		// first set file level languages if they exist
		if (! empty($prefs['ocr_file_level']) && $prefs['ocr_file_level'] === 'y') {
			$langs = json_decode($this->table('tiki_files')->fetchOne('ocr_lang', ['fileId' => $fileId]));
		}
		// if no file level languages we look for gallery level language prefrences
		if (empty($langs)) {
			$galId = $db->fetchOne('galleryId', ['fileId' => $fileId]);
			$db = $this->table('tiki_file_galleries');
			$langs = json_decode($db->fetchOne('ocr_lang', ['galleryId' => $galId]));
			// if gallery does not have prefrences, we take a look at the master gallery for direction.
			if (empty($langs && $galId !== 1)) {
				$langs = json_decode($db->fetchOne('ocr_lang', ['galleryId' => 1]));
			}
		}
		// we fall back on Auto Detect if there are no preferences set
		if (empty($langs)) {
			$langs[] = 'osd';
		}
		return $langs;
	}

	/**
	 *
	 * OCR's a file set by $ocrIngNow. Intended to be used by a CLI command, as OCRing a large file may cause timeouts.
	 *
	 * @return string    Message detailing action performed.
	 * @throws Exception If a problem occurs while processing a file
	 */

	public function OCRfile()
	{

		if (! $this->nextOCRFile) {
			throw new Exception('No files to OCR');
		}

		// Set the database state to reflect that the next file in the queue has begun
		$this->table('tiki_files')->update(
			['ocr_state' => self::OCR_STATUS_PROCESSING],
			['fileId' => $this->nextOCRFile]
		);
		$this->setNextOCRFile();
		// Sets $ocrIngNow with the current file flagged as currently being processed.
		$this->ocrIngNow = $this->table('tiki_files')->fetchOne(
			'fileId',
			['ocr_state' => self::OCR_STATUS_PROCESSING]
		);

		$file = TikiLib::lib('filegal')->get_file($this->ocrIngNow);

		try {
			if ($file['data']) {
				/** @var tempFile string The file path of a temp file for processing */
				$tempFile = writeTempFile($file['data']);
			} else {
				global $prefs;
				$directory = $prefs['fgal_use_dir'];                // lets make sure there is a slash following the directory name
				if (substr($directory, -1) !== '/') {
					$directory = $directory . '/';
				}
				$fileContent = @file_get_contents($directory . $file['path']);
				if ($fileContent === false) {
					throw new Exception('Reading ' . $file['path'] . ' failed');
				}
				$tempFile = writeTempFile($fileContent);
				unset($fileContent);
			}

			// now that we have a temp file written to file, lets start processing it

			$filesystem = new Filesystem();
			if (in_array($file['filetype'], self::OCR_MIME_CONVERT)) {
				/** @var fileName string The path that the file can be read on the server in a format readable to Tesseract. */
				$fileName = writeTempFile('');
				unlink($fileName);
				if (! is_callable('imagepng')) {
					throw new Exception('Install GD to convert.');
				}
				imagepng(imagecreatefromstring(file_get_contents($tempFile)), $fileName);
			} elseif (in_array($file['filetype'], self::OCR_MIME_NATIVE)) {
				$fileName = $tempFile;
				$tempFile = null;                                // we zero this out so the file is not deleted later.
			} elseif (in_array($file['filetype'], self::PDF_MIME)) {
				Tikilib::lib('pdfimages');
				$image = new PdfImagesLib();
				$image->setBinaryPath();
				$image->setArgument('tiff');
				$fileName = writeTempFile(null, 'random'); // in this case we create a directory for writing files to.
				$image->setFilePaths($tempFile, $fileName);
				$image->run();
				unset($image);
			} else {                                                // fall back onto media alchemist if the file type is not otherwise convertible.
				if (! class_exists('MediaAlchemyst\Alchemyst')) {
					throw new Exception('Install Media Alchemist to convert.');
				}
				$alchemy = new Alchemy\AlchemyLib();
				// We create a empty temp file and then delete it, so we know its writable before passing to alchemy
				$fileName = writeTempFile('');
				unlink($fileName);
				if ($alchemy->convertToImage($tempFile, $fileName) === null) {
					throw new Exception('Media Alchemist unable to convert file');
				}
			}
			@$filesystem->remove($tempFile);                                    // now that we are done with the temp file, lets delete it.

			$langs = $this->listFileLanguages();

			if (is_dir($fileName)) {
				$OCRText = '';
				foreach (glob($fileName . '*.tif') as $tiffFile) {
					$OCRText .= ($this->newTesseract($tiffFile))->lang(...$langs)->run();
				}
			} else {
				$OCRText = ($this->newTesseract($fileName))->lang(...$langs)->run();
			}
			$OCRText = TikiFilter::get('striptags')->filter($OCRText);
			$this->table('tiki_files')->update(
				['ocr_data' => $OCRText],
				['fileId' => $this->ocrIngNow]
			);
			$unifiedsearchlib = TikiLib::lib('unifiedsearch');
			$unifiedsearchlib->invalidateObject('file', $this->ocrIngNow);
			$unifiedsearchlib->processUpdateQueue();
			// change the ocr state from processing to finished OCR'ing
			$this->ocrIngNow = $this->table('tiki_files')->update(
				['ocr_state' => self::OCR_STATUS_FINISHED],
				['fileId' => $this->ocrIngNow]
			);
		} catch (Exception $e) {
			@$filesystem->remove($fileName);
			@$filesystem->remove($tempFile);
			// Set the database flag to reflect that it is no longer processing but, still needs to be OCR'd
			$this->table('tiki_files')->update(
				['ocr_state' => self::OCR_STATUS_STALLED],
				['fileId' => $this->ocrIngNow]
			);
			throw new Exception($e->getMessage());
		}
		// if we had to create temp files, lets remove them.
		@$filesystem->remove($fileName);
	}
}
