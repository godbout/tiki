<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @param $installer
 */
function upgrade_20180921_sql_upgrade_fix_from_older_tiki($installer)
{
	$query = <<<SQL
-- Fix upgrade from 9.x
ALTER TABLE `tiki_forums_reported` DROP PRIMARY KEY;
ALTER TABLE `tiki_forums_reported` ADD PRIMARY KEY (`threadId`, `forumId`, `parentId`, `user`); -- Changed in 20121210_better_forum_reported_index_tiki.sql but never make it to tiki.sql
DELETE FROM `users_permissions` WHERE `permName` = 'tiki_p_view_poll_choices';
ALTER TABLE `tiki_secdb` DROP PRIMARY KEY;
ALTER TABLE `tiki_secdb` ADD PRIMARY KEY (`filename`(215),`tiki_version`(40));
UPDATE `tiki_modules` SET `position` = '' WHERE `position` IS NULL;
ALTER TABLE `tiki_modules` CHANGE `position` `position` varchar(20) NOT NULL DEFAULT '';
ALTER TABLE `tiki_profile_symbols` CHANGE `value` `value` varchar(160) NOT NULL;

-- Fix upgrade from 12.x
CREATE TABLE IF NOT EXISTS `tiki_payment_requests` (
    `paymentRequestId` INT NOT NULL AUTO_INCREMENT,
    `amount` DECIMAL(7,2) NOT NULL,
    `amount_paid` DECIMAL(7,2) NOT NULL DEFAULT 0.0,
    `currency` CHAR(3) NOT NULL,
    `request_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `due_date` timestamp NULL DEFAULT NULL,
    `authorized_until` TIMESTAMP NULL DEFAULT NULL,
    `cancel_date` TIMESTAMP NULL DEFAULT NULL,
    `description` VARCHAR(100) NOT NULL,
    `actions` TEXT,
    `detail` TEXT,
    `userId` int(8),
    PRIMARY KEY( `paymentRequestId` )
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS  `tiki_banning` (
  `banId` int(12) NOT NULL AUTO_INCREMENT,
  `mode` enum('user','ip') DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `ip1` char(3) DEFAULT NULL,
  `ip2` char(3) DEFAULT NULL,
  `ip3` char(3) DEFAULT NULL,
  `ip4` char(3)  DEFAULT NULL,
  `user` varchar(200)  DEFAULT '',
  `date_from` timestamp NULL DEFAULT NULL,
  `date_to` timestamp NULL DEFAULT NULL,
  `use_dates` char(1)  DEFAULT NULL,
  `created` int(14) DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`banId`),
  KEY `ban` (`use_dates`,`date_from`,`date_to`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS  `tiki_acct_stack` (
  `stackBookId` int(10) unsigned NOT NULL,
  `stackId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stackDate` date DEFAULT NULL,
  `stackDescription` varchar(255)  NOT NULL,
  `stackTs` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stackId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS  `tiki_acct_journal` (
  `journalBookId` int(10) unsigned NOT NULL,
  `journalId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `journalDate` date DEFAULT NULL,
  `journalDescription` varchar(255)  NOT NULL,
  `journalCancelled` int(1) NOT NULL DEFAULT '0',
  `journalTs` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`journalId`)
) ENGINE=MyISAM;

DELETE FROM `tiki_object_scores` WHERE `id` = 1 AND `triggerObjectType` = 'legacy_score' AND `triggerObjectId` = '0' AND `triggerUser` = 'admin' AND `triggerEvent` = 'tiki.legacy.score' AND `ruleId` = 'Legacy Score' AND `recipientObjectType` = 'user' AND `recipientObjectId` = 'admin';

