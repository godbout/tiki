<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_GlobalSource_SearchableSource implements Search_GlobalSource_Interface
{
    public function getProvidedFields()
    {
        return ['searchable'];
    }

    public function getGlobalFields()
    {
        return [];
    }

    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        // Unless specified by content source explicitly, everything is searchable

        if (isset($data['searchable'])) {
            return [];
        }

        return [
            'searchable' => $typeFactory->identifier('y'),
        ];
    }
}
