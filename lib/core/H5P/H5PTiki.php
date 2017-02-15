<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class H5P_H5PTiki
 *
 * Main wrapper class around the H5P library
 *
 */
class H5P_H5PTiki implements H5PFrameworkInterface
{

	// properties for table objects
	private $tiki_h5p_contents = null;
	private $tiki_h5p_contents_libraries = null;
	private $tiki_h5p_libraries = null;
	private $tiki_h5p_libraries_cachedassets = null;
	private $tiki_h5p_libraries_libraries = null;
	private $tiki_h5p_libraries_languages = null;

	function __construct()
	{
		// just as an example of how to get a table objects
		// docs here https://dev.tiki.org/Database+Access

		$tikiDb = TikiDb::get();

		$this->tiki_h5p_contents = $tikiDb->table('tiki_h5p_contents');
		$this->tiki_h5p_contents_libraries = $tikiDb->table('tiki_h5p_contents_libraries');
		$this->tiki_h5p_libraries = $tikiDb->table('tiki_h5p_libraries');
		$this->tiki_h5p_libraries_cachedassets = $tikiDb->table('tiki_h5p_libraries_cachedassets');
		$this->tiki_h5p_libraries_libraries = $tikiDb->table('tiki_h5p_libraries_libraries');
		$this->tiki_h5p_libraries_languages = $tikiDb->table('tiki_h5p_libraries_languages');
		// possibly others needed?
	}

	/**
	 * Get the different instances of the core components.
	 *
	 * @param string $component
	 * @return \H5PCore|\H5PContentValidator|\H5PExport|\H5PStorage|\H5PValidator|\H5P_H5PTiki
	 */
	public static function get_h5p_instance($component)
	{
		static $interface, $core;

		global $prefs, $tikiroot, $tikipath;    // we still have lots of hairy globals

		if (is_null($interface)) {
			// Setup Core and Interface components that are always needed
			$interface = new \H5P_H5PTiki();

			$path = 'storage/public/';

			$core = new \H5PCore($interface,
				$tikipath . $path,   // Where the extracted content files will be stored
				$tikiroot . $path,     // URL of the previous option
				$prefs['language'],        // TODO: Map proper language code from Tiki to H5P langs
				false               // TODO: Later: Add option for enabling generation of exports? Not sure if this will be needed in Tiki since we already have the .h5p file.
			);

			// This is more of a development option to prevent JS and CSS from being combined. TODO: Remove later
			$core->aggregateAssets = false;
		}

		// Determine which component to return
		switch ($component) {
			case 'validator':
				return new \H5PValidator($interface, $core);
			case 'storage':
				return new \H5PStorage($interface, $core);
			case 'contentvalidator':
				return new \H5PContentValidator($interface, $core);
			case 'export':
				return new \H5PExport($interface, $core);
			case 'interface':
				return $interface;
			case 'core':
				return $core;
		}
	}

	/**
	 * Returns info for the current platform
	 *
	 * @return array
	 *   An associative array containing:
	 *   - name: The name of the platform, for instance "Wordpress"
	 *   - version: The version of the platform, for instance "4.0"
	 *   - h5pVersion: The version of the H5P plugin/module
	 */
	public function getPlatformInfo()
	{
		$TWV = new TWVersion();

		return array(
			'name' => 'Tiki',
			'version' => $TWV->version,
			'h5pVersion' => H5PLib::VERSION,
		);
	}

	/**
	 * Fetches a file from a remote server using HTTP GET
	 *
	 * @param $url
	 * @param $data
	 * @return string The content (response body). null if something went wrong
	 */
	public function fetchExternalData($url, $data)
	{
		// TODO: Implement fetchExternalData() method.
	}

	/**
	 * Set the tutorial URL for a library. All versions of the library is set
	 *
	 * @param string $machineName
	 * @param string $tutorialUrl
	 */
	public function setLibraryTutorialUrl($machineName, $tutorialUrl)
	{
		// TODO: Implement setLibraryTutorialUrl() method.
	}

	/**
	 * Show the user an error message
	 *
	 * @param string $message
	 *   The error message
	 */
	public function setErrorMessage($message)
	{
		if (Perms::get()->h5p_edit) {
			// possibly needs 'session' as the method param if the error happens asychronously
			Feedback::error(tra($message));
		}
	}

