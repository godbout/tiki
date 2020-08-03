<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular;

class Manager
{
    private $table;

    public function __construct(\TikiDb $db)
    {
        $this->table = $db->table('tiki_tabular_formats');
    }

    public function getList($conditions = [])
    {
        return $this->table->fetchAll(['tabularId', 'name', 'trackerId'], $conditions, -1, -1, 'name_asc');
    }

    public function getInfo($tabularId)
    {
        $info = $this->table->fetchFullRow(['tabularId' => $tabularId]);

        $info['format_descriptor'] = json_decode($info['format_descriptor'], true) ?: [];
        $info['filter_descriptor'] = json_decode($info['filter_descriptor'], true) ?: [];
        $info['config'] = json_decode($info['config'], true) ?: [];

        return $info;
    }

    public function create($name, $trackerId)
    {
        return $this->table->insert([
            'name' => $name,
            'trackerId' => $trackerId,
            'format_descriptor' => '[]',
            'filter_descriptor' => '[]',
            'config' => json_encode([
                'simple_headers' => 0,
                'import_update' => 1,
                'ignore_blanks' => 0,
                'import_transaction' => 0,
                'bulk_import' => 0,
                'skip_unmodified' => 0,
            ]),
        ]);
    }

    public function update($tabularId, $name, array $fields, array $filters, array $config)
    {
        return $this->table->update([
            'name' => $name,
            'format_descriptor' => json_encode($fields),
            'filter_descriptor' => json_encode($filters),
            'config' => json_encode([
                'simple_headers' => (int)! empty($config['simple_headers']),
                'import_update' => (int)! empty($config['import_update']),
                'ignore_blanks' => (int)! empty($config['ignore_blanks']),
                'import_transaction' => (int)! empty($config['import_transaction']),
                'bulk_import' => (int)! empty($config['bulk_import']),
                'skip_unmodified' => (int)! empty($config['skip_unmodified']),
            ])
        ], ['tabularId' => $tabularId]);
    }

    public function remove($tabularId)
    {
        return $this->table->delete(['tabularId' => $tabularId]);
    }
}
