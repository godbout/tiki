<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

class H5PLib
{
	/**
	 * Lib version, used for cache-busting of style and script file references.
	 * Keeping track of the DB version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	private $H5PTiki = null;

	private static $settings = null;

	function __construct()
	{
		$this->H5PTiki = new \H5P_H5PTiki();
	}

	function __destruct()
	{
	}

	/**
	 * Triggered by the tiki.file.create event from filegallib
	 *
	 * @param array $args containing:
	 *  - 'type' => 'file'
	 *  - 'object' => $fileId
	 *  - 'user' => $GLOBALS['user']
	 *  - 'galleryId' => $galleryId
	 *  - 'filetype' => $type
	 *
	 */
	function handle_fileCreation($args)
	{
		if ($metadata = $this->getRequestMetadata($args)) {

			$validator = H5P_H5PTiki::get_h5p_instance('validator');

			if ($validator->isValidPackage()) {

				$storage = H5P_H5PTiki::get_h5p_instance('storage');
				$storage->savePackage(null, $args['object']);

				// TODO: Somehow connect the filename/fileId and $storage->contentId ? Needed when .h5p file is updated, deleted(or worse?)
			} else {

				// TODO: What to do if the file isn't a valid H5P? Seems a bit drastic to delete the file – but then again, why would we host broken files?
				// @unlink($interface->getUploadedH5pPath());
			}

		}
	}

	/**
	 * Triggered by the tiki.file.update event from filegallib
	 *
	 * @param array $args containing:
	 *  - 'type' => 'file'
	 *  - 'object' => $fileId
	 *  - 'user' => $GLOBALS['user']
	 *  - 'galleryId' => $galleryId
	 *  - 'filetype' => $type
	 *
	 */
	function handle_fileUpdate($args)
	{
		if (isset($args['object']) && $metadata = $this->getRequestMetadata($args)) {

			$content = $this->loadContentFromFileId($args['object']);

			// Clear content dependency cache
			$this->H5PTiki->deleteLibraryUsage($content['id']);

			$core = \H5P_H5PTiki::get_h5p_instance('core');
			$core->savePackage($content);
		}
	}

	/**
	 * Triggered by the tiki.file.delete event from filegallib
	 *
	 * @param array $args containing:
	 *  - 'type' => 'file'
	 *  - 'object' => $fileId
	 *  - 'user' => $GLOBALS['user']
	 *  - 'galleryId' => $galleryId
	 *  - 'filetype' => $type
	 *
	 */
	function handle_fileDelete($args)
	{
		if (isset($args['object']) && $args['type'] === 'file') {

			$id = $this->getContentIdFromFileId($args['object']);

			if ($id) {
				// Remove the h5p contents
				$this->H5PTiki->deleteContentData($id);
			}
		}
	}

	/**
	 * Get H5P content row from the Tiki fileId
	 *
	 * @param int $fileId
	 *
	 * @return array|bool
	 */
	public function loadContentFromFileId($fileId)
	{
		global $prefs;

		$id = $this->getContentIdFromFileId($fileId);

		if ($id) {// Try to find content with $id.
			$core = \H5P_H5PTiki::get_h5p_instance('core');
			$content = $core->loadContent($id); // TODO: Is it possible to pass $fileId directly here to reduce the number of queries?

			if (is_array($content) && ! empty($content)) {
				// no error
				$content['language'] = substr($prefs['language'], 0, 2);    // TODO better



			}

			return $content;
		} else {
			return false;
		}
	}

	/**
	 * @param int $fileId
	 * @return bool|mixed
	 */
	public function getContentIdFromFileId($fileId)
	{
		$tiki_h5p_contents = TikiDb::get()->table('tiki_h5p_contents');

		return $tiki_h5p_contents->fetchOne('id', ['file_id' => $fileId]);
	}

	private function getRequestMetadata($args)
	{
		$metadata = null;

		if ($this->isZipFile($args) && $zip = $this->getZipFile($args['object'])) {

			if ($manifest = $this->getH5PManifest($zip)) {
				$metadata = $this->getMetadata($manifest);
			}

			$zip->close();
		}

		return $metadata;
	}

	private function isZipFile($args)
	{
		if (! isset($args['filetype'])) {
			return false;
		}

		return in_array($args['filetype'], array('application/zip', 'application/x-zip', 'application/x-zip-compressed'));
	}