	/**
	 * Show the user an information message
	 *
	 * @param string $message
	 *  The error message
	 */
	public function setInfoMessage($message)
	{
		if (Perms::get()->h5p_edit) {
			Feedback::success(tra($message));
		}
	}

	/**
	 * Translation function
	 *
	 * @param string $message
	 *  The english string to be translated.
	 * @param array $replacements
	 *   An associative array of replacements to make after translation. Incidences
	 *   of any key in this array are replaced with the corresponding value. Based
	 *   on the first character of the key, the value is escaped and/or themed:
	 *    - !variable: inserted as is
	 *    - @variable: escape plain text to HTML
	 *    - %variable: escape text and theme as a placeholder for user-submitted
	 *      content
	 * @return string Translated string
	 * Translated string
	 */
	public function t($message, $replacements = array())
	{
		return tr($message, $replacements);    // TODO convert messages to use %0 etc placeholders?
	}

	/**
	 * Get the Path to the last uploaded h5p
	 *
	 * @param string $setDir
	 *   Set the dir insted of using an auto generated one.
	 * @return string
	 *   Path to the folder where the last uploaded h5p for this session is located.
	 */
	public function getUploadedH5pFolderPath($setDir = null)
	{
		static $dir;

		if ($setDir !== null) {
			$dir = $setDir;
		}
		if (is_null($dir)) {
			$core = self::get_h5p_instance('core');
			$dir = $core->fs->getTmpPath();
		}

		return $dir;
	}

	/**
	 * Get the path to the last uploaded h5p file
	 *
	 * @param string $setPath
	 *   Set the path insted of using an auto generated one.
	 * @return string
	 *   Path to the last uploaded h5p
	 */
	public function getUploadedH5pPath($setPath = null)
	{
		static $path;

		if ($setPath !== null) {
			$path = $setPath;
		}
		if (is_null($path)) {
			$core = self::get_h5p_instance('core');
			$path = $core->fs->getTmpPath() . '.h5p';
		}

		return $path;
	}

	/**
	 * Get a list of the current installed libraries
	 *
	 * @return array
	 *   Associative array containing one entry per machine name.
	 *   For each machineName there is a list of libraries(with different versions)
	 */
	public function loadLibraries()
	{
		$res = $this->tiki_h5p_libraries->fetchAll(
			['id', 'name', 'title', 'major_version', 'minor_version', 'patch_version', 'runnable', 'restricted'],
			[],
			-1,
			0,
			['title' => 'ASC', 'major_version' => 'ASC', 'minor_version' => 'ASC']
		);

		$libraries = [];
		foreach ($res as $library) {
			$libraries[$library['name']][] = $library;
		}

		return $libraries;
	}

	/**
	 * Returns the URL to the library admin page
	 *
	 * @return string
	 *   URL to admin page
	 */
	public function getAdminUrl()
	{
		// TODO: What is this for?
		return TikiLib::tikiUrl('tiki-admin.php?page=h5p');
	}

	/**
	 * Get id to an existing library.
	 * If version number is not specified, the newest version will be returned.
	 *
	 * @param string $machineName
	 *   The librarys machine name
	 * @param int $majorVersion
	 *   Optional major version number for library
	 * @param int $minorVersion
	 *   Optional minor version number for library
	 * @return int
	 *   The id of the specified library or FALSE
	 */
	public function getLibraryId($machineName, $majorVersion = null, $minorVersion = null)
	{
		$conditions = [
			'name' => $machineName,
			'major_version' => $majorVersion,
			'minor_version' => $minorVersion,
		];

		$orderby = [];

		if ($majorVersion !== null) {
			$conditions['major_version'] = $majorVersion;
			$orderby[] = ['major_version' => 'desc'];
		}
		if ($minorVersion !== null) {
			$conditions['minor_version'] = $minorVersion;
			$orderby[] = ['minor_version' => 'desc'];
		}
		$orderby[] = ['patch_version' => 'desc'];

		return $this->tiki_h5p_libraries->fetchOne(
			'id',
			$conditions
		);
	}

