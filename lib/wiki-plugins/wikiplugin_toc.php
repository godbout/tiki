<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_toc_info()
{
    return [
        'name' => tra('Table of Contents (Structure)'),
        'documentation' => 'PluginTOC',
        'description' => tra('Display a table of contents of pages in a structure'),
        'prefs' => [ 'wikiplugin_toc', 'feature_wiki_structure' ],
        'iconname' => 'list-numbered',
        'introduced' => 3,
        'lateParse' => true,
        'params' => [
            'structId' => [
                'name' => tra('Structure ID'),
                'description' => tra('By default, structure for the current page will be displayed. Alternate
					structure may be provided.'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'structure',
            ],
            'pagename' => [
                'name' => tra('Page Name'),
                'description' => tra('By default, the table of contents for the current page will be displayed.
					Alternate page may be provided.'),
                'since' => '5.0',
                'required' => false,
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'order' => [
                'name' => tra('Order'),
                'description' => tra('Order items in ascending or descending order (default is ascending).'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'alpha',
                'default' => 'asc',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Ascending'), 'value' => 'asc'],
                    ['text' => tra('Descending'), 'value' => 'desc']
                ]
            ],
            'sortalpha' => [
                'name' => tra('Sort Order'),
                'description' => tr('Order for the first Level of pages that will be displayed. Order by structure is the default.'),
                'since' => '15.3.',
                'required' => false,
                'filter' => 'alpha',
                'default' => 'struct',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Structure Order'), 'value' => 'struct'],
                    ['text' => tra('Alphabetic Order'), 'value' => 'alpha']
                ]
            ],
            'showdesc' => [
                'name' => tra('Show Description'),
                'description' => tra('Display the page description in front of the page name'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'shownum' => [
                'name' => tra('Show Numbering'),
                'description' => tra('Display the section numbers or not'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'type' => [
                'name' => tra('Type'),
                'description' => tra('Style to apply'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'alpha',
                'default' => 'plain',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Plain'), 'value' => 'plain'],
                    ['text' => tra('Fancy'), 'value' => 'fancy'],
                    ['text' => tra('Admin'), 'value' => 'admin'],
                 ]
            ],
            'mindepth' => [
                'name' => tra('Start Level'),
                'description' => tr('Set the level starting from which page names are displayed. %0 or %1 (the default) means from level 1. Starting from %0 (and not 1).', '<code>0</code>', '<code>empty</code>'),
                'since' => '15.3.',
                'required' => false,
                'filter' => 'digits',
                'default' => 0,
            ],
            'maxdepth' => [
                'name' => tra('Maximum Level Depth'),
                'description' => tr('Set the number of levels to display. %0 means only 1 level will be displayed and %1 mean no limit (and is the default).', '<code>0</code>', '<code>empty</code>'),
                'since' => '3.0',
                'required' => false,
                'filter' => 'digits',
                'default' => 0,
            ],
        ],
    ];
}

function wikiplugin_toc($data, $params)
{
    $defaults = [
        'order' => 'asc',
        'showdesc' => false,
        'shownum' => false,
        'type' => 'plain',
        'structId' => '',
        'maxdepth' => 0,
        'mindepth' => 0,
        'sortalpha' => 'struct',
        'numberPrefix' => '',
        'pagename' => '',
    ];

    $params = array_merge($defaults, $params);
    extract($params, EXTR_SKIP);

    global $page_ref_id;
    $structlib = TikiLib::lib('struct');

    global $prefs;
    if ($prefs['feature_jquery_ui'] === 'y' && $type === 'admin') {
        TikiLib::lib('header')
            ->add_jsfile('lib/structures/tiki-edit_structure.js')
            ->add_jsfile('vendor_bundled/vendor/jquery-plugins/nestedsortable/jquery.ui.nestedSortable.js');

        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_button');
        $button = smarty_function_button(
            [
                '_text' => tra('Save'),
                '_style' => 'display:none;',
                '_class' => 'save_structure',
                '_ajax' => 'n',
                '_auto_args' => 'save_structure,page_ref_id',
            ],
            $smarty->getEmptyInternalTemplate()
        );
    } else {
        $button = '';
    }

    if (empty($structId)) {
        $pageName_ref_id = null;
        if (! empty($pagename)) {
            $pageName_ref_id = $structlib->get_struct_ref_id($pagename);
        } elseif (! empty($page_ref_id)) {
            $pageName_ref_id = $page_ref_id;
        }
        if (! empty($pageName_ref_id)) {	// we have a structure
            $page_info = $structlib->s_get_page_info($pageName_ref_id);
            $structure_info = $structlib->s_get_structure_info($pageName_ref_id);
            if (isset($page_info)) {
                $html = $structlib->get_toc($pageName_ref_id, $order, $showdesc, $shownum, $numberPrefix, $type, '', $maxdepth, $mindepth, $sortalpha, $structure_info['pageName']);

                return "~np~$button $html $button~/np~";
            }
        }

        return '';
    }
    $structure_info = $structlib->s_get_structure_info($structId);
    $html = $structlib->get_toc($structId, $order, $showdesc, $shownum, $numberPrefix, $type, '', $maxdepth, $mindepth, $sortalpha, $structure_info['pageName']);

    return "~np~$button $html $button~/np~";
}