	private function getZipFile($fileId)
	{
		global $prefs, $tikipath;

		if (! class_exists('ZipArchive')) {
			Feedback::error(tra('PHP Class "ZipArchive" not found'));
		}

		$filegallib = TikiLib::lib('filegal');

		if (! $info = $filegallib->get_file_info($fileId, false, true, false)) {
			return null;
		}

		// make a copy of the h5p file for the validator to unpack (and eventually delete)
		$dir = $filegallib->get_gallery_save_dir($info['galleryId']);

		$dest = $tikipath . 'temp/' . $info['filename'];
		if ($dir) {
			copy($dir . $info['path'], $dest);
		} else {
			file_put_contents($dest, $info['data']);
		}

		/** @var ZipArchive $zip */
		$zip = new ZipArchive;
		$interface = H5P_H5PTiki::get_h5p_instance('interface');

		$filepath = $interface->getUploadedH5pPath($dest);

		if ($zip->open($filepath) === true) {
			return $zip;
		}
	}

	/**
	 * @param ZipArchive $zip
	 * @return mixed
	 */
	private function getH5PManifest($zip)
	{
		return $zip->getFromName('h5p.json');
	}

	private function getMetadata($manifest)
	{

		return json_decode($manifest, false);
	}


	/**
	 * Include settings and assets for the given content.
	 *
	 * @param array $content
	 * @param boolean $no_cache
	 * @return string Embed code
	 */
	public function addAssets($content, $no_cache = false)
	{
		// Add core assets
		$this->addCoreAssets();

		// Detemine embed type
		$embed = H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);

		// Make sure content isn't added twice
		$cid = 'cid-' . $content['id'];
		if (! isset(self::$settings['contents'][$cid])) {
			self::$settings['contents'][$cid] = $this->getContentSettings($content);
			$core = \H5P_H5PTiki::get_h5p_instance('core');

			// Get assets for this content
			$preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
			$files = $core->getDependenciesFiles($preloaded_dependencies);

			// TODO maybe?
			//$this->alter_assets($files, $preloaded_dependencies, $embed);

			if ($embed === 'div') {
				$this->enqueue_assets($files);
			} elseif ($embed === 'iframe') {
				self::$settings['contents'][$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
				self::$settings['contents'][$cid]['styles'] = $core->getAssetsUrls($files['styles']);
			}
		}

		// Tiki JB note: I had to add this here to get the js files to be included,
		// the WP plugin doesn't use this here so i must be missing something else...
		$this->printSettings(self::$settings);

		if ($embed === 'div') {
			return '<div class="h5p-content" data-content-id="' . $content['id'] . '"></div>';
		} else {
			return '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $content['id'] . '" class="h5p-iframe" data-content-id="' . $content['id'] . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no"></iframe></div>';
		}
	}

	/**
	 * Set core JavaScript settings and add core assets.
	 */
	public function addCoreAssets()
	{

		if (! empty(self::$settings)) {
			return; // Already added
		}

		self::$settings = $this->getCoreSettings();
		self::$settings['core'] = [
			'styles' => [],
			'scripts' => [],
		];
		self::$settings['loadedJs'] = [];
		self::$settings['loadedCss'] = [];
		$TWV = new TWVersion;
		$cache_buster = '?ver=' . $TWV->version;

		$lib_url = 'vendor/h5p/h5p-core/';

		// Add core stylesheets
		foreach (H5PCore::$styles as $style) {
			self::$settings['core']['styles'][] = $lib_url . $style . $cache_buster;
			TikiLib::lib('header')->add_cssfile($lib_url . $style . $cache_buster);
		}

		// Add core JavaScript
		foreach (H5PCore::$scripts as $script) {
			self::$settings['core']['scripts'][] = $lib_url . $script . $cache_buster;
			TikiLib::lib('header')->add_jsfile($lib_url . $script . $cache_buster);
		}
	}

