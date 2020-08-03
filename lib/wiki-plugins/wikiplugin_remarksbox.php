<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_remarksbox_info()
{
    return [
        'name' => tra('Remarks Box'),
        'documentation' => 'PluginRemarksBox',
        'description' => tra('Display a comment, tip, note or warning box'),
        'prefs' => [ 'wikiplugin_remarksbox' ],
        'body' => tra('remarks text'),
        'iconname' => 'comment',
        'introduced' => 2,
        'tags' => [ 'basic' ],
        'format' => 'html',
        'params' => [
            'type' => [
                'required' => false,
                'name' => tra('Type'),
                'description' => tra('Select type of remarksbox, which determines what icon and style will be displayed'),
                'since' => '2.0',
                'default' => 'tip',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Comment'), 'value' => 'comment'],
                    ['text' => tra('Confirm'), 'value' => 'confirm'],
                    ['text' => tra('Errors'), 'value' => 'errors'],
                    ['text' => tra('Information'), 'value' => 'information'],
                    ['text' => tra('Note'), 'value' => 'note'],
                    ['text' => tra('Tip'), 'value' => 'tip'],
                    ['text' => tra('Warning'), 'value' => 'warning']
                ]
            ],
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra('Label displayed above the remark.'),
                'since' => '2.0',
                'filter' => 'text',
                'default' => '',
            ],
            'highlight' => [
                'required' => false,
                'name' => tra('Highlight'),
                'description' => tra('Use the highlight class for formatting (not used by default).') ,
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'icon' => [
                'required' => false,
                'name' => tra('Custom Icon'),
                'description' => tra('Enter a custom icon name (from tiki available icon at https://doc.tiki.org/PluginIcon)'),
                'since' => '2.0',
                'filter' => 'text',
                //'default' => '',  (use empty string for no icon)
            ],
            'close' => [
                'required' => false,
                'name' => tra('Close'),
                'description' => tra('Show a close button (not shown by default).'),
                'since' => '4.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tr('Width (e.g. %0100%%1 or %0250px%1 - default "")', '<code>', '</code>'),
                'since' => '4.1',
                'filter' => 'text',
                'default' => ''
            ],
            'store_cookie' => [
                'name' => tr('Remember Dismiss'),
                'description' => tr('Set whether to remember if the alert is dismissed (not remembered by default).
					Requires %0id%1 and %0version%1 parameters to be set.', '<code>', '</code>'),
                'since' => '14.0',
                'required' => false,
                'filter' => 'text',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'id' => [
                'name' => tr('ID'),
                'description' => tr('Sets an HTML id for the account.'),
                'since' => '14.0',
                'required' => false,
                'filter' => 'text'
            ],
            'version' => [
                'name' => tr('Version'),
                'description' => tr('Sets a version for the alert. If new version, the alert should show up again even
					if it was previously dismissed using the %0store_cookie%1 parameter', '<code>', '</code>'),
                'since' => '14.0',
                'required' => false,
                'filter' => 'text',
                'default' => '',
            ],
            'title_tag' => [
                'name' => tr('Title Tag'),
                'description' => tr(''),
                'since' => '21.1',
                'required' => false,
                'filter' => 'text',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tr('Div class h4') . ' ' . tr('(default)'), 'value' => 'div'],
                    ['text' => tr('H4 (legacy)'), 'value' => 'h4'],
                ],
                'default' => 'div',
            ],
            'title_class' => [
                'name' => tr('Title Class'),
                'description' => tr(''),
                'since' => '21.1',
                'required' => false,
                'filter' => 'text',
                'default' => 'alert-heading h4',
            ],
        ]
    ];
}

function wikiplugin_remarksbox($data, $params)
{
    $plugininfo = wikiplugin_remarksbox_info();
    $default = [];
    foreach ($plugininfo['params'] as $key => $param) {
        if (isset($param['default'])) {
            $default[$key] = $param['default'];
        }
    }
    $params = array_merge($default, $params);

    $smarty = TikiLib::lib('smarty');
    require_once('lib/smarty_tiki/block.remarksbox.php');

    $repeat = false;
    $ret = smarty_block_remarksbox($params, '~/np~' . tra($data) . ' ~np~', $smarty, $repeat);

    return $ret;
}
