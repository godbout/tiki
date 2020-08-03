<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/*
 * Parent class of all test cases that use the database.
 */

//require_once (version_compare(PHPUnit_Runner_Version::id(), '3.5.0', '>=')) ? 'PHPUnit/Autoload.php' : 'PHPUnit/Framework.php';

abstract class TikiDatabaseTestCase extends PHPUnit\DbUnit\TestCase
{
    private static $pdo;

    private $conn;

    public function getConnection()
    {
        require(__DIR__ . '/local.php');

        $dbType = $db_tiki;
        if ($dbType === 'mysqli' || $dbType = 'mysqlpo' || $dbType = 'mysqlt') {
            $dbType = 'mysql'; // force the db type as mysql, all these are AdoDB drivers for mysql
        }

        if ($this->conn === null) {
            if (self::$pdo === null) {
                self::$pdo = new PDO("$dbType:host=$host_tiki;dbname=$dbs_tiki", $user_tiki, $pass_tiki);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo);
        }

        return $this->conn;
    }
}
