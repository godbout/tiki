<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package\Extension;

use Tiki\Package\ExtensionManager;
use TikiDb_Bridge;

class Utilities extends TikiDb_Bridge
{
	public function isInstalled($folder)
	{
		$installed = array_keys(ExtensionManager::getInstalled());
		if (strpos($folder, '/') !== false && strpos($folder, '_') === false) {
			$package = $folder;
		} else {
			$package = str_replace('_', '/', $folder);
		}
		if (in_array($package, $installed)) {
			return true;
		} else {
			return false;
		}
	}

	public function getObjectId($folder, $ref, $profile = '', $domain = '')
	{
		if (strpos($folder, '/') !== false && strpos($folder, '_') === false) {
			$folder = str_replace('/', '_', $folder);
		}
		if (empty($domain)) {
			$extensionPaths = ExtensionManager::getPaths();
			$path = $extensionPaths[$folder];
			$domain = 'file://' . $path . '/profiles';
		}

		if (! $profile) {
			if ($this->table('tiki_profile_symbols')->fetchCount(['domain' => $domain, 'object' => $ref]) > 1) {
				return $this->table('tiki_profile_symbols')->fetchColumn('value', ['domain' => $domain, 'object' => $ref]);
			} else {
				return $this->table('tiki_profile_symbols')->fetchOne('value', ['domain' => $domain, 'object' => $ref]);
			}
		} else {
			return $this->table('tiki_profile_symbols')->fetchOne('value', ['domain' => $domain, 'object' => $ref, 'profile' => $profile]);
		}
	}

	public function getFolderFromObject($type, $id)
	{
		$type = \Tiki_Profile_Installer::convertTypeInvert($type);
		$domain = $this->table('tiki_profile_symbols')->fetchOne('domain', ['value' => $id, 'type' => $type]);

		preg_match('/^file://?(vendor|vendor_custom)/(.*)/profiles$/', $domain, $matches);

		return empty($matches[2]) ? '' : $matches[2];
	}

	public function getExtensionFilePath($filepath)
	{
		foreach (ExtensionManager::getPaths() as $path) {
			if (file_exists($path . "/" . $filepath)) {
				return $path . "/" . $filepath;
			}
		}
		return false;
	}
}
