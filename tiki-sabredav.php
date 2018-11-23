<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Sabre\DAV;
use Tiki\SabreDav\Directory;
use Tiki\SabreDav\BasicAuth;
use Tiki\SabreDav\LocksBackend;

require_once 'tiki-setup.php';
$access->check_feature('feature_webdav');

$publicDir = new Directory($prefs['fgal_root_id']);

$server = new DAV\Server($publicDir);
$server->setBaseUri($tikiroot.'tiki-sabredav.php');

// This ensures that we get a pretty index in the browser, but it is optional.
$server->addPlugin(new DAV\Browser\Plugin());

$authBackend = new BasicAuth();
$authPlugin = new DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

$locksBackend = new LocksBackend($tikipath.'temp/davlocks');
$locksPlugin = new DAV\Locks\Plugin($locksBackend);
$server->addPlugin($locksPlugin);

$server->exec();
