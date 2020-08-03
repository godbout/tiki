<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$



/*
 * Common interface definition for all Resolvers
 * @example A simple example how to use it: Resolver/Default.php
 */
interface Perms_Resolver
{
    /*
     * Check if a specific permission like 'add_object' exist in any of the groups
     * @param string $name  - permission name
     * @param array $groups - all groups available
     * @return bool $success - true if permission was found
     */
    public function check($permission, array $groups);

    /*
     * Get name of the object type the permissons to check belong to : i.e 'object', 'category'
     * @return $string name of object type
     */
    public function from();

    /*
     * Get array of applicable groups.
     * @return array $ applicableGroups
     */
    public function applicableGroups();

    /*
     * Dump useful resolve information for debugging purposes.
     * @return array of resolved permissions
     */
    public function dump();
}
