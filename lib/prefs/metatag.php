<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_metatag_list()
{
    return [
        'metatag_keywords' => [
            'name' => tra('Keywords'),
            'description' => tra('A list of keywords (separated by commas) that describe this website.'),
            'type' => 'textarea',
            'size' => '4',
            'default' => '',
            'tags' => ['basic'],
            'translatable' => true,
        ],
        'metatag_freetags' => [
            'name' => tra('Include tags'),
            'description' => tra('If the Tags feature is enabled, the tags for each page with tags set will be used as meta keywords. This allows individual pages at the site to have different meta tags.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_freetags',
            ],
            'default' => 'n',
        ],
        'metatag_threadtitle' => [
            'name' => tra('Use thread title instead'),
            'description' => tra('Use the forum thread title in the meta title tag.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_forums',
            ],
            'default' => 'n',
        ],
        'metatag_imagetitle' => [
            'name' => tra('Use the image title instead'),
            'description' => tra('Use the image title in the meta title tag'),
            'type' => 'flag',
            'dependencies' => [
                'feature_galleries',
            ],
            'default' => 'n',
        ],
        'metatag_description' => [
            'name' => tra('Description'),
            'description' => tra('A short description of the website. Some search engines display this information with the website\'s listing.'),
            'type' => 'textarea',
            'size' => '5',
            'default' => '',
            'tags' => ['basic'],
            'translatable' => true,
        ],
        'metatag_pagedesc' => [
            'name' => tra('Page description'),
            'description' => tra('Use each page description as a meta tag for that page.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'metatag_author' => [
            'name' => tra('Author'),
            'description' => tra('The author of this website. Typically this is the Admin or Webmaster.'),
            'type' => 'text',
            'size' => '50',
            'default' => '',
            'tags' => ['basic'],
        ],
        'metatag_geoposition' => [
            'name' => tra('geo.position'),
            'description' => tra('The latitude and longitude of the physical location of the site. For example "38.898748, -77.037684".'),
            'type' => 'text',
            'size' => '50',
            'help' => 'http://geotags.com/geo/geotags2.html',
            'default' => '',
        ],
        'metatag_georegion' => [
            'name' => tra('geo.region'),
            'description' => tra('The ISO-3166 country and region codes for your location. For example, "US-NY".'),
            'type' => 'text',
            'size' => '50',
            'help' => 'http://en.wikipedia.org/wiki/ISO_3166-1',
            'default' => '',
        ],
        'metatag_geoplacename' => [
            'name' => tra('geo.placename'),
            'description' => tra('A free-text description of your location.'),
            'type' => 'text',
            'size' => '50',
            'default' => '',
        ],
        'metatag_robots' => [
            'name' => tra('Meta robots'),
            'description' => tra('Specify how search engines robots should index your site. Will override page defaults. Valid values include: noindex, nofollow, none, all, noimageindex, noarchive, nocache, nosnippet, notranslate, unavailable_after and noyaca.'),
            'type' => 'text',
            'shorthint' => tra('Should be comma separated eg. noimageindex, nocache.'),
            'help' => 'Robots-Exclusion-Protocol#HTML_META_Directives',
            'size' => '50',
            'default' => '',
        ],
        'metatag_revisitafter' => [
            'name' => tra('Revisit after'),
            'description' => tra('Specify how often (in days) Web robots should visit your site.'),
            'type' => 'text',
            'unit' => tr('days'),
            'size' => '50',
            'default' => '',
            'tags' => ['experimental'],
        ],
    ];
}
