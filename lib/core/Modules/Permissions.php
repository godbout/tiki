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

		if (null == $objectType = $this->findObjectType($url)) {
			return null;
		}

		switch ($objectType) {
			case 'wiki page':
				$objectId = ! empty($_REQUEST['page']) ? $_REQUEST['page'] : null;
				break;
			case 'file gallery':
				$filegallib = TikiLib::lib('filegal');
				$objectId = ! empty($_REQUEST['galleryId']) ? $_REQUEST['galleryId'] : null;
				break;
			case 'tracker':
				$objectId = ! empty($_REQUEST['trackerId']) ? $_REQUEST['trackerId'] : null;
				$itemId = ! empty($_REQUEST['itemId']) ? $_REQUEST['itemId'] : null;
				if ($itemId) {
					$objectId = $itemId;
					$objectType = 'trackeritem';
				}
				break;
			case 'forum':
				$objectId = ! empty($_REQUEST['forumId']) ? $_REQUEST['forumId'] : null;
				break;
			case 'group':
				$objectId = ! empty($_REQUEST['group']) ? $_REQUEST['group'] : null;
				break;
			case 'articles':
				$objectId = ! empty($_REQUEST['articleId']) ? $_REQUEST['articleId'] : null;
				break;
			case 'blog':
				$objectId = ! empty($_REQUEST['blogId']) ? $_REQUEST['blogId'] : null;
				break;
			case 'calendar':
				$objectId = ! empty($_REQUEST['calendarId']) ? $_REQUEST['calendarId'] : null;
				break;
			case 'sheet':
				$objectId = ! empty($_REQUEST['sheetId']) ? $_REQUEST['sheetId'] : null;
				break;
		}

		$all = TikiLib::lib('user')->get_permissions(0, -1, 'permName_asc', '', $this->findPermType($objectType), '', true);

		$accessor = \Perms::get(['type' => $objectType, 'object' => $objectId]);
		$loaded = $accessor->getResolver()->dump();

		$results = [];

		foreach ($all['data'] as $permDef) {
			foreach ($loaded['perms'] as $perm => $groups) {
				if ($perm != str_replace('tiki_p_', '', $permDef['name'])) {
					continue;
				}
				$results[$permDef['type']][$perm] = $groups;
			}
		}

		$loaded['perms'] = $results;
		return $loaded;
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

	protected function findPermType($objectType) {
		switch($objectType) {
			case 'wiki page':
				return 'wiki';
			case 'file gallery':
				return 'file galleries';
			case 'tracker':
			case 'trackeritem':
				return 'trackers';
			case 'forum':
				return 'forums';
			case 'group':
				return 'group';
			case 'articles':
				return 'articles';
			case 'blog':
				return 'blogs';
			case 'calendar':
				return 'calendar';
			case 'sheet':
				return 'sheet';
		}
	}
}
