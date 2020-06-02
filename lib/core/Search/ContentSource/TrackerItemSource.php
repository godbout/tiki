<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_TrackerItemSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider, Search_FacetProvider_Interface
{
	private $db;
	private $trklib;
	private $mode;

	function __construct($mode = '')
	{
		$this->db = TikiDb::get();
		$this->trklib = TikiLib::lib('trk');
		$this->mode = $mode;
	}

	function getReferenceMap()
	{
		return [
			'tracker_id' => 'tracker',
		];
	}

	function getDocuments()
	{
		return $this->db->table('tiki_tracker_items')->fetchColumn('itemId', []);
	}

	function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
	{
		/*
			If you wonder why this method uses straight SQL and not trklib, it's because
			trklib performs no meaningful work when extracting the data and strips all
			required semantics.
		*/

		$data = [];

		$item = $this->trklib->get_tracker_item($objectId);

		if (empty($item)) {
			return false;
		}

		$itemObject = Tracker_Item::fromInfo($item);

		if (empty($itemObject) || ! $itemObject->getDefinition()) {	// ignore corrupted items, e.g. where trackerId == 0
			return false;
		}

		$permNeeded = $itemObject->getViewPermission();
		$specialUsers = $itemObject->getSpecialPermissionUsers($objectId, 'View');

		$definition = Tracker_Definition::get($item['trackerId']);

		if (! $definition) {
			return $data;
		}

		foreach (self::getIndexableHandlers($definition, $item) as $handler) {
			$data = array_merge($data, $handler->getDocumentPart($typeFactory, $this->mode));
		}

		$ownerGroup = $itemObject->getOwnerGroup();
		$data = array_merge(
			[
				'title' => $typeFactory->sortable($this->trklib->get_isMain_value($item['trackerId'], $objectId)),
				'modification_date' => $typeFactory->timestamp($item['lastModif']),
				'creation_date' => $typeFactory->timestamp($item['created']),
				'contributors' => $typeFactory->multivalue(array_unique([$item['createdBy'], $item['lastModifBy']])),
				'date' => $typeFactory->timestamp($item['created']),

				'tracker_status' => $typeFactory->identifier($item['status']),
				'tracker_id' => $typeFactory->identifier($item['trackerId']),

				'view_permission' => $typeFactory->identifier($permNeeded),

				'parent_view_permission' => $typeFactory->identifier($permNeeded),
				'parent_object_id' => $typeFactory->identifier($item['trackerId']),
				'parent_object_type' => $typeFactory->identifier('tracker'),

				// Fake attributes, removed before indexing
				'_extra_users' => $specialUsers,
				'_permission_accessor' => $itemObject->getPerms(),
				'_extra_groups' => $ownerGroup ? [$ownerGroup] : null,
			],
			$data
		);

		if (empty($data['title'])) {
			$data['title'] = $typeFactory->sortable(tr('Unknown'));
		}
		if (empty($data['language'])) {
			$data['language'] = $typeFactory->identifier('unknown');
		}

		return $data;
	}

	function getProvidedFields()
	{
		static $data;

		if (is_array($data)) {
			return $data;
		}

		$data = [
			'title',
			'language',
			'modification_date',
			'creation_date',
			'date',
			'contributors',

			'tracker_status',
			'tracker_id',

			'parent_view_permission',
			'parent_object_id',
			'parent_object_type',
		];

		foreach ($this->getAllIndexableHandlers() as $handler) {
			$data = array_merge($data, $handler->getProvidedFields());
		}

		return array_unique($data);
	}

	function getGlobalFields()
	{
		static $data;

		if (is_array($data)) {
			return $data;
		}

		$data = [];

		foreach ($this->getAllIndexableHandlers() as $handler) {
			$data = array_merge($data, $handler->getGlobalFields());
		}

		$data['title'] = true;
		$data['date'] = true;
		return $data;
	}

	public static function getIndexableHandlers($definition, $item = [])
	{
		return self::getHandlersMatching('Tracker_Field_Indexable', $definition, $item);
	}

	private static function getHandlersMatching($interface, $definition, $item)
	{
		global $prefs;

		$factory = $definition->getFieldFactory();

		$handlers = [];
		foreach ($definition->getFields() as $field) {
			if ($prefs['unified_trackerfield_keys'] === 'permName' && isset($field['permName']) && strlen($field['permName']) > Tracker_Item::PERM_NAME_MAX_ALLOWED_SIZE) {
				continue;
			}
			if ($prefs['unified_exclude_nonsearchable_fields'] === 'y' && $field['isSearchable'] !== 'y') {
				continue;
			}
			$handler = $factory->getHandler($field, $item);

			if ($handler instanceof $interface) {
				$handlers[] = $handler;
			}
		}

		return $handlers;
	}

	private function getAllIndexableHandlers()
	{
		$trackers = $this->db->table('tiki_trackers')->fetchColumn('trackerId', []);

		$handlers = [];
		foreach ($trackers as $trackerId) {
			$definition = Tracker_Definition::get($trackerId);
			$handlers = array_merge($handlers, self::getIndexableHandlers($definition));
		}

		return $handlers;
	}

	public function getFacets()
	{
		$trackers = $this->db->table('tiki_trackers')->fetchColumn('trackerId', []);

		$handlers = [];
		foreach ($trackers as $trackerId) {
			$definition = Tracker_Definition::get($trackerId);
			$handlers = array_merge($handlers, self::getHandlersMatching('Search_FacetProvider_Interface', $definition, []));
		}

		$source = new Search_FacetProvider;
		$source->addFacets([
			Search_Query_Facet_Term::fromField('tracker_id')
				->setLabel(tr('Tracker'))
				->setRenderCallback(function ($id) {
					$lib = TikiLib::lib('object');
					return $lib->get_title('tracker', $id);
				}),
			Search_Query_Facet_Term::fromField('tracker_status')
				->setLabel(tr('Tracker Status'))
				->setRenderCallback(function ($status) {
					$statuses = [
						'o' => 'Open',
						'p' => 'Pending',
						'c' => 'Closed'
					];
					return $statuses[$status];
				})
		]);

		foreach ($handlers as $handler) {
			$source->addProvider($handler);
		}

		return $source->getFacets();
	}
}
