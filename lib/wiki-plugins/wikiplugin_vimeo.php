<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wiki-plugins/wikiplugin_flash.php');

function wikiplugin_vimeo_info()
{
    global $prefs;

    return [
        'name' => tra('Vimeo'),
        'documentation' => 'PluginVimeo',
        'description' => tra('Embed a Vimeo video'),
        'prefs' => [ 'wikiplugin_vimeo' ],
        'iconname' => 'vimeo',
        'introduced' => 6.1,
        'format' => 'html',
        'params' => [
            'url' => [
                'required' => $prefs['vimeo_upload'] !== 'y',
                'name' => tra('URL'),
                'description' => tra('Complete URL to the Vimeo video. Example:') . ' <code>http://vimeo.com/3319966</code>'
                    . ($prefs['vimeo_upload'] === 'y' ? ' ' . tra('or leave blank to upload one.') : ''),
                'since' => '6.1',
                'filter' => 'url',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => 425,
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels'),
                'since' => '6.1',
                'filter' => 'text',
                'default' => 350,
            ],
            'quality' => [
                'required' => false,
                'name' => tra('Quality'),
                'description' => tra('Quality of the video'),
                'since' => '6.1',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('High'), 'value' => 'high'],
                    ['text' => tra('Medium'), 'value' => 'medium'],
                    ['text' => tra('Low'), 'value' => 'low'],
                ],
                'default' => 'high',
                'advanced' => true
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Full screen'),
                'description' => tra('Expand to full screen'),
                'since' => '6.1',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'true'],
                    ['text' => tra('No'), 'value' => 'false'],
                ],
                'default' => '',
                'advanced' => true
            ],
            'fileId' => [
                'required' => false,
                'name' => tra('File ID'),
                'description' => tr(
                    'Numeric ID of a Vimeo file in a File Gallery (or list separated by commas or %0).',
                    '<code>|</code>'
                ),
                'since' => '12.0',
                'filter' => 'text',
                'default' => '',
                'advanced' => true
            ],
            'fromFieldId' => [
                'required' => false,
                'name' => tra('Field ID'),
                'description' => tra('Numeric ID of a Tracker Files field, using Vimeo displayMode.'),
                'since' => '12.0',
                'filter' => 'int',
                'default' => 0,
                'advanced' => true
            ],
            'fromItemId' => [
                'required' => false,
                'name' => tra('Item ID'),
                'description' => tra('Numeric ID of a Tracker item, using Vimeo displayMode.'),
                'since' => '12.0',
                'filter' => 'int',
                'default' => 0,
                'advanced' => true
            ],
            'galleryId' => [
                'required' => false,
                'name' => tra('Gallery ID'),
                'description' => tra('Gallery ID to upload to.'),
                'since' => '12.0',
                'filter' => 'int',
                'advanced' => true
            ],
            'useFroogaloopApi' => [
                'required' => false,
                'name' => tra('Froogaloop API'),
                'description' => tra('Use Vimeo Froogaloop API'),
                'since' => '14.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'true'],
                    ['text' => tra('No'), 'value' => 'false'],
                ],
                'default' => '',
                'advanced' => true,
            ],
            'showTitle' => [
                'required' => false,
                'name' => tra('Show Title'),
                'description' => tra('Show the Video Title') . ' ' . tra('(default is to show)'),
                'since' => '15.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'true'],
                    ['text' => tra('No'), 'value' => 'false'],
                ],
                'default' => '',
                'advanced' => true,
            ],
            'showByline' => [
                'required' => false,
                'name' => tra('Show Byline') . ' ' . tra('(default is to show)'),
                'description' => tra("Show the creator's byline"),
                'since' => '15.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'true'],
                    ['text' => tra('No'), 'value' => 'false'],
                ],
                'default' => '',
                'advanced' => true,
            ],
            'showPortrait' => [
                'required' => false,
                'name' => tra('Show Portrait') . ' ' . tra('(default is to show)'),
                'description' => tra("Show the creator's profile picture"),
                'since' => '15.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'true'],
                    ['text' => tra('No'), 'value' => 'false'],
                ],
                'default' => '',
                'advanced' => true,
            ],
        ],
    ];
}

function vimeo_iframe($data, $params)
{
    if (! empty($params['height'])) {
        $height = $params['height'];
    } else {
        $height = '350';
    }
    if (! empty($params['width'])) {
        $width = $params['width'];
    } else {
        $width = '425';
    }

    $urlparts = explode('/', $params['vimeo']);
    foreach ($urlparts as $urlpart) {
        if (ctype_digit($urlpart)) {
            $vimeoId = $urlpart;
        }
    }
    if (! isset($vimeoId)) {
        return '';
    }
    $url = '//player.vimeo.com/video/' . $vimeoId;

    $args = [];
    if (! empty($params['showTitle']) && $params['showTitle'] === 'false') {
        $args['title'] = 0;
    }
    if (! empty($params['showByline']) && $params['showByline'] === 'false') {
        $args['byline'] = 0;
    }
    if (! empty($params['showPortrait']) && $params['showPortrait'] === 'false') {
        $args['portrait'] = 0;
    }
    if ($args) {
        $url .= '?' . http_build_query($args);
    }

    $output = '<iframe data-fileid="' . $params['vimeo_fileId'] . '" id="' . $params['player_id'] . '" src="' . $url . '" width="' . $width . '" height="' . $height . '" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

    return $output;
}

