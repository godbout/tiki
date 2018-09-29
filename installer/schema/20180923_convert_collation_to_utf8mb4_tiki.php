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

	$tikiTables20180927 = [
		'messu_messages',
		'messu_archive',
		'messu_sent',
		'sessions',
		'tiki_actionlog',
		'tiki_actionlog_params',
		'tiki_activity_stream',
		'tiki_activity_stream_mapping',
		'tiki_activity_stream_rules',
		'tiki_articles',
		'tiki_article_types',
		'tiki_banners',
		'tiki_banning',
		'tiki_banning_sections',
		'tiki_blog_activity',
		'tiki_blog_posts',
		'tiki_blog_posts_images',
		'tiki_blogs',
		'tiki_calendar_categories',
		'tiki_calendar_recurrence',
		'tiki_calendar_items',
		'tiki_calendar_locations',
		'tiki_calendar_roles',
		'tiki_calendars',
		'tiki_calendar_options',
		'tiki_categories',
		'tiki_objects',
		'tiki_categorized_objects',
		'tiki_category_objects',
		'tiki_object_ratings',
		'tiki_category_sites',
		'tiki_chat_channels',
		'tiki_chat_messages',
		'tiki_chat_users',
		'tiki_comments',
		'tiki_content',
		'tiki_content_templates',
		'tiki_content_templates_sections',
		'tiki_cookies',
		'tiki_copyrights',
		'tiki_custom_route',
		'tiki_directory_categories',
		'tiki_directory_search',
		'tiki_directory_sites',
		'tiki_dsn',
		'tiki_dynamic_variables',
		'tiki_extwiki',
		'tiki_faq_questions',
		'tiki_faqs',
		'tiki_featured_links',
		'tiki_file_galleries',
		'tiki_files',
		'tiki_file_drafts',
		'tiki_forum_attachments',
		'tiki_forum_reads',
		'tiki_forums',
		'tiki_forums_queue',
		'tiki_forums_reported',
		'tiki_galleries',
		'tiki_galleries_scales',
		'tiki_group_inclusion',
		'tiki_group_watches',
		'tiki_h5p_contents',
		'tiki_h5p_contents_libraries',
		'tiki_h5p_libraries',
		'tiki_h5p_libraries_hub_cache',
		'tiki_h5p_libraries_libraries',
		'tiki_h5p_libraries_languages',
		'tiki_h5p_tmpfiles',
		'tiki_h5p_results',
		'tiki_h5p_libraries_cachedassets',
		'tiki_history',
		'tiki_hotwords',
		'tiki_html_pages',
		'tiki_html_pages_dynamic_zones',
		'tiki_images',
		'tiki_images_data',
		'tiki_language',
		'tiki_link_cache',
		'tiki_links',
		'tiki_live_support_events',
		'tiki_live_support_message_comments',
		'tiki_live_support_messages',
		'tiki_live_support_modules',
		'tiki_live_support_operators',
		'tiki_live_support_requests',
		'tiki_logs',
		'tiki_mail_events',
		'tiki_mailin_accounts',
		'tiki_menu_languages',
		'tiki_menu_options',
		'tiki_menus',
		'tiki_minical_events',
		'tiki_minical_topics',
		'tiki_modules',
		'tiki_newsletter_subscriptions',
		'tiki_newsletter_groups',
		'tiki_newsletter_included',
		'tiki_newsletter_pages',
		'tiki_newsletters',
		'tiki_page_footnotes',
		'tiki_pages',
		'tiki_pageviews',
		'tiki_poll_objects',
		'tiki_poll_options',
		'tiki_polls',
		'tiki_preferences',
		'tiki_private_messages',
		'tiki_programmed_content',
		'tiki_quiz_question_options',
		'tiki_quiz_questions',
		'tiki_quiz_results',
		'tiki_quiz_stats',
		'tiki_quiz_stats_sum',
		'tiki_quizzes',
		'tiki_received_articles',
		'tiki_received_pages',
		'tiki_referer_stats',
		'tiki_related_categories',
		'tiki_rss_modules',
		'tiki_rss_feeds',
		'tiki_search_stats',
		'tiki_secdb',
		'tiki_semaphores',
		'tiki_sent_newsletters',
		'tiki_sent_newsletters_errors',
		'tiki_sessions',
		'tiki_sheet_layout',
		'tiki_sheet_values',
		'tiki_sheets',
		'tiki_shoutbox',
		'tiki_shoutbox_words',
		'tiki_structure_versions',
		'tiki_structures',
		'tiki_submissions',
		'tiki_suggested_faq_questions',
		'tiki_survey_question_options',
		'tiki_survey_questions',
		'tiki_surveys',
		'tiki_tags',
		'tiki_theme_control_categs',
		'tiki_theme_control_objects',
		'tiki_theme_control_sections',
		'tiki_topics',
		'tiki_tracker_fields',
		'tiki_tracker_item_attachments',
		'tiki_tracker_item_fields',
		'tiki_tracker_item_field_logs',
		'tiki_tracker_items',
		'tiki_tracker_options',
		'tiki_trackers',
		'tiki_untranslated',
		'tiki_user_answers',
		'tiki_user_answers_uploads',
		'tiki_user_assigned_modules',
		'tiki_user_bookmarks_folders',
		'tiki_user_bookmarks_urls',
		'tiki_user_login_cookies',
		'tiki_user_mail_accounts',
		'tiki_user_menus',
		'tiki_user_modules',
		'tiki_user_notes',
		'tiki_user_postings',
		'tiki_user_preferences',
		'tiki_user_quizzes',
		'tiki_user_taken_quizzes',
		'tiki_user_tasks_history',
		'tiki_user_tasks',
		'tiki_user_votings',
		'tiki_user_watches',
		'tiki_userfiles',
		'tiki_userpoints',
		'tiki_webmail_contacts',
		'tiki_webmail_contacts_groups',
		'tiki_webmail_messages',
		'tiki_wiki_attachments',
		'tiki_zones',
		'tiki_download',
		'users_grouppermissions',
		'users_groups',
		'users_objectpermissions',
		'users_permissions',
		'users_usergroups',
		'users_users',
		'tiki_integrator_reps',
		'tiki_integrator_rules',
		'tiki_translated_objects',
		'tiki_score',
		'tiki_object_scores',
		'tiki_file_handlers',
		'tiki_stats',
		'tiki_registration_fields',
		'tiki_actionlog_conf',
		'tiki_freetags',
		'tiki_freetagged_objects',
		'tiki_contributions',
		'tiki_contributions_assigned',
		'tiki_webmail_contacts_ext',
		'tiki_webmail_contacts_fields',
		'tiki_pages_translation_bits',
		'tiki_pages_changes',
		'tiki_minichat',
		'tiki_profile_symbols',
		'tiki_feature',
		'tiki_schema',
		'tiki_semantic_tokens',
		'tiki_webservice',
		'tiki_webservice_template',
		'tiki_groupalert',
		'tiki_sent_newsletters_files',
		'tiki_sefurl_regex_out',
		'tiki_plugin_security',
		'tiki_user_reports',
		'tiki_user_reports_cache',
		'tiki_perspectives',
		'tiki_perspective_preferences',
		'tiki_transitions',
		'tiki_auth_tokens',
		'tiki_file_backlinks',
		'tiki_payment_requests',
		'tiki_payment_received',
		'tiki_discount',
		'tiki_translations_in_progress',
		'tiki_rss_items',
		'tiki_object_attributes',
		'tiki_rating_configs',
		'tiki_rating_obtained',
		'tiki_object_relations',
		'tiki_todo',
		'tiki_todo_notif',
		'tiki_url_shortener',
		'tiki_invite',
		'tiki_invited',
		'tiki_credits',
		'tiki_credits_usage',
		'tiki_credits_types',
		'tiki_acct_account',
		'tiki_acct_bankaccount',
		'tiki_acct_book',
		'tiki_acct_item',
		'tiki_acct_journal',
		'tiki_acct_stack',
		'tiki_acct_stackitem',
		'tiki_acct_statement',
		'tiki_acct_tax',
		'tiki_queue',
		'tiki_cart_inventory_hold',
		'tiki_source_auth',
		'tiki_connect',
		'tiki_areas',
		'tiki_page_references',
		'tiki_db_status',
		'tiki_mail_queue',
		'tiki_workspace_templates',
		'tiki_user_mailin_struct',
		'tiki_search_queries',
		'tiki_user_monitors',
		'tiki_output',
		'tiki_goals',
		'tiki_goal_events',
		'tiki_addon_profiles',
		'tiki_tabular_formats',
		'tiki_scheduler',
		'tiki_scheduler_run',
	];

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

	foreach ($tikiTables20180927 as $table) {
		$installer->query('ALTER TABLE `' . $table . '` convert to character set DEFAULT COLLATE DEFAULT');
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