	/**
	 * Get file extension whitelist
	 *
	 * The default extension list is part of h5p, but admins should be allowed to modify it
	 *
	 * @param boolean $isLibrary
	 *   TRUE if this is the whitelist for a library. FALSE if it is the whitelist
	 *   for the content folder we are getting
	 * @param string $defaultContentWhitelist
	 *   A string of file extensions separated by whitespace
	 * @param string $defaultLibraryWhitelist
	 *   A string of file extensions separated by whitespace
	 *
	 * @return string
	 */
	public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
	{
		global $prefs;

		return $prefs['h5p_whitelist'] . ($isLibrary ? ' ' . $defaultLibraryWhitelist : '');
	}

	/**
	 * Is the library a patched version of an existing library?
	 *
	 * @param object $library
	 *   An associative array containing:
	 *   - machineName: The library machineName
	 *   - majorVersion: The librarys majorVersion
	 *   - minorVersion: The librarys minorVersion
	 *   - patchVersion: The librarys patchVersion
	 * @return boolean
	 *   TRUE if the library is a patched version of an existing library
	 *   FALSE otherwise
	 */
	public function isPatchedLibrary($library)
	{
		$operator = $this->isInDevMode() ? '<=' : '<';

		$result = $this->tiki_h5p_libraries->fetchCount([
			'name' => $library['machineName'],
			'majorVersion' => $library['majorVersion'],
			'minorVersion' => $library['minorVersion'],
			'patchVersion' => $this->tiki_h5p_libraries->expr("$$ $operator ?", [$library['patchVersion']]),
		]);

		return !empty($result);
	}

	/**
	 * Is H5P in development mode?
	 *
	 * @return boolean
	 *  TRUE if H5P development mode is active
	 *  FALSE otherwise
	 */
	public function isInDevMode()
	{
		global $prefs;

		return $prefs['h5p_dev_mode'] === 'y';
	}

	/**
	 * Is the current user allowed to update libraries?
	 *
	 * @return boolean
	 *  TRUE if the user is allowed to update libraries
	 *  FALSE if the user is not allowed to update libraries
	 */
	public function mayUpdateLibraries()
	{
		return Perms::get()->h5p_admin;    // Do we need a separate perm for update? Or h5p_edit maybe?
	}

