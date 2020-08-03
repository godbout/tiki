<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_GroupSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('users_groups')->fetchColumn('groupName', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
    {
        $row = $this->db->table('users_groups')->fetchRow(['groupDesc'], ['groupName' => $objectId]);

        if (! $row) {
            return false;
        }

        $groupName = $objectId;
    
        $data = [
            'title' => $typeFactory->sortable($groupName),
            'description' => $typeFactory->plaintext($row['groupDesc']),

            'searchable' => $typeFactory->identifier('n'),

            'view_permission' => $typeFactory->identifier('tiki_p_group_view'),
        ];

        return $data;
    }

    public function getProvidedFields()
    {
        return [
            'title',
            'description',

            'searchable',

            'view_permission',
        ];
    }

    public function getGlobalFields()
    {
        return [
            'title' => true,
            'description' => true,
        ];
    }
}
