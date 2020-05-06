<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_TrackerFieldSource implements Search_ContentSource_Interface
{
	private $db;

	function __construct()
	{
		$this->db = TikiDb::get();
	}

	function getDocuments()
	{
		return $this->db->table('tiki_tracker_fields')->fetchColumn('fieldId', []);
	}

	function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
	{
		global $prefs;

		$lib = TikiLib::lib('trk');

		$field = $lib->get_tracker_field($objectId);

		if (! $field) {
			return false;
		}

		if ($prefs['unified_exclude_nonsearchable_fields'] === 'y' && $field['isSearchable'] !== 'y') {
			return false;
		}

		if ($prefs['unified_trackerfield_keys'] === 'permName' && isset($field['permName']) && strlen($field['permName']) > Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE) {
			Feedback::error(tr(
				'Object "%0" (type %1) was not indexed because its "Permanent name" contains more than %2 characters. It\'s recommended to change its value.',
				$objectId,
				'trackerfield',
				Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE
			));

			return false;
		}

		$trackername = tr('unknown');
		if ($definition = Tracker_Definition::get($field['trackerId'])) {
			$trackername = $definition->getConfiguration('name');
		}

		$data = [
			'title' => $typeFactory->sortable($field['name']),
			'description' => $typeFactory->plaintext($field['description']),
			'tracker_id' => $typeFactory->identifier($field['trackerId']),
			'tracker_name' => $typeFactory->sortable($trackername),
			'position' => $typeFactory->numeric($field['position']),

			'searchable' => $typeFactory->identifier('n'),

			'view_permission' => $typeFactory->identifier('tiki_p_view_trackers'),
		];

		return $data;
	}

	function getProvidedFields()
	{
		return [
			'title',
			'description',
			'tracker_id',
			'tracker_name',

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
