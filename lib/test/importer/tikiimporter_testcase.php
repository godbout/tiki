<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//require_once('PHPUnit/Framework/TestCase.php');

/**
 * @group importer
 */
abstract class TikiImporter_TestCase extends PHPUnit\Framework\TestCase
{
    protected $backupGlobals = false;
}