	/**
	 * Get generic h5p settings
	 */
	public function getCoreSettings()
	{
		global $user, $base_url, $prefs;

		$userId = TikiLib::lib('tiki')->get_user_id($user);

		$settings = array(
			'baseUrl' => $base_url,
			'url' => $base_url . \H5P_H5PTiki::$h5p_path,
			/* TODO tracking and saving
						 'postUserStatistics' => ($prefs['h5p_track_user'] === 'y') && $userId,
						'ajaxPath' => 'tiki-ajax_services.php?controller=h5p',
						'ajax' => array(
							'setFinished' => 'tiki-ajax_services.php?controller=h5p&action=setFinished',
							'contentUserData' => 'tiki-ajax_services.php?controller=h5p&action=contents_user_data&content_id=:contentId&data_type=:dataType&sub_content_id=:subContentId',
						),
						'tokens' => array(
							'result' => wp_create_nonce('h5p_result'),
							'contentUserData' => wp_create_nonce('h5p_contentuserdata'),
						),
						'saveFreq' => $prefs['h5p_save_content_state'] === 'y' ? $prefs['h5p_save_content_frequency'] : false,
			*/
			'siteUrl' => $base_url,
			'l10n' => array(
				'H5P' => array(
					'fullscreen' => tra('Fullscreen'),
					'disableFullscreen' => tra('Disable fullscreen'),
					'download' => tra('Download'),
					'copyrights' => tra('Rights of use'),
					'embed' => tra('Embed'),
					'size' => tra('Size'),
					'showAdvanced' => tra('Show advanced'),
					'hideAdvanced' => tra('Hide advanced'),
					'advancedHelp' => tra('Include this script on your website if you want dynamic sizing of the embedded content:'),
					'copyrightInformation' => tra('Rights of use'),
					'close' => tra('Close'),
					'title' => tra('Title'),
					'author' => tra('Author'),
					'year' => tra('Year'),
					'source' => tra('Source'),
					'license' => tra('License'),
					'thumbnail' => tra('Thumbnail'),
					'noCopyrights' => tra('No copyright information available for this content.'),
					'downloadDescription' => tra('Download this content as a H5P file.'),
					'copyrightsDescription' => tra('View copyright information for this content.'),
					'embedDescription' => tra('View the embed code for this content.'),
					'h5pDescription' => tra('Visit H5P.org to check out more cool content.'),
					'contentChanged' => tra('This content has changed since you last used it.'),
					'startingOver' => tra("You'll be starting over."),
				),
			),
		);

		if ($userId) {
			$settings['user'] = array(
				'name' => $user,
				//'mail' => $userId->user_email,
			);
		}

		return $settings;
	}

	/**
	 * Enqueue assets for content embedded by div.
	 *
	 * @param array $assets
	 */
	public function enqueue_assets(&$assets)
	{
		$rel_url = \H5P_H5PTiki::$h5p_path;

		foreach ($assets['scripts'] as $script) {
			$url = $rel_url . $script->path . $script->version;
			if (! in_array($url, self::$settings['loadedJs'])) {
				self::$settings['loadedJs'][] = $url;
				TikiLib::lib('header')->add_jsfile( $rel_url . $script->path);
			}
		}
		foreach ($assets['styles'] as $style) {
			$url = $rel_url . $style->path . $style->version;
			if (! in_array($url, self::$settings['loadedCss'])) {
				self::$settings['loadedCss'][] = $url;
				TikiLib::lib('header')->add_cssfile( $rel_url . $style->path);
			}
		}
	}


	/**
	 * Add H5P JavaScript settings to the bottom of the page.
	 */
	public function addSettings()
	{
		if (self::$settings !== null) {
			$this->printSettings(self::$settings);
		}
	}

	/**
	 * JSON encode and print the given H5P JavaScript settings.
	 *
	 * @param array $settings
	 */
	public function printSettings(&$settings, $obj_name = 'H5PIntegration')
	{
		static $printed;
		if (! empty($printed[$obj_name])) {
			return; // Avoid re-printing settings
		}

		$json_settings = json_encode($settings);
		if ($json_settings !== false) {
			$printed[$obj_name] = true;
			TikiLib::lib('header')->add_js('var ' . $obj_name . ' = ' . $json_settings . ";\n");
		}
	}

