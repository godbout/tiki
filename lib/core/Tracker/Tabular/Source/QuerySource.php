<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class QuerySource implements SourceInterface
{
    private $schema;
    private $trackerId;
    protected $query;

    public function __construct(\Tracker\Tabular\Schema $schema, \Search_Query $query)
    {
        $def = $schema->getDefinition();
        $this->trackerId = $def->getConfiguration('trackerId');
        $this->query = $query;
        $this->schema = $schema;
    }

    public function getEntries()
    {
        $lib = \TikiLib::lib('unifiedsearch');

        $result = $this->query->scroll($lib->getIndex());

        foreach ($result as $row) {
            yield new QuerySourceEntry($row);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
