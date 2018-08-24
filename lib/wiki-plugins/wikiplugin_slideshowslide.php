<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_slideshowslide_info()
{
	return [
		'name' => tra('SlideshowSlide'),
		'documentation' => 'SlideshowSlide',
		'description' => tra('Advance plugin to add slide in slideshow with different background colors, background video, transition and other settings.'),
		'prefs' => [  'wikiplugin_slideshow', 'feature_slideshow' ],
		'body' => tr('Enter content of slide'),
		'iconname' => 'tv',
		'introduced' => 19,
		'tags' => [ 'advance' ],
		'params' => [
			'bgColor' => [
				'required' => false,
				'name' => tra('Slide Background color'),
				'description' => tr('Set background color for slide.'),
				'default' => '',
				'since' => '19.0',
			],
			'backgroundUrl' => [
				'required' => false,
				'name' => tra('Parallax Background Image'),
				'description' => tr(
					'URL of the background image to use in your slideshow, overrides %0',
					'<code>backgroundcolor</code>'
				),
				'filter' => 'url',
				'accepted' => tra('Valid URL'),
				'default' => '',
				'since' => '19.0',
			],
			'parallaxBackgroundSize' => [
				'required' => false,
				'name' => tra('Parallax Background Size'),
				'description' => tra('syntax, e.g. "2100px 900px" - currently only pixels are supported (don\'t use % or auto)'),
				'default' => '',
				'since' => '19.0'
			],

			'parallaxBackgroundHorizontal' => [
				'required' => false,
				'name' => tra('Parallax Background Horizontal'),
				'description' => tra('Number of pixels to move the parallax background per slide, Calculated automatically unless specified. Set to 0 to disable movement along an axis'),
				'default' => '',
				'since' => '19.0'
			],
			'parallaxBackgroundVertical' => [
				'required' => false,
				'name' => tra('Parallax Background Vertical'),
				'description' => '',
				'default' => '',
				'since' => '19.0'
			],

			'backgroundVideoUrl' => [
				'required' => false,
				'name' => tra('Background Video URL'),
				'description' => tr('Automatically plays a full size video behind the slide'),
				'default' => '',
				'since' => '19.0',
			],
			'videoMuted' => [
				'required' => false,
				'name' => tra('Mute Background Video'),
				'description' => tra('Flags if the audio should be muted for background video'),
				'filter' => 'word',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'transitionIn' => [
				'required' => false,
				'name' => tra('Transition In'),
				'description' => tra('Select entry transition effect for slide'),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => 'None', 'value' => 'none'],
					['text' => 'Fade', 'value' => 'fade'],
					['text' => 'Slide', 'value' => 'slide'],
					['text' => 'Convex', 'value' => 'convex'],
					['text' => 'Concave', 'value' => 'concave'],
					['text' => 'Zoom', 'value' => 'zoom'],
				],
			],
			'transitionOut' => [
				'required' => false,
				'name' => tra('Transition Out'),
				'description' => tra('Select exit transition effect for slide'),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => 'None', 'value' => 'none'],
					['text' => 'Fade', 'value' => 'fade'],
					['text' => 'Slide', 'value' => 'slide'],
					['text' => 'Convex', 'value' => 'convex'],
					['text' => 'Concave', 'value' => 'concave'],
					['text' => 'Zoom', 'value' => 'zoom'],
				],
			],
			'transitionSpeed' => [
				'required' => false,
				'name' => tra('Transition Speed'),
				'description' => tra('Transition Speed'),
				'filter' => 'word',
				'default' =>'',
				'since' => '19.0',
				'options' => [
					['text' => 'Default', 'value' => ''],
					['text' => 'Fast', 'value' => 'fast'],
					['text' => 'Slow', 'value' => 'slow'],
				],
			],
			'backgroundTransition' => [
				'required' => false,
				'name' => tra('Background Transition'),
				'description' => tra('Transition style for full page slide backgrounds'),
				'filter' => 'word',
				'default' => 'fade',
				'since' => '19.0',
				'options' => [
					['text' => 'None', 'value' => 'none'],
					['text' => 'Fade', 'value' => 'fade'],
					['text' => 'Slide', 'value' => 'slide'],
					['text' => 'Convex', 'value' => 'convex'],
					['text' => 'Concave', 'value' => 'concave'],
					['text' => 'Zoom', 'value' => 'zoom'],
				],
			],
			'fragments' => [
				'required' => false,
				'name' => tra('Fragments'),
				'description' => tra('Turns fragments on and off globally'),
				'filter' => 'word',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'On', 'value' => 'y'],
					['text' => 'Off', 'value' => 'n'],
				],
			],
			'fragmentClass' => [
				'required' => false,
				'name' => tra('Fragment Effects'),
				'description' => tra(''),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => 'Grow', 'value' => 'grow'],
					['text' => 'Shrink', 'value' => 'shrink'],
					['text' => 'Fade-OUT', 'value' => 'fade-out'],
					['text' => 'Fade-UP', 'value' => 'fade-up'],
					['text' => 'Current-Visible', 'value' => 'current-visible'],
				],
			],
			'fragmentHighlightColor' => [
				'required' => false,
				'name' => tra('Fragment Highlight Color'),
				'description' => tra(''),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => 'None', 'value' => 'none'],
					['text' => 'highlight-red', 'value' => 'highlight-red'],
					['text' => 'highlight-green', 'value' => 'highlight-green'],
					['text' => 'highlight-blue', 'value' => 'highlight-blue']
				],
			],
		],
	];
}

function wikiplugin_slideshowslide($data, $params)
{
	
	$defaults = [];
	$plugininfo = wikiplugin_slideshowslide_info();
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults["$key"] = $param['default'];
		//separating digits filter parameters
		if($param['filter']=="digits") {
			$slideshowslideDigitsParams[]=$key;
		}
	}
	$params = array_merge($defaults, $params);

	$slideShowSlideParams=array("data-background-color"=>'bgColor',"data-background-image"=>'backgroundUrl',"data-background-size"=>'parallaxBackgroundSize',"data-background-horizontal"=>'parallaxBackgroundHorizontal',"data-background-vertical"=>'parallaxBackgroundVertical',"data-background-video"=>'backgroundVideoUrl',"data-background-transitionspeed"=>'transitionSpeed',"data-background-transition"=>'backgroundTransition',"data-fragments"=>'fragments',"data-fragment-class"=>'fragmentClass',"data-fragment-highlight-color"=>'fragmentHighlightColor');
	$slideSettings = '';
	foreach($slideShowSlideParams as $key=>$param) {
		if($params[$param]){ 
			$slideSettings.=$key;
			if(!in_array($param,$slideshowslideDigitsParams)) {
				$slideSettings.="='".$params[$param]."' ";
			}
			else {
				$slideSettings.="='".$params[$param]."' ";
			}
		}
	}
	$slideSettings =str_replace(array("'y'","'n'"),array("'true'","'false'"),$slideSettings);


	$transitionIn = (isset($params['transitionIn']) ? $params['transitionIn']."-in" : '');
	$transitionOut = (isset($params['transitionOut']) ? $params['transitionOut']."-out" : '');
	if($transitionIn  || $transitionOut) {
		$slideSettings.="data-transition=\"".$transitionIn." ".$transitionOut."\"";
	}
	if($params['videoMuted']=='y') {
		$slideSettings.=" data-background-video-muted";
	}
	return "<sslide data-plugin-slide ".$slideSettings.">".html_entity_decode(TikiLib::lib('parser')->parse_data(trim($data), ['is_html' => true, 'parse_wiki' => true])).'</sslide>';
}
