<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: $
function wikiplugin_slideradvance_info()
{
    return [
        'name' => tra('Slider Advance'),
        'documentation' => 'PluginSliderAdvance',
        'description' => tra('Embed advance slider in content, support file galleries, files id and custom content'),
        'prefs' => 'wikiplugin_slideradvance',
        'body' => tra('Enter custom slides separated by "/////", support HTML and text'),
        'iconname' => 'tv',
        'introduced' => 19,
        'tags' => 'basic',
        'params' => [
            'fgalId' => [
				'required' => false,
				'name' => tra('File Gallery ID'),
				'description' => tra('Enter file galleries id for slider'),
				'since' => '19',
				'default' => null,
				'separator' => ':',
				'profile_reference' => 'file_gallery',
			],
			'fileIds' => [
				'required' => false,
				'name' => tra('File IDs'),
				'description' => tra('List of IDs of images from the File Galleries separated by commas.'),
				'filter' => 'striptags',
				'default' => '',
			],
	        'effect' => [
		        'required' => false,
		        'name' => tra('Transition Effect'),
		        'description' => tra('Tranisition effect. Could be "slide", "fade", "cube", "coverflow" or "flip"'),
		        'filter' => 'word',
		        'default' => null,
		        'since' => '19.0',
		        'options' => [
			        ['text' => 'Slide', 'value' => 'slide'],
			        ['text' => 'Fade', 'value' => 'fade'],
			        ['text' => 'Cube', 'value' => 'cube'],
			        ['text' => 'Coverflow', 'value' => 'coverflow'],
			        ['text' => 'Flip', 'value' => 'flip'],
		        ],
	        ],
            'sliderposition' => [
                'required' => false,
                'name' => tra('Slider type'),
                'description' => tra('Placement of slider, above topbar, below topbar, above menus and content or inside content'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
					['text' => tra(''), 'value' => ''],				
                    ['text' => tra('Inside Content, default'), 'value' => ''],
                    ['text' => tra('Above top bar'), 'value' => 'abovetopbar'],
					['text' => tra('Above Content/Under top bar'), 'value' => 'undertopbar']
                ],
            ],
            'pagination' => [
                'required' => false,
                'name' => tra('Pagination'),
                'description' => tra('Slider pagniation, default bullets'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
					['text' => '', 'value' => ''],				
                    ['text' => 'Off', 'value' => 'off'],
                    ['text' => 'bullets', 'value' => 'bullets'],
                    ['text' => 'fraction', 'value' => 'fraction'],
                    ['text' => 'progressbar', 'value' => 'progressbar'],
                ],
            ],
            'direction' => [
                'required' => false,
                'name' => tra('Direction'),
                'description' => tra('Could be \'horizontal\' or \'vertical\' (for vertical slider).'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'Horizontal', 'value' => 'horizontal'],
                    ['text' => 'Vertical', 'value' => 'vertical'],
                ],
            ],
			'background' => [
                'required' => false,
                'name' => tra('Background color'),
                'description' => tra('Slider background color, enter color code for example #000'),
                'default' => null,
                'advanced' => true,
                'since' => '19.0'
            ],
            'autoplay' => [
                'required' => false,
                'name' => tra('Auto Play'),
                'description' => tra('Autoplay slider, on by default'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'on', 'value' => 'on'],
                    ['text' => 'off ', 'value' => 'off'],
                ],
            ],
            'autoplaydelay' => [
                'required' => false,
                'name' => tra('Auto Play Delay'),
                'description' => tra('Time interval to pause before moving to next slide in seconds.'),
				'filter' => 'digits',
                'default' => null,
                'since' => '19.0'
            ],
			'displaythumbnails' => [
                'required' => false,
                'name' => tra('Display Thumbnails'),
                'description' => tra('Show thumbnails under main slider'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'off', 'value' => 'off'],
                    ['text' => 'on', 'value' => 'on'],
                ],
            ],
			'speed' => [
                'required' => false,
                'name' => tra('Speed'),
                'description' => tra('Duration of transition between slides (in seconds)'),
                'filter' => 'digits',
                'default' => null,
				'advance'=>true,
                'since' => '19.0'
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tr('Enter width of slider in px, default 100%'),
                'filter' => 'digits',
                'default' => null,
                'advanced' => true,
                'since' => '19.0'
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tr('Enter height of slider in px, default min height 650px, max height will adjust with content'),
                'filter' => 'digits',
                'default' => null,
                'advanced' => true,
                'since' => '19.0'
            ],
            'autoheight' => [
                'required' => false,
                'name' => tra('Auto Height'),
                'description' => tra('Set to true and slider wrapper will adopt its height to the height of the currently active slide'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'False', 'value' => 'false'],
                    ['text' => 'True', 'value' => 'true'],
                ],
            ],
            //Slides grid
            'spacebetween' => [
                'required' => false,
                'name' => tra('Space Between'),
                'description' => tra('Distance between slides in px.'),
                'filter' => 'digits',
                'default' => null,
                'since' => '19.0'
            ],
            'slidesperview' => [
                'required' => false,
                'name' => tra('Slides Per View'),
                'description' => tra('Number of slides per view (slides visible at the same time on slider\'s container).'),
                'filter' => 'digits',
                'default' => null,
                'since' => '19.0'
            ],
            'slidespercolumn' => [
                'required' => false,
                'name' => tra('Slides Per Column'),
                'description' => tra('Number of slides per column, for multirow layout'),
                'filter' => 'digits',
                'default' => null,
                'since' => '19.0'
            ],
            'slidespercolumnfill' => [
                'required' => false,
                'name' => tra('Slides Per Column Fill'),
                'description' => tra('Could be \'column\' or \'row\'. Defines how slides should fill rows, by column or by row'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'Column', 'value' => 'column'],
                    ['text' => 'Row', 'value' => 'row'],
                ],
            ],
            'centeredslides' => [
                'required' => false,
                'name' => tra('Centered Slides'),
                'description' => tra('If true, then active slide will be centered, not always on the left side.'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'False', 'value' => 'false'],
                    ['text' => 'True', 'value' => 'true'],
                ],
            ],
            'slidesoffsetbefore' => [
                'required' => false,
                'name' => tra('Slides Off setBefore'),
                'description' => tra('Add (in px) additional slide offset in the beginning of the container (before all slides)'),
                'filter' => 'digits',
                'default' => null,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidesoffsetafter' => [
                'required' => false,
                'name' => tra('Slides Off setAfter'),
                'description' => tra('Add (in px) additional slide offset in the end of the container (after all slides)'),
                'filter' => 'digits',
                'default' => null,
                'advanced' => true,
                'since' => '19.0'
            ],
            'slidetoclickedslide' => [
                'required' => false,
                'name' => tra('Slide To Clicked Slide'),
                'description' => tra('Set to true and click on any slide will produce transition to this slide.'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'False', 'value' => 'false'],
                    ['text' => 'True', 'value' => 'true'],
                ],
            ],
            //freemode
            'freemode' => [
                'required' => false,
                'name' => tra('Free Mode'),
                'description' => tra('If true then slides will not have fixed positions.'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'False', 'value' => 'false'],
                    ['text' => 'True', 'value' => 'true'],
                ],
            ],
            //Images
            'preloadimages' => [
                'required' => false,
                'name' => tra('Preload Images'),
                'description' => tra('When enabled Swiper will force to load all images.'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'True', 'value' => 'true'],
                    ['text' => 'False', 'value' => 'false'],
                ],
            ],
            'updateonimagesready' => [
                'required' => false,
                'name' => tra('Update On Images Ready'),
                'description' => tra('When enabled Swiper will be reinitialized after all inner images (<img> tags) are loaded. Required preloadimages: true.'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'advanced' => true,
                'options' => [
                    ['text' => 'True', 'value' => 'true'],
                    ['text' => 'False', 'value' => 'false'],
                ],
            ],
            //Loop
            'loop' => [
                'required' => false,
                'name' => tra('Loop Slider'),
                'description' => tra('Set to true to enable continuous loop mode (If you use it along with slidesperView: \'auto\' then you need to specify loopedslides parameter with amount of slides to loop (duplicate)).'),
                'filter' => 'word',
                'default' => null,
                'since' => '19.0',
                'options' => [
                    ['text' => 'False', 'value' => 'false'],
                    ['text' => 'True', 'value' => 'true'],
                ],
            ],
        ]
    ];
}

