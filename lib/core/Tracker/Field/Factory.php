<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_Factory
{
    private static $trackerFieldLocalCache;

    private $trackerDefinition;
    private $typeMap = [];
    private $infoMap = [];

    public function __construct($trackerDefinition = null)
    {
        $this->trackerDefinition = $trackerDefinition;

        $fieldMap = $this->buildTypeMap(
            [
                'lib/core/Tracker/Field' => 'Tracker_Field_',
            ]
        );
    }

    private function getPreCacheTypeMap()
    {
        if (! empty(self::$trackerFieldLocalCache)) {
            $this->typeMap = self::$trackerFieldLocalCache['type'];
            $this->infoMap = self::$trackerFieldLocalCache['info'];

            return true;
        }

        return false;
    }

    private function setPreCacheTypeMap($data)
    {
        self::$trackerFieldLocalCache = [
            'type' => $data['typeMap'],
            'info' => $data['infoMap']
        ];
    }

    private function buildTypeMap($paths)
    {
        global $prefs;
        $cacheKey = 'fieldtypes.' . $prefs['language'];

        if ($this->getPreCacheTypeMap()) {
            return;
        }

        $cachelib = TikiLib::lib('cache');
        if ($data = $cachelib->getSerialized($cacheKey)) {
            $this->typeMap = $data['typeMap'];
            $this->infoMap = $data['infoMap'];

            $this->setPreCacheTypeMap($data);

            return;
        }

        foreach ($paths as $path => $prefix) {
            foreach (glob("$path/*.php") as $file) {
                if ($file === "$path/index.php") {
                    continue;
                }
                $class = $prefix . substr($file, strlen($path) + 1, -4);
                $reflected = new ReflectionClass($class);

                if ($reflected->isInstantiable() && $reflected->implementsInterface('Tracker_Field_Interface')) {
                    $providedFields = call_user_func([$class, 'getTypes']);

                    foreach ($providedFields as $key => $info) {
                        $this->typeMap[$key] = $class;
                        $this->infoMap[$key] = $info;
                    }
                }
            }
        }

        uasort($this->infoMap, [$this, 'compareName']);

        $data = [
            'typeMap' => $this->typeMap,
            'infoMap' => $this->infoMap,
        ];

        if (defined('TIKI_PREFS_DEFINED')) {
            $cachelib->cacheItem($cacheKey, serialize($data));
            $this->setPreCacheTypeMap($data);
        }
    }

    public function compareName($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    public function getFieldTypes()
    {
        return $this->infoMap;
    }

    public function getFieldInfo($type)
    {
        if (isset($this->infoMap[$type])) {
            return $this->infoMap[$type];
        }

        return [];
    }

    /**
     * Get a list of field types by their letter type and the corresponding class name
     * @Example 'q' => 'Tracker_Field_AutoIncrement', ...
     * @return array letterType => classname
     */
    public function getTypeMap()
    {
        return $this->typeMap;
    }

    public function getHandler($field_info, $itemData = [])
    {
        if (! isset($field_info['type'])) {
            // When does a field have no type? Should this not throw an exception? Chealer 2017-05-23
            return null;
        }
        $type = $field_info['type'];

        if (isset($this->typeMap[$type])) {
            $info = $this->infoMap[$type];
            $class = $this->typeMap[$type];

            global $prefs;
            foreach ($info['prefs'] as $pref) {
                if ($prefs[$pref] != 'y') {
                    Feedback::error(tr(
                        'Tracker Field Factory Error: Pref "%0" required for field type "%1"',
                        $pref,
                        $class
                    ));

                    return null;
                }
            }

            $field_info = array_merge($info, $field_info);

            if (class_exists($class) && is_callable([$class, 'build'])) {
                return call_user_func([$class, 'build'], $type, $this->trackerDefinition, $field_info, $itemData);
            }

            return new $class($field_info, $itemData, $this->trackerDefinition);
        }
    }
}