	/**
	 * Store data about a library
	 *
	 * Also fills in the libraryId in the libraryData object if the object is new
	 *
	 * @param array $libraryData
	 *   Associative array containing:
	 *   - libraryId: The id of the library if it is an existing library.
	 *   - title: The library's name
	 *   - machineName: The library machineName
	 *   - majorVersion: The library's majorVersion
	 *   - minorVersion: The library's minorVersion
	 *   - patchVersion: The library's patchVersion
	 *   - runnable: 1 if the library is a content type, 0 otherwise
	 *   - fullscreen(optional): 1 if the library supports fullscreen, 0 otherwise
	 *   - embedTypes(optional): list of supported embed types
	 *   - preloadedJs(optional): list of associative arrays containing:
	 *     - path: path to a js file relative to the library root folder
	 *   - preloadedCss(optional): list of associative arrays containing:
	 *     - path: path to css file relative to the library root folder
	 *   - dropLibraryCss(optional): list of associative arrays containing:
	 *     - machineName: machine name for the librarys that are to drop their css
	 *   - semantics(optional): Json describing the content structure for the library
	 *   - language(optional): associative array containing:
	 *     - languageCode: Translation in json format
	 * @param bool $new
	 * @return
	 */
	public function saveLibraryData(&$libraryData, $new = true)
	{
		global $prefs, $user;

		$preloadedJs = $this->pathsToCsv($libraryData, 'preloadedJs');
		$preloadedCss = $this->pathsToCsv($libraryData, 'preloadedCss');
		$dropLibraryCss = '';

		if (isset($libraryData['dropLibraryCss'])) {
			$libs = array();
			foreach ($libraryData['dropLibraryCss'] as $lib) {
				$libs[] = $lib['machineName'];
			}
			$dropLibraryCss = implode(', ', $libs);
		}

		$embedTypes = '';
		if (isset($libraryData['embedTypes'])) {
			$embedTypes = implode(', ', $libraryData['embedTypes']);
		}
		if (!isset($libraryData['semantics'])) {
			$libraryData['semantics'] = '';
		}
		if (!isset($libraryData['fullscreen'])) {
			$libraryData['fullscreen'] = 0;
		}
		if ($new) {
			$libraryId = $this->tiki_h5p_libraries->insert([
				'name' => $libraryData['machineName'],
				'title' => $libraryData['title'],
				'major_version' => $libraryData['majorVersion'],
				'minor_version' => $libraryData['minorVersion'],
				'patch_version' => $libraryData['patchVersion'],
				'runnable' => $libraryData['runnable'],
				'fullscreen' => $libraryData['fullscreen'],
				'embed_types' => $embedTypes,
				'preloaded_js' => $preloadedJs,
				'preloaded_css' => $preloadedCss,
				'drop_library_css' => $dropLibraryCss,
				'semantics' => $libraryData['semantics'],
			]);

			$libraryData['libraryId'] = $libraryId;

			/*			if ($libraryData['runnable']) {
							if (!$prefs['h5p_first_runnable_saved'] == 0) {    // what does this do? Drupal only?
								TikiLib::lib('tiki')->set_preference('h5p_first_runnable_saved', 1);
							}
						}
			*/
		} else {
			$this->tiki_h5p_libraries->update([
				'title' => $libraryData['title'],
				'patch_version' => $libraryData['patchVersion'],
				'runnable' => $libraryData['runnable'],
				'fullscreen' => $libraryData['fullscreen'],
				'embed_types' => $embedTypes,
				'preloaded_js' => $preloadedJs,
				'preloaded_css' => $preloadedCss,
				'drop_library_css' => $dropLibraryCss,
				'semantics' => $libraryData['semantics'],
			],
				['id' => $libraryData['libraryId']]
			);

			$this->deleteLibraryDependencies($libraryData['libraryId']);
		}

		// Log library successfully installed/upgraded
		new H5P_Event('library', ($new ? 'create' : 'update'),
			NULL, NULL,
			$libraryData['machineName'], $libraryData['majorVersion'] . '.' . $libraryData['minorVersion']);

		$this->tiki_h5p_libraries_languages->deleteMultiple(['library_id', $libraryData['libraryId']]);

		if (isset($libraryData['language'])) {
			foreach ($libraryData['language'] as $languageCode => $languageJson) {
				$id = $this->tiki_h5p_libraries_languages->insert([
					'library_id' => $libraryData['libraryId'],
					'language_code' => $languageCode,
					'language_json' => $languageJson,
				]);
				// TODO error checking?
			}
		}
	}

	/**
	 * Convert list of file paths to csv (from the WP implementation)
	 *
	 * @param array $libraryData
	 *  Library data as found in library.json files
	 * @param string $key
	 *  Key that should be found in $libraryData
	 * @return string
	 *  file paths separated by ', '
	 */
	private function pathsToCsv($libraryData, $key)
	{
		if (isset($libraryData[$key])) {
			$paths = array();
			foreach ($libraryData[$key] as $file) {
				$paths[] = $file['path'];
			}
			return implode(', ', $paths);
		}
		return '';
	}

	/**
	 * Insert new content.
	 *
	 * @param array $content
	 *   An associative array containing:
	 *   - id: The content id
	 *   - params: The content in json format
	 *   - library: An associative array containing:
	 *     - libraryId: The id of the main library for this content
	 * @param int $contentMainId
	 *   Main id for the content if this is a system that supports versions
	 *
	 * @return mixed
	 */
	public function insertContent($content, $contentMainId = null)
	{
		return $this->updateContent($content, $contentMainId);
	}

