<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_H5P_Controller
{
	function setUp()
	{
		global $prefs;

		if ($prefs['h5p_enabled'] !== 'y') {
			throw new Services_Exception_Disabled(tr('h5p_enabled'));
		}
		if ($prefs['feature_file_galleries'] != 'y') {
			throw new Services_Exception_Disabled('feature_file_galleries');
		}
	}

	function action_embed($input)
	{
		$smarty = TikiLib::lib('smarty');
		$smarty->loadPlugin('smarty_function_button');

		$fileId = $input->fileId->int();

		$perms = Perms::get();

		if (empty($fileId)) {
			if ($perms->h5p_edit) {

				return [
					'html' => smarty_function_button([
						'href' => TikiLib::lib('service')->getUrl(['controller' => 'h5p', 'action' => 'edit']),
						'_text' => tra('Create H5P content'),
						'_onclick' => '$.clickModal',
					], $smarty),
				];

			} else {
				throw new Services_Exception_NotAvailable(tr('H5P Embed:') . ' ' . tr('No fileID provided.'));
			}
		}

		$content = TikiLib::lib('h5p')->loadContentFromFileId($fileId);

		if (! $content) {
			Feedback::error(tr('H5P Plugin:') . ' ' . tr('Cannot find H5P content with fileId: %0.', $fileId));
			return '';
		}

		if (is_string($content)) {
			// Return error message if the user has the correct cap
			return Perms::get()->h5p_edit ? $content : NULL;
		}

		// Log view
		new H5P_Event('content', 'embed',
			$content['id'],
			$content['title'],
			$content['library']['name'],
			$content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

		$html = TikiLib::lib('h5p')->addAssets($content);

		if ($perms->h5p_edit) {

			$html .= smarty_function_button([
				'href' => TikiLib::lib('service')->getUrl(['controller' => 'h5p', 'action' => 'edit', 'fileId' => $fileId]),
				'_text' => tra('Edit'),
			], $smarty);

		}
		return [
			'html' => $html,
			'title' => TikiLib::lib('filegal')->get_file_label($fileId),
		];
	}

	function action_edit($input)
	{
		if (! Perms::get()->h5p_edit) {
			throw new Services_Exception_Denied(tr('H5P Edit:') . ' ' . tr('Permission denied.'));
		}

		$fileId = $input->fileId->int();

		if (! empty($fileId)) {
			// retrieve existing, asomething like

			$content = TikiLib::lib('h5p')->loadContentFromFileId($fileId);
			$title = TikiLib::lib('filegal')->get_file_label($fileId);

			// Log editor opened
			new H5P_Event('content', 'edit',
					$content['id'],
					$title,
					$content['library']['name'],
					$content['library']['majorVersion'] . '.' . $this->content['library']['minorVersion']);

		} else {
			$content = [];
			$title = '';

			// Log editor opened
			new H5P_Event('content', 'new');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			// process the form
			// see \Services_Tracker_Controller::action_update_item for example of redirecting if necessary

		}

		TikiLib::lib('h5p')->addEditorAssets(empty($content['id']) ? NULL : $content['id']);

		$core = \H5P_H5PTiki::get_h5p_instance('core');
		return [
			'content' => $content,
			'fileId' => $fileId,
			'title' => $title,
			'library' => empty($content['library']) ? 0 : H5PCore::libraryToString($content['library']),
			'parameters' => empty($content['params']) ? '{}' : $core->filterParameters($content),
			'loading' => tra('Waiting for javascript...')
			// etc
		];
	}

	function action_libraries($input)
	{
		global $prefs;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$_POST['libraries'] = array();
			foreach ($input->libraries as $library) {
				$_POST['libraries'][] = $library;
			}
		}

		$editor = \H5P_EditorTikiStorage::get_h5peditor_instance();

		$name = filter_input(INPUT_GET, 'machineName', FILTER_SANITIZE_STRING);
		$major_version = filter_input(INPUT_GET, 'majorVersion', FILTER_SANITIZE_NUMBER_INT);
		$minor_version = filter_input(INPUT_GET, 'minorVersion', FILTER_SANITIZE_NUMBER_INT);

		header('Cache-Control: no-cache');
		header('Content-type: application/json');

		if ($name) {
			print $editor->getLibraryData($name, $major_version, $minor_version, substr($prefs['language'], 0, 2), '', \H5P_H5PTiki::$h5p_path);

			// Log library load
			new H5P_Event('library', NULL,
					NULL, NULL,
					$name, $major_version . '.' . $minor_version);
		}
		else {
			print $editor->getLibraries();
		}

		exit;
	}

	function action_files($input)
	{
		print 'TODO';
		exit;
	}
}
