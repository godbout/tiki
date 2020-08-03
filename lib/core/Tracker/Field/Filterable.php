<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Tracker_Field_Filterable
{
    public function getFilterCollection();

    /**
     * Defined in abstract, but needed when using remote indexing.
     * @param mixed $prefix
     */
    public function setBaseKeyPrefix($prefix);
}