	/**
	 * Update old content.
	 *
	 * @param array $content
	 *   An associative array containing:
	 *   - id: The content id
	 *   - params: The content in json format
	 *   - library: An associative array containing:
	 *     - libraryId: The id of the main library for this content
	 * @param int $contentMainId
	 *   Main id for the content if this is a system that supports versions
	 */
	public function updateContent($content, $contentMainId = null)
	{
		global $user;

		$data = array(
			'updated_at' => current_time('mysql', 1),
			'title' => $content['title'],
			'parameters' => $content['params'],
			'embed_type' => 'div', // TODO: Determine from library?
			'library_id' => $content['library']['libraryId'],
			'filtered' => '',
			'disable' => $content['disable'],
		);

		if (!isset($content['id'])) {
			// Insert new content
			$data['created_at'] = $data['updated_at'];
			$data['user_id'] = $user;

			$content['id'] = $this->tiki_h5p_contents->insert($data);
			$event_type = 'create';
		} else {
			// Update existing content
			$this->tiki_h5p_contents->update(
				$data,
				['id' => $content['id']]
			);
			$event_type = 'update';
		}

		// Log content create/update/upload
		if (!empty($content['uploaded'])) {
			$event_type .= ' upload';
		}
		new H5P_Event('content', $event_type,
			$content['id'],
			$content['title'],
			$content['library']['machineName'],
			$content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

		return $content['id'];
	}

	/**
	 * Resets marked user data for the given content.
	 *
	 * @param int $contentId
	 */
	public function resetContentUserData($contentId)
	{
		// TODO: Implement resetContentUserData() method.
	}

	/**
	 * Save what libraries a library is depending on
	 *
	 * @param int $libraryId
	 *   Library Id for the library we're saving dependencies for
	 * @param array $dependencies
	 *   List of dependencies as associative arrays containing:
	 *   - machineName: The library machineName
	 *   - majorVersion: The library's majorVersion
	 *   - minorVersion: The library's minorVersion
	 * @param string $dependency_type
	 *   What type of dependency this is, the following values are allowed:
	 *   - editor
	 *   - preloaded
	 *   - dynamic
	 */
	public function saveLibraryDependencies($libraryId, $dependencies, $dependency_type)
	{
		// TODO: Implement saveLibraryDependencies() method.
	}

	/**
	 * Give an H5P the same library dependencies as a given H5P
	 *
	 * @param int $contentId
	 *   Id identifying the content
	 * @param int $copyFromId
	 *   Id identifying the content to be copied
	 * @param int $contentMainId
	 *   Main id for the content, typically used in frameworks
	 *   That supports versions. (In this case the content id will typically be
	 *   the version id, and the contentMainId will be the frameworks content id
	 */
	public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = null)
	{
		// TODO: Implement copyLibraryUsage() method.
	}

	/**
	 * Deletes content data
	 *
	 * @param int $contentId
	 *   Id identifying the content
	 */
	public function deleteContentData($contentId)
	{
		// TODO: Implement deleteContentData() method.
	}

	/**
	 * Delete what libraries a content item is using
	 *
	 * @param int $contentId
	 *   Content Id of the content we'll be deleting library usage for
	 */
	public function deleteLibraryUsage($contentId)
	{
		// TODO: Implement deleteLibraryUsage() method.
	}

	/**
	 * Saves what libraries the content uses
	 *
	 * @param int $contentId
	 *   Id identifying the content
	 * @param array $librariesInUse
	 *   List of libraries the content uses. Libraries consist of associative arrays with:
	 *   - library: Associative array containing:
	 *     - dropLibraryCss(optional): comma separated list of machineNames
	 *     - machineName: Machine name for the library
	 *     - libraryId: Id of the library
	 *   - type: The dependency type. Allowed values:
	 *     - editor
	 *     - dynamic
	 *     - preloaded
	 */
	public function saveLibraryUsage($contentId, $librariesInUse)
	{
		// TODO: Implement saveLibraryUsage() method.
	}

	/**
	 * Get number of content/nodes using a library, and the number of
	 * dependencies to other libraries
	 *
	 * @param int $libraryId
	 *   Library identifier
	 * @return array
	 *   Associative array containing:
	 *   - content: Number of content using the library
	 *   - libraries: Number of libraries depending on the library
	 */
	public function getLibraryUsage($libraryId)
	{
		// TODO: Implement getLibraryUsage() method.
	}

