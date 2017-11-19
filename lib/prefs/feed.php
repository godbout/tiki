<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_feed_list()
{
	return [
		'feed_default_version' => [
			'name' => tra('Default feed format'),
			'description' => tra(''),
			'type' => 'list',
			'options' => [
				'5' => tra('ATOM 1.0'),
				'2' => tra('RSS 2.0'),
			],
			'default' => '5',
			'shorthint' => '[https://validator.w3.org/feed/docs/atom.html|Atom 1.0]'
						. ' - '
						. '[http://cyber.law.harvard.edu/rss/rss.html|RSS 2.0]',
		],

		// atom specific preferences
		'feed_atom_author_name' => [
			'name' => tra('Feed author name'),
			'description' => tra('Default author of each item in the feed.'),
			'type' => 'text',
			'size' => '40',
			'hint' => tra('This field is mandatory unless both feed author email and homepage are empty.'),
			'default' => '',
		],
		'feed_atom_author_email' => [
			'name' => tra('Feed author email'),
			'description' => tra('Email of the author of each item in the feed.'),
			'type' => 'text',
			'size' => '40',
			'default' => '',
		],
		'feed_atom_author_url' => [
			'name' => tra('Feed author homepage'),
			'description' => tra('Homepage URL of the author of each item in the feed.'),
			'type' => 'text',
			'size' => '40',
			'default' => '',
		],

		// rss specific preferences
		'feed_rss_editor_email' => [
			'name' => tra('Feed editor email'),
			'description' => tra('Email address for person responsible for editorial content.'),
			'type' => 'text',
			'size' => '40',
			'default' => '',
		],
		'feed_rss_webmaster_email' => [
			'name' => tra('Feed webmaster email'),
			'description' => tra('Email address for person responsible for technical issues relating to channel.'),
			'type' => 'text',
			'size' => '40',
			'default' => '',
		],

		'feed_img' => [
			'name' => tra('Feed image path'),
			'description' => tra('Specifies a GIF, JPEG or PNG image that can be displayed with the feed.'),
			'type' => 'text',
			'size' => '40',
			'default' => 'img/tiki/Tiki_WCG.png',
		],
		'feed_language' => [
			'name' => tra('Feed language'),
			'description' => tra('The default language for this feed'),
			'type' => 'text',
			'size' => '10',
			'default' => 'en-us',
			'tags' => ['basic'],
		],
		'feed_basic_auth' => [
			'name' => tra('RSS basic authentication'),
			'description' => tra('Propose basic HTTP authentication if the user has no permission to see the feed'),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_cache_time' => [
			'name' => tra('Feed caching time'),
			'description' => tra('Cache the feed for this number of seconds.'),
			'type' => 'text',
			'size' => '5',
			'filter' => 'digits',
			'units' => tra('seconds'),
			'hint' => tra('Use 0 for no caching'),
			'default' => '300', // 5 minutes
			'detail' => tra('Feed caching is done for anonymous users only.'),
		],
		'feed_articles' => [
			'name' => tra('RSS for articles'),
			'description' => tra('RSS feeds for articles'),
			'type' => 'flag',
			'dependencies' => [
				'feature_articles',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_blogs' => [
			'name' => tra('RSS for blogs'),
			'description' => tra('RSS feeds for blogs'),
			'type' => 'flag',
			'dependencies' => [
				'feature_blogs',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_blog' => [
			'name' => tra('RSS for individual blogs'),
			'description' => tra('RSS feeds for individual blogs'),
			'type' => 'flag',
			'dependencies' => [
				'feature_blogs',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_image_galleries' => [
			'name' => tra('RSS for image galleries'),
			'description' => tra('RSS feed for image galleries'),
			'type' => 'flag',
			'dependencies' => [
				'feature_galleries',
			],
			'default' => 'n',
		],
		'feed_image_gallery' => [
			'name' => tra('RSS for individual image galleries'),
			'description' => tra('RSS feeds for individual image galleries'),
			'type' => 'flag',
			'dependencies' => [
				'feature_galleries',
			],
			'default' => 'n',
		],
		'feed_file_galleries' => [
			'name' => tra('RSS for file galleries'),
			'description' => tra('RSS feed for file galleries'),
			'type' => 'flag',
			'dependencies' => [
				'feature_file_galleries',
			],
			'default' => 'n',
		],
		'feed_file_gallery' => [
			'name' => tra('RSS for individual file galleries'),
			'description' => tra('RSS feeds for individual file galleries'),
			'type' => 'flag',
			'dependencies' => [
				'feature_file_galleries',
			],
			'default' => 'n',
		],
		'feed_wiki' => [
			'name' => tra('RSS for wiki pages'),
			'description' => tra('RSS feed for wiki pages'),
			'type' => 'flag',
			'dependencies' => [
				'feature_wiki',
			],
			'default' => 'n',
		],
		'feed_forums' => [
			'name' => tra('RSS for forums'),
			'description' => tra('RSS feed for forums'),
			'type' => 'flag',
			'dependencies' => [
				'feature_forums',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_forum' => [
			'name' => tra('RSS for individual forums'),
			'description' => tra('RSS feeds for individual forums'),
			'type' => 'flag',
			'dependencies' => [
				'feature_forums',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_tracker' => [
			'name' => tra('RSS per tracker'),
			'description' => tra('RSS feed per tracker'),
			'type' => 'flag',
			'dependencies' => [
				'feature_trackers',
			],
			'default' => 'n',
		],
		'feed_calendar' => [
			'name' => tra('RSS for calendar events'),
			'description' => tra('RSS feed for calendar events'),
			'type' => 'flag',
			'dependencies' => [
				'feature_calendar',
			],
			'default' => 'n',
			'tags' => ['basic'],
		],
		'feed_directories' => [
			'name' => tra('RSS for directories'),
			'description' => tra('RSS feed for directories'),
			'type' => 'flag',
			'dependencies' => [
				'feature_directory',
			],
			'default' => 'n',
		],
		'feed_shoutbox' => [
			'name' => tra('RSS for shoutbox'),
			'description' => tra('RSS feed for shoutbox'),
			'type' => 'flag',
			'dependencies' => [
				'feature_shoutbox',
			],
			'default' => '',
		],
		'feed_articles_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_blogs_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_blog_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_image_galleries_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_image_gallery_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_file_galleries_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_file_gallery_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_wiki_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_forums_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_forum_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_tracker_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_calendar_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_directories_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => 10,
		],
		'feed_shoutbox_max' => [
			'name' => tra('Maximum number of items to display'),
			'description' => tra(''),
			'type' => 'text',
			'size' => 5,
			'filter' => 'digits',
			'units' => tra('items'),
			'default' => '',
		],
		'feed_articles_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_blogs_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_blog_showAuthor' => [
			'name' => tra('Show author'),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_image_galleries_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_image_gallery_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_file_galleries_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_file_gallery_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_wiki_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_forums_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_forum_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_tracker_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_calendar_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_directories_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_shoutbox_showAuthor' => [
			'name' => tra('Show author'),
			'description' => tra(''),
			'type' => 'flag',
			'default' => 'n',
		],
		'feed_articles_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_blogs_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_blog_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_image_galleries_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_image_gallery_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_file_galleries_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_file_gallery_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_wiki_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_forum_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_tracker_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_calendar_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_directories_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_shoutbox_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
		'feed_articles_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for articles'),
		],
		'feed_blogs_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for blogs'),
		],
		'feed_blog_title' => [
			'name' => tra('Title'),
			'description' => tra('Title to be prepended to the blog title for all blogs. If this field is empty only the blog title will be used.'),
			'type' => 'text',
			'size' => '80',
			'default' => '',
		],
		'feed_image_galleries_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for image galleries'),
		],
		'feed_image_gallery_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for the image gallery: '),
		],
		'feed_file_galleries_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for file galleries'),
		],
		'feed_file_gallery_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for the file gallery: '),
		],
		'feed_wiki_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for the wiki pages'),
		],
		'feed_forums_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for forums'),
		],
		'feed_forum_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for forum: '),
		],
		'feed_tracker_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for individual trackers: '),
		],
		'feed_calendar_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for calendars'),
		],
		'feed_directories_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for directory sites'),
		],
		'feed_shoutbox_title' => [
			'name' => tra('Title'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '80',
			'default' => tra('Tiki RSS feed for shoutbox messages'),
		],
		'feed_articles_desc' => [
			'name' => tra('Article RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for articles.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Last articles.'),
		],
		'feed_blogs_desc' => [
			'name' => tra('Blogs RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for blogs.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Last posts to weblogs.'),
		],
		'feed_blog_desc' => [
			'name' => tra('Blog RSS Description'),
			'description' => tra('Description to be prepended to the blog description and published as part of the RSS feeds for individual blogs. If this field is empty, the blog description only will be used.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => '',
		],
		'feed_image_galleries_desc' => [
			'name' => tra('Image galleries RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for image galleries.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest images uploaded to the image galleries.'),
		],
		'feed_image_gallery_desc' => [
			'name' => tra('Individual image galleries RSS Description'),
			'description' => tra('Description to be published as part of the RSS feeds for individual image galleries.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest images uploaded to this image gallery.'),
		],
		'feed_file_galleries_desc' => [
			'name' => tra('File galleries RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for file galleries.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest files uploaded to the file galleries.'),
		],
		'feed_file_gallery_desc' => [
			'name' => tra('Individual file galleries RSS description'),
			'description' => tra('Description to be published as part of the RSS feeds for individual file galleries.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest files uploaded to this file gallery.'),
		],
		'feed_wiki_desc' => [
			'name' => tra('Wiki pages RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for wiki pages pages.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest wiki page changes.'),
		],
		'feed_forums_desc' => [
			'name' => tra('Forums RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for forums.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest forum topics.'),
		],
		'feed_forum_desc' => [
			'name' => tra('Individual forums RSS description'),
			'description' => tra('Description to be published as part of the RSS feeds for individual forums.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest posts to this form'),
		],
		'feed_tracker_desc' => [
			'name' => tra('Individual trackers RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for individual trackers.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest additions to this tracker.'),
		],
		'feed_calendar_desc' => [
			'name' => tra('Calendar events RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for calendar events.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Upcoming Events.'),
		],
		'feed_directories_desc' => [
			'name' => tra('Directories RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for directories.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest sites.'),
		],
		'feed_shoutbox_desc' => [
			'name' => tra('Shoutbox RSS description'),
			'description' => tra('Description to be published as part of the RSS feed for shoutbox messages.'),
			'type' => 'textarea',
			'size' => 2,
			'default' => tra('Latest shoutbox messages.'),
		],
		'feed_tracker_labels' => [
			'name' => tra('Tracker labels'),
			'description' => tra('Include tracker field labels in the RSS output'),
			'type' => 'flag',
			'dependencies' => [
				'feature_trackers',
			],
			'default' => 'y',
		],
		'feed_forums_homepage' => [
			'name' => tra('Homepage URL'),
			'description' => tra(''),
			'type' => 'text',
			'size' => '60',
			'default' => '',
		],
	];
}
