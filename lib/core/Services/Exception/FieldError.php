<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Exception_FieldError extends Services_Exception
{
    public function __construct($field, $message)
    {
        parent::__construct("<!--field[$field]-->" . $message, 409);
    }
}