ALTER TABLE `tiki_language` CHANGE `source` `source` text NOT NULL;
ALTER TABLE `tiki_galleries` CHANGE `description` `description` text;
ALTER TABLE `tiki_html_pages_dynamic_zones` CHANGE `content` `content` text;
ALTER TABLE `tiki_images` CHANGE `description` `description` text;
ALTER TABLE `tiki_integrator_reps` CHANGE `description` `description` text NOT NULL;
ALTER TABLE `tiki_integrator_rules` CHANGE `description` `description` text NOT NULL;
ALTER TABLE `tiki_invite` CHANGE `emailcontent` `emailcontent` text NOT NULL;
ALTER TABLE `tiki_invite` CHANGE `wikicontent` `wikicontent` text;
ALTER TABLE `tiki_forums_queue` CHANGE `data` `data` text;
ALTER TABLE `tiki_language` CHANGE `tran` `tran` text;
ALTER TABLE `tiki_live_support_events` CHANGE `data` `data` text;
ALTER TABLE `tiki_live_support_message_comments` CHANGE `data` `data` text;
ALTER TABLE `tiki_live_support_requests` CHANGE `reason` `reason` text;
ALTER TABLE `tiki_payment_requests` CHANGE `due_date` `due_date` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `tiki_preferences` CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `tiki_score` CHANGE `event` `event` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `tiki_user_login_cookies` CHANGE `expiration` `expiration` timestamp NULL DEFAULT NULL;
ALTER TABLE `users_groups` CHANGE `registrationUsersFieldIds` `registrationUsersFieldIds` text;
ALTER TABLE `tiki_live_support_messages` CHANGE `data` `data` text;
ALTER TABLE `tiki_file_galleries` CHANGE `description` `description` text;
ALTER TABLE `tiki_forums` CHANGE `description` `description` text;
ALTER TABLE `sessions` CHANGE `data` `data` text NOT NULL;
ALTER TABLE `tiki_activity_stream_rules` CHANGE `notes` `notes` text;
ALTER TABLE `tiki_activity_stream_rules` CHANGE `rule` `rule` text;
ALTER TABLE `tiki_actionlog_params` CHANGE `value` `value` text;
ALTER TABLE `tiki_actionlog` CHANGE `comment` `comment` text;
ALTER TABLE `tiki_acct_statement` CHANGE `statementValueDate` `statementValueDate` date DEFAULT NULL;
ALTER TABLE `tiki_acct_statement` CHANGE `statementBookingDate` `statementBookingDate` date DEFAULT NULL;
ALTER TABLE `tiki_acct_book` CHANGE `bookEndDate` `bookEndDate` date DEFAULT NULL;
ALTER TABLE `tiki_acct_book` CHANGE `bookStartDate` `bookStartDate` date DEFAULT NULL;
ALTER TABLE `tiki_acct_account` CHANGE `accountNotes` `accountNotes` text NOT NULL;
ALTER TABLE `tiki_acct_journal` CHANGE `journalDate` `journalDate` date DEFAULT NULL;
ALTER TABLE `tiki_acct_stack` CHANGE `stackDate` `stackDate` date DEFAULT NULL;
ALTER TABLE `messu_sent` CHANGE `body` `body` text;
ALTER TABLE `tiki_articles` CHANGE `image_caption` `image_caption` text;
ALTER TABLE `messu_sent` CHANGE `user_bcc` `user_bcc` text;
ALTER TABLE `messu_sent` CHANGE `user_cc` `user_cc` text;
ALTER TABLE `messu_sent` CHANGE `user_to` `user_to` text;
ALTER TABLE `messu_messages` CHANGE `body` `body` text;
ALTER TABLE `messu_messages` CHANGE `user_bcc` `user_bcc` text;
ALTER TABLE `messu_messages` CHANGE `user_cc` `user_cc` text;
ALTER TABLE `messu_messages` CHANGE `user_to` `user_to` text;
ALTER TABLE `messu_archive` CHANGE `body` `body` text;
ALTER TABLE `messu_archive` CHANGE `user_bcc` `user_bcc` text;
ALTER TABLE `tiki_areas` CHANGE `perspectives` `perspectives` text;
ALTER TABLE `tiki_articles` CHANGE `heading` `heading` text;
ALTER TABLE `tiki_files` CHANGE `description` `description` text;
ALTER TABLE `tiki_cookies` CHANGE `cookie` `cookie` text;
ALTER TABLE `tiki_featured_links` CHANGE `description` `description` text;
ALTER TABLE `tiki_feature` CHANGE `tip` `tip` text;
ALTER TABLE `tiki_faqs` CHANGE `description` `description` text;
ALTER TABLE `tiki_faq_questions` CHANGE `answer` `answer` text;
ALTER TABLE `tiki_faq_questions` CHANGE `question` `question` text;
ALTER TABLE `tiki_dynamic_variables` CHANGE `data` `data` text;
ALTER TABLE `tiki_discount` CHANGE `comment` `comment` text;
ALTER TABLE `tiki_directory_sites` CHANGE `description` `description` text;
ALTER TABLE `tiki_directory_categories` CHANGE `description` `description` text;
ALTER TABLE `tiki_content` CHANGE `description` `description` text;
ALTER TABLE `tiki_articles` CHANGE `body` `body` text;
ALTER TABLE `tiki_connect` CHANGE `data` `data` text;
ALTER TABLE `tiki_comments` CHANGE `data` `data` text;
ALTER TABLE `tiki_calendar_items` CHANGE `description` `description` text;
ALTER TABLE `tiki_blogs` CHANGE `post_heading` `post_heading` text;
ALTER TABLE `tiki_blogs` CHANGE `heading` `heading` text;
ALTER TABLE `tiki_blogs` CHANGE `description` `description` text;
ALTER TABLE `tiki_blog_posts` CHANGE `trackbacks_from` `trackbacks_from` text;
ALTER TABLE `tiki_blog_posts` CHANGE `excerpt` `excerpt` text;
ALTER TABLE `tiki_blog_posts` CHANGE `data` `data` text;
ALTER TABLE `tiki_auth_tokens` CHANGE `groups` `groups` text;
ALTER TABLE `tiki_banning` CHANGE `date_to` `date_to` timestamp NULL DEFAULT NULL;
ALTER TABLE `tiki_banning` CHANGE `date_from` `date_from` timestamp NULL DEFAULT NULL;

