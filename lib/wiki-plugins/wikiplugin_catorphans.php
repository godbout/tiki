<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_catorphans_info()
{
    return [
        'name' => tra('Category Orphans'),
        'documentation' => 'PluginCatOrphans',
        'description' => tra('List objects that are not categorized'),
        'prefs' => [ 'feature_categories', 'wikiplugin_catorphans' ],
        'iconname' => 'structure',
        'introduced' => 1,
        'params' => [
            'objects' => [
                'required' => false,
                'name' => tra('Object'),
                'description' => tr('Determine which type of objects are shown (default is %0)', '<code>wiki</code>'),
                'since' => '1',
                'default' => 'wiki',
                'filter' => 'text',
                'options' => [
                    ['text' => tra('Wiki Pages'), 'value' => 'wiki'],
                    ['text' => tra('File Galleries'), 'value' => 'file gallery'],
                    ['text' => tra('Articles'), 'value' => 'article'],
                    ['text' => tra('Trackers'), 'value' => 'tracker'],
                    ['text' => tra('Blogs'), 'value' => 'blog'],
                    ['text' => tra('Calendars'), 'value' => 'calendar'],
                    ['text' => tra('Forums'), 'value' => 'forum'],
                ]
            ],
            'max' => [
                'required' => false,
                'name' => tra('Max'),
                'description' => tr('Maximum number of items. Use %0 for unlimited. Default is the site admin setting
					for maximum records.', '<code>-1</code>'),
                'since' => '1',
                'default' => '$prefs[\'maxRecords\']',
                'filter' => 'int',
            ],
            'offset' => [
                'required' => false,
                'name' => tra('Result Offset'),
                'description' => tra('Result number at which the listing should start (default is no offset)'),
                'since' => '1',
                'default' => 0,
                'filter' => 'int',
            ],
        ],
    ];
}

function wikiplugin_catorphans($data, $params)
{
    global $prefs;
    $access = TikiLib::lib('access');
    $access->check_feature('feature_categories');
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $categlib = TikiLib::lib('categ');

    $default = ['offset' => 0, 'max' => $prefs['maxRecords'], 'objects' => 'wiki'];
    $params = array_merge($default, $params);
    extract($params, EXTR_SKIP);
    // check required objects parameter
    if ($params['objects'] !== 'wiki' && $params['objects'] !== 'file gallery' && $params['objects'] !== 'article' && $params['objects'] !== 'tracker' && $params['objects'] !== 'blog' && $params['objects'] !== 'calendar' && $params['objects'] !== 'forum') {
        return ("<span class='error'>Wrong objects parameter - only wiki, file gallery, article, tracker, blog, calendar, and forum allowed at present</span>");
    }

    if (! empty($_REQUEST['offset'])) {
        $offset = $_REQUEST['offset'];
    }

    // assign the various smarty variables and get the data for the individual object types
    $smarty->assign('objecttype', $objects);
    if ($objects == 'wiki') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'wiki page', 'pages', 'page_Id');
    } elseif ($objects == 'file gallery') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'file gallery', 'file_galleries', 'galleryId');
    } elseif ($objects == 'article') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'article', 'articles', 'articleId');
    } elseif ($objects == 'tracker') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'tracker', 'trackers', 'trackerId');
    } elseif ($objects == 'blog') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'blog', 'blogs', 'blogId');
    } elseif ($objects == 'calendar') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'calendar', 'calendars', 'calendarId');
    } elseif ($objects == 'forum') {
        $listobjects = $categlib->get_catorphan_object_type($offset, $max, 'forum', 'forums', 'forumId');
    }
    $smarty->assign_by_ref('orphans', $listobjects['data']);
    $smarty->assign('pagination', ['cant' => $listobjects['cant'], 'step' => $max, 'offset' => $offset]);
    $smarty->assign('totalcount', $listobjects['countall']);
    $out = '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_catorphans.tpl') . '~/np~';
    $smarty->assign('pagination', null);

    return $out;
}
