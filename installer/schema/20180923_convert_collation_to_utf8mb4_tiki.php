<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * @param $installer
 */
function upgrade_20180923_convert_collation_to_utf8mb4_tiki($installer)
{

	global $dbs_tiki;
	require(TikiInit::getCredentialsFile());

	// Update table indexes to be utf8mb4 compliant
	$query = <<<SQL
ALTER TABLE `messu_messages` DROP KEY `userIsRead`, ADD KEY userIsRead (user(191), `isRead`);
ALTER TABLE `tiki_actionlog_params` DROP KEY `nameValue`, ADD KEY `nameValue` (`name`, `value`(191));
ALTER TABLE `tiki_articles` DROP KEY `title`, DROP KEY `heading`, DROP KEY `body`, ADD KEY `title` (`title` (191)), ADD KEY `heading` (`heading`(191)), ADD KEY `body` (`body`(191));
ALTER TABLE `tiki_blog_posts` DROP KEY `data`, ADD KEY `data` (`data`(191));
ALTER TABLE `tiki_blogs` DROP KEY `title`, DROP KEY `description`, ADD KEY `title` (`title`(191)), ADD KEY `description` (`description`(191));
ALTER TABLE `tiki_objects` DROP KEY `itemId`, ADD  KEY (`itemId`(191), `type`);
ALTER TABLE `tiki_chat_users` DROP PRIMARY KEY , ADD PRIMARY KEY (`nickname`(191),`channelId`);
ALTER TABLE `tiki_comments` DROP KEY `title`, DROP KEY `data`, DROP KEY `objectType`, ADD KEY `title` (`title`(191)), ADD KEY `data` (`data`(191)), ADD KEY `objectType` (object(191), `objectType`);
ALTER TABLE `tiki_content_templates_sections` DROP PRIMARY KEY, ADD PRIMARY KEY (`templateId`,`section`(191));
ALTER TABLE `tiki_directory_search` DROP PRIMARY KEY, ADD PRIMARY KEY (`term`(191));
ALTER TABLE `tiki_directory_sites` DROP KEY `url`, ADD KEY (url(191));
ALTER TABLE `tiki_faq_questions` DROP KEY `question`, DROP KEY `answer`, ADD KEY `question` (question(191)), ADD KEY `answer` (answer(191));
ALTER TABLE `tiki_faqs` DROP KEY `title`, DROP KEY `description`, ADD KEY `title` (title(191)), ADD KEY `description` (description(191));
ALTER TABLE `tiki_featured_links` DROP PRIMARY KEY, ADD PRIMARY KEY (`url`(191));
ALTER TABLE `tiki_files` DROP KEY `name`, DROP KEY `description`, ADD KEY `name` (name(191)), ADD KEY `description` (description(191));
ALTER TABLE `tiki_file_drafts` DROP PRIMARY KEY, ADD PRIMARY KEY (`fileId`, `user`(191));
ALTER TABLE `tiki_forum_reads` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191),`threadId`);
ALTER TABLE `tiki_galleries` DROP KEY `description`, DROP KEY `visibleUser`, ADD KEY `description` (description(191)), ADD KEY `visibleUser` (visible, user(191));
ALTER TABLE `tiki_h5p_tmpfiles` DROP KEY path, ADD KEY path (path(191));
ALTER TABLE `tiki_history` DROP KEY `user`, ADD KEY `user` (`user`(191));
ALTER TABLE `tiki_hotwords` DROP PRIMARY KEY, ADD PRIMARY KEY (`word`(191));
ALTER TABLE `tiki_html_pages` DROP PRIMARY KEY, ADD PRIMARY KEY (`pageName`(191));
ALTER TABLE `tiki_images` DROP KEY `name`, DROP KEY `description`, DROP KEY `ti_us`, ADD KEY `name` (name(191)), ADD KEY `description` (description(191)), ADD KEY `ti_us` (user(191)) ;
ALTER TABLE `tiki_link_cache` DROP KEY `url`, DROP KEY `urlindex`, ADD KEY `url` (url(191)), ADD KEY `urlindex` (url(191));
ALTER TABLE `tiki_live_support_operators` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191));
ALTER TABLE `tiki_newsletter_subscriptions` DROP PRIMARY KEY, ADD PRIMARY KEY (`nlId`,`email`(191),`isUser`);
ALTER TABLE `tiki_newsletter_groups` DROP PRIMARY KEY, ADD PRIMARY KEY (`nlId`,`groupName`(191));
ALTER TABLE `tiki_pages` DROP KEY `data`, ADD KEY `data` (`data`(191));
ALTER TABLE `tiki_polls` DROP KEY `tiki_poll_lookup`, ADD KEY `tiki_poll_lookup` ( active , title(191) );
ALTER TABLE `tiki_preferences` DROP PRIMARY KEY, ADD PRIMARY KEY (`name`(191));
ALTER TABLE `tiki_received_pages` DROP KEY `structureName`, ADD KEY `structureName` (`structureName`(191));
ALTER TABLE `tiki_referer_stats` DROP PRIMARY KEY, ADD PRIMARY KEY (`referer`(191));
ALTER TABLE `tiki_secdb` DROP PRIMARY KEY, DROP KEY `sdb_fn`, ADD PRIMARY KEY (`filename`(191),`tiki_version`), ADD KEY `sdb_fn` (filename(191));
ALTER TABLE `tiki_semaphores` DROP PRIMARY KEY, ADD PRIMARY KEY (`semName`(191));
ALTER TABLE `tiki_sessions` DROP KEY `user`, ADD KEY `user` (user(191));
ALTER TABLE `tiki_theme_control_sections` DROP PRIMARY KEY, ADD PRIMARY KEY (`section`(191));
ALTER TABLE `tiki_tracker_item_fields` DROP KEY `value`, ADD KEY `value` (value(191));
ALTER TABLE `tiki_user_assigned_modules` DROP PRIMARY KEY, ADD PRIMARY KEY (`name`(30),`user`(191),`position`, `ord`);
ALTER TABLE `tiki_user_bookmarks_folders` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191),`folderId`);
ALTER TABLE `tiki_user_modules` DROP PRIMARY KEY, ADD PRIMARY KEY (`name`(191));
ALTER TABLE `tiki_user_postings` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191));
ALTER TABLE `tiki_user_preferences` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191),`prefName`);
ALTER TABLE `tiki_user_taken_quizzes` DROP PRIMARY KEY, ADD PRIMARY KEY (`user`(191),`quizId`(50));
ALTER TABLE `tiki_user_tasks` DROP KEY `creator`, ADD UNIQUE KEY (creator(191), created);
ALTER TABLE `tiki_user_votings` DROP KEY `id`, ADD KEY `id` (`id`(191));
ALTER TABLE `tiki_webmail_contacts_groups` DROP PRIMARY KEY, ADD PRIMARY KEY (`contactId`,`groupName`(191));
ALTER TABLE `tiki_webmail_messages` DROP PRIMARY KEY, ADD PRIMARY KEY (`accountId`,`mailId`(191));
ALTER TABLE `tiki_wiki_attachments` DROP KEY `page`, ADD KEY `page` (page(191));
ALTER TABLE `tiki_download` DROP KEY `object`, ADD KEY `object` (object(191),`userId`,type);
ALTER TABLE `users_groups` DROP KEY `groupName`, ADD UNIQUE KEY `groupName` (`groupName` (191));
ALTER TABLE `users_usergroups` DROP PRIMARY KEY, ADD PRIMARY KEY (`userId`,`groupName`(191));
ALTER TABLE `users_users` DROP KEY `login`, DROP KEY `openid_url`, ADD UNIQUE KEY `login` (login (191)), ADD KEY `openid_url` (openid_url(191));
ALTER TABLE `tiki_translated_objects` DROP PRIMARY KEY, ADD PRIMARY KEY (`type`, `objId`(191));
ALTER TABLE `tiki_score` DROP PRIMARY KEY, ADD PRIMARY KEY (`event`(191));
ALTER TABLE `tiki_stats` DROP PRIMARY KEY, ADD PRIMARY KEY (`object`(191),`type`,`day`);
ALTER TABLE `tiki_freetagged_objects` DROP PRIMARY KEY, DROP KEY `user`, ADD PRIMARY KEY (`tagId`,`user`(191),`objectId`), ADD KEY (user(191));
ALTER TABLE `tiki_webmail_contacts_fields` DROP KEY `user`, ADD KEY ( `user` (191));
ALTER TABLE `tiki_groupalert` DROP PRIMARY KEY, ADD PRIMARY KEY (`groupName`(191), `objectType`, `objectId` );
ALTER TABLE `tiki_plugin_security` DROP PRIMARY KEY, DROP KEY `last_object`, ADD PRIMARY KEY (`fingerprint`(191)), ADD KEY `last_object` (last_objectType, last_objectId(191));
ALTER TABLE `tiki_transitions` DROP KEY `transition_lookup`, ADD KEY `transition_lookup` (`type`, `from`(191));
ALTER TABLE `tiki_discount` DROP KEY `code`, ADD KEY `code` (`code`(191));
ALTER TABLE `tiki_rss_items` DROP KEY `tiki_rss_items_item`, ADD KEY `tiki_rss_items_item` (`rssId`, `guid`(191));
ALTER TABLE `tiki_object_attributes` DROP KEY `attribute_lookup_ix`, ADD KEY `attribute_lookup_ix` (`attribute`, `value`(191));
ALTER TABLE `tiki_todo` DROP KEY `what`, ADD KEY `what` (`objectType`, `objectId`(191));
ALTER TABLE `tiki_todo_notif` DROP KEY `objectId`, ADD KEY `objectId` (`objectId`(191));
ALTER TABLE `tiki_invited` DROP KEY `used_on_user`, ADD KEY `used_on_user` (`used_on_user`(191));
ALTER TABLE `tiki_source_auth` DROP KEY `tiki_source_auth_ix`, ADD KEY `tiki_source_auth_ix` (`scheme`, `domain`(191));
ALTER TABLE `tiki_page_references` DROP KEY `idx_tiki_page_ref_title`, DROP KEY `idx_tiki_page_ref_author`, ADD KEY `idx_tiki_page_ref_title` (title(191)), ADD KEY `idx_tiki_page_ref_author` (author(191));
SQL;

	$installer->query($query);

	$installer->query("ALTER DATABASE `" . $dbs_tiki . "` CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
	unset($dbs_tiki);
	$results = $installer->fetchAll('SHOW TABLES');
	foreach ($results as $table) {
		$installer->query('ALTER TABLE ' . reset($table) . ' convert to character set DEFAULT COLLATE DEFAULT');
	}


	$query = <<<SQL
ALTER TABLE `messu_archive` CHANGE `user_to` `user_to` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quizzes` CHANGE `sEpilogue` `sEpilogue` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_queue` CHANGE `message` `message` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_quiz_question_options` CHANGE `optionText` `optionText` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quiz_questions` CHANGE `question` `question` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quiz_results` CHANGE `answer` `answer` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quizzes` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quizzes` CHANGE `sPrologue` `sPrologue` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_quizzes` CHANGE `sData` `sData` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rating_configs` CHANGE `formula` `formula` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_preferences` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rating_configs` CHANGE `callbacks` `callbacks` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_received_articles` CHANGE `heading` `heading` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_referer_stats` CHANGE `lasturl` `lasturl` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rss_items` CHANGE `url` `url` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_rss_items` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rss_items` CHANGE `content` `content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rss_items` CHANGE `categories` `categories` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_programmed_content` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_perspective_preferences` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rss_modules` CHANGE `actions` `actions` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_modules` CHANGE `groups` `groups` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_menu_options` CHANGE `section` `section` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_menu_options` CHANGE `perm` `perm` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_menu_options` CHANGE `groupname` `groupname` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_menu_options` CHANGE `class` `class` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_menus` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_minical_events` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_modules` CHANGE `params` `params` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_newsletters` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_payment_requests` CHANGE `detail` `detail` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_newsletters` CHANGE `articleClipTypes` `articleClipTypes` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_objects` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_page_footnotes` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_pages` CHANGE `data` `data` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_pages` CHANGE `keywords` `keywords` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_payment_received` CHANGE `details` `details` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_payment_requests` CHANGE `due_date` `due_date` datetime DEFAULT NULL;
ALTER TABLE `tiki_payment_requests` CHANGE `actions` `actions` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_rss_modules` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_scheduler` CHANGE `params` `params` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_logs` CHANGE `logclient` `logclient` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_transitions` CHANGE `guards` `guards` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_fields` CHANGE `errorMsg` `errorMsg` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_fields` CHANGE `visibleBy` `visibleBy` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_fields` CHANGE `editableBy` `editableBy` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_item_field_logs` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_item_fields` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_options` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_trackers` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_url_shortener` CHANGE `longurl` `longurl` tinytext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_tracker_fields` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_user_notes` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_user_preferences` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_user_reports_cache` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_user_tasks_history` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_webservice` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_webservice_template` CHANGE `content` `content` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_workspace_templates` CHANGE `definition` `definition` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_fields` CHANGE `itemChoices` `itemChoices` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tracker_fields` CHANGE `options` `options` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_scheduler_run` CHANGE `output` `output` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_submissions` CHANGE `heading` `heading` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_score` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_search_queries` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_sheets` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_source_auth` CHANGE `arguments` `arguments` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_submissions` CHANGE `image_caption` `image_caption` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_submissions` CHANGE `bibliographical_references` `bibliographical_references` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_submissions` CHANGE `resume` `resume` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_submissions` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tabular_formats` CHANGE `config` `config` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_suggested_faq_questions` CHANGE `question` `question` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_suggested_faq_questions` CHANGE `answer` `answer` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_survey_question_options` CHANGE `qoption` `qoption` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_survey_questions` CHANGE `question` `question` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_survey_questions` CHANGE `options` `options` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_surveys` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tabular_formats` CHANGE `format_descriptor` `format_descriptor` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_tabular_formats` CHANGE `filter_descriptor` `filter_descriptor` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_mail_queue` CHANGE `message` `message` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_logs` CHANGE `logmessage` `logmessage` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `messu_archive` CHANGE `user_cc` `user_cc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blog_posts` CHANGE `trackbacks_to` `trackbacks_to` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_banners` CHANGE `HTMLData` `HTMLData` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_banners` CHANGE `textData` `textData` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_banners` CHANGE `onlyInURIs` `onlyInURIs` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_banners` CHANGE `exceptInURIs` `exceptInURIs` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_banning` CHANGE `message` `message` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blog_posts` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blog_posts` CHANGE `excerpt` `excerpt` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blog_posts` CHANGE `trackbacks_from` `trackbacks_from` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_articles` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blogs` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blogs` CHANGE `heading` `heading` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_blogs` CHANGE `post_heading` `post_heading` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_calendar_items` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_comments` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_connect` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_content` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_auth_tokens` CHANGE `groups` `groups` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_articles` CHANGE `heading` `heading` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_custom_route` CHANGE `redirect` `redirect` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `messu_sent` CHANGE `user_cc` `user_cc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_archive` CHANGE `user_bcc` `user_bcc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_archive` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_messages` CHANGE `user_to` `user_to` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_messages` CHANGE `user_cc` `user_cc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_messages` CHANGE `user_bcc` `user_bcc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_messages` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_sent` CHANGE `user_to` `user_to` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_sent` CHANGE `user_bcc` `user_bcc` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_articles` CHANGE `image_caption` `image_caption` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `messu_sent` CHANGE `body` `body` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sessions` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_acct_account` CHANGE `accountNotes` `accountNotes` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_actionlog` CHANGE `comment` `comment` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_actionlog_params` CHANGE `value` `value` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_activity_stream_rules` CHANGE `rule` `rule` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_activity_stream_rules` CHANGE `notes` `notes` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_areas` CHANGE `perspectives` `perspectives` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_cookies` CHANGE `cookie` `cookie` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_directory_categories` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_live_support_requests` CHANGE `reason` `reason` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_integrator_reps` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `screenshots` `screenshots` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `license` `license` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `keywords` `keywords` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `categories` `categories` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries_languages` CHANGE `translation` `translation` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_html_pages_dynamic_zones` CHANGE `content` `content` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_images` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_integrator_rules` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `summary` `summary` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_invite` CHANGE `emailcontent` `emailcontent` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_invite` CHANGE `wikicontent` `wikicontent` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_language` CHANGE `source` `source` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_language` CHANGE `tran` `tran` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_live_support_events` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_live_support_message_comments` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_live_support_messages` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries_hub_cache` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_h5p_libraries` CHANGE `semantics` `semantics` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `tiki_directory_sites` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_file_galleries` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_discount` CHANGE `comment` `comment` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_dynamic_variables` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_faq_questions` CHANGE `question` `question` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_faq_questions` CHANGE `answer` `answer` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_faqs` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_feature` CHANGE `tip` `tip` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_featured_links` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_files` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries` CHANGE `drop_library_css` `drop_library_css` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_forums` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_forums_queue` CHANGE `data` `data` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_galleries` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_goals` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_contents` CHANGE `keywords` `keywords` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_contents` CHANGE `description` `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries` CHANGE `preloaded_js` `preloaded_js` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_h5p_libraries` CHANGE `preloaded_css` `preloaded_css` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `users_groups` CHANGE `registrationUsersFieldIds` `registrationUsersFieldIds` text COLLATE utf8mb4_unicode_ci;
SQL;

	$installer->query($query);
}
