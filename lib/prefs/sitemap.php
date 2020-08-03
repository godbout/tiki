<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_sitemap_list()
{
    return [
        'sitemap_enable' => [
            'name' => tra('Sitemap protocol'),
            'description' => tra('Allows generating site maps based on the Sitemap protocol, in the form of XML documents. Mostly used to facilitate indexation of a site by web search engines.'),
            'type' => 'flag',
            'help' => 'https://www.sitemaps.org/protocol.html',
            'default' => 'n',
            'since' => '18',
            'tags' => ['advanced'],
            'admin' => 'tiki-admin_sitemap.php',
        ],
    ];
}
