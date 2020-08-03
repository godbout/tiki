<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_forum_list()
{
    return [
        'forum_image_file_gallery' => [
            'name' => tr('Forum image file gallery'),
            'description' => tr('File gallery used to store images for forums'),
            'type' => 'text',
            'default' => 0,
            'profile_reference' => 'file_gallery',
            'dependencies' => ['feature_file_galleries'],
        ],
        'forum_comments_no_title_prefix' => [
            'name' => tra("Do not start messages titles with 'Re:'"),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_match_regex' => [
            'name' => tra('Uploaded filenames must match regex'),
            'type' => 'text',
            'size' => '20',
            'default' => '',
        ],
        'forum_thread_defaults_by_forum' => [
            'name' => tra('Manage thread defaults per-forum'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_thread_user_settings' => [
            'name' => tra('Display thread configuration bar'),
            'type' => 'flag',
            'hint' => tra('Allows users to override the defaults'),
            'default' => 'y',
        ],
        'forum_thread_user_settings_threshold' => [
            'name' => tra('Display the thread configuration bar only when the number of posts exceeds'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'units' => tra('posts'),
            'default' => 10,
                ],
        'forum_thread_user_settings_keep' => [
            'name' => tra('Keep settings for all forums during the user session'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_comments_per_page' => [
            'name' => tra('Number per page'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'units' => tra('comments'),
            'default' => 20,
        ],
        'forum_thread_style' => [
            'name' => tra('Default style'),
            'type' => 'list',
            'options' => [
                'commentStyle_plain' => tra('Plain'),
                'commentStyle_threaded' => tra('Threaded'),
                'commentStyle_headers' => tra('Headers only'),
            ],
            'default' => 'commentStyle_plain',
        ],
        'forum_thread_sort_mode' => [
            'name' => tra('Default sort mode'),
            'type' => 'list',
            'options' => [
                'commentDate_desc' => tra('Newest first'),
                'commentDate_asc' => tra('Oldest first'),
                'points_desc' => tra('Score'),
                'title_desc' => tra('Title (desc)'),
                'title_asc' => tra('Title (asc)'),
            ],
            'default' => 'commentDate_asc',
        ],
        'forum_list_topics' => [
            'name' => tra('Topics'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_list_posts' => [
            'name' => tra('Posts'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'forum_list_ppd' => [
            'name' => tra('Posts per day') . ' (PPD)',
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_list_lastpost' => [
            'name' => tra('Last post'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'forum_list_visits' => [
            'name' => tra('Visits'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'forum_list_desc' => [
            'name' => tra('Description'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'forum_list_description_len' => [
            'name' => tra('Description length'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'units' => tra('characters'),
            'default' => '240',
        ],
        'forum_reply_notitle' => [
            'name' => tra("Don't display forum thread titles"),
            'description' => tra("Titles of posts usually don't change because they are a direct reply to the parent post. This feature turns off the display of titles in edit forms and forum display."),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_reply_forcetitle' => [
            'name' => tra('Require reply to have a title'),
            'description' => tra('Present an empty title input form and require it to be filled in before the forum post is submitted.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_quote_prevent_nesting' => [
            'name' => tra('Prevent Nesting of Quote wikiplugins when replying'),
            'description' => tra('Strips quote plugin in reply in order to prevent nesting of quote plugins.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_use_quoteplugin'],
        ],
        'forum_available_categories' => [
            'name' => tr('Forum post categories'),
            'description' => tr('Categories available in the category picker for forum posts.'),
            'type' => 'text',
            'separator' => ',',
            'filter' => 'digits',
            'default' => [],
            'dependencies' => ['feature_categories'],
            'profile_reference' => 'category',
        ],
        'forum_category_selector_in_list' => [
            'name' => tr('Include category selector in forum list'),
            'description' => tr("Include a dropdown selector in the forum list to choose a category for the post."),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_categories'],
        ],
        'forum_inbound_mail_ignores_perms' => [
            'name' => tr('Allow inbound email posts from anyone'),
            'description' => tr('Allow posts from non-users in forums using inbound posts from a specified email address.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'forum_inbound_mail_parse_html' => [
            'name' => tr('Parse HTML in inbound email posts'),
            'description' => tr('Attempt to keep the formatting of HTML "rich text" emails if using WYSIWYG.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['experimental'],
            'warning' => tra('Experimental') . ' ' . tra('Has problems with some HTML emails, especially those with table-based layouts.'),
            'dependencies' => ['feature_wysiwyg'],
        ],
        'forum_strip_wiki_syntax_outgoing' => [
            'name' => tr('Strip wiki markup from outgoing forum emails'),
            'description' => tr('Convert outgoing emails from forum posts to plain text.'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['feature_forum_parse'],
        ],
        'forum_moderator_notification' => [
            'name' => tr('Send moderation email'),
            'description' => tr('Send email to forum moderators when post is queued'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'forum_moderator_email_approve' => [
            'name' => tr('Approve link in moderation email'),
            'description' => tr('Include a link for forum moderators to approve queue from email'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['forum_moderator_notification'],
        ],
    ];
}
