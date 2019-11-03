<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery;

use TikiLib;
use Feedback;
use JitFilter;

/**
 * A basic file representation in Tiki. Includes params needed to store back contents
 * in database. Includes methods to retrieve and store/replace contents of the file
 * regardless of underlying storage method.
 * Two basic ways of using it:
 * 1. File::id(fileId) - load existing file from database.
 * 2. new File(fileInfo) - create a new file or load a file out of an info array from db
 */
class File
{
	public $param = [
		"fileId" 	=> 0,
		"galleryId" 	=> 1,
		"name"		=> "",
		"description"	=> "",
		"created" 	=> 0,
		"filename" 	=> "",
		"filesize" 	=> 0,
		"filetype" 	=> "",
		"data" 		=> "",
		"user"	 	=> "",
		"author" 	=> "",
		"hits" 		=> 0,
		"maxhits"	=> 0,
		"lastDownload" 	=> 0,
		"votes" 	=> 0,
		"points" 	=> 0,
		"path" 		=> "",
		"reference_url" => "",
		"is_reference" 	=> false,
		"hash" 		=> "",
		"metadata" => "",
		"search_data" 	=> "",
		"lastModif" 	=> 0,
		"lastModifUser" => "",
		"lockedby" 	=> "",
		"comment"	=> "",
		"archiveId"	=> 0,
		"deleteAfter" 	=> 0,
		"backlinkPerms"	=> "",
		"ocr_state" => null,
	];
	private $exists = false;
	private $wrapper = null;

	function __construct($params = [])
	{
		global $mimetypes;
		include_once(__DIR__ . '/../../../mime/mimetypes.php');

		$this->setParam('filetype', $mimetypes["txt"]);
		$this->setParam('name', tr("New File"));
		$this->setParam('description', tr("New File"));
		$this->setParam('filename', tr("New File"));

		$this->init($params);
	}

	function __get($name) {
		return $this->getParam($name);
	}

	function __isset($name) {
		return isset($this->param[$name]);
	}

	static function filename($filename = "")
	{
		$tikilib = TikiLib::lib('tiki');

		$id = $tikilib->getOne("SELECT fileId FROM tiki_files WHERE filename = ? AND archiveId  < 1", [$filename]);

		if (! empty($id)) {
			return self::id($id);
		}

		//always use ->exists() to check if the file was found, if the above is returned, a file was found, if below, there wasent
		$me = new self();
		$me->setParam('filename', $filename);
		return $me;
	}

	/**
	 * Facade method to instantiate a File object based on the db fileId
	 */
	static function id($id = 0)
	{
		$me = new self(TikiLib::lib("filegal")->get_file((int)$id));
		return $me;
	}

	function clone() {
		$params = $this->getParams();
		unset($params['fileId']);
		$params['created'] = 0;
		return new self($params);
	}

	function init($params) {
		foreach ($params as $key => $val) {
			$this->setParam($key, $val);
		}
		if ($this->getParam('created') > 0) {
			$this->exists = true;
		}
	}

	function validateDraft($draft) {
		foreach ($draft->getParams() as $key => $val) {
			$this->setParam($key, $val);
		}
		if ($this->replaceContents($draft->getContents())) {
			$saveHandler = new SaveHandler($this);
			$saveHandler->validateDraft();
		}
	}

	function setParam($param = "", $value)
	{
		$this->param[$param] = $value;
		return $this;
	}

	function getParam($param = "")
	{
		return $this->param[$param];
	}

	function getParams() {
		return $this->param;
	}

	/**
	 * Retrieve parameters to be saved in files db table.
	 */
	function getParamsForDB() {
		return array_filter($this->param, function($key){
			return $key != 'backlinkPerms';
		}, ARRAY_FILTER_USE_KEY);
	}

	function archive($archive = 0)
	{
		$archives = $this->listArchives();
		return self::id($archives[$archive]['id']);
	}

