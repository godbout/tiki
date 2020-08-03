<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_GlobalSource_Static implements Search_GlobalSource_Interface
{
    private $data;
    private $typeMap;

    public function __construct($data, $typeMap)
    {
        $this->data = $data;
        $this->typeMap = $typeMap;
    }

    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        $out = [];

        foreach ($this->data["$objectType:$objectId"] as $key => $value) {
            $type = $this->typeMap[$key];
            $out[$key] = $typeFactory->$type($value);
        }

        return $out;
    }

    public function getProvidedFields()
    {
        return array_keys($this->typeMap);
    }

    public function getGlobalFields()
    {
        return array_fill_keys(array_keys($this->typeMap), true);
    }
}
