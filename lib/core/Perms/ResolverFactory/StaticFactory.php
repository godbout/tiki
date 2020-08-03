<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Simple factory always providing the same resolver. Used to provide
 * a grant-all resolver to admin users.
 */
class Perms_ResolverFactory_StaticFactory implements Perms_ResolverFactory
{
    private $key;
    private $resolver;

    public function __construct($key, $resolver)
    {
        $this->key = $key;
        $this->resolver = $resolver;
    }

    public function bulk(array $baseContext, $bulkKey, array $values)
    {
        return [];
    }

    public function getHash(array $context)
    {
        return $this->key;
    }

    public function getResolver(array $context)
    {
        return $this->resolver;
    }
}
