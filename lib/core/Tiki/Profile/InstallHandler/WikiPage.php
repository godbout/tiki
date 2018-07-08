<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_WikiPage extends Tiki_Profile_InstallHandler
{
	private $content;
	private $description;
	private $namespace;
	private $name;
	private $lang;
	private $translations = [];
	private $message;
	private $structure;
	private $structure_as_sibling;
	private $wysiwyg;
	private $wiki_authors_style;
	private $geolocation;
	private $hide_title;
	private $locked;
	private $freetags;
	private $mode = 'create_or_update';
	private $exists;

	function fetchData()
	{
		if ($this->name) {
			return;
		}

		$data = $this->obj->getData();

		if (array_key_exists('message', $data)) {
			$this->message = $data['message'];
		}
		if (array_key_exists('freetags', $data)) {
			$this->freetags = $data['freetags'];
		}
		if (array_key_exists('name', $data)) {
			$this->name = $data['name'];
		}
		if (array_key_exists('namespace', $data)) {
			$this->namespace = $data['namespace'];
		}
		if (array_key_exists('description', $data)) {
			$this->description = $data['description'];
		}
		if (array_key_exists('lang', $data)) {
			$this->lang = $data['lang'];
		}
		if (array_key_exists('content', $data)) {
			$this->content = $data['content'];
		}
		if (array_key_exists('mode', $data)) {
			$this->mode = $data['mode'];
		}
		if ($this->lang
			&& array_key_exists('translations', $data)
			&& is_array($data['translations']) ) {
			$this->translations = $data['translations'];
		}
		if (array_key_exists('structure', $data)) {
			$this->structure = $data['structure'];
		}
		if (array_key_exists('structure_as_sibling', $data)) {
			$this->structure_as_sibling = $data['structure_as_sibling'];
		}
		if (array_key_exists('wysiwyg', $data)) {
			$this->wysiwyg = $data['wysiwyg'];
		}
		if (array_key_exists('wiki_authors_style', $data)) {
			$this->wiki_authors_style = $data['wiki_authors_style'];
		}
		if (array_key_exists('geolocation', $data)) {
			$this->geolocation = $data['geolocation'];
		}
		if (array_key_exists('hide_title', $data)) {
			$this->hide_title = $data['hide_title'];
		}
		if (array_key_exists('locked', $data)) {
			$this->locked = $data['locked'];
		}
	}

	function canInstall()
	{
		$this->fetchData();
		if (empty($this->name)) {
			return false;
		}

		$this->convertMode();

		return true;
	}

	private function convertMode()
	{
		global $tikilib;

		$name = $this->getPageName();

		$this->exists = $tikilib->page_exists($name);

		switch ($this->mode) {
			case 'create':
				if ($this->exists) {
					throw new Exception("Page {$name} already exists and profile does not allow update.");
				}
				break;
			case 'update':
			case 'append':
				if (! $this->exists) {
					throw new Exception("Page {$name} does not exist and profile only allows update.");
				}
				break;
			case 'create_or_update':
				return $this->exists ? 'update' : 'create';
			case 'create_or_append':
				return $this->exists ? 'append' : 'create';
			default:
				throw new Exception("Invalid mode '{$this->mode}' for wiki handler.");
		}

		return $this->mode;
	}

	function _install()
	{
		// Normalize mode
		$this->canInstall();

		global $tikilib;
		$this->fetchData();
		$this->replaceReferences($this->name);
		$this->replaceReferences($this->namespace);
		$this->replaceReferences($this->description);
		$this->replaceReferences($this->content);
		$this->replaceReferences($this->lang);
		$this->replaceReferences($this->translations);
		$this->replaceReferences($this->message);
		$this->replaceReferences($this->structure);
		$this->replaceReferences($this->structure_as_sibling);
		$this->replaceReferences($this->wysiwyg);
		$this->replaceReferences($this->wiki_authors_style);
		$this->replaceReferences($this->geolocation);
		$this->replaceReferences($this->hide_title);
		$this->replaceReferences($this->locked);

		$this->mode = $this->convertMode();

		if (strpos($this->content, 'wikidirect:') === 0) {
			$pageName = substr($this->content, strlen('wikidirect:'));
			$this->content = $this->obj->getProfile()->getPageContent($pageName);
		}

		$finalName = $this->getPageName();

		$hash = [];

		if ($this->mode == 'create') {
			if ($this->wysiwyg) {
				$this->wysiwyg = 'y';
				$is_html = true;
			} else {
				$this->wysiwyg = 'n';
				$is_html = false;
			}
			if ($this->locked == 'y') {
				$hash['lock_it'] = 'y';
			} else {
				$this->locked = 'n';
				$hash = null;
			}
			if (! $this->message) {
				$this->message = tra('Created by profile installer');
			}
			if (! $tikilib->create_page($finalName, 0, $this->content, time(), $this->message, 'admin', '0.0.0.0', $this->description, $this->lang, $is_html, $hash, $this->wysiwyg, $this->wiki_authors_style)) {
				return null;
			}
		} else {
			$info = $tikilib->get_page_info($finalName, true, true);

			if (! $this->wysiwyg) {
				if (! empty($info['wysiwyg'])) {
					$this->wysiwyg = $info['wysiwyg'];
				} else {
					$this->wysiwyg = 'n';
				}
				if (isset($info['is_html'])) {
					$is_html = $info['is_html'];
				} else {
					$is_html = false;
				}
			} else {
				$this->wysiwyg = 'y';
				$is_html = true;
			}

			if (! $this->description) {
				$this->description = $info['description'];
			}

			if (! $this->lang) {
				$this->lang = $info['lang'];
			}

			if ($this->mode == 'append') {
				$this->content = rtrim($info['data']) . "\n" . trim($this->content) . "\n";
			}

			if (! $this->message) {
				$this->message = tra('Page updated by profile installer');
			}

			$tikilib->update_page($finalName, $this->content, $this->message, 'admin', '0.0.0.0', $this->description, 0, $this->lang, $is_html, null, null, $this->wysiwyg, $this->wiki_authors_style);
		}

		global $prefs;
		if (! empty($prefs['geo_locate_wiki']) && $prefs['geo_locate_wiki'] == 'y' && ! empty($this->geolocation)) {
			TikiLib::lib('geo')->set_coordinates('wiki page', $this->name, $this->geolocation);
		}

		if ($prefs['wiki_page_hide_title'] == 'y' && ! empty($this->hide_title)) {
			if ($this->hide_title == 'y') {
				$isHideTitle = -1;
			} elseif ($this->hide_title == 'n') {
				$isHideTitle = 0;
			}
			TikiLib::lib('wiki')->set_page_hide_title($finalName, $isHideTitle);
		}

		$multilinguallib = TikiLib::lib('multilingual');

		$current = $tikilib->get_page_id_from_name($finalName);
		foreach ($this->translations as $targetName) {
			$target = $tikilib->get_page_info($targetName);

			if ($target && $target['lang'] && $target['lang'] != $this->lang) {
				$multilinguallib->insertTranslation('wiki page', $current, $this->lang, $target['page_id'], $target['lang']);
			}
		}

		// only create a new structure or add a new page to a structure if the structure parameter has been set AND mode is 'create'
		if (isset($this->structure) && $this->mode == 'create') {
			$structlib = TikiLib::lib('struct');
			if ($this->structure === 0) {
				$page_ref_id = 0;
				// create a new structure with just the new wiki page if the profile structure: parameter is set to zero
				$structlib->s_create_page(null, null, $finalName, '', 0);
			} elseif (ctype_digit($this->structure)) {
				$page_ref_id = $this->structure;
			} else {
				$page_ref_id = (int) $structlib->get_struct_ref_id($this->structure);
			}
			if ($page_ref_id > 0) {
				// add the page to an existing structure when the profile structure: parameter is non-zero
				// where the parameter is set to a page_ref_id and the new page is inserted after this page ref in the structure hierarchy
				// In addition, if structure_as_sibling is set to 'y', then the new page is created as sibling rather than child.
				$pageinfo = $structlib->s_get_page_info($page_ref_id);
				$structure_id = $pageinfo['structure_id'];
				if ($this->structure_as_sibling == 'y' && $pageinfo['parent_id'] > 0) {
					$structure_parent = $pageinfo['parent_id'];
				} else {
					$structure_parent = $page_ref_id;
				}
				$structlib->s_create_page($structure_parent, $page_ref_id, $finalName, '', $structure_id);
			}
		}

		if ($this->freetags != "" && $tikilib->page_exists($finalName, false)) {
			$cat_type = "wiki page";
			$cat_objid = $finalName;
			$cat_name = $finalName;
			$tag_string = $this->freetags;
			$cat_lang = null;
			require_once 'freetag_apply.php';
		}

		return $finalName;
	}

	private function getPageName()
	{
		global $prefs;

		$name = $this->name;

		if ($this->namespace) {
			$name = "{$this->namespace}{$prefs['namespace_separator']}{$name}";
		}

		return $name;
	}

	/**
	 * Export wikipages
	 *
	 * @param Tiki_Profile_Writer $writer
	 * @param string $page
	 * @param bool $all
	 * @return bool
	 */
	public static function export(Tiki_Profile_Writer $writer, $page, $all = false)
	{
		$tikilib = \TikiLib::lib('tiki');

		$pageName = ! empty($page) ? $page : '';
		$listPages = $tikilib->list_pages(0, -1, 'pageName_desc', $pageName);
		if ($listPages['cant'] == 0) {
			return false;
		}

		foreach ($listPages['data'] as $page) {
			$pageName = $page['pageName'];
			$writer->writeExternal($pageName, $writer->getReference('wiki_content', $page['data']));
			$writer->addObject(
				'wiki_page',
				$pageName,
				array_filter([
					'name' => $pageName,
					'content' => "wikicontent:$pageName",
					'description' => $page['description'],
					'lang' => $page['lang'],
					'wysiwyg' => $page['wysiwyg'],
				])
			);
		}
		return true;
	}

	/**
	 * Remove wiki page
	 *
	 * @param string $pageName
	 * @return bool
	 */
	function remove($pageName)
	{
		if (! empty($pageName)) {
			$tikilib = \TikiLib::lib('tiki');
			if ($tikilib->remove_all_versions($pageName)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return wiki page object data
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->obj->getData();
	}

	/**
	 * Get current wiki page data
	 *
	 * @param array $wikiPage
	 * @return mixed
	 */
	public function getCurrentData($wikiPage)
	{
		$wikiPageName = ! empty($wikiPage['name']) ? $wikiPage['name'] : '';
		if (! empty($wikiPage)) {
			$tikilib = \TikiLib::lib('tiki');
			$pageData = $tikilib->get_page_info($wikiPageName);
			if (! empty($pageData)) {
				return $pageData;
			}
		}
		return false;
	}

	/**
	 * Get wiki page changes
	 *
	 * @param array $before
	 * @param array $after
	 * @return mixed
	 */
	public function getChanges($before, $after)
	{
		if (! empty($before['page_id']) && ! empty($after['page_id']) && $before['page_id'] === $after['page_id']) {
			return $before;
		}
		return false;
	}

	/**
	 * Revert wiki page data
	 *
	 * @param array $pageData
	 * @return bool
	 */
	public function revert($pageData)
	{
		if (! empty($pageData)) {
			$tikilib = \TikiLib::lib('tiki');
			$tikilib->update_page(
				$pageData['pageName'],
				$pageData['data'],
				$pageData['comment'],
				$pageData['user'],
				$pageData['ip'],
				$pageData['description'],
				$pageData['version_minor'],
				$pageData['lang'],
				$pageData['is_html'],
				'',
				null,
				$pageData['wysiwyg'],
				$pageData['wiki_authors_style']
			);
			return true;
		}
		return false;
	}
}
