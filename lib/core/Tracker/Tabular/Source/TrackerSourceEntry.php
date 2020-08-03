<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class TrackerSourceEntry
{
    private $item;
    private $data;
    private $extra;

    public function __construct($itemId)
    {
        $this->item = \Tracker_Item::fromId($itemId);
        $this->data = $this->item->getData();
        $this->extra = [
            'itemId' => $this->data['itemId'],
            'status' => $this->data['status'],
        ];
    }

    public function render(\Tracker\Tabular\Schema\Column $column)
    {
        $field = $column->getField();
        if (isset($this->data['fields'][$field])) {
            $value = $this->data['fields'][$field];
        } else {
            $value = null;
        }

        return $column->render($value, $this->extra);
    }
}
