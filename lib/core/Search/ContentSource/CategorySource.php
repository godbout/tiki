<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_CategorySource implements Search_ContentSource_Interface
{
	private $db;

	function __construct()
	{
		$this->db = TikiDb::get();
	}

	function getDocuments()
	{
		return $this->db->table('tiki_categories')->fetchColumn('categId', []);
	}

	function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
	{
		$lib = TikiLib::lib('categ');

		$item = $lib->get_category($objectId);

		if (! $item) {
			return false;
		}

		$data = [
			'title' => $typeFactory->sortable($item['name']),
			'description' => $typeFactory->plaintext($item['description']),
			'path' => $typeFactory->sortable($item['categpath']),

			'searchable' => $typeFactory->identifier('n'),

			'view_permission' => $typeFactory->identifier('tiki_p_view_category'),
		];

		return $data;
	}

	function getProvidedFields()
	{
		return [
			'title',
			'description',
			'path',

			'searchable',

			'view_permission',
		];
	}

	function getGlobalFields()
	{
		return [
			'title' => true,
			'description' => true,
		];
	}
}
