<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\VendorHelper;

function wikiplugin_mediaplayer_info()
{
    return [
        'name' => tra('Media Player'),
        'documentation' => 'PluginMediaplayer',
        'description' => tra('Add a media player to a page'),
        'extraparams' => true,
        'prefs' => [ 'wikiplugin_mediaplayer' ],
        'iconname' => 'play',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'fullscreen' => [
                'required' => false,
                'name' => tra('Allow full-screen'),
                'description' => tra('Allow full-screen mode.'),
                'since' => '5.0',
                'filter' => 'alpha',
                'options' => [
                    [
                        'text' => '',
                        'value' => '',
                    ],
                    [
                        'text' => tra('Yes'),
                        'value' => 'true',
                    ],
                    [
                        'text' => tra('No'),
                        'value' => 'false'
                    ]
                ]
            ],
            'mp3' => [
                'required' => false,
                'name' => tra('MP3 URL'),
                'description' => tr("Complete URL to the MP3 to include. Examples: %0http://example.org/example.mp3%1
					for an external file, or for a video file in the site's File Gallery:
					%0tiki-download_file.php?fileId=2%1 (No need for %0http://%1 in this case)", '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'url',
            ],
            'flv' => [
                'required' => false,
                'name' => tra('FLV URL'),
                'description' => tr("Complete URL to the FLV to include. Examples: %0http://example.org/example.flv%1
					for an external file, or for a video file in the site's File Gallery:
					%0tiki-download_file.php?fileId=2%1 (the missing %0//%1 is intentional as this is a valid internal
					link)", '<code>', '</code>'),
                'since' => '3.0',
                'filter' => 'url'
            ],

            // The following param needs an URL with an extension (ex.: example.wmv works but not tiki-download_file.php?fileId=4&display)
            'src' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra("Complete URL to the media to include, which has the appropriate extension.
					If your URL doesn't have an extension, use the File type parameter below."),
                'since' => '6.0',
                'accepted' => 'asx, asf, avi, mov, mpg, mpeg, mp4, qt, ra, smil, swf, wmv, 3g2, 3gp, aif, aac, au, gsm,
					mid, midi, mov, m4a, snd, ra, ram, rm, wav, wma, bmp, html, pdf, psd, qif, qtif, qti, tif, tiff,
					xaml',
                'filter' => 'url',
                'default' => '',
            ],

            // The type parameter is verified for Quicktime, Windows Media Player, Real Player, iframe (PDF), but doesn't work for flv param of the plugin
            'type' => [
                'required' => false,
                'name' => tra('File type'),
                'description' => tr('File type for source URL, e.g. %0mp4%1, %0pdf%1 or %0odp%1. Specify one of the supported file types when
					the URL of the file is missing the file extension. This is the case for File Gallery files which
					have a URL such as %0tiki-download_file.php?fileId=4&display%1 or %0display4%1 if you have Clean URLs
					enabled.', '<code>', '</code>'),
                'since' => '10.0',
                'filter' => 'url',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Player width in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                    'description' => tra('Player height in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'style' => [
                'required' => false,
                'name' => tra('Style'),
                'description' => tra('Set the style'),
                'since' => '3.0',
                'filter' => 'alpha',
                'options' => [
                    [
                        'text' => '', 'value' => ''
                    ],
                    [
                        'text' => 'Mini', 'value' => 'mini'
                    ],
                    [
                        'text' => 'Normal', 'value' => 'normal'
                    ],
                    [
                        'text' => 'Maxi', 'value' => 'maxi'
                    ],
                    [
                        'text' => 'Multi', 'value' => 'multi'
                    ],
                    [
                        'text' => 'Native Video (HTML5)', 'value' => 'native'
                    ]
                ]
            ],
            'mediatype' => [
                'required' => false,
                'name' => tra('Media Type'),
                'description' => tra('Media type for HTML5'),
                'since' => '13.2',
                'filter' => 'word',
                'options' => [
                    [
                        'text' => '', 'value' => ''
                    ],
                    [
                        'text' => tra('Audio'), 'value' => 'audio'
                    ],
                    [
                        'text' => tra('Video'), 'value' => 'video'
                    ]
                ]
            ],
            'wmode' => [
                'required' => false,
                'name' => tra('Flash Window Mode'),
                'description' => tra('Sets the Window Mode property of the Flash movie. Transparent lets what\'s behind
					the movie show through and allows the movie to be covered Opaque hides what\'s behind the movie and
					Window plays the movie in its own window. Default value: ') . '<code>transparent</code>',
                'since' => '5.0',
                'filter' => 'word',
                'options' => [
                    [
                        'text' => '',
                        'value' => '',
                    ],
                    [
                        'text' => tra('Transparent'),
                        'value' => 'transparent',
                    ],
                    [
                        'text' => tra('Opaque'),
                        'value' => 'opaque',
                    ],
                    [
                        'text' => tra('Window'),
                        'value' => 'window',
                    ]
                ]
            ],
        ],
    ];
}
function wikiplugin_mediaplayer($data, $params)
{
    global $prefs;
    $access = TikiLib::lib('access');
    static $iMEDIAPLAYER = 0;
    $id = 'mediaplayer' . ++$iMEDIAPLAYER;
    $params['type'] = strtolower($params['type']);

    if (empty($params['mp3']) && empty($params['flv']) && empty($params['src'])) {
        return '';
    }
    if (! empty($params['src']) && isset($params['style']) && $params['style'] != 'native') { // FIXME: Too broad - this does not use jQuery Media in all these cases.
        $access->check_feature('feature_jquery_media');
    }
    //checking if pdf generation request
    if (in_array($params['type'], ['pdf']) && strstr($_GET['display'], 'pdf') != '') {
        return "<pdfpage>.<pdfinclude src='" . TikiLib::lib('access')->absoluteUrl($params['src']) . "' /></pdfpage>";
    }
    $defaults_mp3 = [
        'width' => 200,
        'height' => 20,
        'player' => 'player_mp3.swf',
        'where' => 'vendor_bundled/vendor/player/mp3/template_default/',
    ];
    $defaults_flv = [
        'width' => 320,
        'height' => 240,
        'player' => 'player_flv.swf',
        'where' => 'vendor_bundled/vendor/player/flv/template_default/'
    ];
    $defaults_html5 = [
        'width' => '',
        'height' => '',
    ];
    $defaults = [
        'width' => 320,
        'height' => 240,
    ];
    if (preg_match('/webm/', $params['type']) > 0 && $params['type'] != 'video/webm') {
        $params['type'] = 'video/webm';
    }
    if ($params['type'] == 'video/webm') {
        $params['style'] = 'native';
    }

    if (empty($params['type'])) {
        preg_match('/(?:dl|display|fileId=)(\d*)/', $params['src'], $matches);
        if (! empty($matches[1])) { // fileId 0 is also invalid
            $fileId = $matches[1];
            $filegallib = TikiLib::lib('filegal');
            $file = $filegallib->get_file_info($fileId);
            if (! empty($file['filetype']) && $file['fileId'] == $fileId) {
                $fileExtension = pathinfo($file['filename'], PATHINFO_EXTENSION);
                $params['type'] = $fileExtension;
                if (! in_array($fileExtension, ['pdf', 'odt', 'ods', 'odp'])) {
                    $params['style'] = ! empty($params['style']) ? $params['style'] : 'native';
                    $params['type'] = $file['filetype'];
                }
                if ($fileExtension == 'mp3') {
                    $params['mp3'] = $params['src'];
                }
                if ($fileExtension == 'flv') {
                    $params['flv'] = $params['src'];
                    $params['type'] = $fileExtension;
                    unset($params['style']);
                    unset($params['src']);
                }
            }
        }
    }

    if (! empty($params['flv'])) {
        $params = array_merge($defaults_flv, $params);
    } elseif (! empty($params['mp3'])) {
        $params = array_merge($defaults_mp3, $params);
    } elseif (! empty($params['style']) && $params['style'] == 'native') {
        $params = array_merge($defaults_html5, $params);
    } else {
        $params = array_merge($defaults, $params);
    }
    if (! empty($params['src']) && (empty($params['style']) || $params['style'] != 'native')) {
        $headerlib = TikiLib::lib('header');
        $js = "\n var media_$id = $('#$id').media( {";
        foreach ($params as $param => $value) {
            if ($param == 'src') {
                continue;
            }

            if (is_numeric($value) == false &&
                strtolower($value) != 'true' &&
                strtolower($value) != 'false') {
                $value = "\"" . $value . "\"";
            }

            $js .= "$param: $value,";
        }
        // Force scaling (keeping the aspect ratio) of the QuickTime player
        //	Tried with .mp4. Not sure how this will work with other formats, not using QuickTime.
        // See: http://jquery.malsup.com/media/#players for default players for different formats. arildb
        $js .= " params: { 
				scale: 'aspect'
				} 
			} );";

        if (in_array($params['type'], ['pdf', 'odt', 'ods', 'odp'])) {
            if ($prefs['fgal_pdfjs_feature'] === 'n') {
                return "<p>" . tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.') . "</p>";
            }
            if ($prefs['fgal_pdfjs_feature'] === 'y') {
                $smarty = TikiLib::lib('smarty');

                $url = TikiLib::lib('access')->absoluteUrl($params['src']);
                $smarty->assign('url', $url);
                $smarty->assign('mediaplayerId', $iMEDIAPLAYER);
                $oldPdfJsFile = VendorHelper::getAvailableVendorPath('pdfjs', '/npm-asset/pdfjs-dist/build/pdf.js');
                $oldPdfJsFileAvailable = file_exists($oldPdfJsFile);
                $smarty->assign('oldPdfJsFileAvailable', $oldPdfJsFileAvailable);

                $pdfJsfile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/build/pdf.js');
                $pdfJsAvailable = file_exists($pdfJsfile);
                $smarty->assign('pdfJsAvailable', $pdfJsAvailable);

                $headerlib = TikiLib::lib('header');
                $headerlib->add_css("
					.iframe-container {
						overflow: hidden;
						padding-top: 56.25%;
						position: relative;
						height: 900px;
					}
					
					.iframe-container iframe {
						border: 0;
						height: 100%;
						left: 0;
						position: absolute;
						top: 0;
						width: 100%;
					}
					
					@media (max-width: 767px) {
						.iframe-container {
							height: 500px;
						} 
					}
					
					@media (min-width: 768px) AND (max-width: 991px) {
						.iframe-container {
							height: 600px;
						}
					}
					
					@media (min-width: 992px) AND (max-width: 1209px){
						.iframe-container {
							height: 700px;
						}
					}
				");

                $fileId = '';
                $sourceLink = '';

                $parts = parse_url($params['src'], PHP_URL_QUERY);
                if ($parts) {
                    parse_str($parts, $query);
                    if (! empty($query['fileId'])) {
                        $fileId = $query['fileId'];
                    }
                } else {
                    preg_match('/(display|dl)(.*)$/', $params['src'], $matches);
                    if (! empty($matches[2])) {
                        $fileId = $matches[2];
                    }
                }

                if (! empty($fileId)) {
                    $smarty->loadPlugin('smarty_modifier_sefurl');
                    $sourceLink = smarty_modifier_sefurl($fileId, 'display');
                } else {
                    $sourceLink = TikiLib::lib('access')->absoluteUrl($params['src']);
                }

                if (! empty($sourceLink)) {
                    $htmlViewFile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/web/viewer.html') . '?file=';
                    $sourceLink = $htmlViewFile . urlencode(TikiLib::lib('access')->absoluteUrl($sourceLink));
                }

                $smarty->assign('source_link', $sourceLink);

                return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_mediaplayer_pdfjs.tpl') . '~/np~';
            } elseif ($params['type'] === 'pdf') {
                $js = '
var found = false;
$.each(navigator.plugins, function(i, plugins) { // navigator.plugins is unspecified according to https://developer.mozilla.org/fr/docs/Web/API/NavigatorPlugins/plugins . Something other in NavigatorPlugins may be standard. 
	$.each(plugins, function(i, plugin) {
		if (plugin.type === "application/pdf") {
			found = true;
			return;
		}
	});
});
if (!found) {
    // IE doesnt bother using the plugins array (sometimes?), plus ActiveXObject is hidden now so just try and catch... :(
    try {
        var oAcro7 = new ActiveXObject("AcroPDF.PDF.1");
        if (oAcro7) {
            found = true;
        }
    } catch (e) {
    }
}
if (found) {
	' . $js . '
} else {
	// no pdf plugin
	$("#' . $id . '").text(tr("Download file:") + " " + "' . $params['src'] . '");
}';
            }
        }

        $headerlib->add_jq_onready($js);

        return "<a href=\"" . $params['src'] . "\" id=\"$id\"></a>";
    }

    // Check the style of the player
    $styles = ['normal', 'mini', 'maxi', 'multi', 'native'];
    if (empty($params['style']) || $params['style'] == 'normal' || ! in_array($params['style'], $styles)) {
        $player = $params['player'];
    } elseif ($params['style'] == 'native') {
        $player = '';
    } else {
        $params['where'] = str_replace('_default', '_' . $params['style'], $params['where']);
        $player = str_replace('.swf', '_' . $params['style'] . '.swf', $params['player']);
    }

    // check if native native HTML5 video object is requested

    if ($params['style'] == 'native') {
        if (! empty($params['mediatype']) && $params['mediatype'] == 'audio') {
            $mediatype = 'audio';
        } else {
            $mediatype = 'video';
        }
        $code = '<' . $mediatype;
        if (! empty($params['height'])) {
            $code .= ' height="' . $params['height'] . '"';
        }
        if (! empty($params['width'])) {
            $code .= ' width="' . $params['width'] . '"';
        }
        $code .= ' style="max-width: 100%" controls>';
        $code .= '	<source src="' . $params['src'] . '" type=\'' . $params['type'] . '\'>'; // type can be e.g. 'video/webm; codecs="vp8, vorbis"'
        $code .= '</' . $mediatype . '>';
    } else {
        // else use flash

        $code = '<object type="application/x-shockwave-flash" data="' . $params['where'] . $player . '" width="' . $params['width'] . '" height="' . $params['height'] . '">';
        $code .= '<param name="movie" value="' . $params['where'] . $player . '" />';
        if (! empty($params['fullscreen'])) {
            $code .= '<param name="allowFullscreen" value="' . $params['fullscreen'] . '" />';
        }
        if (empty($params['wmode'])) {
            $wmode = 'transparent';
        } else {
            $wmode = $params['wmode'];
        }
        $code .= '<param name="wmode" value="' . $wmode . '" />';
        $code .= '<param name="FlashVars" value="';
        if (empty($params['flv']) && ! empty($params['mp3'])) {
            $code .= 'mp3=' . $params['mp3'];
        }

        // Disabled due to MSIE issue still experienced with version 9: http://flv-player.net/help/#faq2
        //unset($params['width']); unset($params['height']);
        unset($params['where']);
        unset($params['player']);
        unset($params['mp3']);
        unset($params['style']);
        unset($params['fullscreen']);
        unset($params['wmode']);

        foreach ($params as $key => $value) {
            $code .= '&amp;' . $key . '=' . $value;
        }
        $code .= '" />';
        $code .= '</object>';
    } // end of else use flash

    return "~np~$code~/np~";
}
