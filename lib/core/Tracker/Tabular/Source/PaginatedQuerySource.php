<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class PaginatedQuerySource extends QuerySource
{
    private $resultset;

    public function getEntries()
    {
        $result = $this->getResultSet();

        foreach ($result as $row) {
            yield new QuerySourceEntry($row);
        }
    }

    public function getResultSet()
    {
        if (! $this->resultset) {
            $lib = \TikiLib::lib('unifiedsearch');

            $this->resultset = $this->query->search($lib->getIndex());
        }

        return $this->resultset;
    }
}
