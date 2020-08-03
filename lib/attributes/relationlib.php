<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * RelationLib
 *
 * @uses TikiDb_Bridge
 */
class RelationLib extends TikiDb_Bridge
{
    private $table;

    public function __construct()
    {
        $this->table = $this->table('tiki_object_relations');
    }

    /**
     * Obtains the list of relations with a given object as the source.
     * Optionally, the relation searched for can be specified. If the
     * relation ends with a dot, it will be used as a wildcard.
     * @param mixed $type
     * @param mixed $object
     * @param null|mixed $relation
     * @param mixed $orderBy
     * @param mixed $max
     */
    public function get_relations_from($type, $object, $relation = null, $orderBy = '', $max = -1)
    {
        if (substr($relation, -7) === '.invert') {
            return $this->get_relations_to($type, $object, substr($relation, 0, -7), $orderBy, $max);
        }

        $cond = [
            'source_type' => $type,
            'source_itemId' => $object
        ];

        $fields = [
            'relationId',
            'relation',
            'type' => 'target_type',
            'itemId' => 'target_itemId',
        ];

        $cond = $this->apply_relation_condition($relation, $cond);

        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy);
    }


    /**
     * Obtain a list of objects that have a given relation
     *
     * @param mixed $relation
     * @param mixed $orderBy
     * @param mixed $max
     */
    public function get_related_objects($relation, $orderBy = '', $max = -1)
    {
        $cond = [
            'relation' => $relation,
        ];

        $fields = [
            'source_type',
            'source_itemId',
            'target_type',
            'target_itemId',
        ];

        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy); /// resultset as an array
    }

    /**
     * This is a convenience function to get all the matching IDs from
     * get_relations_from without caring about the object type which might be assumed
     * @param mixed $type
     * @param mixed $object
     * @param null|mixed $relation
     */
    public function get_object_ids_with_relations_from($type, $object, $relation = null)
    {
        $ret = [];
        $relations = $this->get_relations_from($type, $object, $relation);
        foreach ($relations as $r) {
            $ret[] = $r['itemId'];
        }

        return $ret;
    }

    /**
     * This is a convenience function to get all the matching IDs from
     * get_relations_to without caring about the object type which might be assumed
     * @param mixed $type
     * @param mixed $object
     * @param null|mixed $relation
     */
    public function get_object_ids_with_relations_to($type, $object, $relation = null)
    {
        $ret = [];
        $relations = $this->get_relations_to($type, $object, $relation);
        foreach ($relations as $r) {
            $ret[] = $r['itemId'];
        }

        return $ret;
    }

    /**
     * @param $type
     * @param $object
     * @param null $relation
     * @param mixed $orderBy
     * @param mixed $max
     * @return mixed
     */
    public function get_relations_to($type, $object, $relation = null, $orderBy = '', $max = -1)
    {
        if (substr($relation, -7) === '.invert') {
            return $this->get_relations_from($type, $object, substr($relation, 0, -7), $orderBy, $max);
        }

        $cond = [
            'target_type' => $type,
            'target_itemId' => $object
        ];

        $fields = [
            'relationId',
            'relation',
            'type' => 'source_type',
            'itemId' => 'source_itemId',
        ];

        $cond = $this->apply_relation_condition($relation, $cond);

        return $this->table->fetchAll($fields, $cond, $max, -1, $orderBy);
    }

    /**
     * The relation must contain at least two dots and only lowercase letters.
     * NAMESPACE management and relation naming.
     * Please see http://dev.tiki.org/Object+Attributes+and+Relations for guidelines on
     * relation naming, and document new tiki.*.* names that you add.
     * (also grep "add_relation" just in case there are undocumented names already used)
     * @param mixed $relation
     * @param mixed $src_type
     * @param mixed $src_object
     * @param mixed $target_type
     * @param mixed $target_object
     * @param mixed $ignoreExisting
     */
    public function add_relation($relation, $src_type, $src_object, $target_type, $target_object, $ignoreExisting = false)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (substr($relation, -7) === '.invert') {
            return $this->add_relation(substr($relation, 0, -7), $target_type, $target_object, $src_type, $src_object);
        }

        if ($relation) {
            if (! $id = $this->get_relation_id($relation, $src_type, $src_object, $target_type, $target_object)) {
                $id = $this->table->insert(
                    [
                        'relation' => $relation,
                        'source_type' => $src_type,
                        'source_itemId' => $src_object,
                        'target_type' => $target_type,
                        'target_itemId' => $target_object,
                    ]
                );
            } elseif ($ignoreExisting) {
                return 0;
            }

            // Array written to match event trigger that was previously in wikiplugin_addrelation
            TikiLib::events()->trigger('tiki.social.relation.add', [
                'relation' => $relation,
                'sourcetype' => $src_type,
                'sourceobject' => $src_object,
                'type' => $target_type,
                'object' => $target_object,
                'user' => $GLOBALS['user'],
            ]);

            TikiLib::lib('tiki')->refresh_index($src_type, $src_object);
            TikiLib::lib('tiki')->refresh_index($target_type, $target_object);

            return $id;
        }

        return 0;
    }

    /**
     * @param $relation
     * @param $src_type
     * @param $src_object
     * @param $target_type
     * @param $target_object
     * @return int
     */
    public function get_relation_id($relation, $src_type, $src_object, $target_type, $target_object)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (substr($relation, -7) === '.invert') {
            return $this->get_relation_id(substr($relation, 0, -7), $target_type, $target_object, $src_type, $src_object);
        }

        $id = 0;
        if ($relation) {
            $id = $this->table->fetchOne(
                'relationId',
                [
                    'relation' => $relation,
                    'source_type' => $src_type,
                    'source_itemId' => $src_object,
                    'target_type' => $target_type,
                    'target_itemId' => $target_object,
                ]
            );
        }

        return $id;
    }

    /**
     * @param $relation_prefix
     * @param $src_type
     * @param $src_object
     * @param $target_type
     * @param $target_object
     * @return array
     */
    public function get_relations_by_prefix($relation_prefix, $src_type, $src_object, $target_type, $target_object)
    {
        $ids = [];
        if ($relation_prefix) {
            $ids = $this->table->fetchAll(
                [],
                [
                    'relation' => $this->table->like($relation_prefix . ".%"),
                    'source_type' => $src_type,
                    'source_itemId' => $src_object,
                    'target_type' => $target_type,
                    'target_itemId' => $target_object,
                ]
            );
        }

        return $ids;
    }

    /**
     * @param $relation
     * @param $type
     * @param $object
     * @param $get_invert default=false
     * @return int
     */
    public function get_relation_count($relation, $type, $object = null, $get_invert = false)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if (! $relation) {
            return 0;
        }

        if ($get_invert) {
            $count = $this->table->fetchCount(
                array_filter([
                    'relation' => $relation,
                    'source_type' => $type,
                    'source_itemId' => $object,
                ])
            );
        } else {
            $count = $this->table->fetchCount(
                array_filter([
                    'relation' => $relation,
                    'target_type' => $type,
                    'target_itemId' => $object,
                ])
            );
        }

        return $count;
    }

    public function relation_exists($relation, $type)
    {
        return $this->get_relation_count($relation, $type) || $this->get_relation_count($relation, $type, null, true);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_relation($id)
    {
        return $this->table->fetchFullRow(
            [
                'relationId' => $id,
            ]
        );
    }

    /**
     * @param $id
     */
    public function remove_relation($id)
    {
        $relation_info = $this->get_relation($id);
        $this->table->delete(
            [
                'relationId' => $id,
            ]
        );
        $this->table('tiki_object_attributes')->deleteMultiple(
            [
                'type' => 'relation',
                'itemId' => $id,
            ]
        );

        TikiLib::events()->trigger('tiki.social.relation.remove', [
            'relation' => $relation_info['relation'],
            'sourcetype' => $relation_info['source_type'],
            'sourceobject' => $relation_info['source_itemId'],
            'type' => $relation_info['target_type'],
            'object' => $relation_info['target_itemId'],
            'user' => $GLOBALS['user'],
        ]);

        TikiLib::lib('tiki')->refresh_index($relation_info['source_type'], $relation_info['source_itemId']);
        TikiLib::lib('tiki')->refresh_index($relation_info['target_type'], $relation_info['target_itemId']);
    }

    /**
     * Remove all relations from that type and source items belonging to that tracker
     * @param $relation - the relation type
     * @param $trackerId - the tracker id
     */
    public function remove_relation_type($relation, $trackerId)
    {
        return $this->query("DELETE FROM tiki_object_relations
			WHERE relation = ?
			AND source_type = 'trackeritem'
			AND source_itemId IN(
				SELECT itemId FROM tiki_tracker_items WHERE trackerId = ?
			)", [$relation, $trackerId]);
    }

    /**
     * Remove all relations of a type for single object
     * @param $fromType - object type
     * @param $fromId - object itemId
     * @param $relationType - relation type originating from that object
     */
    public function remove_relations_from($fromType, $fromId, $relationType)
    {
        return $this->table->deleteMultiple(
            [
                'relation' => $relationType,
                'source_type' => $fromType,
                'source_itemId' => $fromId
            ]
        );
    }

    /**
     * Changes to relation name should update existing relation table entries
     *
     * @param $from - old relation name
     * @param $to - new relation name
     */
    public function update_relation($from, $to)
    {
        $this->table->updateMultiple([
            'relation' => $to
        ], [
            'relation' => $from
        ]);
    }

    /**
     * @param $relation
     * @param $cond
     * @param $vars
     */
    private function apply_relation_condition($relation, $cond)
    {
        $relation = TikiFilter::get('attribute_type')->filter($relation);

        if ($relation) {
            if (substr($relation, -1) == '.') {
                $relation .= '%';
            }

            $cond['relation'] = $this->table->like($relation);
        }

        return $cond;
    }
}
