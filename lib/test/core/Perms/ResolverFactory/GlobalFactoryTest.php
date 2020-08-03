<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */
class Perms_ResolverFactory_GlobalFactoryTest extends PHPUnit\Framework\TestCase
{
    private $tableData = [];

    protected function setUp() : void
    {
        $db = TikiDb::get();

        $result = $db->query('SELECT groupName, permName FROM users_grouppermissions');
        while ($row = $result->fetchRow()) {
            $this->tableData[] = $row;
        }

        $db->query('DELETE FROM users_grouppermissions');
    }

    protected function tearDown() : void
    {
        $db = TikiDb::get();

        $db->query('DELETE FROM users_grouppermissions');

        foreach ($this->tableData as $row) {
            $db->query('INSERT INTO users_grouppermissions (groupName, permName) VALUES(?,?)', array_values($row));
        }
    }

    public function testHashIsConstant()
    {
        $factory = new Perms_ResolverFactory_GlobalFactory;

        $this->assertEquals('global', $factory->getHash([]));
        $this->assertEquals('global', $factory->getHash(['type' => 'wiki page', 'object' => 'HomePage']));
    }

    public function testObtainGlobalPermissions()
    {
        $db = TikiDb::get();
        $query = 'INSERT INTO users_grouppermissions (groupName, permName) VALUES(?,?)';
        $db->query($query, ['Anonymous', 'tiki_p_view']);
        $db->query($query, ['Anonymous', 'tiki_p_edit']);
        $db->query($query, ['Registered', 'tiki_p_remove']);
        $db->query($query, ['Admins', 'tiki_p_admin']);

        $expect = new Perms_Resolver_Static(
            [
                'Anonymous' => ['view', 'edit'],
                'Registered' => ['remove'],
                'Admins' => ['admin'],
            ]
        );

        $factory = new Perms_ResolverFactory_GlobalFactory;
        $this->assertEquals($expect, $factory->getResolver([]));
    }
}
