<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_ForumSource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        return $this->db->table('tiki_forums')->fetchColumn('forumId', []);
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
    {
        $lib = TikiLib::lib('comments');

        $item = $lib->get_forum($objectId);

        if (! $item) {
            return false;
        }

        $data = [
            'title' => $typeFactory->sortable($item['name']),
            'creation_date' => $typeFactory->timestamp($item['created']),
            'date' => $typeFactory->timestamp($item['created']),
            'description' => $typeFactory->plaintext($item['description']),
            'language' => $typeFactory->identifier($item['forumLanguage'] ?: 'unknown'),

            'forum_section' => $typeFactory->identifier($item['section']),

            'view_permission' => $typeFactory->identifier('tiki_p_forum_read'),
        ];

        return $data;
    }

    public function getProvidedFields()
    {
        return [
            'title',
            'creation_date',
            'date',
            'description',
            'language',

            'forum_section',

            'searchable',

            'view_permission',
        ];
    }

    public function getGlobalFields()
    {
        return [
            'title' => true,
            'description' => true,
            'date' => true,
        ];
    }
}
