<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_MySql_Table extends TikiDb_Table
{
    const MAX_MYSQL_INDEXES_PER_TABLE = 64;

    private $definition = false;
    private $indexes = [];
    private $exists = null;

    private $schemaBuffer;
    private $dataBuffer;
    private $tfTranslator;

    public function __construct($db, $table)
    {
        parent::__construct($db, $table);

        $table = $this->escapeIdentifier($this->tableName);
        $this->schemaBuffer = new Search_MySql_QueryBuffer($db, 2000, "ALTER TABLE $table ");
        $this->dataBuffer = new Search_MySql_QueryBuffer($db, 100, '-- '); // Null Object, replaced later
        $this->tfTranslator = new Search_MySql_TrackerFieldTranslator;
    }

    public function __destruct()
    {
        try {
            $this->flush();
        } catch (Search_MySql_Exception $e) {
            # ignore this to cleanly destruct the object
        }
    }

    public function drop()
    {
        $table = $this->escapeIdentifier($this->tableName);
        $this->db->query("DROP TABLE IF EXISTS $table");
        $this->definition = false;
        $this->exists = false;

        $this->emptyBuffer();
    }

    public function exists()
    {
        if (is_null($this->exists)) {
            $tables = $this->db->listTables();
            $this->exists = in_array($this->tableName, $tables);
        }

        return $this->exists;
    }

    public function insert(array $values, $ignore = false)
    {
        $keySet = implode(', ', array_map([$this, 'escapeIdentifier'], array_map([$this->tfTranslator, 'shortenize'], array_keys($values))));

        $valueSet = '(' . implode(', ', array_map([$this->db, 'qstr'], $values)) . ')';

        $this->addToBuffer($keySet, $valueSet);

        return 0;
    }

    public function ensureHasField($fieldName, $type)
    {
        $this->loadDefinition();

        if (! isset($this->definition[$fieldName])) {
            $this->addField($fieldName, $type);
            $this->definition[$fieldName] = $type;
        }
    }

    public function hasIndex($fieldName, $type)
    {
        $this->loadDefinition();

        $indexName = $fieldName . '_' . $type;

        return isset($this->indexes[$indexName]);
    }

    public function ensureHasIndex($fieldName, $type)
    {
        global $prefs;

        $this->loadDefinition();

        if (! isset($this->definition[$fieldName]) && $prefs['search_error_missing_field'] === 'y') {
            if (preg_match('/^tracker_field_/', $fieldName)) {
                $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', $fieldName);
                if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                    $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                }
            } else {
                $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', $fieldName, $fieldName);
            }
            $e = new Search_MySql_QueryException($msg);
            if ($fieldName == 'tracker_id') {
                $e->suppress_feedback = true;
            }

            throw $e;
        }

        $indexName = $fieldName . '_' . $type;

        // Static MySQL limit on 64 indexes per table
        if (! isset($this->indexes[$indexName]) && count($this->indexes) < self::MAX_MYSQL_INDEXES_PER_TABLE) {
            if ($type == 'fulltext') {
                $this->addFullText($fieldName);
            } elseif ($type == 'index') {
                $this->addIndex($fieldName);
            }

            $this->indexes[$indexName] = true;
        }
    }

    private function loadDefinition()
    {
        if (! empty($this->definition)) {
            return;
        }

        if (! $this->exists()) {
            $this->createTable();
            $this->loadDefinition();
        }

        $table = $this->escapeIdentifier($this->tableName);
        $result = $this->db->fetchAll("DESC $table");
        $this->definition = [];
        foreach ($result as $row) {
            $this->definition[$this->tfTranslator->normalize($row['Field'])] = $row['Type'];
        }

        $result = $this->db->fetchAll("SHOW INDEXES FROM $table");
        $this->indexes = [];
        foreach ($result as $row) {
            $this->indexes[$this->tfTranslator->normalize($row['Key_name'])] = true;
        }
    }

    private function createTable()
    {
        $table = $this->escapeIdentifier($this->tableName);
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS $table (
				`id` INT NOT NULL AUTO_INCREMENT,
				`object_type` VARCHAR(15) NOT NULL,
				`object_id` VARCHAR(235) NOT NULL,
				PRIMARY KEY(`id`),
				INDEX (`object_type`, `object_id`(160))
			) ENGINE=MyISAM"
        );
        $this->exists = true;

        $this->emptyBuffer();
    }

    private function addField($fieldName, $type)
    {
        $table = $this->escapeIdentifier($this->tableName);
        $fieldName = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));
        $this->schemaBuffer->push("ADD COLUMN $fieldName $type");
    }

    private function addIndex($fieldName)
    {
        $currentType = $this->definition[$fieldName];
        $alterType = null;

        $indexName = $fieldName . '_index';
        $table = $this->escapeIdentifier($this->tableName);
        $escapedIndex = $this->escapeIdentifier($this->tfTranslator->shortenize($indexName));
        $escapedField = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));

        if ($currentType == 'TEXT' || $currentType == 'text') {
            $this->schemaBuffer->push("MODIFY COLUMN $escapedField VARCHAR(235)");
            $this->definition[$fieldName] = 'VARCHAR(235)';
        }

        $this->schemaBuffer->push("ADD INDEX $escapedIndex ($escapedField)");
    }

    private function addFullText($fieldName)
    {
        $indexName = $fieldName . '_fulltext';
        $table = $this->escapeIdentifier($this->tableName);
        $escapedIndex = $this->escapeIdentifier($this->tfTranslator->shortenize($indexName));
        $escapedField = $this->escapeIdentifier($this->tfTranslator->shortenize($fieldName));
        $this->schemaBuffer->push("ADD FULLTEXT INDEX $escapedIndex ($escapedField)");
    }

    private function emptyBuffer()
    {
        $this->schemaBuffer->clear();
        $this->dataBuffer->clear();
    }

    private function addToBuffer($keySet, $valueSet)
    {
        $this->schemaBuffer->flush();

        $this->dataBuffer->setPrefix("INSERT INTO {$this->escapeIdentifier($this->tableName)} ($keySet) VALUES ");
        $this->dataBuffer->push($valueSet);
    }

    public function flush()
    {
        $this->schemaBuffer->flush();
        $this->dataBuffer->flush();
    }
}
