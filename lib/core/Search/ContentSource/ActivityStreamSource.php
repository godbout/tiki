<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_ActivityStreamSource implements Search_ContentSource_Interface
{
    private $lib;
    private $relationlib;
    private $tikilib;
    private $source;
    private $mapping;
    private $trackerLib;

    public function __construct($source = null)
    {
        global $prefs;
        $this->lib = TikiLib::lib('activity');
        $this->source = $source;
        $this->relationlib = TikiLib::lib('relation');
        $this->tikilib = TikiLib::lib('tiki');
        $this->mapping = $this->lib->getMapping();
        $this->trackerLib = TikiLib::lib('trk');
    }

    public function getDocuments()
    {
        return $this->lib->getActivityList();
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
    {
        global $prefs;

        if (! $info = $this->lib->getActivity($objectId)) {
            return false;
        }

        $document = [
            'event_type' => $typeFactory->identifier($info['eventType']),
            'modification_date' => $typeFactory->timestamp($info['eventDate']),
            'date' => $typeFactory->timestamp($info['eventDate']),

            'searchable' => $typeFactory->identifier('n'),
        ];

        foreach ($info['arguments'] as $key => $value) {
            $type = isset($this->mapping[$key]) ? $this->mapping[$key] : '';

            if ($type) {
                $document[$key] = $typeFactory->$type($value);
            }
        }

        if (! isset($document['stream'])) {
            $document['stream'] = $typeFactory->multivalue(['custom']);
        }

        if ($this->source && isset($document['type'], $document['object'])) {
            $related = $this->source->getDocuments($info['arguments']['type'], $info['arguments']['object']);

            if (count($related)) {
                $first = reset($related);
                $collectedFields = ['allowed_groups', 'categories', 'deep_categories', 'freetags', 'freetags_text', 'geo_located', 'geo_location', 'relations', 'relation_types'];

                foreach ($collectedFields as $field) {
                    if (isset($first[$field])) {
                        $document[$field] = $first[$field];
                    }
                }
            }
        }

        if (isset($document['type'], $document['object'])) {
            // Add tracker special perms
            if ($info['arguments']['type'] == 'trackeritem') {
                $item = $this->trackerLib->get_tracker_item($info['arguments']['object']);
                if (empty($item)) {
                    return false;
                }

                $itemObject = Tracker_Item::fromInfo($item);

                if (empty($itemObject) || ! $itemObject->getDefinition()) {	// ignore corrupted items, e.g. where trackerId == 0
                    return false;
                }

                $specialUsers = $itemObject->getSpecialPermissionUsers($info['arguments']['object'], 'View');

                if ($specialUsers) {
                    $document['_extra_users'] = $specialUsers;
                }
            }
        }

        if ($prefs['monitor_individual_clear'] == 'y') {
            $clearList = $this->getClearList($objectId);
            $document['clear_list'] = $typeFactory->multivalue($clearList);
        } else {
            $document['clear_list'] = $typeFactory->multivalue([]);
        }

        return $document;
    }

    public function getProvidedFields()
    {
        $mapping = $this->lib->getMapping();

        return array_merge(['event_type', 'modification_date', 'clear_list', 'date'], array_keys($mapping));
    }

    public function getGlobalFields()
    {
        return [
            'date' => true,
        ];
    }

    private function getClearList($activityId)
    {
        $list = $this->relationlib->get_relations_to('activity', $activityId, 'tiki.monitor.cleared');
        $out = [];
        foreach ($list as $rel) {
            if ($rel['type'] == 'user') {
                $out[] = $rel['itemId'];
            }
        }

        return $out;
    }
}