	/**
	 * Get added JavaScript settings.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return self::$settings;
	}

	/**
	 * Get settings for given content
	 *
	 * @since 1.5.0
	 * @param array $content
	 * @return array
	 */
	public function getContentSettings($content)
	{
		global $prefs;

		$core = \H5P_H5PTiki::get_h5p_instance('core');

		// Add global disable settings - odd, not found?
		//$content['disable'] |= $core->getGlobalDisable();

		$safe_parameters = $core->filterParameters($content);
		/*		if (has_action('h5p_alter_filtered_parameters')) {
					// Parse the JSON parameters
					$decoded_parameters = json_decode($safe_parameters);

					/**
					 * Allows you to alter the H5P content parameters after they have been
					 * filtered. This hook only fires before view.
					 *
					 * @since 1.5.3
					 *
					 * @param object &$parameters
					 * @param string $libraryName
					 * @param int $libraryMajorVersion
					 * @param int $libraryMinorVersion
					 * /
					do_action_ref_array('h5p_alter_filtered_parameters', array(&$decoded_parameters, $content['library']['name'], $content['library']['majorVersion'], $content['library']['minorVersion']));

					// Stringify the JSON parameters
					$safe_parameters = json_encode($decoded_parameters);
				}
		*/

		$smarty = TikiLib::lib('smarty');
		$smarty->loadPlugin('smarty_function_service');
		$embedUrl = smarty_function_service([
			'controller' => 'h5p',
			'action' => 'embed',
			'fileId' => $content['fileId'],
		], $smarty);


		// Add JavaScript settings for this content
		$settings = [
			'library' => H5PCore::libraryToString($content['library']),
			'jsonContent' => $safe_parameters,
			'fullScreen' => $content['library']['fullscreen'],
			'exportUrl' => ($prefs['h5p_export'] === 'y' ? 'tiki-download_file.php?fileId=' . $content['fileId'] : ''),
			'embedCode' => '<iframe src="' . $embedUrl . '" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen"></iframe>',
			'resizeCode' => '<script src="vendor/h5p/h5p-core/js/h5p-resizer.js" charset="UTF-8"></script>',
			'url' => $embedUrl,
			'title' => $content['title'],
			'disable' => $content['disable'],
			'contentUserData' => [
				0 => [
					'state' => '{}',
				],
			],
			'displayOptions' => [],
		];

		// Get preloaded user data for the current user
		global $user;

		$userId = TikiLib::lib('tiki')->get_user_id($user);

		if ($prefs['h5p_save_content_state'] === 'y' && $userId) {

			$results = TikiDb::get()->table('tiki_h5p_contents_user_data')->fetchAll(
				[
					'sub_content_id',
					'data_id',
					'data',
				], [
					'user_id' => $userId,
					'content_id' => $content['id'],
					'preload' => 1,
				]
			);

			if ($results) {
				foreach ($results as $result) {
					$settings['contentUserData'][$result['sub_content_id']][$result['data_id']] = $result['data'];
				}
			}
		}

		return $settings;
	}


	/**
	 * Add assets and JavaScript settings for the editor.
	 *
	 * @since 1.1.0
	 * @param int $id optional content identifier
	 */
	public function addEditorAssets($id = NULL)
	{
		global $tikiroot, $tikipath, $prefs;

		// Add core assets
		$this->addCoreAssets();

		// Use jQuery and styles from core.
		$assets = array(
			'css' => self::$settings['core']['styles'],
			'js' => self::$settings['core']['scripts']
		);

		// Use relative URL to support both http and https.
		$editorpath = 'vendor/h5p/h5p-editor/';
		$url = $tikiroot . $editorpath;

		// Make sure files are reloaded for new versions
		$TWV = new TWVersion;
		$cachebuster = '?ver=' . $TWV->version;

		// Add editor styles
		foreach (H5peditor::$styles as $style) {
			$assets['css'][] = $url . $style . $cachebuster;
		}

		// Add editor JavaScript
		foreach (H5peditor::$scripts as $script) {
			// We do not want the creator of the iframe inside the iframe
			if ($script !== 'scripts/h5peditor-editor.js') {
				$assets['js'][] = $url . $script . $cachebuster;
			}
		}

		// Add JavaScript with library framework integration (editor part)
		TikiLib::lib('header')->add_jsfile($url . 'scripts/h5peditor-editor.js');
		TikiLib::lib('header')->add_jsfile($tikiroot . 'lib/core/H5P/editor.js');

		// Add translation
		$languagescript = $editorpath . 'language/' . substr($prefs['language'], 0, 2) . '.js';
		if (!file_exists($tikipath . $languagescript)) {
			$languagescript = $editorpath . 'language/en.js';
		}
		TikiLib::lib('header')->add_jsfile($tikiroot . $languagescript);

		// needs to be non-sefurl version so h5p can append the action and params
		$ajaxPath = 'tiki-ajax_services.php?controller=h5p&action=';

		// Add JavaScript settings
		$contentvalidator = \H5P_H5PTiki::get_h5p_instance('contentvalidator');
		self::$settings['editor'] = array(
			'filesPath' => \H5P_H5PTiki::$h5p_path . '/editor',
			'fileIcon' => array(
				'path' => $url . 'images/binary-file.png',
				'width' => 50,
				'height' => 50,
			),
			'ajaxPath' => $ajaxPath,
			'libraryUrl' => $url,
			'copyrightSemantics' => $contentvalidator->getCopyrightSemantics(),
			'assets' => $assets,
		);

		if ($id !== NULL) {
			self::$settings['editor']['nodeVersionId'] = $id;
		}

		$this->printSettings(self::$settings);
	}


}
