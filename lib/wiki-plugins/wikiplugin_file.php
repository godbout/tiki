<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

define('WIKIPLUGIN_FILE_PAGE_LAST_MOD', 'PAGE_LAST_MOD');
define('WIKIPLUGIN_FILE_PAGE_VIEW_DATE', 'PAGE_VIEW_DATE');

function wikiplugin_file_info()
{
    global $prefs;
    $info = [
        'name' => tra('File'),
        'documentation' => 'PluginFile',
        'description' => tra('Link to a file that\'s attached or in a gallery or archive'),
        'prefs' => [ 'wikiplugin_file' ],
        'body' => tra('Label for the link to the file (ignored if the file is a wiki attachment)'),
        'iconname' => 'file',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('Type'),
                'description' => tra('Indicate whether the file is in a file gallery or is a wiki page attachment'),
                'since' => '6.1',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                ], //rest filled in below
            ],
            'name' => [
                'required' => true,
                'name' => tra('Name'),
                'description' => tra('Identify an attachment by entering its file name, which will show as a link to the
					file. If the page parameter is empty, it must be a file name of an attachment to the page where the
					plugin is used.'),
                'since' => '3.0',
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'attachment'],
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Custom Description'),
                'description' => tra('Custom text that will be used for the link instead of the file name or file description'),
                'since' => '3.0',
                'parentparam' => ['name' => 'type', 'value' => 'attachment'],
                'advanced' => true,
                'default' => '',
            ],
            'page' => [
                'required' => false,
                'name' => tra('Page'),
                'description' => tra('Name of the wiki page the file is attached to. Defaults to the wiki page where the
					plugin is used if empty.'),
                'since' => '3.0',
                'parentparam' => ['name' => 'type', 'value' => 'attachment'],
                'default' => '',
                'advanced' => true,
                'profile_reference' => 'wiki_page',
            ],
            'showdesc' => [
                'required' => false,
                'name' => tra('Attachment Description'),
                'description' => tra('Show the attachment description as the link label instead of the attachment file name.'),
                'since' => '3.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0],
                ],
                'parentparam' => ['name' => 'type', 'value' => 'attachment'],
                'default' => '',
                'advanced' => true,
            ],
            'image' => [
                'required' => false,
                'name' => tra('Image'),
                'description' => tra('Indicates that this attachment is an image, and should be displayed inline using the img tag'),
                'since' => '3.0',
                'parentparam' => ['name' => 'type', 'value' => 'attachment'],
                'advanced' => true,
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            'fileId' => [
                'required' => true,
                'name' => tra('File ID'),
                'description' => tra('File ID of a file in a file gallery or an archive.') . ' ' . tra('Example value:')
                    . ' <code>42</code>',
                'since' => '5.0',
                'type' => 'fileId',
                'area' => 'fgal_picker_id',
                'filter' => 'digits',
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'gallery'],
                'profile_reference' => 'file',
            ],
            'date' => [
                'required' => false,
                'name' => tra('Date'),
                'description' => tr('For an archive file, the archive created just before this date will be linked to.
					Special values : %0 and %1.', '<code>PAGE_LAST_MOD</code>', '<code>PAGE_VIEW_DATE</code>'),
                'since' => '5.0',
                'parentparam' => ['name' => 'type', 'value' => 'gallery'],
                'default' => '',
                'advanced' => true,
            ],
            'showicon' => [
                'required' => false,
                'name' => tra('Show Icon'),
                'description' => tra('Show an icon version of the file or file type with the link to the file.'),
                'since' => '6.1',
                'filter' => 'alpha',
                'parentparam' => ['name' => 'type', 'value' => 'gallery'],
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
            'browserdisplay' => [
                'required' => false,
                'name' => tra('Browser Display'),
                'description' => tra('Display in different browser window or tab instead of downloading.'),
                'since' => '18.1',
                'filter' => 'alpha',
                'parentparam' => ['name' => 'type', 'value' => 'gallery'],
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'advanced' => true,
            ],
        ]
    ];
    if ($prefs['feature_file_galleries'] == 'y') {
        $info['params']['type']['options'][] = ['text' => tra('File Gallery File/Archive'), 'value' => 'gallery'];
    }
    if ($prefs['feature_wiki_attachments'] == 'y') {
        $info['params']['type']['options'][] = ['text' => tra('Wiki Page Attachment'), 'value' => 'attachment'];
    }

    return $info;
}

