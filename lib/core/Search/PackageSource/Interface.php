<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_PackageSource_Interface
{
    /*
     * Returns a boolean for whether or not the AddonSource should be indexing for this particular item
     */
    public function toIndex($objectType, $objectId, $data);

    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = []);

    public function getProvidedFields();

    public function getGlobalFields();
}
