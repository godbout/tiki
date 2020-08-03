<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for geographic features (points, lines, polygons)
 *
 * Letter key: ~GF~
 *
 */
class Tracker_Field_GeographicFeature extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Indexable
{
    public static function getTypes()
    {
        return [
            'GF' => [
                'name' => tr('Geographic Feature'),
                'description' => tr('Store a geographic feature on a map, allowing paths (LineString) and boundaries (Polygon) to be drawn on a map and saved.'),
                'help' => 'Geographic feature Tracker Field',
                'prefs' => ['trackerfield_geographicfeature'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        if (isset($requestData[$this->getInsertId()])) {
            $value = $requestData[$this->getInsertId()];
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        return tr('Feature cannot be set or modified through this interface.');
    }

    public function renderOutput($context = [])
    {
        return tr('Feature cannot be viewed.');
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        return [
            'geo_located' => $typeFactory->identifier('y'),
            'geo_feature' => $typeFactory->identifier($this->getValue()),
            'geo_feature_field' => $typeFactory->identifier($this->getConfiguration('permName')),
        ];
    }

    public function getProvidedFields()
    {
        return ['geo_located', 'geo_feature', 'geo_feature_field'];
    }

    public function getGlobalFields()
    {
        return [];
    }
}
