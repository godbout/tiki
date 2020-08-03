<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

abstract class DeclFilter_UnsetRule implements DeclFilter_Rule
{
    public function apply(array &$data, $key)
    {
        unset($data[$key]);
    }
}