	function archiveFromLastModif($lastModif)
	{
		foreach ($this->listArchives() as $archive) {
			if ($archive['lastModif'] == $lastModif) {
				return $archive;
			}
		}
	}

	function data()
	{
		return $this->getParam('data');
	}

	function exists()
	{
		return $this->exists;
	}

	function listArchives()
	{
		$archives = TikiLib::lib("filegal")->get_archives((int)$this->getParam('fileId'));
		$archives = \array_reverse($archives['data']);
		return $archives;
	}

	function replace($data, $type = null, $name = null, $filename = null, $resizex = null, $resizey = null) {
		global $user, $prefs, $jitRequest;

		$user = (! empty($user) ? $user : 'Anonymous');

		if ($type) {
			$this->setParam('filetype', $type);
		}
		if ($name) {
			$this->setParam('name', $name);
		}
		if ($filename) {
			$this->setParam('filename', $filename);
		}
		if (($jitRequest instanceof JitFilter) && ! empty($jitRequest->ocr_state->int())) {
			$this->setParam('ocr_state', $jitRequest->ocr_state->int());
		}

		if ($data && !$this->replaceContents($data)) {
			// Do not replace with empty file as could be updating properties only
			return false;
		}

		$result = (new Manipulator\Validator($this))->run();
		if (! $result) {
			// uploaded file was saved to folder already (eg by jquery upload),
			// so we need to remove it again or we'll have tons of
			// unreferenced junk files in the folder
			$this->galleryDefinition()->delete($this);
			return false;
		}

		(new Manipulator\ImageTransformer($this))->run(['width' => $resizex, 'height' => $resizey]);
		(new Manipulator\MetadataExtractor($this))->run();

		$saveHandler = new SaveHandler($this);
		return $saveHandler->save();
	}

	function replaceQuick($data) {
		global $user;
		if (!$this->replaceContents($data)) {
			return false;
		}
		$this->setParam('lastModifUser', $user);
		TikiLib::lib('filegal')->update_file($this->fileId, $this->getParamsForDB());
	}

	function delete()
	{
		TikiLib::lib("filegal")->remove_file($this->param);
	}

	function diffLatestWithArchive($archive = 0)
	{
		include_once(__DIR__ . "/../../../diff/Diff.php");

		$textDiff = new \Text_Diff(
			self::id($this->getParam('fileId'))
			->archive($archive)
			->data(),
			$this->data()
		);

		return $textDiff->getDiff();
	}

	/**
	 * Get gallery definition object for this file.
	 */
	function galleryDefinition() {
		return TikiLib::lib('filegal')->getGalleryDefinition($this->getParam('galleryId'));
	}

	/**
	 * Get file wrapper object responsible for accessing the underlying storage.
	 * Ensures unique filename is available for new files if underlying storage
	 * requires it. Ensures data/path db parameters are sane.
	 * @see FileWrapper\WrapperInterface for supported methods.
	 */
	function getWrapper() {
		if ($this->wrapper !== null) {
			return $this->wrapper;
		}
		$definition = $this->galleryDefinition();
		$this->setParam('path', $definition->uniquePath($this));
		if ($this->getParam('path')) {
			$this->setParam('data', '');
		}
		$this->wrapper = $definition->getFileWrapper($this);
		return $this->wrapper;
	}

	/**
	 * Retrieve file contents as a string.
	 */
	function getContents() {
		return $this->getWrapper()->getContents();
	}

	/**
	 * Replace file contents from a string. Prepares the params to be later saved in db.
	 */
	function replaceContents($data) {
		$wrapper = $this->getWrapper();
		try {
			$wrapper->replaceContents($data);
		} catch (FileWrapper\WriteException $e) {
			Feedback::error($e->getMessage());
			return false;
		}
		foreach ($wrapper->getStorableContent() as $key => $val) {
			$this->setParam($key, $val);
		}
		return true;
	}
}
