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
use Sabre\CardDAV;
use Sabre\DAVACL;
use Tiki\SabreDav\BasicAuth;
use Tiki\SabreDav\CardDAVBackend;
use Tiki\SabreDav\PrincipalBackend;
use Tiki\SabreDav\AclPlugin;

require_once 'tiki-setup.php';
TikiLib::setExternalContext(true);

// Backends
$authBackend = new BasicAuth();
$principalBackend = new PrincipalBackend();
$carddavBackend = new CardDAVBackend();

// Directory tree
$tree = array(
    new DAVACL\PrincipalCollection($principalBackend),
    new CardDAV\AddressBookRoot($principalBackend, $carddavBackend)
);  

// The object tree needs in turn to be passed to the server class
$server = new DAV\Server($tree);
$server->setBaseUri($tikiroot.'tiki-carddav.php');

// Authentication plugin
$authPlugin = new DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

// CardDAV plugin
$carddavPlugin = new CardDAV\Plugin();
$server->addPlugin($carddavPlugin);

// ACL plugin
$aclPlugin = new AclPlugin();
$aclPlugin->allowUnauthenticatedAccess = false;
$server->addPlugin($aclPlugin);

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

// And off we go!
$server->exec();