-- Fix upgrade from 15.x
CREATE TABLE IF NOT EXISTS `tiki_user_assigned_modules` (
  `moduleId` int(8) NOT NULL,
  `name` varchar(200) NOT NULL default '',
  `position` varchar(20) NOT NULL default '',
  `ord` int(4) NOT NULL default 0,
  `type` char(1) default NULL,
  `user` varchar(200) NOT NULL default '',
  PRIMARY KEY (`name`(30),`user`(191),`position`, `ord`),
  KEY `id` (moduleId)
) ENGINE=MyISAM;

UPDATE tiki_menu_options SET icon = 'icon-configuration48x48' WHERE name = 'Settings' and url = 'tiki-admin.php' and perm = 'tiki_p_admin_webservices';

ALTER TABLE `users_users` CHANGE `hash` `hash` varchar(60) DEFAULT NULL;

INSERT IGNORE INTO `tiki_actionlog_conf` (action, `objectType`, status)  VALUES('Removed','trackeritem','y');

DELETE FROM tiki_menu_options WHERE name = 'Dump' AND url = 'dump/new.tar';

UPDATE `tiki_actionlog_conf` SET `status` = 'y' WHERE `action` = 'Viewed' AND `objectType` = 'trackeritem';
UPDATE `tiki_actionlog_conf` SET `status` = 'y' WHERE `action` = 'Updated' AND `objectType` = 'trackeritem';
UPDATE `tiki_actionlog_conf` SET `status` = 'y' WHERE `action` = 'Created' AND `objectType` = 'trackeritem';

