<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_article_list()
{
    $comment_sort_orders = [
        'commentDate_desc' => tra('Newest first'),
        'commentDate_asc' => tra('Oldest first'),
        'points_desc' => tra('Points'),
    ];

    $prefslib = TikiLib::lib('prefs');
    $advanced_columns = $prefslib->getExtraSortColumns();

    foreach ($advanced_columns as $key => $label) {
        $comment_sort_orders[ $key . '_asc' ] = $label . ' ' . tr('ascending');
        $comment_sort_orders[ $key . '_desc' ] = $label . ' ' . tr('descending');
    }

    return [
        'article_comments_per_page' => [
            'name' => tra('Number per page'),
            'description' => tra('Set the number of comments per page.'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'units' => tra('comments'),
            'default' => 10,
        ],
        'article_comments_default_ordering' => [
            'name' => tra('Display order'),
            'description' => tra('Set the display order of comments.'),
            'type' => 'list',
            'options' => $comment_sort_orders,
            'default' => 'points_desc',
        ],
        'article_paginate' => [
            'name' => tra('Paginate articles'),
            'description' => tra('Divide articles into multiple pages with pagebreak markers.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'article_remembers_creator' => [
            'name' => tra('Article creator remains article owner'),
            'description' => tra('Last article editor does not automatically become author (owner).'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'article_user_rating' => [
            'name' => tra('User ratings on articles'),
            'description' => tra('Allow users to rate articles.'),
            'type' => 'flag',
            'hint' => tr('Permissions involved: %0. Also, when configuring articles, "Admin Types > Comment can rate article" needs to be set.', 'rate_article, ratings_view_results'),
            'default' => 'n',
        ],
        'article_user_rating_options' => [
            'name' => tra('Article rating options'),
            'description' => tra('List of options available for the rating of articles.'),
            'type' => 'text',
            'separator' => ',',
            'filter' => 'int',
            'default' => "0,1,2,3,4,5",
        ],
        'article_image_size_x' => [
            'name' => tra('Default maximum width for custom article images'),
            'description' => tra('Set the default maximum width of the article image'),
            'type' => 'text',
            'size' => 3,
            'filter' => 'int',
            'units' => tra('pixels'),
            'hint' => tra('"0" for no maximum'),
            'default' => '0',
        ],
        'article_image_size_y' => [
            'name' => tra('Default maximum height for custom article images'),
            'description' => tra('Set the default maximum height of article images'),
            'type' => 'text',
            'size' => 3,
            'filter' => 'int',
            'units' => tra('pixels'),
            'hint' => tra('"0" for no maximum') ,
            'default' => '0',
        ],
        'article_default_list_image_size_x' => [
            'name' => tra('Default maximum width for custom article images in list mode (on View Articles)'),
            'description' => tra('Sets the default maximum width of custom article images in list mode (on View Articles page)'),
            'type' => 'text',
            'size' => 3,
            'filter' => 'int',
            'units' => tra('pixels'),
            'hint' => tra('"0" to default to the view mode maximum'),
            'default' => '0',
        ],
        'article_default_list_image_size_y' => [
            'name' => tra('Default maximum height of custom article images in list mode (on View Articles page)'),
            'description' => tra('Set the default maximum height of custom article images in list mode (on the View Articles page)'),
            'type' => 'text',
            'size' => 3,
            'filter' => 'int',
            'units' => tra('pixels'),
            'hint' => tra('"0" to default to the view mode maximum'),
            'default' => '0',
        ],
        'article_image_file_size_max' => [
            'name' => tra('Article image maximum file size'),
            'description' => tra('Maximum file size for an article image. Article images are stored in the database so it should remain low.'),
            'type' => 'text',
            'size' => '10',
            'filter' => 'digits',
            'units' => tra('kilobytes'),
            'default' => 500000,
        ],
        'article_custom_attributes' => [
            'name' => tra('Custom attributes for article types'),
            'description' => tra('Enable additional custom fields for article types'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'article_sharethis_publisher' => [
            'name' => tra('Your ShareThis publisher identifier (optional)'),
            'description' => tra('Set to define your ShareThis publisher identifier'),
            'type' => 'text',
            'size' => '40',
            'hint' => tra('record your ShareThis publisher ID'),
            'default' => '',
        ],
        'article_related_articles' => [
            'name' => tr('Related articles'),
            'description' => tr('Display a list of related articles at the bottom of an article page'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_freetags',
            ],
        ],
        'article_use_new_list_articles' => [
            'name' => tr('Use new articles'),
            'description' => tr('Use the new CustomSearch-based article lists rather than database information'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['experimental'],
        ],
        'article_feature_copyrights' => [
            'name' => tra('Article copyright'),
            'description' => tra('Apply copyright management preferences to this feature.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_articles',
            ],
            'default' => 'n',
        ],
    ];
}
