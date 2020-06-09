<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_slideshow_info()
{
	return [
		'name' => tra('Slideshow'),
		'documentation' => 'Slideshow',
		'description' => tra('Create a slideshow from the content of a wiki page'),
		'prefs' => [ 'wikiplugin_slideshow', 'feature_slideshow' ],
		'iconname' => 'tv',
		'introduced' => 7,
		'tags' => [ 'basic' ],
		'params' => [
			'theme' => [
				'required' => false,
				'name' => tra('Theme'),
				'description' => tra('The theme you want to use for the slideshow, default will be what you choose from
					the admin panel under Look and Feel for jQuery UI'),
				'filter' => 'text',
				'default' => 'black',
				'since' => '19.0',
				'options' => [
					['text' => 'Black: Black background, white text, blue links', 'value' => 'black'],
					['text' => 'Blood: Dark gray background, dark text, maroon links', 'value' => 'blood'],
					['text' => 'Beige: Beige background, dark text, brown links', 'value' => 'beige'],
					['text' => 'League: Gray background, white text, blue links', 'value' => 'league'],
					['text' => 'Moon: Navy blue background, blue links', 'value' => 'moon'],
					['text' => 'Night: Black background, thick white text, orange links', 'value' => 'night'],
					['text' => 'Serif: Cappuccino background, gray text, brown links', 'value' => 'serif'],
					['text' => 'Simple: White background, black text, blue links', 'value' => 'simple'],
					['text' => 'Sky: Blue background, thin dark text, blue links', 'value' => 'sky'],
					['text' => 'Solarized: Cream-colored background, dark green text, blue links', 'value' => 'solarized'],
				],
			],
			'parallaxBackgroundImage' => [
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
                'default' => null,
                'since' => '19.0'
            ],
            'parallaxBackgroundVertical' => [
                'required' => false,
                'name' => tra('Parallax Background Vertical'),
                'description' => '',
                'default' => null,
                'since' => '19.0'
            ],
            'transition' => [
                'required' => false,
                'name' => tra('Transition'),
                'description' => tra(' Transition style'),
                'filter' => 'word',
                'default' => 'zoom',
                'since' => '19.0',
                'options' => [
					['text' => 'Zoom', 'value' => 'zoom'],
                    ['text' => 'Fade', 'value' => 'fade'],
                    ['text' => 'Slide', 'value' => 'slide'],
                    ['text' => 'Convex', 'value' => 'convex'],
                    ['text' => 'Concave', 'value' => 'concave'],
					['text' => 'off', 'value' => ''],


				],
            ],
            'transitionSpeed' => [
                'required' => false,
                'name' => tra('Transition Speed'),
                'description' => tra('Transition Speed'),
                'filter' => 'word',
                'default' => '',
                'since' => '19.0',
                'options' => [
                    ['text' => 'Default', 'value' => 'default'],
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
            'controls' => [
                'required' => false,
                'name' => tra('Controls'),
                'description' => tra('Display presentation control arrows'),
                'filter' => 'word',
                'default' => 'y',
                'since' => '19.0',
                'options' => [
                    ['text' => 'True', 'value' => 'y'],
                    ['text' => 'False', 'value' => 'n'],
                ],
            ],
            'controlsLayout' => [
                'required' => false,
                'name' => tra('Controls Layout'),
                'description' => tra('Determines where controls appear, "edges" or "bottom-right"'),
                'filter' => 'word',
                'default' => 'bottom-right',
                'since' => '19.0',
                'options' => [
                    ['text' => 'Bottom-Right', 'value' => 'bottom-right'],
                    ['text' => 'Edges', 'value' => 'edges'],
                ],
            ],
            'controlsBackArrows' => [
                'required' => false,
                'name' => tra('Controls Back Arrows'),
                'description' => tra('Visibility rule for backwards navigation arrows; "faded", "hidden" or "visible"'),
                'filter' => 'word',
                'default' => 'faded',
                'since' => '19.0',
                'options' => [
                    ['text' => 'Faded', 'value' => 'faded'],
                    ['text' => 'Hidden', 'value' => 'hidden'],
                    ['text' => 'Visible', 'value' => 'visible'],
                ],
            ],
            'progress' => [
                'required' => false,
                'name' => tra('Progress'),
                'description' => tra('Display a presentation progress bar'),
                'filter' => 'word',
                'default' => 'y',
                'since' => '19.0',
                'options' => [
                    ['text' => 'True', 'value' => 'y'],
                    ['text' => 'False', 'value' => 'n'],
                ],
            ],
            'slideNumber' => [
                'required' => false,
                'name' => tra('Slide Number'),
                'description' => tra('Display the page number of the current slide'),
                'filter' => 'word',
                'default' => 'n',
                'since' => '19.0',
                'options' => [
                    ['text' => 'True', 'value' => 'y'],
                    ['text' => 'False', 'value' => 'n'],
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
                'default' => 'grow',
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
                'default' => 'blue',
                'since' => '19.0',
                'options' => [
                    ['text' => 'None', 'value' => 'none'],
                    ['text' => 'Red', 'value' => 'red'],
                    ['text' => 'Green', 'value' => 'green'],
                    ['text' => 'Blue', 'value' => 'blue']
                ],
            ],
            'autoSlide' => [
                'required' => false,
                'name' => tra('Auto Slide'),
                'description' => tra('Number of milliseconds between automatically proceeding to the next slide, disabled when set to 0, this value can be overwritten by using a data-autoslide attribute on your slides'),
                'filter' => 'digits',
                'default' => '0',
                'since' => '19.0',
            ],
            'autoSlideStoppable' => [
                'required' => false,
                'name' => tra('Auto Slide Stoppable'),
                'description' => tra('Stop auto-sliding after user input'),
                'filter' => 'word',
                'default' => 'y',
                'since' => '19.0',
                'options' => [
                    ['text' => 'On', 'value' => 'y'],
                    ['text' => 'Off', 'value' => 'n'],
                ],
            ],
			'alignImage' => [
				'required' => false,
				'name' => tra('Auto-align Images'),
				'description' => tra('Automatically move images to left hand side of slide text, will only align images greater than 200px in width'),
				'filter' => 'word',
				'default' => 'n',
				'since' => '19.0',
				'options' => [
					['text' => 'Off', 'value' => 'n'],
					['text' => 'On', 'value' => 'y']
				],
			],

		],
	];
}

function wikiplugin_slideshow($data, $params)
{
	if(strstr($_SERVER['PHP_SELF'],'tiki-slideshow.php')=='') {
		if (strstr($_SERVER['PHP_SELF'], 'tiki-index.php')) {
			return '<a class="btn btn-primary hidden-print" data-role="button" data-inline="true" title="Start Slideshow" href="./tiki-slideshow.php?page='
				. $_REQUEST['page'] . '">'.tr('Start Slideshow Presentation').'</a>';
		}
		return;
	}

	if(! empty($_REQUEST['pdf'])){
		global $pdfStyles;
		if(isset($params['parallaxBackgroundImage'])) {
			$pdfStyles='<style>@page,body,div.reveal{background-image-resize:0 !important;
			background-image:url("'.$params['parallaxBackgroundImage'].'") !important;}</style>';
		}
	}
    $defaults = [];
    $plugininfo = wikiplugin_slideshow_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
        //separating digits filter parameters
        if(isset($param['filter']) && $param['filter'] === "digits") {
            $slideshowDigitsParams[]=$key;
        }
    }
    $params = array_merge($defaults, $params);

    $revealParams=array('parallaxBackgroundImage','parallaxBackgroundSize','parallaxBackgroundHorizontal','parallaxBackgroundVertical','slideSeconds','transition','transitionSpeed','backgroundTransition','controls','controlsLayout','controlsBackArrows','progress','slideNumber','autoSlide','autoSlideStoppable');
    $revealSettings = '';
    foreach($revealParams as $revealParam) {
		if (isset($params[$revealParam])) {
			$revealSettings .= $revealParam . ":";
			if (! in_array($revealParam, $slideshowDigitsParams)) {
				$revealSettings .= "'" . $params[$revealParam] . "',";
			} else {
				$revealSettings .= $params[$revealParam] . ",";
			}
		}
    }
    $revealSettings =str_replace(array("'y'","'n'"),array("true","false"),$revealSettings);
    $revealSettings.='viewDistance:3,display:"block",hash:true';
	$headerlib = TikiLib::lib('header');
	if(!isset($_REQUEST['theme'])) {
		$headerlib->add_cssfile('vendor_bundled/vendor/npm-asset/reveal.js/css/theme/'.$params['theme'].'.css',1);
		$headerlib->add_js(
			'$( "#showtheme" ).val( "'.$params['theme'].'" );'
		);
	}
	if($params['alignImage']=='y'){
		$headerlib->add_js('(function(){

			var images = [];
			$("section table tr").each(function(){
				var tr=this;
				var imgsrc="";
				var minwidth="";
				
				if($(this).text().length>20 && window.innerHeight < window.innerWidth){ //checking for text content and screen orientation
				
					$(this).find("img").each(function(){
					
						if(this.width>200 ){
							$(tr).find("td").attr("style","vertical-align:top");
							imgsrc=this.src;
							minwidth=(this.width)/2; 
							this.remove();
							if(minwidth>450){ //to avoid distortion of text, in case of large image
								minwidth=450;
							}
						}
					});
					if(imgsrc!="") {
						var tableData = $("<td style=\"width:50%\"><img src="+imgsrc+" style=\"min-width:"+minwidth+"px;max-height:85%\"></td>");
						$(this).append(tableData);
					}
				}	
			})
		})()');
	}
	$headerlib->add_js(
	"Reveal.configure({".$revealSettings."});
	var fragments='".$params['fragments']."';
	var fragmentClass='".$params['fragmentClass']."';
	var fragmentHighlightColor='highlight-".$params['fragmentHighlightColor']."';"
	);
}