ALTER TABLE `users_usergroups` DROP PRIMARY KEY;
ALTER TABLE `users_usergroups` ADD PRIMARY KEY (`userId`,`groupName`);
ALTER TABLE `tiki_todo_notif` DROP INDEX `objectId`;
ALTER TABLE `tiki_todo_notif` ADD KEY `objectId` (`objectId`);
ALTER TABLE `tiki_freetagged_objects` DROP INDEX `user`;
ALTER TABLE `tiki_freetagged_objects` ADD KEY `user` (`user`);
ALTER TABLE `tiki_groupalert` DROP PRIMARY KEY;
ALTER TABLE `tiki_groupalert` ADD PRIMARY KEY (`groupName`,`objectType`,`objectId`);
ALTER TABLE `tiki_invited` DROP INDEX `used_on_user`;
ALTER TABLE `tiki_invited` ADD KEY `used_on_user` (`used_on_user`);
ALTER TABLE `tiki_object_attributes` DROP INDEX `attribute_lookup_ix`;
ALTER TABLE `tiki_object_attributes` ADD KEY `attribute_lookup_ix` (`attribute`,`value`);
ALTER TABLE `tiki_page_references` DROP INDEX `idx_tiki_page_ref_title`;
ALTER TABLE `tiki_page_references` ADD KEY `idx_tiki_page_ref_title` (`title`);
ALTER TABLE `tiki_page_references` DROP INDEX `idx_tiki_page_ref_author`;
ALTER TABLE `tiki_page_references` ADD KEY `idx_tiki_page_ref_author` (`author`);
ALTER TABLE `tiki_plugin_security` DROP PRIMARY KEY;
ALTER TABLE `tiki_plugin_security` ADD PRIMARY KEY (`fingerprint`);
ALTER TABLE `tiki_plugin_security` DROP INDEX `last_object`;
ALTER TABLE `tiki_plugin_security` ADD KEY `last_object` (`last_objectType`,`last_objectId`);
ALTER TABLE `tiki_rss_items` DROP INDEX `tiki_rss_items_item`;
ALTER TABLE `tiki_rss_items` ADD KEY `tiki_rss_items_item` (`rssId`,`guid`);
ALTER TABLE `tiki_score` DROP PRIMARY KEY;
ALTER TABLE `tiki_score` ADD PRIMARY KEY (`event`);
ALTER TABLE `tiki_source_auth` DROP INDEX `tiki_source_auth_ix`;
ALTER TABLE `tiki_source_auth` ADD KEY `tiki_source_auth_ix` (`scheme`,`domain`);
ALTER TABLE `tiki_stats` DROP PRIMARY KEY;
ALTER TABLE `tiki_stats` ADD PRIMARY KEY (`object`,`type`,`day`);
ALTER TABLE `tiki_todo` DROP INDEX `what`;
ALTER TABLE `tiki_todo` ADD KEY `what` (`objectType`,`objectId`);
ALTER TABLE `tiki_transitions` DROP INDEX `transition_lookup`;
ALTER TABLE `tiki_transitions` ADD KEY `transition_lookup` (`type`,`from`);
ALTER TABLE `users_groups` DROP INDEX `groupName`;
ALTER TABLE `users_groups` ADD UNIQUE KEY `groupName` (`groupName`);
ALTER TABLE `tiki_user_votings` DROP INDEX `id`;
ALTER TABLE `tiki_user_votings` ADD KEY `id` (`id`);
ALTER TABLE `tiki_wiki_attachments` DROP INDEX `page`;
ALTER TABLE `tiki_wiki_attachments` ADD KEY `page` (`page`);
ALTER TABLE `users_users` DROP INDEX `login`;
ALTER TABLE `users_users` ADD UNIQUE KEY `login` (`login`);
ALTER TABLE `users_users` DROP INDEX `openid_url`;
ALTER TABLE `users_users` ADD KEY `openid_url` (`openid_url`);
ALTER TABLE `tiki_webmail_messages` DROP PRIMARY KEY;
ALTER TABLE `tiki_webmail_messages` ADD PRIMARY KEY (`accountId`,`mailId`);
ALTER TABLE `tiki_webmail_contacts_groups` DROP PRIMARY KEY;
ALTER TABLE `tiki_webmail_contacts_groups` ADD PRIMARY KEY (`contactId`,`groupName`);
ALTER TABLE `tiki_webmail_contacts_fields` DROP INDEX `user`;
ALTER TABLE `tiki_webmail_contacts_fields` ADD KEY `user` (`user`);
ALTER TABLE `tiki_user_tasks` DROP INDEX `creator`;
ALTER TABLE `tiki_user_tasks` ADD UNIQUE KEY `creator` (`creator`,`created`);
ALTER TABLE `tiki_translated_objects` DROP PRIMARY KEY;
ALTER TABLE `tiki_translated_objects` ADD PRIMARY KEY (`type`,`objId`);
ALTER TABLE `tiki_user_taken_quizzes` DROP PRIMARY KEY;
ALTER TABLE `tiki_user_taken_quizzes` ADD PRIMARY KEY (`user`,`quizId`(50));
ALTER TABLE `tiki_user_preferences` DROP PRIMARY KEY;
ALTER TABLE `tiki_user_preferences` ADD PRIMARY KEY (`user`,`prefName`);
ALTER TABLE `tiki_user_postings` DROP PRIMARY KEY;
ALTER TABLE `tiki_user_postings` ADD PRIMARY KEY (`user`);
ALTER TABLE `tiki_download` DROP INDEX `object`;
ALTER TABLE `tiki_download` ADD KEY `object` (`object`,`userId`,`type`);
ALTER TABLE `tiki_user_modules` DROP PRIMARY KEY;
ALTER TABLE `tiki_user_modules` ADD PRIMARY KEY (`name`);
ALTER TABLE `tiki_user_bookmarks_folders` DROP PRIMARY KEY;
ALTER TABLE `tiki_user_bookmarks_folders` ADD PRIMARY KEY (`user`,`folderId`);
ALTER TABLE `tiki_discount` DROP INDEX `code`;
ALTER TABLE `tiki_discount` ADD KEY `code` (`code`);

-- Fix upgrade from 18.x
INSERT IGNORE INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'o','References','tiki-references.php',255,'feature_wiki,feature_references','tiki_p_edit_references','', 0);
ALTER TABLE `tiki_queue` CHANGE `handler` `handler` varchar(64) DEFAULT NULL;
SQL;

	$installer->query($query);
}
