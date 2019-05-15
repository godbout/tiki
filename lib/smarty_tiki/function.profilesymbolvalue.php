<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 *
 * \brief Smarty {profilesymbolvalue} block handler
 *
 * Retrieves the value of a profile symbol
 * i.e. the ID of an item created via a profile from its profile reference
 * Cache is default on unless specified as 'n'
 *
 * Usage:
 *    {profilesymbolvalue ref=profile_reference [profile=profile_name] [domain=profile_domain] [cache=n]}
 *
 * Examples:
 *
 *  Lookup reference from any profile installed from anywhere
 *    {profilesymbolvalue ref="profile_reference"}
 *
 *   Lookup based on reference from profile_name installed from anywhere
 *    {profilesymbolvalue ref="profile_reference" profile="profile_name"}
 *
 *  Lookup based on reference from profile_name installed from the local profiles directory
 *    {profilesymbolvalue ref="profile_reference" profile="profile_name" domain="file://profiles"}
 *
 *  Lookup based on reference from profile_name installed from profiles.tiki.org without caching
 *    {profilesymbolvalue ref="profile_reference" profile="profile_name" domain="https://profiles.tiki.org" cache="n"}
 *
 */


//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_function_profilesymbolvalue($params, $smarty)
{
	extract($params, EXTR_SKIP);

	if (empty($params['reference']) && empty($params['ref'])) {
		return '';
	}

	if (!empty($params['ref'])) {
		$ref = $params['ref'];
	} else {
		$ref = $params['reference'];
	}

	if (!empty($params['profile'])) {
		$profile = $params['profile'];
	} else {
		$profile = '';
	}

	if (!empty($params['domain'])) {
		$domain = $params['domain'];
	} else {
		$domain = '';
	}

	if (empty($domain) &&
		!empty($params['package']) &&
		\Tiki\Package\ExtensionManager::isExtensionEnabled($params['package'])) {

		$extension = \Tiki\Package\ExtensionManager::get($params['package']);
		$domain = 'file://' . $extension->getPath() . '/profiles';
	}

	if (!isset($params['cache']) || $params['cache'] != 'n') {
		$cachelib = TikiLib::lib('cache');
		$cacheType = 'profilesymbolval';
		$cacheName = $ref . '-' . $profile . '-' . $domain;
		if ($cachelib->isCached($cacheName, $cacheType)) {
			return $cachelib->getCached($cacheName, $cacheType);
		}
	}

	if (empty($domain) && empty($profile)) {
		$result = TikiLib::lib('tiki')->table('tiki_profile_symbols')->fetchOne('value', ['object' => $ref]);
	} elseif (empty($domain)) {
		$result = TikiLib::lib('tiki')->table('tiki_profile_symbols')->fetchOne('value', ['object' => $ref, 'profile' => $profile]);
	} else {
		$result = TikiLib::lib('tiki')->table('tiki_profile_symbols')->fetchOne('value', ['object' => $ref, 'profile' => $profile, 'domain' => $domain]);
	}

	if ($result) {
		if (!isset($params['cache']) || $params['cache'] != 'n') {
			$cachelib->cacheItem($cacheName, $result, $cacheType);
		}
		return $result;
	} else {
		return '';
	}
}
