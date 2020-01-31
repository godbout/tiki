<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAVACL;

/**
 * Tiki SabreDav ACL Plugin
 *
 * Only uses principal exposure. Real ACL is done in backends.
 *
 */
class AclPlugin extends DAVACL\Plugin {
    /**
     * Checks if the current user has the specified privilege(s).
     * Always return true as real ACL is done in corresponding backends.
     *
     * @return bool
     */
    public function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true)
    {
        
        return true;
    }
}
