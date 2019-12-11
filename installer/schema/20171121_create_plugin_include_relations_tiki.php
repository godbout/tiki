<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * Create object relations of type 'tiki.wiki.include', by parsing all
 * wiki pages and comments and check for Plugin Include calls.
 *
 * These are used to keep consistency when renaming an included page and to
 * warn user when an edition can affect other pages.
 *
 * @param Installer $installer
 */
function upgrade_20171121_create_plugin_include_relations_tiki($installer)
{
	$maxRecordsPerQuery = 100;

	global $prefs;
	$prefs['wikiplugin_maximum_passes'] = 500;

	$create_relations = function ($installer, $type, $objectId, $data) {
		$matches = WikiParser_PluginMatcher::match($data);
		$argParser = new WikiParser_PluginArgumentParser();
		foreach ($matches as $match) {
			if ($match->getName() == 'include') {
				$params = $argParser->parse($match->getArguments());
				if (! isset($params['page'])) {
					continue;
				}
				$existing = $installer->table('tiki_object_relations')->fetchCount(
					[
						'relation' => 'tiki.wiki.include',
						'source_type' => $type,
						'source_itemId' => $objectId,
						'target_type' => 'wiki page',
						'target_itemId' => $params['page'],
					]
				);
				if (! $existing) {
					$installer->query(
						'INSERT INTO `tiki_object_relations` (`relation`, `source_type`, `source_itemId`, `target_type`, `target_itemId`) VALUES(?, ?, ?, ?, ?)',
						[
							'tiki.wiki.include',
							$type,
							$objectId,
							'wiki page',
							$params['page'],
						]
					);
				}
			}
		}
	};

	/**
	 * Wiki pages
	 */
	$tiki_pages = $installer->table('tiki_pages');

	$offset = 0;
	do {
		$pages = $tiki_pages->fetchAll([], [], $maxRecordsPerQuery, $offset);

		foreach ($pages as $page) {
			$create_relations($installer, 'wiki page', $page['pageName'], $page['data']);
		}

		$resultCount = count($pages);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);

	/**
	 * Comments and forum posts
	 */
	$table = $installer->table('tiki_comments');

	$offset = 0;
	do {
		$comments = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);

		foreach ($comments as $comment) {
			if ($comment['objectType'] == 'forum') {
				$type = 'forum post';
			} else {
				$type = $comment['objectType'] . ' comment';
			}
			$create_relations($installer, $type, $comment['threadId'], $comment['data']);
		}

		$resultCount = count($comments);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Blog posts
	 */
	$table = $installer->table('tiki_blog_posts');

	$offset = 0;
	do {
		$blogPosts = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);
		foreach ($blogPosts as $item) {
			$create_relations($installer, 'post', $item['postId'], $item['data']);
		}

		$resultCount = count($blogPosts);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Articles
	 */
	$table = $installer->table('tiki_articles');

	$offset = 0;
	do {
		$articles = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);
		foreach ($articles as $item) {
			$data = $item['heading'] . "\n" . $item['body'];
			$create_relations($installer, 'article', $item['articleId'], $data);
		}

		$resultCount = count($articles);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Calendar events
	 */
	$table = $installer->table('tiki_calendar_items');

	$offset = 0;
	do {
		$calendarItems = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);
		foreach ($calendarItems as $item) {
			$create_relations($installer, 'calendar event', $item['calitemId'], $item['description']);
		}

		$resultCount = count($calendarItems);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Trackers
	 */
	$table = $installer->table('tiki_trackers');

	$offset = 0;
	do {
		$trackers = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);
		foreach ($trackers as $item) {
			if ($item['descriptionIsParsed'] == 'y') {
				$create_relations($installer, 'tracker', $item['trackerId'], $item['description']);
			}
		}

		$resultCount = count($trackers);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Tracker fields
	 */
	$table = $installer->table('tiki_tracker_fields');

	$offset = 0;
	do {
		$trackerFieldList = $table->fetchAll([], [], $maxRecordsPerQuery, $offset);
		foreach ($trackerFieldList as $item) {
			if ($item['descriptionIsParsed'] == 'y') {
				$create_relations($installer, 'trackerfield', $item['fieldId'], $item['description']);
			}
		}

		$resultCount = count($trackerFieldList);
		$offset += $maxRecordsPerQuery;
	} while ($resultCount == $maxRecordsPerQuery);


	/**
	 * Tracker item fields
	 */
	$trackerFields = $installer->table('tiki_tracker_fields');
	$itemFields = $installer->table('tiki_tracker_item_fields');
	foreach ($trackerFields->fetchAll(['fieldId'], ['type' => 'a']) as $field) {
		$fieldId = $field['fieldId'];
		$offset = 0;
		do {
			$itemFieldList = $itemFields->fetchAll([], ['fieldId' => (int)$fieldId], $maxRecordsPerQuery, $offset);
			foreach ($itemFieldList as $itemField) {
				$objectId = sprintf("%d:%d", (int)$itemField['itemId'], $fieldId);
				$create_relations($installer, 'trackeritemfield', $objectId, $itemField['value']);
			}

			$resultCount = count($itemFieldList);
			$offset += $maxRecordsPerQuery;
		} while ($resultCount == $maxRecordsPerQuery);
	}

	return true;
}
