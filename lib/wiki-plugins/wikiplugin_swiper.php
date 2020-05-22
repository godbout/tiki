<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
function wikiplugin_swiper_info()
{
	return [
		'name' => tr('Swiper'),
		'documentation' => 'PluginSwiper',
		'description' => tr('Embed swiper in content, support file galleries, files id and custom content'),
		'prefs' => ['wikiplugin_swiper'],
		'body' => tr('Enter custom slides data separated by "|". Wiki Syntax / HTML supported in slide text, to include wiki page in slide text use pluginInclude.<code>title:Slide 1 title;text:HTML/Wiki Syntax Supported Slide 1 text;image:Slide Image URL;bgcolor:#colorcode;color: #color code for text | title:Slide 2 title;text:Slide 2 text;image:Slide Image URL;bgcolor:#colorcode</code> '),
		'iconname' => 'tv',
		'introduced' => 19,
		'tags' => 'basic',
		'params' => [
			'fgalId' => [
				'required' => false,
				'name' => tr('File Gallery ID'),
				'description' => tr('Enter file gallery id for slider'),
				'since' => '19',
				'separator' => ':',
				'profile_reference' => 'file_gallery',
			],
			'fileIds' => [
				'required' => false,
				'name' => tr('File IDs'),
				'description' => tr('List of IDs of images from the File Galleries separated by commas.'),
				'filter' => 'striptags',
				'default' => '',
			],
			'effect' => [
				'required' => false,
				'name' => tr('Transition Effect'),
				'description' => tr('Tranisition effect. Could be "slide", "fade", "cube", "coverflow" or "flip"'),
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
				'name' => tr('Slider Placement on Page'),
				'description' => tr('Placement of slider, above topbar, below topbar, above menus and content or inside content'),
				'filter' => 'word',
				'default' => '',
				'since' => '19.0',
				'options' => [
					['text' => tr(''), 'value' => ''],				
					['text' => tr('Inside Content'), 'value' => ''],
					['text' => tr('Above top bar / Top of page'), 'value' => 'abovetopbar'],
					['text' => tr('Above Content/Under top bar'), 'value' => 'undertopbar']
				],
			],
			'pagination' => [
				'required' => false,
				'name' => tr('Pagination'),
				'description' => tr('Slider pagination, default bullets'),
				'filter' => 'word',
				'default' => 'bullets',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => '', 'value' => ''],				
					['text' => 'Off', 'value' => 'n'],
					['text' => tr('Bullets'), 'value' => 'bullets'],
					['text' => tr('Fraction'), 'value' => 'fraction'],
					['text' => tr('Progress bar'), 'value' => 'progressbar'],
				],
			],
			'navigation' => [
				'required' => false,
				'name' => tr('Navigation'),
				'description' => tr('Display navigation arrows'),
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '19.0',
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'background' => [
				'required' => false,
				'name' => tr('Slider Background Color'),
				'description' => tr('Slider background color, enter color code for example #000'),
				'since' => '19.0'
			],
			'parallaxBgImg' => [
				'required' => false,
				'name' => tr('Slider Parallax Background Image'),
				'description' => tr('Enter image url for parallax background behind swiper'),
				'filter' => 'text',
				'default' => '',
				'advanced' => true,
				'since' => '19.0'
			],
			'width' => [
				'required' => false,
				'name' => tr('Width'),
				'description' => tr('Enter width of slider in px, default 100%'),
				'filter' => 'word',
				'default' => '100%',
				'since' => '19.0'
			],
			'height' => [
				'required' => false,
				'name' => tr('Height'),
				'description' => tr('Enter height of slider in px, default min height 100px, max height will adjust with content'),
				'filter' => 'word',
				'default' => '100px',
				'since' => '19.0'
			],
			'titleColor' => [
				'required' => false,
				'name' => tr('Slide title color'),
				'description' => tr('Enter text color code of slide title, for example #ccc'),
				'filter' => 'text',
				'default' => '',
				'since' => '19.0'
			],
			'titleSize' => [
				'required' => false,
				'name' => tr('Title font size'),
				'description' => tr('For example 42px, default 32 px'),
				'filter' => 'word',
				'default' => '32px',
				'advanced' => true,
				'since' => '19.0'
			],
			'descriptionColor' => [
				'required' => false,
				'name' => tr('Slide description color'),
				'description' => tr('Enter text color code of slide description, for example #ccc'),
				'filter' => 'text',
				'default' => '',
				'since' => '19.0'
			],
			'descriptionSize' => [
				'required' => false,
				'name' => tr('Description font size'),
				'description' => tr('For example 24px, default 16 px'),
				'filter' => 'word',
				'default' => '16px',
				'advanced' => true,
				'since' => '19.0'
			],
			'slideContentBg' => [
				'required' => false,
				'name' => tr('Slide content background'),
				'description' => tr('Enter a valid CSS color code, or an rgba value if opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'),
				'filter' => 'text',
				'default' => '',
				'since' => '19.0'
			],
			'slideContentPostion' => [
				'required' => false,
				'name' => tr('Slide content position'),
				'description' => tr('Enter position for example top:20%;left:20% or bottom:20%;right:10%'),
				'filter' => 'text',
				'default' => 'top:20%;left:20%',
				'since' => '19.0'
			],
			'autoPlay' => [
				'required' => false,
				'name' => tr('Auto Play'),
				'description' => tr('Autoplay slider, on by default'),
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
				'name' => tr('Auto Play Delay'),
				'description' => tr('Time interval to pause before moving to next slide in seconds.'),
				'filter' => 'digits',
				'default' => '5',
				'since' => '19.0'
			],
			'displayThumbnails' => [
				'required' => false,
				'name' => tr('Display Thumbnails'),
				'description' => tr('Show thumbnails under main slider'),
				'filter' => 'alpha',
				'default' => 'n',
				'since' => '19.0',
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'speed' => [
				'required' => false,
				'name' => tr('Speed'),
				'description' => tr('Duration of transition between slides (in seconds)'),
				'filter' => 'digits',
				'default' => 300,
				'advance'=>true,
				'since' => '19.0'
			],
			'autoHeight' => [
				'required' => false,
				'name' => tr('Auto Height'),
				'description' => tr('Set to true and slider wrapper will adopt its height to the height of the currently active slide'),
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
				'name' => tr('Space Between'),
				'description' => tr('Distance between slides in px.'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesPerView' => [
				'required' => false,
				'name' => tr('Slides Per View'),
				'description' => tr('Slides visible at the same time on slider\'s container. Coverflow transition works best with 3 slides per view'),
				'filter' => 'digits',
				'default' => 1,
				'since' => '19.0'
			],
			'slidesPerViewMobile' => [
				'required' => false,
				'name' => tr('Slides Per View Mobile Screen'),
				'description' => tr('Slides visible at the same time on small screens'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesPerViewTab' => [
				'required' => false,
				'name' => tr('Slides Per View Tablet'),
				'description' => tr('Slides visible at the same time on low resolution tablets'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesPerColumn' => [
				'required' => false,
				'name' => tr('Slides Per Column'),
				'description' => tr('Number of slides per column, for multirow layout'),
				'filter' => 'digits',
				'default' => 1,
				'advanced'=>true,
				'since' => '19.0'
			],
			'slidesPerColumnFill' => [
				'required' => false,
				'name' => tr('Slides Per Column Fill'),
				'description' => tr('Could be \'column\' or \'row\'. Defines how slides should fill rows, by column or by row'),
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
				'name' => tr('Centered Slides'),
				'description' => tr('If true, then active slide will be centered, not always on the left side.'),
				'filter' => 'alpha',
				'default' => 'y',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'No', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'slidesOffsetBefore' => [
				'required' => false,
				'name' => tr('Slides Offset Before'),
				'description' => tr('Add (in px) additional slide offset in the beginning of the container (before all slides)'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slidesOffsetAfter' => [
				'required' => false,
				'name' => tr('Slides Offset After'),
				'description' => tr('Add (in px) additional slide offset in the end of the container (after all slides)'),
				'filter' => 'digits',
				'default' => 0,
				'advanced' => true,
				'since' => '19.0'
			],
			'slideToClickedSlide' => [
				'required' => false,
				'name' => tr('Slide To Clicked Slide'),
				'description' => tr('Set to true and click on any slide will produce transition to this slide.'),
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
				'name' => tr('Free Mode'),
				'description' => tr('If true then slides will not have fixed positions.'),
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
				'name' => tr('Preload Images'),
				'description' => tr('When enabled Swiper will force to load all images.'),
				'filter' => 'word',
				'default' => 'y',
				'since' => '19.0',
				'advanced' => true,
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No', 'value' => 'n'],
				],
			],
			'updateOnImagesReady' => [
				'required' => false,
				'name' => tr('Update On Images Ready'),
				'description' => tr('When enabled Swiper will be reinitialized after all inner images (<img> tags) are loaded. Required preloadimages: true.'),
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
				'name' => tr('Loop Slider'),
				'description' => tr('Set to true to enable continuous loop mode (If you use it along with slidesperView: \'auto\' then you need to specify loopedslides parameter with amount of slides to loop (duplicate)).'),
				'filter' => 'word',
				'default' => 'n',
				'since' => '19.0',
				'options' => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No', 'value' => 'n'],
				],
			],
		]
	];
}

function wikiplugin_swiper($data, $params)
{
	//checking for swiper existance
	if(!file_exists("vendor_bundled/vendor/nolimits4web/swiper/dist/js/swiper.min.js")) {
		Feedback::error(tr(' Please update composer to install required files'));
		return;
	}
	if((! empty($params['fileIds']) && !$params['fileIds']) && !$params['fgalId'] && !$data) {
		Feedback::error(tr('Paramaters missing: Please either select file gallery, give file ids or custom slide code in body.'));
		return;
	}
	static $uid = 0;
	$uid++;
	$defaults = [];
	$plugininfo = wikiplugin_swiper_info();
	foreach ($plugininfo['params'] as $key => $param) {
		$defaults["$key"] = (! empty($param['default']) ? $param['default'] : '');
		//separating digits filter parameters
		if(! empty($param['filter']) && $param['filter']=="digits") {
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
	if($params['pagination'] != 'n') {
		$pagination='pagination: {
			el: \'.swiper-pagination\',
			clickable: true,
			type:"'.$params['pagination'].'"
			},';
		$paginationDiv="<div class=\"swiper-pagination\"></div>";
	}
	else {
		$pagination="";
		$paginationDiv="";
	}
	if($params['effect']=="fade"){
		$fadeEffectCSS="#swiper-container".$uid." .swiper-slide:not(.swiper-slide-active){opacity: 0 !important;}";
	} else {
		$fadeEffectCSS = '';
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
		$navigationDiv = '';
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
	if ($params['fileIds']) {
		$params['fileIds'] = explode(',', $params['fileIds']);
		foreach ($params['fileIds'] as $fileId) {
			$file = $filegallib->get_file($fileId);

			if (! is_null($file)) {
				$files['data'][] = $file;
			}
		}
	}
	if (! empty($files['data'])) {
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
			(empty($slideArr['text']) || $slideArr['text']=='') ? $slideArr['text'] = '' : $slideArr['text']='<div>'.TikiLib::lib('parser')->parse_data($slideArr['text'], ['is_html' => true, 'parse_wiki' => true]).'</div>';
			if (empty($slideArr['color'])) {
				$slideArr['color'] = '';
			}
			if (empty($slideArr['bgcolor'])) {
				$slideArr['bgcolor'] = '';
			}
			if (empty($slideArr['title'])) {
				$slideArr['title'] = '';
			}
			$slidesHtml .= '<div data-swiper-parallax="-300" class="swiper-slide" style="color:'.$slideArr['color'].';background-color:'.$slideArr['bgcolor'].';background-image:url('.$slideArr['image'].')"><div class="slide-content'.$uid.'"><h1>' . $slideArr['title'] . '</h1>'.$slideArr['text'].'</div></div>';
		}	
	}
	$swiperSettings = '';
	$swiperParams=array('effect','autoHeight','speed','spaceBetween','slidesPerView','slidesPerColumn','slidesPerColumnFill','centeredSlides','slidesOffsetBefore','slidesOffsetAfter','loop','preloadImages','slideToClickedSlide','freeMode','updateOnImagesReady');
	foreach($swiperParams as $swiperParam) {
		$swiperSettings.=$swiperParam.":";
		if(!in_array($swiperParam,$swiperDigitsParams)) {
			$swiperSettings.="'".$params[$swiperParam]."',";
		}
		else {
			$params[$swiperParam]==''?$swiperSettings.="0,":$swiperSettings.=$params[$swiperParam].",";
		}
	}
	$breakpoints='';
	if($params['slidesPerViewMobile']>0 || $params['slidesPerViewTab']>0){
		$slidesPerView=$params['slidesPerView']==''?1:$params['slidesPerView'];
		$slidesPerViewTab=$params['slidesPerViewTab']==''?$slidesPerView:$params['slidesPerViewTab']; //if not defined use default slides per view
		$slidesPerViewMobile=$params['slidesPerViewMobile']==''?$slidesPerViewTab:$params['slidesPerViewMobile']; //if not defined use tablets slides per view
		$breakpoints='breakpointsInverse: true, breakpoints: { 320: { slidesPerView: '.$slidesPerViewMobile.'},768: {slidesPerView: '.$slidesPerViewTab.'},1024: {slidesPerView: '.$slidesPerView.'}},';
	}
	$swiperSettings=str_replace(array("'y'","'n'"),array("'true'","'false'"),$swiperSettings);
	$headerlib->add_css('#swiper-container'.$uid.' {width: '.$params['width'].';background:'.$params['background'].';margin-bottom:20px;} #swiper-container'.$uid.' .swiper-slide {font-size:'.$params['descriptionSize'].';color:'.$params['descriptionColor'].';min-height:'.$params['height'].';text-align: center;width:100%;overflow:hidden;} .gallery-top {height: 80%;width: 100%;}.gallery-thumbs {height: 20%;box-sizing: border-box;padding: 10px 0;}.gallery-thumbs img {max-height:120px;height:120px;width:auto;  margin-bottom:2%;cursor:pointer}.gallery-thumbs .swiper-slide {width: 25%; height: 100%;opacity: 0.4;}.gallery-thumbs .swiper-slide-active {opacity: 1;} #swiper-container'.$uid.' .swiper-slide h1{font-size:'.$params['titleSize'].';color:'.$params['titleColor'].'} .slide-content'.$uid.'{min-width:60%;position:absolute;'.$params['slideContentPostion'].';background:'.$params['slideContentBg'].';padding:1%;text-align:left}  .parallax-bg { position: absolute;left: 0;top: 0;width: 130%;height: 100%;-webkit-background-size: cover;background-size: cover;background-position: center;} .swiper-slide img{max-width:100%}'.$fadeEffectCSS);
	$thumbnails = '';
	$thumbclass = '';
	$swiperOpts = '';
	$thumbAfter = '';
	$thumbsSettings='';
	if($params['displayThumbnails']=="y") {
		$thumbnails=' <div id="gallery-thumbs'.$uid.'" class="swiper-container gallery-thumbs"><div class="swiper-wrapper">'.$slidesHtml.'</div></div>'	;
		$thumbclass='gallery-top';
		$swiperOpts='var galleryThumbs'.$uid.' = new Swiper("#gallery-thumbs'.$uid.'", {spaceBetween: 10,
			slidesPerView: 3,
			loopedSlides:1,
			loop:true,
			watchSlidesVisibility: true,
			watchSlidesProgress: true,
			slideToClickedSlide: true
		});';
		$thumbsSettings=''; //' thumbs: {swiper: galleryThumbs'.$uid.'},';
		$thumbAfter='swiper'.$uid.'.controller.control = galleryThumbs'.$uid.';galleryThumbs'.$uid.'.controller.control =swiper'.$uid.';';
	}
	$swiperOpts.='var swiper'.$uid.' = new Swiper("#swiper-container'.$uid.'",{
			'.$swiperSettings.'
			init:false,
			'.$thumbsSettings.'
			'.$pagination.'
			keyboard: {
				enabled: true,
				onlyInViewport: false,
			},
			'.$breakpoints.'
			'.$autoPlay.'
			'.$navigation.'
	 
			});'.$thumbAfter;
	if($params['sliderPosition']=='abovetopbar') {
		$headerlib->add_css("#swiper-container".$uid."{visibility:hidden;}");
		$swiperOpts.='var container=$(".container").first();$("#swiper-container'.$uid.'").insertBefore( container );$("#gallery-thumbs'.$uid.'").insertAfter( "#swiper-container'.$uid.'" );';
	}
	elseif($params['sliderPosition']=='undertopbar') {
		$headerlib->add_css("#swiper-container".$uid."{visibility:hidden;}");
		$headerlib->add_js('$( document ).ready(function() { $("#swiper-container'.$uid.'").insertAfter( "#page-header" );  $("#gallery-thumbs'.$uid.'").insertAfter( "#swiper-container'.$uid.'" );})');
	}
	//delaying initialization till window is fully loaded
	$swiperOpts.='setTimeout( function(){
		$(window).trigger("resize")
		}, 100); $(window).resize(function(){swiper'.$uid.'.init(); $("#swiper-container'.$uid.'").css("visibility","visible"); });';
	$headerlib->add_js(
		'$( document ).ready(function() {'.$swiperOpts.';$("#swiper-container'.$uid.'").css("max-width",$("#swiper-container'.$uid.'").parent().width());})');
		$swiperCode='<div id="swiper-container'.$uid.'" class="swiper-container '.$thumbclass.'"> <div class="parallax-bg" style="background-image:url('.$params['parallaxBgImg'].')" data-swiper-parallax="-23%"></div> <div class="swiper-wrapper">'.$slidesHtml.'</div><!-- Add Pagination -->'.$paginationDiv.$navigationDiv.'</div>'.$thumbnails;
		return $swiperCode;
}