function wikiplugin_file($data, $params)
{
    global $tikilib, $prefs, $info, $page_view_date;
    if (isset($params['fileId'])) {
        $filegallib = TikiLib::lib('filegal');
        if ($prefs['feature_file_galleries'] != 'y') {
            return;
        }
        $fileId = $params['fileId'];
        if (isset($params['date'])) {
            static $wikipluginFileDate = 0;
            if (empty($params['date'])) {
                if (empty($wikipluginFileDate)) {
                    return tra('The date has not been set');
                }
                $date = $wikipluginFileDate;
            } else {
                if (strcmp($params['date'], WIKIPLUGIN_FILE_PAGE_LAST_MOD) == 0) {
                    // Page last modification date
                    $date = $info['lastModif'];
                } elseif (strcmp($params['date'], WIKIPLUGIN_FILE_PAGE_VIEW_DATE) == 0) {
                    // Current date parameter
                    $date = (isset($page_view_date)) ? $page_view_date : time();
                } elseif (($date = strtotime($params['date'])) === false) {
                    return tra('Incorrect date format');
                }
                $wikipluginFileDate = $date;
            }
            $fileId = $filegallib->getArchiveJustBefore($fileId, $date);
            if (empty($fileId)) {
                return tra('No such file');
            }
        } else {
            $file_info = $filegallib->get_file_info($fileId, false, false);
            if (empty($file_info)) {
                return tra('Incorrect parameter') . ' fileId';
            }
        }

        if (empty($data)) { // to avoid problem with parsing
            $data = empty($file_info['name']) ? $file_info['filename'] : $file_info['name'];
        }
        if (isset($params['browserdisplay']) && $params['browserdisplay'] == 'y') {
            if (isset($params['showicon']) && $params['showicon'] == "y") {
                return "{img src=tiki-download_file.php?fileId=$fileId&amp;thumbnail=y link=tiki-download_file.php?fileId=$fileId&display=y styleimage=max-width:32px;max-height:36px thumb=y responsive='n'} " . "<a class='wiki' href='tiki-download_file.php?fileId=$fileId&display=y' target='_blank' >" . $data . "</a>";
            }

            return "<a class='wiki' href='tiki-download_file.php?fileId=$fileId&display=y' target='_blank' >" . $data . "</a>";
        }
        if (isset($params['showicon']) && $params['showicon'] == "y") {
            return "{img src=tiki-download_file.php?fileId=$fileId&amp;thumbnail=y link=tiki-download_file.php?fileId=$fileId styleimage=max-width:32px responsive='n'} [tiki-download_file.php?fileId=$fileId|$data]";
        }

        return "[tiki-download_file.php?fileId=$fileId|$data]";
    }

    if ($prefs['feature_wiki_attachments'] != 'y') {
        return "<span class='warn'>" . tra("Wiki attachments are disabled.") . "</span>";
    }
    $filedata = [];
    $filedata["name"] = '';
    $filedata["desc"] = '';
    $filedata["showdesc"] = '';
    $filedata["page"] = '';
    $filedata["image"] = '';

    $filedata = array_merge($filedata, $params);

    if (! $filedata["name"]) {
        return;
    }

    $forward = [];
    $forward['file'] = $filedata['name'];
    $forward['inline'] = 1;
    $forward['page'] = $filedata['page'];
    if ($filedata['showdesc']) {
        $forward['showdesc'] = 1;
    }
    if ($filedata['image']) {
        $forward['image'] = 1;
    }
    $middle = $filedata["desc"];

    return TikiLib::lib('parser')->plugin_execute('attach', $middle, $forward);
}
