<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

class ReferencesLib extends TikiLib
{
	public function list_references($page)
	{
		$query = 'select * from `tiki_page_references` WHERE `page_id`=? ORDER BY `biblio_code`';
		$query_cant = 'select count(*) from `tiki_page_references` WHERE `page_id`=?';
		$result = $this->query($query, [$page]);
		$cant = $this->getOne($query_cant, [$page]);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$query_1 = 'select * from `tiki_page_references` WHERE `biblio_code`=? AND page_id IS NULL';
			$result_1 = $this->query($query_1, [$res['biblio_code']]);
			$res['is_library'] = $result_1->numrows;
			$ret[] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		$retval['cant'] = $cant;

		return $retval;
	}

	public function list_assoc_references($page)
	{
		$query = 'select * from `tiki_page_references` WHERE `page_id`=? ORDER BY `biblio_code`';
		$query_cant = 'select count(*) from `tiki_page_references` WHERE `page_id`=?';
		$result = $this->query($query, [$page]);
		$cant = $this->getOne($query_cant, [$page]);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[$res['biblio_code']] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		$retval['cant'] = $cant;

		return $retval;
	}

	public function get_references_from_biblio($code)
	{
		$query = 'select * from `tiki_page_references` WHERE `biblio_code`=?';
		$query_cant = 'select count(*) from `tiki_page_references` WHERE `biblio_code`=?';
		$result = $this->query($query, [$code]);
		$cant = $this->getOne($query_cant, [$code]);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		$retval['cant'] = $cant;

		return $retval;
	}

	public function get_reference_from_code($code)
	{
		$query = 'select * from `tiki_page_references` WHERE `biblio_code`=?';
		$result = $this->query($query, [$code]);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		return $retval;
	}

	public function get_reference_from_id($ref_id)
	{
		$query = 'select * from `tiki_page_references` WHERE `ref_id`=?';
		$result = $this->query($query, [$ref_id]);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		return $retval;
	}

	public function get_reference_from_code_and_page($codes, $page)
	{
		$biblios = '';
		foreach ($codes as $code) {
			if (is_array($code)) {
				$biblios .= '\'' . $code['biblio_code'] . '\'' . ',';
			} else {
				$biblios .= '\'' . $code . '\'' . ',';
			}
		}
		$biblios = substr($biblios, 0, strlen($biblios) - 1);

		$codes = "'first'" . ',' . 'second';
		$query = "select * from `tiki_page_references` WHERE `biblio_code` IN ($biblios) AND `page_id`=?";
		$result = $this->query($query, [$page]);

		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[$res['biblio_code']] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;

		return $retval;
	}

	/**
	 * list the library references (not linked to specific pages)
	 *
	 * @param string $search string to search (optional)
	 * @param int $maxRecords
	 * @param int $offset
	 * @return array
	 */
	public function list_lib_references($search = '', $maxRecords = -1, $offset = 0)
	{
		if (! empty($search)) {
			$filter = ' AND (`biblio_code` LIKE ? OR `author` LIKE ? OR `title` LIKE ? OR `part` LIKE ? OR `uri` LIKE ?'
				. ' OR `code` LIKE ? OR `year` LIKE ? OR `style` LIKE ? OR `template` LIKE ? OR `publisher` LIKE ? '
				. ' OR `location` LIKE ?)';
			$likeSearch = '%' . $search . '%';
			$queryArg = [
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
				$likeSearch,
			];
		} else {
			$filter = '';
			$queryArg = [];
		}

		$query = 'select * from `tiki_page_references` WHERE `page_id` IS NULL ' . $filter . ' ORDER BY `biblio_code`';

		if ($maxRecords > 0) {
			$query .= 'LIMIT ' . (int)$offset . ', ' . (int)$maxRecords;
		}

		$query_cant = 'select count(*) from `tiki_page_references` WHERE `page_id` IS NULL ' . $filter;

		$result = $this->query($query, $queryArg);
		$cant = $this->getOne($query_cant, $queryArg);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = [];
		$retval['data'] = $ret;
		$retval['cant'] = $cant;

		return $retval;
	}

	public function add_reference(
		$page,
		$biblio_code,
		$author,
		$title,
		$part,
		$uri,
		$code,
		$year,
		$style,
		$template,
		$publisher,
		$location
	) {

		$query = 'insert `tiki_page_references`' .
							' (`page_id`, `biblio_code`, `author`, `title`, `part`, `uri`,' .
							' `code`, `year`, `style`, `template`, `publisher`, `location`)' .
							' values (?,?,?,?,?,?,?,?,?,?,?,?)';

		$this->query(
			$query,
			[
				$page,
				$biblio_code,
				$author,
				$title,
				$part,
				$uri,
				$code,
				$year,
				$style,
				$template,
				$publisher,
				$location
			]
		);
		if (empty($biblio_code)) {
			$query = 'update `tiki_page_references`' .
							' SET `biblio_code`=?' .
							' where `ref_id`=?';

			$this->query(
				$query,
				[
					'BIBLIO' . $this->lastInsertId(),
					(int)$this->lastInsertId(),
				]
			);
		}


		return $this->lastInsertId();
	}