	/**
	 * Loads a library
	 *
	 * @param string $machineName
	 *   The library's machine name
	 * @param int $majorVersion
	 *   The library's major version
	 * @param int $minorVersion
	 *   The library's minor version
	 * @return array|FALSE
	 *   FALSE if the library does not exist.
	 *   Otherwise an associative array containing:
	 *   - libraryId: The id of the library if it is an existing library.
	 *   - title: The library's name
	 *   - machineName: The library machineName
	 *   - majorVersion: The library's majorVersion
	 *   - minorVersion: The library's minorVersion
	 *   - patchVersion: The library's patchVersion
	 *   - runnable: 1 if the library is a content type, 0 otherwise
	 *   - fullscreen(optional): 1 if the library supports fullscreen, 0 otherwise
	 *   - embedTypes(optional): list of supported embed types
	 *   - preloadedJs(optional): comma separated string with js file paths
	 *   - preloadedCss(optional): comma separated sting with css file paths
	 *   - dropLibraryCss(optional): list of associative arrays containing:
	 *     - machineName: machine name for the librarys that are to drop their css
	 *   - semantics(optional): Json describing the content structure for the library
	 *   - preloadedDependencies(optional): list of associative arrays containing:
	 *     - machineName: Machine name for a library this library is depending on
	 *     - majorVersion: Major version for a library this library is depending on
	 *     - minorVersion: Minor for a library this library is depending on
	 *   - dynamicDependencies(optional): list of associative arrays containing:
	 *     - machineName: Machine name for a library this library is depending on
	 *     - majorVersion: Major version for a library this library is depending on
	 *     - minorVersion: Minor for a library this library is depending on
	 *   - editorDependencies(optional): list of associative arrays containing:
	 *     - machineName: Machine name for a library this library is depending on
	 *     - majorVersion: Major version for a library this library is depending on
	 *     - minorVersion: Minor for a library this library is depending on
	 */
	public function loadLibrary($machineName, $majorVersion, $minorVersion)
	{
		// TODO: Implement loadLibrary() method.
	}

	/**
	 * Loads library semantics.
	 *
	 * @param string $machineName
	 *   Machine name for the library
	 * @param int $majorVersion
	 *   The library's major version
	 * @param int $minorVersion
	 *   The library's minor version
	 * @return string
	 *   The library's semantics as json
	 */
	public function loadLibrarySemantics($machineName, $majorVersion, $minorVersion)
	{
		// TODO: Implement loadLibrarySemantics() method.
	}

	/**
	 * Makes it possible to alter the semantics, adding custom fields, etc.
	 *
	 * @param array $semantics
	 *   Associative array representing the semantics
	 * @param string $machineName
	 *   The library's machine name
	 * @param int $majorVersion
	 *   The library's major version
	 * @param int $minorVersion
	 *   The library's minor version
	 */
	public function alterLibrarySemantics(&$semantics, $machineName, $majorVersion, $minorVersion)
	{
		// TODO: Implement alterLibrarySemantics() method.
	}

	/**
	 * Delete all dependencies belonging to given library
	 *
	 * @param int $libraryId
	 *   Library identifier
	 */
	public function deleteLibraryDependencies($libraryId)
	{
		// TODO: Implement deleteLibraryDependencies() method.
	}

	/**
	 * Start an atomic operation against the dependency storage
	 */
	public function lockDependencyStorage()
	{
		// TODO: Implement lockDependencyStorage() method.
	}

	/**
	 * Stops an atomic operation against the dependency storage
	 */
	public function unlockDependencyStorage()
	{
		// TODO: Implement unlockDependencyStorage() method.
	}

	/**
	 * Delete a library from database and file system
	 *
	 * @param stdClass $library
	 *   Library object with id, name, major version and minor version.
	 */
	public function deleteLibrary($library)
	{
		// TODO: Implement deleteLibrary() method.
	}

	/**
	 * Load content.
	 *
	 * @param int $id
	 *   Content identifier
	 * @return array
	 *   Associative array containing:
	 *   - contentId: Identifier for the content
	 *   - params: json content as string
	 *   - embedType: csv of embed types
	 *   - title: The contents title
	 *   - language: Language code for the content
	 *   - libraryId: Id for the main library
	 *   - libraryName: The library machine name
	 *   - libraryMajorVersion: The library's majorVersion
	 *   - libraryMinorVersion: The library's minorVersion
	 *   - libraryEmbedTypes: CSV of the main library's embed types
	 *   - libraryFullscreen: 1 if fullscreen is supported. 0 otherwise.
	 */
	public function loadContent($id)
	{
		// TODO: Implement loadContent() method.
	}

	/**
	 * Load dependencies for the given content of the given type.
	 *
	 * @param int $id
	 *   Content identifier
	 * @param int $type
	 *   Dependency types. Allowed values:
	 *   - editor
	 *   - preloaded
	 *   - dynamic
	 * @return array
	 *   List of associative arrays containing:
	 *   - libraryId: The id of the library if it is an existing library.
	 *   - machineName: The library machineName
	 *   - majorVersion: The library's majorVersion
	 *   - minorVersion: The library's minorVersion
	 *   - patchVersion: The library's patchVersion
	 *   - preloadedJs(optional): comma separated string with js file paths
	 *   - preloadedCss(optional): comma separated sting with css file paths
	 *   - dropCss(optional): csv of machine names
	 */
	public function loadContentDependencies($id, $type = null)
	{
		// TODO: Implement loadContentDependencies() method.
	}

