<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class CsvSourceEntry
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render(\Tracker\Tabular\Schema\Column $column)
    {
        $entry = $this->data[spl_object_hash($column)];

        return $column->render($entry);
    }

    public function parseInto(& $info, $column)
    {
        $entry = $this->data[spl_object_hash($column)];
        $column->parseInto($info, $entry);
    }
}
