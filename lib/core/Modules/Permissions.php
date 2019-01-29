<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Modules;

use \TikiLib;

/**
 * Class responsible for all modules permission logic
 */
class Permissions
{
	/**
	 * Return page permissions
	 *
	 * @return array
	 */
	public function getPagePermissions()
	{
		$url = $_SERVER['SCRIPT_NAME'];
		$tikilib = TikiLib::lib('tiki');
		$userlib = TikiLib::lib('user');
		$allGroups = $userlib->list_all_groups();
		$permissions = [];

		if (null == $objectType = $this->findObjectType($url)) {
			return null;
		}

		switch ($objectType) {
			case 'wiki page':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$objectId = ! empty($_REQUEST['page']) ? $_REQUEST['page'] : null;
				if (isset($objectId)) {
					$listPermissions = $this->listPermissions($objectId, $objectType, $objectId);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'file gallery':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$filegallib = TikiLib::lib('filegal');
				$object = ! empty($_REQUEST['galleryId']) ? $filegallib->get_file_gallery_info($_REQUEST['galleryId']) : null;
				if (isset($object)) {
					$listPermissions = $this->listPermissions($object['galleryId'], $objectType, $object['name']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'tracker':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$trackerId = ! empty($_REQUEST['trackerId']) ? $_REQUEST['trackerId'] : null;
				$item = ! empty($_REQUEST['itemId']) ? TikiLib::lib('trk')->get_tracker_item($_REQUEST['itemId']) : null;
				if (isset($trackerId) || isset($item)) {
					$objectId = isset($trackerId) ? $trackerId : $item['trackerId'];
					$object = TikiLib::lib('trk')->get_tracker($objectId);
					$listPermissions = $this->listPermissions($objectId, $objectType, $object['name']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'forum':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$commentslib = TikiLib::lib('comments');
				$object = ! empty($_REQUEST['forumId']) ? $commentslib->get_forum($_REQUEST['forumId']) : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object['forumId'], $objectType, $object['name']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'group':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$object = ! empty($_REQUEST['group']) ? $_REQUEST['group'] : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object, $objectType, '');
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'articles':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$artlib = TikiLib::lib('art');
				$object = ! empty($_REQUEST['articleId']) ? $artlib->get_article($_REQUEST['articleId']) : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object['articleId'], $objectType, $object['title']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'blog':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$bloglib = TikiLib::lib('blog');
				$object = ! empty($_REQUEST['blogId']) ? $bloglib->get_blog($_REQUEST['blogId']) : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object['blogId'], $objectType, $object['title']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'calendar':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$calendarlib = TikiLib::lib('calendar');
				$object = ! empty($_REQUEST['calendarId']) ? $calendarlib->get_calendar($_REQUEST['calendarId']) : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object['calendarId'], $objectType, $object['name']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
			case 'sheet':
				$permissions = $this->getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups);
				$sheetlib = TikiLib::lib('sheet');
				$object = ! empty($_REQUEST['sheetId']) ? $sheetlib->get_sheet_info($_REQUEST['sheetId']) : null;
				if (! empty($object)) {
					$listPermissions = $this->listPermissions($object['sheetId'], $objectType, $object['title']);
					$this->getOtherPermissions($permissions, $listPermissions);
				}
				break;
		}

		ksort($permissions);

		return $permissions;
	}

	protected function findObjectType($url)
	{

		$objectPaths = [
			'wiki page' => [
				'tiki-index.php',
				'tiki-listpages.php',
				'tiki-editpage.php',
				'tiki-copypage.php',
				'tiki-pagehistory.php'
			],
			'file gallery' => [
				'tiki-list_file_gallery.php'
			],
			'tracker' => [
				'tiki-list_trackers.php',
				'tiki-view_tracker.php',
				'tiki-view_tracker_item.php',
				'tiki-admin_tracker_fields.php'
			],
			'forum' => [
				'tiki-forums.php',
				'tiki-view_forum.php',
				'tiki-admin_forums.php',
				'tiki-forum_import.php'],
			'group' => [
				'tiki-admingroups.php'
			],
			'articles' => [
				'tiki-list_articles.php',
				'tiki-view_articles.php',
				'tiki-edit_article.php',
				'tiki-read_article.php'
			],
			'blog' => [
				'tiki-list_blogs.php',
				'tiki-edit_blog.php',
				'tiki-blog_post.php',
				'tiki-list_posts.php',
				'tiki-view_blog.php',
				'tiki-view_blog_post.php'
			],
			'calendar' => [
				'tiki-calendar.php',
				'tiki-calendar_edit_item.php',
				'tiki-admin_calendars.php',
				'tiki-calendar_import.php'
			],
			'sheet' => [
				'tiki-sheets.php',
				'tiki-view_sheets.php',
				'tiki-graph_sheet.php',
				'tiki-history_sheets.php',
				'tiki-export_sheet.php',
				'tiki-import_sheet.php',
			],
		];

		foreach ($objectPaths as $object => $paths) {
			foreach ($paths as $path) {
				if (strpos($url, $path) !== false) {
					return $object;
				}
			}
		}

		return null;
	}

	/**
	 * Get global permission for a given object
	 *
	 * @param $objectType
	 * @param $tikilib
	 * @param $userlib
	 * @param $allGroups
	 * @return array
	 */
	protected function getGlobalPermissions($objectType, $tikilib, $userlib, $allGroups)
	{
		$globalPermissions = [];
		$permissionGroup = $tikilib->get_permGroup_from_objectType($objectType);
		$allPermissionGroup = $userlib->get_permissions(0, -1, 'permName_asc', '', $permissionGroup);

		foreach ($allGroups as $group) {
			$permissions = $userlib->get_group_permissions($group);
			foreach ($allPermissionGroup['data'] as $permission) {
				if (in_array($permission['permName'], $permissions)) {
					if (! empty($globalPermissions[$permission['permName']]['global']) && ! in_array($group, $globalPermissions[$permission['permName']]['global'])) {
						$globalPermissions[$permission['permName']]['global'][] = $group;
					}
				}
			}
		}

		return $globalPermissions;
	}

	/**
	 * Get object and category permissions
	 *
	 * @param array $permissions
	 * @param array $listPermissions
	 */
	protected function getOtherPermissions(&$permissions, $listPermissions)
	{
		if (count($listPermissions['special']) > 0) {
			foreach ($listPermissions['special'] as $objectInfo) {
				$permissions[$objectInfo['perm']]['object'][] = $objectInfo;
			}
		}
		if (count($listPermissions['category']) > 0) {
			foreach ($listPermissions['category'] as $objectInfo) {
				$permissions[$objectInfo['perm']]['category'][] = $objectInfo;
			}
		}
	}

	/**
	 * List permissions for a given object
	 *
	 * @param $objectId
	 * @param $objectType
	 * @param $objectName
	 * @param string $filterGroup
	 * @return array
	 */
	protected function listPermissions($objectId, $objectType, $objectName, $filterGroup = '')
	{
		global $prefs;
		$userlib = TikiLib::lib('user');
		$ret = [];
		$cats = [];
		$perms = $userlib->get_object_permissions($objectId, $objectType);
		$allPermissions = $userlib->get_permissions();

		if (! empty($perms)) {
			foreach ($perms as $perm) {
				if (empty($filterGroup) || in_array($perm['groupName'], $filterGroup)) {
					$json = json_encode([
						'group' => $perm['groupName'],
						'perm' => $perm['permName'],
						'objectId' => $objectId,
						'objectType' => $objectType
					]);
					$ret[] = [
						'group' => $perm['groupName'],
						'perm' => $perm['permName'],
						'reason' => 'Object',
						'objectId' => $objectId,
						'objectType' => $objectType,
						'objectName' => $objectName,
						'json' => $json
					];
				}
			}
		}

		if ($prefs['feature_categories'] == 'y') {
			$categlib = TikiLib::lib('categ');
			$categs = $categlib->get_object_categories($objectType, $objectId);
			if (! empty($categs)) {
				foreach ($categs as $categId) {
					$categoryObjectPermissions = $userlib->get_object_permissions($categId, 'category');
					if (! empty($categoryObjectPermissions)) {
						foreach ($categoryObjectPermissions as $categoryPermission) {
							if ($this->isPermission($allPermissions, $categoryPermission['permName'], $objectType)) {
								$cats[] = [
									'group' => $categoryPermission['groupName'],
									'perm' => $categoryPermission['permName'],
									'reason' => 'Category',
									'objectId' => $categId,
									'objectType' => 'category',
									'objectName' => $categlib->get_category_name($categId)
								];
							}
						}
					}
				}
			}
		}

		return [
			'objectId' => $objectId,
			'special' => $ret,
			'category' => $cats
		];
	}

	/**
	 * Check user has permission
	 *
	 * @param $permName
	 * @param $objectType
	 * @return bool
	 */
	protected function isPermission($allPermissions, $permName, $objectType)
	{
		global $tikilib;
		if (! empty($allPermissions)) {
			$permGroup = $tikilib->get_permGroup_from_objectType($objectType);
			foreach ($allPermissions['data'] as $perm) {
				if ($perm['permName'] == $permName) {
					return $permGroup == $perm['type'];
				}
			}
		}
		return false;
	}
}
