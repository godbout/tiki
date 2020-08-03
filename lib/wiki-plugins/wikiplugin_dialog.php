<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_dialog_info()
{
    return [
        'name' => tra('Dialog'),
        'documentation' => 'PluginDialog',
        'validate' => 'all',
        'description' => tra('Create a custom popup dialog box'),
        'prefs' => [ 'wikiplugin_dialog', 'feature_jquery_ui' ],
        'iconname' => 'link-external',
        'introduced' => 8,
        'body' => tra('text'),
        'params' => [
            'title' => [
                'required' => false,
                'name' => tra('Title'),
                'description' => tra(''),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'buttons' => [
                'required' => false,
                'name' => tra('Buttons'),
                'description' => tra('Button labels separated by commas.'),
                'since' => '8.0',
                'filter' => 'text',
                'separator' => ',',
                'default' => [tra('Ok')],
            ],
            'actions' => [
                'required' => false,
                'name' => tra('Button Actions'),
                'description' => tra('JavaScript to perform on first button click.'),
                'since' => '8.0',
                'filter' => 'rawhtml_unsafe',
                'separator' => ',',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Dialog Width'),
                'description' => tra('In pixels.'),
                'since' => '8.0',
                'filter' => 'digits',
                'default' => '300',
            ],
            'id' => [
                'required' => false,
                'name' => tra('HTML ID'),
                'description' => tr('Automatically generated if left empty in the form %0 (must be unique
					per page)', '<code>wpdialog_XX</code>'),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'showAnim' => [
                'required' => false,
                'name' => tra('Show Animation'),
                'description' => tra(''),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'hideAnim' => [
                'required' => false,
                'name' => tra('Hide Animation'),
                'description' => tra(''),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'autoOpen' => [
                'required' => false,
                'name' => tra('Auto Open'),
                'description' => tra('Open dialog automatically'),
                'since' => '8.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'modal' => [
                'required' => false,
                'name' => tra('Modal'),
                'description' => tra('Use modal form'),
                'since' => '8.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki Page'),
                'description' => tra('Wiki page to use as dialog body'),
                'since' => '8.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'openAction' => [
                'required' => false,
                'name' => tra('Open Action'),
                'description' => tra('JavaScript to execute when dialog opens.'),
                'since' => '8.0',
                'filter' => 'rawhtml_unsafe',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_dialog($data, $params)
{
    static $id = 0;
    $unique = 'wpdialog_' . ++$id;

    $headerlib = TikiLib::lib('header');

    $defaults = [];
    $plugininfo = wikiplugin_dialog_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    if (empty($params['id'])) {
        $params['id'] = $unique;
    }

    if (! empty($params['wiki'])) {
        $ignore = '';
        $data = TikiLib::lib('wiki')->get_parse($params['wiki'], $ignore, true);
    }

    $options = ['width' => $params['width']];
    $options['autoOpen'] = ($params['autoOpen'] === 'y');
    $options['modal'] = ($params['modal'] === 'y');
    if (! empty($params['showAnim'])) {
        $options['show'] = $params['showAnim'];
    }
    if (! empty($params['hideAnim'])) {
        $options['hide'] = $params['hideAnim'];
    }

    if (empty($params['actions'])) {
        $params['actions'] = [];
    }

    if (empty($params['buttons'])) {
        $params['buttons'] = [];
    }

    $buttons = '';	// buttons need functions attached and json_encode cannot deal with them ;(

    $nbuts = count($params['buttons']);
    if ($nbuts > 0) {
        $buttons = '{';
        for ($i = 0; $i < $nbuts; $i++) {
            if (! isset($params['actions'][$i])) {
                $params['actions'][$i] = '$(this).dialog("close");';
            }
            if (strpos($params['actions'][$i], '$(this).dialog("close");') === false) {
                $params['actions'][$i] .= ';$(this).dialog("close");';
            }
            $buttons .= strlen($buttons) > 1 ? ',' : '';
            $buttons .= json_encode($params['buttons'][$i]);
            $buttons .= ': function(){' . $params['actions'][$i] . '}';
        }
        $buttons .= '}';
        $options['buttons'] = 'buttonsdummy';
    }
    $opens = '';
    if (! empty($params['openAction'])) {
        $opens = 'function(){' . $params['openAction'] . '}';
        $options['open'] = 'opendummy';
    }
    $optString = json_encode($options);
    if (! empty($buttons)) {
        $optString = str_replace('"buttonsdummy"', $buttons, $optString);
    }
    if (! empty($opens)) {
        $optString = str_replace('"opendummy"', $opens, $optString);
    }

    $js = '$("#' . $params['id'] . '").dialog(' . $optString . ');';
    $headerlib->add_js('$(function() {' . $js . '});');

    if (empty($params['title'])) {
        $titlestr = '';
    } else {
        $titlestr = ' title="' . $params['title'] . '"';
    }
    $html = '<div id="' . $params['id'] . '"' . $titlestr . ' style="display:none">' . $data . '</div>';

    return $html;
}