	/**
	 * Add a library reference to a page (duplicate and link to the page)
	 *
	 * @param int $ref_id Id of the reference
	 * @param int $page id of the page
	 * @return bool|int
	 */
	public function add_lib_ref_to_page($ref_id, $page)
	{

		$query = 'select * from `tiki_page_references` WHERE `ref_id`=?';
		$result = $this->query($query, [$ref_id]);

		if (! $result->numrows) {
			return false;
		}

		return $this->copy_lib_ref_to_page($result->result[0], $page);
	}

	/**
	 * Add a library reference to a page (duplicate and link to the page)
	 *
	 * @param string $code Bibliographic code
	 * @param int $page id of the page
	 * @return bool|int
	 */
	public function add_lib_ref_to_page_by_code($code, $page)
	{

		$query = 'select * from `tiki_page_references` WHERE `page_id` IS NULL AND `biblio_code`=?';
		$result = $this->query($query, [$code]);

		if (! $result->numrows) {
			return false;
		}

		return $this->copy_lib_ref_to_page($result->result[0], $page);
	}

	/**
	 * Copy the library reference to given page
	 *
	 * @param $libraryReference
	 * @param $page
	 * @return int the ID of the record inserted, -1 if exists
	 */
	protected function copy_lib_ref_to_page($libraryReference, $page)
	{
		$exists = $this->check_existence($page, $libraryReference['biblio_code']);

		if ($exists > 0) {
			return -1;
		} else {
			$query = 'insert `tiki_page_references`' .
				' (`page_id`, `biblio_code`, `author`, `title`, `part`, `uri`,' .
				' `code`, `year`, `style`, `template`, `publisher`, `location`)' .
				' values (?,?,?,?,?,?,?,?,?,?,?,?)';

			$this->query(
				$query,
				[
					$page,
					$libraryReference['biblio_code'],
					$libraryReference['author'],
					$libraryReference['title'],
					$libraryReference['part'],
					$libraryReference['uri'],
					$libraryReference['code'],
					$libraryReference['year'],
					$libraryReference['style'],
					$libraryReference['template'],
					$libraryReference['publisher'],
					$libraryReference['location']
				]
			);

			return $this->lastInsertId();
		}
	}

	public function edit_reference(
		$ref_id,
		$biblio_code,
		$author,
		$title,
		$part,
		$uri,
		$code,
		$year,
		$style,
		$template,
		$publisher,
		$location
	) {

		$query = 'update `tiki_page_references`' .
							' SET `biblio_code`=?, `author`=?, `title`=?, `part`=?, `uri`=?,' .
							' `code`=?, `year`=?, `style`=?, `template`=?, `publisher`=?, `location`=?' .
							' where `ref_id`=?';

		$this->query(
			$query,
			[
					$biblio_code,
					$author,
					$title,
					$part,
					$uri,
					$code,
					$year,
					$style,
					$template,
					$publisher,
					$location,
					(int) $ref_id
			]
		);

		return true;
	}

	public function remove_reference($id)
	{
		$query = 'delete from `tiki_page_references` where `ref_id`=?';
		$this->query($query, [(int) $id]);
		return true;
	}

	public function check_existence($page_id, $biblio_code)
	{
		$query = 'select * from `tiki_page_references` WHERE `biblio_code`=? AND `page_id`=?';
		$result = $this->query($query, [$biblio_code, $page_id]);
		return $result->numrows;
	}

	public function check_lib_existence($biblio_code)
	{
		$query = 'select * from `tiki_page_references` WHERE `biblio_code`=? AND `page_id` IS NULL';
		$result = $this->query($query, [$biblio_code]);

		return $result->numrows;
	}

	/**
	 * Return Library references containing the search term
	 *
	 * @param string $search
	 * @return array
	 */
	public function getLibContaining($search)
	{
		$result = $this->list_lib_references($search);

		$ret = [];

		foreach ($result['data'] as $res) {
			$label = [$res['biblio_code']];
			if (! empty($res['author'])) {
				$label[] = $res['author'];
			}
			if (! empty($res['title'])) {
				$label[] = $res['title'];
			}
			$ret[] = [
				'value' => $res['biblio_code'],
				'label' => implode(', ', $label)
			];
		}
		return $ret;
	}

	/**
	 * Event listener for tiki.wiki.save, will help the user by auto link library references to the page on save
	 * This avoids the need for the user to go page by page and manually link the references to the page.
	 *
	 * @param array $arguments see \TikiLib::create_page for format
	 * @param string $eventName should be tiki.wiki.create or tiki.wiki.save (not used)
	 * @param $priority the event priority (not used)
	 */
	public function autoCopyLibraryReferencesToPageReferences($arguments, $eventName, $priority)
	{
		if ($arguments['type'] !== 'wiki page') { // references are only linked in wiki pages
			return;
		}

		$codeList = \Tiki\WikiPlugin\Reference::extractBibliographicCodesFromText($arguments['data'], true);

		if (count($codeList) == 0) {
			return;
		}

		$existingReferences = $this->get_reference_from_code_and_page($codeList, $arguments['page_id']);

		$missingReferences = array_diff($codeList, array_keys($existingReferences));

		if (count($missingReferences) == 0) {
			return;
		}

		foreach ($missingReferences as $reference) {
			$this->add_lib_ref_to_page_by_code($reference, $arguments['page_id']);
		}
	}
}
