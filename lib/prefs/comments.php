<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_comments_list()
{
    return [
        'comments_notitle' => [
            'name' => tra('Disable comment titles'),
            'description' => tra('Don\'t display the title input field on comments and their replies.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'comments_field_email' => [
            'name' => tra('Email field'),
            'description' => tra('Email field for comments (only for anonymous users).'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_field_website' => [
            'name' => tra('Website field'),
            'description' => tra('Website field for comments (only for anonymous users).'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_vote' => [
            'name' => tra('Use vote system for comments'),
            'description' => tra('Allow users with permission to vote on comments.'),
            'hint' => tr('Permissions involved: %0', 'vote_comments, wiki_view_comments, ratings_view_results'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_archive' => [
            'name' => tra('Archive comments'),
            'description' => tra('If a comment is archived, only admins can see it'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_akismet_filter' => [
            'name' => tra('Use Akismet to filter comments'),
            'description' => tra('Prevent comment spam by using the Akismet service to determine if the comment is spam. If comment moderation is enabled, Akismet will indicate if the comment is to be moderated or not. If there is no comment moderation, the comment will be rejected if considered to be spam.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
            'keywords' => 'askimet', // Let an admin find the preference even if his query has this common typo
        ],
        'comments_akismet_apikey' => [
            'name' => tra('Akismet API Key'),
            'description' => tra('Key required for the Akismet comment spam prevention.'),
            'hint' => tr('Obtain this key by registering your site at [%0]', 'http://akismet.com'),
            'type' => 'text',
            'filter' => 'word',
            'tags' => ['advanced'],
            'default' => '',
            'keywords' => 'askimet',
        ],
        'comments_akismet_check_users' => [
            'name' => tr('Filter spam for registered users'),
            'description' => tr('Activate spam filtering for registered users as well. Useful if your site allows anyone to register without screening.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced'],
            'keywords' => 'askimet',
        ],
        'comments_allow_correction' => [
            'name' => tr('Allow comments to be edited by their author'),
            'description' => tr('Allow a comment to be modified by its author for a 30-minute period after posting it, for clarifications, correction of errors, etc.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced'],
        ],
        'comments_inline_annotator' => [
            'name' => tr('Inline comments using Apache Annotator'),
            'description' => tr('Use the Open/Apache Annotator JavaScript based library for managing inline comments as annotations.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'feature_inline_comments',
            ],
            'keywords' => 'annotation annotatorjs',
        ],
        'comments_heading_links' => [
            'name' => tr('Anchor links on headings'),
            'description' => tr('Cause a link icon to appear on hover over each heading, useful for sharing the URL to an exact location on a page.'),
            'keywords' => 'Display hidden anchor on mouseover of headings',
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => [],
        ],
        'comments_per_page' => [
            'name' => tr('Number of comments per page'),
            'type' => 'text',
            'filter' => 'digits',
            'default' => 25,
            'dependencies' => [],
        ],
        'comments_sort_mode' => [
            'name' => tr('Sort mode for comments'),
            'type' => 'list',
            'default' => 'commentDate_asc',
            'options' => [
                'commentDate_asc' => tra('Oldest first'),
                'commentDate_desc' => tra('Newest first'),
            ],
        ],
    ];
}