function wikiplugin_vimeo($data, $params)
{
    global $prefs;
    static $instance = 0;
    $instance++;

    if ($params['useFroogaloopApi']) {
        TikiLib::lib('header')->add_jsfile('vendor_bundled/vendor/vimeo/froogaloop/javascript/froogaloop.min.js', true);
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-vimeo.js');
    }

    if (isset($params['url'])) {
        $params['vimeo'] = $params['url'];
        $params['player_id'] = "pid_" . uniqid();
        $params['vimeo_fileId'] = 0;
        unset($params['url']);
        if ($params['useFroogaloopApi']) {
            $params['vimeo'] .= "?api=1&player_id=" . $params['player_id'];
        }

        return vimeo_iframe($data, $params);
    } elseif (isset($params['fileId'])) {
        $fileIds = preg_split('/\D+/', $params['fileId'], -1, PREG_SPLIT_NO_EMPTY);
        unset($params['fileId']);

        $out = '';
        foreach ($fileIds as $fileId) {
            $attributelib = TikiLib::lib('attribute');
            $attributes = $attributelib->get_attributes('file', $fileId);
            if (! empty($attributes['tiki.content.url'])) {
                $params['vimeo'] = $attributes['tiki.content.url'];
                $params['player_id'] = "pid_" . uniqid();
                $params['vimeo_fileId'] = $fileId;
                if ($params['useFroogaloopApi']) {
                    $params['vimeo'] .= "?api=1&player_id=" . $params['player_id'];
                }
                $out .= vimeo_iframe($data, $params);
            } else {
                Feedback::error(tr('Vimeo video not found for file #%0', $fileId));
            }
        }

        return $out;
    }
    global $page;
    $smarty = TikiLib::lib('smarty');
    if ($prefs['vimeo_upload'] !== 'y') {
        $smarty->loadPlugin('smarty_block_remarksbox');
        $repeat = false;

        return smarty_block_remarksbox(
            ['type' => 'error', 'title' => tra('Feature required')],
            tra('Feature "vimeo_upload" is required to be able to add videos here.'),
            $smarty,
            $repeat
        );
    }

    // old perms access to get "special" gallery perms to handle user gals etc
    $perms = TikiLib::lib('tiki')->get_perm_object(
        ! empty($params['galleryId']) ? $params['galleryId'] : $prefs['vimeo_default_gallery'],
        'file gallery',
        TikiLib::lib('filegal')->get_file_gallery_info($prefs['vimeo_default_gallery']),
        false
    );
    if ($perms['tiki_p_upload_files'] !== 'y') {
        return '';		//$permMessage = tra('You do not have permsission to add files here.');
    } elseif (! empty($params['fromFieldId'])) {
        $fieldInfo = TikiLib::lib('trk')->get_tracker_field($params['fromFieldId']);
        if (empty($params['fromItemId'])) {
            $item = Tracker_Item::newItem($fieldInfo['trackerId']);
        } else {
            $item = Tracker_Item::fromId($params['fromItemId']);
        }
        if (! $item->canModify()) {
            return '';		//$permMessage = tra('You do not have permsission modify this tracker item.');
        }
    } elseif ($page) {
        $pagePerms = Perms::get([ 'type' => 'wiki page', 'object' => $page ])->edit;
        if (! $pagePerms) {
            return '';		//$permMessage = tra('You do not have permsission modify this page.');
        }
    }

    // set up for an upload
    $smarty->loadPlugin('smarty_function_button');
    $smarty->loadPlugin('smarty_function_service');
    $html = smarty_function_button(
        [
                '_keepall' => 'y',
                '_class' => 'vimeo dialog',
                'href' => smarty_function_service(
                    [
                        'controller' => 'vimeo',
                        'action' => 'upload',
                    ],
                    $smarty->getEmptyInternalTemplate()
                ),
                '_text' => tra('Upload Video'),
            ],
        $smarty->getEmptyInternalTemplate()
    );

    if (! empty($page) && empty($params['fromFieldId'])) {
        // Wikiplugin used within Wiki page
        $access = TikiLib::lib('access');
        $access->checkAuthenticity();
        $ticket = $access->getTicket();
        $js = '
				$("body").on("vimeo_uploaded", ".vimeo_upload", function(event, data) {
					var params = {
						daconfirm: "y",
						ticket: "' . $ticket . '",
						page: ' . json_encode($page) . ',
						content: "",
						index: ' . $instance . ',
						type: "vimeo",
						params: {
							url: data.url
						}
					};
					$.post("tiki-wikiplugin_edit.php", params, function() {
						$.get($.service("wiki", "get_page", {page:' . json_encode($page) . '}), function (data) {
							if (data) {
								$("#page-data").html(data);
							}
						});
					});
					$.closeModal();
				});
			';
    } else {
        // Tracker edit, whether in tracker UI or tracker plugin within wiki page
        $js = '
				$("body").on("vimeo_uploaded", ".vimeo_upload", function(event, data) {
					handleVimeoFile($(".vimeo.dialog"), data);
					$.closeModal();
				});
			';
    }
    $js .= '
			$(".vimeo.dialog").click(function () {
				vimeoUploadModal = $.openModal({
					remote: $.service(
						"vimeo",
						"upload",
						{
							title: tr("Upload Video")' .
                (! empty($params['galleryId']) ? ',galleryId:' . $params['galleryId'] : '') .
                (! empty($params['fromFieldId']) ? ',fieldId:' . $params['fromFieldId'] : '') .
                (! empty($params['fromItemId']) ? ',itemId:' . $params['fromItemId'] : '') . '
						}
					)
				});
				return false;
			});
		';

    TikiLib::lib('header')->add_jq_onready($js);

    return $html;
}
