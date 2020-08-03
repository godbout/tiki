<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiDb_Exception_DuplicateEntry extends Exception
{
    private $key;

    public function __construct($key, $entry)
    {
        parent::__construct(tr("Duplicate entry found (%0) for key '%1'", $entry, $key));
        $this->key = $key;
    }
}
