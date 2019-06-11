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

	public function removeObject($objectId, $type)
	{
		if (empty($objectId) || empty($type)) {
			return;
		}
		// TODO add other types
		if ($type == 'wiki_page' || $type == 'wiki' || $type == 'wiki page' || $type == 'wikipage') {
			\TikiLib::lib('tiki')->remove_all_versions($objectId);
		}
		if ($type == 'tracker' || $type == 'trk') {
			\TikiLib::lib('trk')->remove_tracker($objectId);
		}
		if ($type == 'category' || $type == 'cat') {
			\TikiLib::lib('categ')->remove_category($objectId);
		}
		if ($type == 'file_gallery' || $type == 'file gallery' || $type == 'filegal' || $type == 'fgal' || $type == 'filegallery') {
			\TikiLib::lib('filegal')->remove_file_gallery($objectId);
		}
		if ($type == 'activity' || $type == 'activitystream' || $type == 'activity_stream' || $type == 'activityrule' || $type == 'activity_rule') {
			\TikiLib::lib('activity')->deleteRule($objectId);
		}
		if ($type == 'forum' || $type == 'forums') {
			\TikiLib::lib('comments')->remove_forum($objectId);
		}
		if ($type == 'trackerfield' || $type == 'trackerfields' || $type == 'tracker field') {
			$trklib = \TikiLib::lib('trk');
			$res = $trklib->get_tracker_field($objectId);
			$trklib->remove_tracker_field($objectId, $res['trackerId']);
		}
		if ($type == 'module' || $type == 'modules') {
			$modlib = \TikiLib::lib('mod');
			$modlib->unassign_module($objectId);
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
