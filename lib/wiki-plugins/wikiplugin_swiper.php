<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id:$
function wikiplugin_swiper_info()
{
	return [
		'name' => tra('Swiper'),
		'documentation' => 'PluginSwiper',
		'description' => tra('Embed swiper in content, support file galleries, files id and custom content'),
		'prefs' => 'wikiplugin_swiper',
		'body' => tra('Enter custom slides data separated by "|".<code>title:Slide 1 title;text:Slide 1 text:img:Slide Image URL;bgcolor:#colorcode | title:Slide 2 title;text:Slide 2 text:img:Slide Image URL;bgcolor:#colorcode</code> '),
		'iconname' => 'tv',
		'introduced' => 19,
		'tags' => 'basic',
		'params' => [
			'fgalId' => [
				'required' => false,
				'name' => tra('File Gallery ID'),
				'description' => tra('Enter file gallery id for slider'),
				'since' => '19',
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
				'default' => 'slide',
				'since' => '19.0',
				'options' => [
					['text' => 'Slide', 'value' => 'slide'],
					['text' => 'Fade', 'value' => 'fade'],
					['text' => 'Cube', 'value' => 'cube'],
					['text' => 'Coverflow', 'value' => 'coverflow'],
					['text' => 'Flip', 'value' => 'flip'],
				],
			],
		'sliderPosition' => [
				'required' => false,
				'name' => tra('Slider Type'),
				'description' => tra('Placement of slider, above topbar, below topbar, above menus and content or inside content'),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => tra(''), 'value' => ''],				
					['text' => tra('Inside Content'), 'value' => ''],
					['text' => tra('Above top bar / Top of page'), 'value' => 'abovetopbar'],
					['text' => tra('Above Content/Under top bar'), 'value' => 'undertopbar']
				],
			],
			'pagination' => [
				'required' => false,
				'name' => tra('Pagination'),
				'description' => tra('Slider pagniation, default bullets'),
				'filter' => 'word',
				'default' => 'bullets',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => '', 'value' => ''],				
					['text' => 'Off', 'value' => 'n'],
					['text' => 'bullets', 'value' => 'bullets'],
					['text' => 'fraction', 'value' => 'fraction'],
					['text' => 'progressbar', 'value' => 'progressbar'],
				],
			],
			'navigation' => [
				'required' => false,
				'name' => tra('Navigation'),
				'description' => tra('Display navigation arrows'),
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'direction' => [
				'required' => false,
				'name' => tra('Direction'),
				'description' => tra('Could be \'horizontal\' or \'vertical\' (for vertical slider).'),
				'filter' => 'word',
				'default' => 'horizontal',
				'since' => '19.0',
				'options' => [
					['text' => 'Horizontal', 'value' => 'horizontal'],
					['text' => 'Vertical', 'value' => 'vertical'],
				],
			],
			'background' => [
				'required' => false,
				'name' => tra('Background Color'),
				'description' => tra('Slider background color, enter color code for example #000'),
				'since' => '19.0'
			],
			'autoPlay' => [
				'required' => false,
				'name' => tra('Auto Play'),
				'description' => tra('Autoplay slider, on by default'),
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'autoPlayDelay' => [
				'required' => false,
				'name' => tra('Auto Play Delay'),
				'description' => tra('Time interval to pause before moving to next slide in seconds.'),
				'filter' => 'digits',
				'default' => '5',
				'since' => '19.0'
			],
			'displayThumbnails' => [
				'required' => false,
				'name' => tra('Display Thumbnails'),
				'description' => tra('Show thumbnails under main slider'),
				'filter' => 'alpha',
				'default' => 'n',
				'since' => '19.0',
				'options' => [
					['text' => 'Yes', 'value' => 'n'],
					['text' => 'No', 'value' => 'y'],
				],
			],
			'speed' => [
				'required' => false,
				'name' => tra('Speed'),
				'description' => tra('Duration of transition between slides (in seconds)'),
				'filter' => 'digits',
				'default' => 300,
				'advance'=>true,
				'since' => '19.0'
			],
			'width' => [
				'required' => false,
				'name' => tra('Width'),
				'description' => tr('Enter width of slider in px, default 100%'),
				'filter' => 'word',
				'default' => '100%',
				'advanced' => true,
				'since' => '19.0'
			],
			'height' => [
				'required' => false,
				'name' => tra('Height'),
				'description' => tr('Enter height of slider in px, default min height 350px, max height will adjust with content'),
				'filter' => 'word',
				'default' => '350px',
				'advanced' => true,
				'since' => '19.0'
			],
			'autoHeight' => [
				'required' => false,
				'name' => tra('Auto Height'),
				'description' => tra('Set to true and slider wrapper will adopt its height to the height of the currently active slide'),
				'filter' => 'alpha',
				'default' => 'n',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			//Slides grid
			'spaceBetween' => [
				'required' => false,
				'name' => tra('Space Between'),
				'description' => tra('Distance between slides in px.'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesPerView' => [
				'required' => false,
				'name' => tra('Slides Per View'),
				'description' => tra('Slides visible at the same time on slider\'s container. Coverflow transition works best with 3 slides per view'),
				'filter' => 'digits',
				'default' => 1,
				'since' => '19.0'
			],
			'slidesPerColumn' => [
				'required' => false,
				'name' => tra('Slides Per Column'),
				'description' => tra('Number of slides per column, for multirow layout'),
				'filter' => 'digits',
				'default' => 1,
				'advanced'=>true,
				'since' => '19.0'
			],
			'slidesPerColumnFill' => [
				'required' => false,
				'name' => tra('Slides Per Column Fill'),
				'description' => tra('Could be \'column\' or \'row\'. Defines how slides should fill rows, by column or by row'),
				'filter' => 'word',
				'default' => 'column',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'Column', 'value' => 'column'],
					['text' => 'Row', 'value' => 'row'],
				],
			],
			'centeredSlides' => [
				'required' => false,
				'name' => tra('Centered Slides'),
				'description' => tra('If true, then active slide will be centered, not always on the left side.'),
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'slidesOffsetBefore' => [
				'required' => false,
				'name' => tra('Slides Offset Before'),
				'description' => tra('Add (in px) additional slide offset in the beginning of the container (before all slides)'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesOffsetAfter' => [
				'required' => false,
				'name' => tra('Slides Offset After'),
				'description' => tra('Add (in px) additional slide offset in the end of the container (after all slides)'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slideToClickedSlide' => [
				'required' => false,
				'name' => tra('Slide To Clicked Slide'),
				'description' => tra('Set to true and click on any slide will produce transition to this slide.'),
				'filter' => 'word',
				'default' => 'n',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			//freemode
			'freeMode' => [
				'required' => false,
				'name' => tra('Free Mode'),
				'description' => tra('If true then slides will not have fixed positions.'),
				'filter' => 'word',
				'default' => 'n',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			//Images
			'preloadImages' => [
				'required' => false,
				'name' => tra('Preload Images'),
				'description' => tra('When enabled Swiper will force to load all images.'),
				'filter' => 'word',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No', 'value' => 'n'],
				],
			],
			'updateOnImagesReady' => [
				'required' => false,
				'name' => tra('Update On Images Ready'),
				'description' => tra('When enabled Swiper will be reinitialized after all inner images (<img> tags) are loaded. Required preloadimages: true.'),
				'filter' => 'word',
				'default' => 'y',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No', 'value' => 'n'],
				],
			],
			//Loop
			'loop' => [
				'required' => false,
				'name' => tra('Loop Slider'),
				'description' => tra('Set to true to enable continuous loop mode (If you use it along with slidesperView: \'auto\' then you need to specify loopedslides parameter with amount of slides to loop (duplicate)).'),
				'filter' => 'word',
				'default' => 'n',
				'since' => '19.0',
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'titleColor' => [
				'required' => false,
				'name' => tra('Slide title color'),
				'description' => tra('Enter text color code of slide title, for example #ccc'),
				'filter' => 'text',
				'default' => '',
				'advanced' => true,
				'since' => '19.0'
			],
			'titleSize' => [
				'required' => false,
				'name' => tra('TItle font size'),
				'description' => tra('For example 42px, default 32 px'),
				'filter' => 'word',
				'default' => '32px',
				'advanced' => true,
				'since' => '19.0'
			],
			'descriptionColor' => [
				'required' => false,
				'name' => tra('Slide description color'),
				'description' => tra('Enter text color code of slide description, for example #ccc'),
				'filter' => 'text',
				'default' => '',
				'advanced' => true,
				'since' => '19.0'
			],
			'descriptionSize' => [
				'required' => false,
				'name' => tra('Description font size'),
				'description' => tra('For example 24px, default 16 px'),
				'filter' => 'word',
				'default' => '16px',
				'advanced' => true,
				'since' => '19.0'
			],
			'slideContentBg' => [
				'required' => false,
				'name' => tra('Slide content background'),
				'description' => tra('Enter color code or rgba if opacity is desired value for example: #000 or rgba(00, 00, 00, 0.5)'),
				'filter' => 'text',
				'default' => '',
				'advanced' => true,
				'since' => '19.0'
			],
			'slideContentPostion' => [
				'required' => false,
				'name' => tra('Slide content position'),
				'description' => tra('Enter position for example top:20%;left:20% or bottom:20%;right:10%'),
				'filter' => 'text',
				'default' => 'top:20%;left:20%',
				'advanced' => true,
				'since' => '19.0'
			],
			'parallaxBgImg' => [
				'required' => false,
				'name' => tra('Swiper Parallax Background Image'),
				'description' => tra('Enter image url for parallax background behind all slides'),
				'filter' => 'text',
				'default' => 1,
				'advanced' => true,
				'since' => '19.0'
			],
		]
	];
}

function wikiplugin_swiper($data, $params)
{
	//checking for swiper existance
	if(!file_exists("vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js")) {
		Feedback::error(tra(' Please update composer to install required files'));
		return;
	}
	if(!$params['fileIds'] && !$params['fgalId'] && !$data) {
		Feedback::error(tra('Paramaters missing: Please either select file gallery, give file ids or custom slide code in body.'));
		return;
	}
	static $uid = 0;
	$uid++;
	$defaults = [];
	$plugininfo = wikiplugin_swiper_info();
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults["$key"] = $param['default'];
		//separating digits filter parameters
		if($param['filter']=="digits") {
			$swiperDigitsParams[]=$key;
		}
	}
	$params = array_merge($defaults, $params);
	if($params['autoPlay'] != 'n') {
		$autoplayDelay=$params['autoPlayDelay']*1000;
		$autoPlay='autoplay: {
			delay: '.$autoplayDelay.',
		},';
	}
	else {
		$autoPlay='';
	}
	if($$params['pagination'] != 'n') {
		$pagination='pagination: {
			el: \'.swiper-pagination\',
			clickable: true,
			type:"'.$params['pagination'].'"
			},';
	}
	else {
		$pagination="";
	}
	if($params['navigation'] != 'n') {
		$navigation='navigation: {
				nextEl: \'#swiper'.$uid.'-button-next\',
				prevEl: \'#swiper'.$uid.'-button-prev\',
			},';
		$navigationDiv='<div id="swiper'.$uid.'-button-prev" class="swiper-button-prev"></div><div class="swiper-button-next" id="swiper'.$uid.'-button-next"></div>';	
	}
	else {
		$navigation="";
	}
	$headerlib = TikiLib::lib('header');
	$headerlib->add_jsfile('vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js');
	$headerlib->add_cssfile('vendor_bundled/vendor/nolimits4web/swiper/dist/css/swiper.css');

	$slides = explode("|", ($data ? $data : ""));
	$slidesHtml = '';
	//checking if gallery is choosen
	$filegallib = TikiLib::lib('filegal');
	if ($params['fgalId']) {
		$files = $filegallib->get_files(0, -1, '', '', $params['fgalId']);
	} 
	if ($fileIds) {
		$params['fileIds'] = explode(',', $params['fileIds']);
		foreach ($params['fileIds'] as $fileId) {
			$file = $filegallib->get_file($fileId);

			if (! is_null($file)) {
				$files['data'][] = $file;
			}
		}
	}
	foreach ($files['data'] as $file) {
		$slidesHtml .= '<div class="swiper-slide"><img src="tiki-download_file.php?fileId=' . $file['fileId'] . '&amp;display';
		if (! empty($params['displaySize'])) {
			if ($params['displaySize'] > 10) {
				$slidesHtml .= '&amp;max=' . $params['displaySize'];
			} elseif ($params['displaySize'] <= 1) {
				$slidesHtml .= '&amp;scale=' . $params['displaySize'];
			}
		}
		$slidesHtml .= '" alt="' . htmlentities($file['description']) . '" /></div>';
	}
	foreach ($slides as $slide) {
		if(trim($slide)) {
			//processing slides
			$slideArr=explode(";",$slide);
			if(count($slideArr)>0) {
				foreach($slideArr as $slideValue) {
					$slideData=explode(":",$slideValue,2);
					$slideArr[trim($slideData[0])]=trim($slideData[1]);
				}
			}
			else {
				$slideArr['text']=$slideArr[0]; //single attribute slide
			}
			$slidesHtml .= '<div data-swiper-parallax="-300" class="swiper-slide" style="color:'.$slideArr['color'].';background-color:'.$slideArr['bgcolor'].';background-image:url('.$slideArr['img'].')"><div class="slide-content'.$uid.'"><h1>' . $slideArr['title'] . '</h1><div>'.$slideArr['text'].'</div></div></div>';
		}	
	}
	$swiperParams=array('direction','effect','autoHeight','speed','spaceBetween','slidesPerView','slidesPerColumn','slidesPerColumnFill','centeredSlides','slidesOffsetBefore','slidesOffsetAfter','loop','preloadImages','slideToClickedSlide','freeMode','updateOnImagesReady');
	foreach($swiperParams as $swiperParam) {
		$swiperSettings.=$swiperParam.":";
		if(!in_array($swiperParam,$swiperDigitsParams)) {
			$swiperSettings.="'".$params[$swiperParam]."',";
		}
		else {
			$swiperSettings.=$params[$swiperParam].","; 
		}
	}
	$swiperSettings=str_replace(array("'y'","'n'"),array("'true'","'false'"),$swiperSettings);
	$headerlib->add_css('#swiper-container'.$uid.' {width: '.$params['width'].';min-height: '.$params['height'].';background:'.$params['background'].';margin-bottom:20px;} #swiper-container'.$uid.' .swiper-slide {font-size:'.$params['descriptionSize'].';color:'.$params['descriptionColor'].';min-height:'.$params['height'].';text-align: center;width:100%;overflow:hidden;} .gallery-top {height: 80%;width: 100%;}.gallery-thumbs {height: 20%;box-sizing: border-box;padding: 10px 0;}.gallery-thumbs img {max-height:120px;height:120px;width:auto;}.gallery-thumbs .swiper-slide {width: 25%; height: 100%;opacity: 0.4;}.gallery-thumbs .swiper-slide-active {opacity: 1;} #swiper-container'.$uid.' .swiper-slide h1{font-size:'.$params['titleSize'].';color:'.$params['titleColor'].'} .slide-content'.$uid.'{min-width:60%;position:absolute;'.$params['slideContentPostion'].';background:'.$params['slideContentBg'].';padding:1%;text-align:left}  .parallax-bg { position: absolute;left: 0;top: 0;width: 130%;height: 100%;-webkit-background-size: cover;background-size: cover;background-position: center;
	}');
	$headerlib->add_js(
		'var swiper'.$uid.' = new Swiper("#swiper-container'.$uid.'",{
			'.$swiperSettings.'
			'.$pagination.'
			keyboard: {
				enabled: true,
				onlyInViewport: false,
			},
			
			'.$autoPlay.'
			'.$navigation.'
	 
			});
			 
		');
		if($params['displayThumbnails']=="y") {
			$thumbnails=' <div id="gallery-thumbs'.$uid.'" class="swiper-container gallery-thumbs"><div class="swiper-wrapper">'.$slidesHtml.'</div></div>'	;
			$thumbclass='gallery-top';
			$headerlib->add_js('
				var galleryThumbs'.$uid.' = new Swiper("#gallery-thumbs'.$uid.'", {
					spaceBetween:'.$params['spaceBetween'].',	
					slidesPerView: "auto",
					speed:'.$params['speed'].',
					loop:"'.$params['loop'].'"
				});
				swiper'.$uid.'.controller.control = galleryThumbs'.$uid.';
				galleryThumbs'.$uid.'.controller.control = swiper'.$uid.';
			');
			
		}
		$swiperCode='<div id="swiper-container'.$uid.'" class="swiper-container '.$thumbclass.'"> <div class="parallax-bg" style="background-image:url('.$params['parallaxBgImg'].')" data-swiper-parallax="-23%"></div> <div class="swiper-wrapper">'.$slidesHtml.'</div><!-- Add Pagination --><div class="swiper-pagination"></div>'.$navigationDiv.'</div>'.$thumbnails;
		
		if($params['sliderPosition']=='abovetopbar') {
			echo $swiperCode;
			return;
		}
		elseif($params['sliderPosition']=='undertopbar') {
			$headerlib->add_js('$("#swiper-container'.$uid.'").insertAfter( "#page-header" );');
		}
		return $swiperCode;
	}