	/**
	 * Get stored setting.
	 *
	 * @param string $name
	 *   Identifier for the setting
	 * @param string $default
	 *   Optional default value if settings is not set
	 * @return mixed
	 *   Whatever has been stored as the setting
	 */
	public function getOption($name, $default = null)
	{
		// TODO: Implement getOption() method.
	}

	/**
	 * Stores the given setting.
	 * For example when did we last check h5p.org for updates to our libraries.
	 *
	 * @param string $name
	 *   Identifier for the setting
	 * @param mixed $value Data
	 *   Whatever we want to store as the setting
	 */
	public function setOption($name, $value)
	{
		// TODO: Implement setOption() method.
	}

	/**
	 * This will update selected fields on the given content.
	 *
	 * @param int $id Content identifier
	 * @param array $fields Content fields, e.g. filtered or slug.
	 */
	public function updateContentFields($id, $fields)
	{
		// TODO: Implement updateContentFields() method.
	}

	/**
	 * Will clear filtered params for all the content that uses the specified
	 * library. This means that the content dependencies will have to be rebuilt,
	 * and the parameters re-filtered.
	 *
	 * @param int $library_id
	 */
	public function clearFilteredParameters($library_id)
	{
		// TODO: Implement clearFilteredParameters() method.
	}

	/**
	 * Get number of contents that has to get their content dependencies rebuilt
	 * and parameters re-filtered.
	 *
	 * @return int
	 */
	public function getNumNotFiltered()
	{
		// TODO: Implement getNumNotFiltered() method.
	}

	/**
	 * Get number of contents using library as main library.
	 *
	 * @param int $libraryId
	 * @return int
	 */
	public function getNumContent($libraryId)
	{
		// TODO: Implement getNumContent() method.
	}

	/**
	 * Determines if content slug is used.
	 *
	 * @param string $slug
	 * @return boolean
	 */
	public function isContentSlugAvailable($slug)
	{
		// TODO: Implement isContentSlugAvailable() method.
	}

	/**
	 * Generates statistics from the event log per library
	 *
	 * @param string $type Type of event to generate stats for
	 * @return array Number values indexed by library name and version
	 */
	public function getLibraryStats($type)
	{
		// TODO: Implement getLibraryStats() method.
	}

	/**
	 * Aggregate the current number of H5P authors
	 * @return int
	 */
	public function getNumAuthors()
	{
		// TODO: Implement getNumAuthors() method.
	}

	/**
	 * Stores hash keys for cached assets, aggregated JavaScripts and
	 * stylesheets, and connects it to libraries so that we know which cache file
	 * to delete when a library is updated.
	 *
	 * @param string $key
	 *  Hash key for the given libraries
	 * @param array $libraries
	 *  List of dependencies(libraries) used to create the key
	 */
	public function saveCachedAssets($key, $libraries)
	{
		// TODO: Implement saveCachedAssets() method.
	}

	/**
	 * Locate hash keys for given library and delete them.
	 * Used when cache file are deleted.
	 *
	 * @param int $library_id
	 *  Library identifier
	 * @return array
	 *  List of hash keys removed
	 */
	public function deleteCachedAssets($library_id)
	{
		// TODO: Implement deleteCachedAssets() method.
	}

	/**
	 * Get the amount of content items associated to a library
	 * return int
	 */
	public function getLibraryContentCount()
	{
		// TODO: Implement getLibraryContentCount() method.
	}

	/**
	 * Will trigger after the export file is created.
	 */
	public function afterExportCreated()
	{
		// TODO: Implement afterExportCreated() method.
	}

	/**
	 * Check if user has permissions to an action
	 *
	 * @method hasPermission
	 * @param  [H5PPermission] $permission Permission type, ref H5PPermission
	 * @param  [int]           $id         Id need by platform to determine permission
	 * @return boolean
	 */
	public function hasPermission($permission, $id = null)
	{
		// TODO: Implement hasPermission() method.
	}
}