function wikiplugin_slideradvance($data, $params)
{
	//checking for swiper existance
	if(!file_exists("vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js")) {
		return '{REMARKSBOX(type=error, title=Swiper files missing)}' . tra(' Please update composer to install required files ').'{REMARKSBOX}';
	}
	
    global $tiki_p_admin, $prefs, $user, $page;
	static $uid = 0;
	$uid++;
    extract($params, EXTR_SKIP);
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $sliderposition = (isset($sliderposition) ? $sliderposition : '');
    $direction = (isset($direction) ? $direction : 'horizontal');
    $speed = (isset($speed) ? $speed*1000 : 500);
    $width = (isset($width) ? $width : '100%');
    $height = (isset($height) ? $height : '600px');
    $pagination = (isset($pagination) ? $pagination : 'bullets');
	$slidetoclickedslide = (isset($slidetoclickedslide) ? $slidetoclickedslide : 'false');
    $autoheight = (isset($autoheight) ? $autoheight : 'false');
    $effect = (isset($effect) ? $effect : 'slide');
    $spacebetween = (isset($spacebetween) ? $spacebetween : 30);
    $slidesperview = (isset($slidesperview) ? $slidesperview : 'auto');
    $slidespercolumn = (isset($slidespercolumn) ? $slidespercolumn : 1);
    $slidespercolumnfill = (isset($slidespercolumnfill) ? $slidespercolumnfill : 'column');
    $centeredslides = (isset($centeredslides) ? $centeredslides : 'true');
    $slidesoffsetbefore = (isset($slidesoffsetbefore) ? $slidesoffsetbefore : 1);
    $slidesoffsetafter = (isset($slidesoffsetafter) ? $slidesoffsetafter : 1);
    $freemode = (isset($freemode) ? $freemode : 'false');
    $preloadimages = (isset($preloadimages) ? $preloadimages : 'false');
    $updateonimagesready = (isset($updateonimagesready) ? $updateonimagesready : 'false');
    $displaythumbnails = (isset($displaythumbnails) ? $displaythumbnails : 'off');	
    $loop = (isset($loop) ? $loop : 'true');
 	$autoplay = (isset($autoplay) ? $autoplay : 'on');
	if($autoplay != 'off') {
    	$autoplaydelay = (isset($autoplaydelay) ? $autoplaydelay*1000 : 5000);
		$autoplay='autoplay: {
			delay: '.$autoplaydelay.',
		},';
	}
	else {
		$autoplay='';
	}
	if($pagination != 'off') {
 		$pagination='pagination: {
			el: \'.swiper-pagination\',
			clickable: true,
			type:"'.$pagination.'"
			},';
	}
	else {
		$pagination="";
	}
    $headerlib = TikiLib::lib('header');
    $headerlib->add_jsfile('vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js');
    $headerlib->add_cssfile('vendor_bundled/vendor/nolimits4web/swiper/dist/css/swiper.css');

    $slides = explode("/////", ($data ? $data : ""));
    $slidesHtml = '';
	//checking if gallery is choosen
	$filegallib = TikiLib::lib('filegal');
	if ($fgalId) {
		$files = $filegallib->get_files(0, -1, $params['sort_mode'], '', $params['fgalId']);
	} 
	if ($fileIds) {
		$params['fileIds'] = explode(',', $params['fileIds']);
		foreach ($params['fileIds'] as $fileId) {
			$file = $filegallib->get_file($fileId);

			if (! is_null($file)) {
				$files['data'][] = $file;
			}
		}

		$files['cant'] = count($files['data']);
	}
	foreach ($files['data'] as $file) {
		$slidesHtml .= '<div class="swiper-slide"><img src="tiki-download_file.php?fileId=' . $file['fileId'] . '&amp;display';
		if (! empty($params['displaySize'])) {
			if ($params['displaySize'] > 10) {
				$html .= '&amp;max=' . $params['displaySize'];
			} elseif ($params['displaySize'] <= 1) {
				$html .= '&amp;scale=' . $params['displaySize'];
			}
		}

		$slidesHtml .= '" alt="' . htmlentities($file['description']) . '" /></div>';
	}
    foreach ($slides as $slide) {
		if(trim($slide)) {
        	$slidesHtml .= '<div class="swiper-slide">' . $slide . '</div>';
		}	
    }
	$headerlib->add_css('#swiper-container'.$uid.' {width: '.$width.';height: '.$height.';background:'.$background.';margin-bottom:20px;}.swiper-slide {min-height:'.$height.';text-align: center;max-width:100%;overflow:hidden;display: -webkit-box;display: -ms-flexbox;display: -webkit-flex;display: flex;-webkit-box-pack: center;-ms-flex-pack: center;-webkit-justify-content: center;justify-content: center;-webkit-box-align: center;-ms-flex-align: center;-webkit-align-items: center;align-items: center;} .gallery-top {height: 80%;width: 100%;}.gallery-thumbs {height: 20%;box-sizing: border-box;padding: 10px 0;}.gallery-thumbs img {max-height:120px;height:120px;width:auto;}.gallery-thumbs .swiper-slide {width: 25%; height: 100%;opacity: 0.4;}.gallery-thumbs .swiper-slide-active {opacity: 1;}');
	$headerlib->add_js(
		'var swiper'.$uid.' = new Swiper("#swiper-container'.$uid.'",{
			direction:"'.$direction.'",	
			effect:"'.$effect.'",	
			autoHeight:"'.$autoheight.'",	
			speed:'.$speed.',
			spaceBetween:'.$spacebetween.',	
			slidesPerView:"'.$slidesperview.'",	
			slidesPerColumn:"'.$slidespercolumn.'",	
			slidespercolumnfill:"'.$slidespercolumnfill.'",	
			centeredSlides:"'.$centeredslides.'",	
			slidesOffsetBefore:"'.$slidesoffsetbefore.'",	
			slidesOffsetAfter:"'.$slidesoffsetafter.'",	
			loop:"'.$loop.'",
			preloadImages:"'.$preloadimages.'",	
			slidetoclickedslide:"'.$slidetoclickedslide.'",	
			freeMode:"'.$freemode.'",
			updateOnImagesReady:"'.$updateonimagesready.'",
			'.$pagination.'
			keyboard: {
				enabled: true,
				onlyInViewport: false,
			},
			
			'.$autoplay.'
			navigation: {
				nextEl: \'.swiper-button-next\',
				prevEl: \'.swiper-button-prev\',
			},
	 
    		});
			 
		');
		if($displaythumbnails=="on") {
			$thumbnails=' <div id="gallery-thumbs'.$uid.'" class="swiper-container gallery-thumbs"><div class="swiper-wrapper">'.$slidesHtml.'</div></div>'	;
			$thumbclass='gallery-top';
			$headerlib->add_js('
				var galleryThumbs'.$uid.' = new Swiper("#gallery-thumbs'.$uid.'", {
					spaceBetween:'.$spacebetween.',	
					slidesPerView: "auto",
					speed:'.$speed.',
					loop:"'.$loop.'"
				});
				swiper'.$uid.'.controller.control = galleryThumbs'.$uid.';
				galleryThumbs'.$uid.'.controller.control = swiper'.$uid.';
			');
			
		}
		$swiperCode='<div  id="swiper-container'.$uid.'" class="swiper-container '.$thumbclass.'"> <div class="swiper-wrapper">'.$slidesHtml.'</div><!-- Add Pagination --><div class="swiper-pagination"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>'.$thumbnails;
		
		if($sliderposition=='overtopbar') {
			echo $swiperCode;
			return;
		}
		elseif($sliderposition=='undertopbar') {
			$headerlib->add_js('$("#swiper-container'.$uid.'").insertAfter( "#page-header" );');
		}
		return $swiperCode;
	}
