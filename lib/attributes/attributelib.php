<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * AttributeLib
 *
 * @uses TikiDb_Bridge
 */
class AttributeLib extends TikiDb_Bridge
{
    private $attributes;
    private $cache;

    /**
     *
     */
    public function __construct()
    {
        $this->attributes = $this->table('tiki_object_attributes');
        $this->cache = [];
    }

    /**
     * Get all attributes for an object
     *
     * @param $type string      One of \ObjectLib::get_supported_types()
     * @param $objectId mixed   Object id (or name for wiki pages)
     * @return array            Array [attribute => value]
     */
    public function get_attributes($type, $objectId)
    {
        if (count($this->cache) > 2048) {
            $this->cache = [];
        }
        if (! isset($this->cache[$type . $objectId])) {
            $this->cache[$type . $objectId] = $this->attributes->fetchMap(
                'attribute',
                'value',
                ['type' => $type, 'itemId' => $objectId, ]
            );
        }

        return $this->cache[$type . $objectId];
    }

    /**
     * Get a single attribute
     *
     * @param $type string          One of \ObjectLib::get_supported_types()
     * @param $objectId mixed       Object id (or name for wiki pages)
     * @param $attribute string     At least two dots and only lowercase letters
     * @return string|boolean       Contents of the attribute on the object or false if not present
     */
    public function get_attribute($type, $objectId, $attribute)
    {
        return $this->attributes->fetchOne(
            'value',
            ['type' => $type, 'itemId' => $objectId, 'attribute' => $attribute]
        );
    }

    /**
     * The attribute must contain at least two dots and only lowercase letters.
     * @param mixed $type
     * @param mixed $objectId
     * @param mixed $attribute
     * @param mixed $value
     * @param null|mixed $comment
     */

    /**
     * NAMESPACE management and attribute naming.
     * Please see http://dev.tiki.org/Object+Attributes+and+Relations for guidelines on
     * attribute naming, and document new tiki.*.* names that you add
     * (also grep "set_attribute" just in case there are undocumented names already used)
     */
    public function set_attribute($type, $objectId, $attribute, $value, $comment = null)
    {
        if (false === $name = $this->get_valid($attribute)) {
            return false;
        }

        if ($value == '') {
            $this->attributes->delete(
                [
                    'type' => $type,
                    'itemId' => $objectId,
                    'attribute' => $name,
                ]
            );
        } else {
            $this->attributes->insertOrUpdate(
                [
                    'value' => $value,
                    'comment' => $comment,
                ],
                [
                    'type' => $type,
                    'itemId' => $objectId,
                    'attribute' => $name,
                ]
            );
        }

        // update the cache
        $this->cache[$type . $objectId] = $this->attributes->fetchMap(
            'attribute',
            'value',
            ['type' => $type, 'itemId' => $objectId, ]
        );

        return true;
    }

    /**
     * @param $name
     * @return mixed
     */
    private function get_valid($name)
    {
        $filter = TikiFilter::get('attribute_type');

        return $filter->filter($name);
    }

    /**
     * @param $attribute
     * @param $value
     * @return mixed
     */
    public function find_objects_with($attribute, $value)
    {
        $attribute = $this->get_valid($attribute);

        return $this->attributes->fetchAll(
            ['type', 'itemId'],
            ['attribute' => $attribute, 'value' => $value, ]
        );
    }

    /**
     * @param $attribute
     * @param $value
     * @return mixed
     */
    public function delete_objects_with($attribute, $value)
    {
        $attribute = $this->get_valid($attribute);

        return $this->attributes->delete(
            ['attribute' => $attribute, 'value' => $value]
        );
    }
}
