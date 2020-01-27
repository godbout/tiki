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
use Sabre\CalDAV;
use Sabre\DAVACL;
use Tiki\SabreDav\BasicAuth;
use Tiki\SabreDav\CalDAVBackend;
use Tiki\SabreDav\PrincipalBackend;
use Tiki\SabreDav\AclPlugin;

require_once 'tiki-setup.php';
$access->check_feature('feature_calendar');
include_once('lib/calendar/calrecurrence.php');

// Backends
$authBackend = new BasicAuth();
$principalBackend = new PrincipalBackend();
$calendarBackend = new CalDAVBackend();

// Directory tree
$tree = array(
    new DAVACL\PrincipalCollection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend)
);  

// The object tree needs in turn to be passed to the server class
$server = new DAV\Server($tree);
$server->setBaseUri($tikiroot.'tiki-caldav.php');

// Authentication plugin
$authPlugin = new DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

// CalDAV plugin
$caldavPlugin = new CalDAV\Plugin();
$server->addPlugin($caldavPlugin);

// CalDAV addons
$server->addPlugin(new CalDAV\Schedule\Plugin());
$server->addPlugin(new DAV\Sharing\Plugin());
$server->addPlugin(new CalDAV\SharingPlugin());
$server->addPlugin(new CalDAV\ICSExportPlugin());

// ACL plugin
$aclPlugin = new AclPlugin();
$server->addPlugin($aclPlugin);

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();
