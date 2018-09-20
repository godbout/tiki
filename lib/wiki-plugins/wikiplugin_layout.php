<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
function wikiplugin_layout_info()
{
	return [
		'name'          => tra('Layout'),
		'documentation' => 'PluginLayout',
		'description'   => tra(
			'Plugin to control width/background/header and footer of individual page, helpful in creating landing pages for projects'
		),
		'prefs'         => ['wikiplugin_layout'],
		'iconname'      => 'tv',
		'introduced'    => 19,
		'tags'          => 'basic',
		'params'        => [
			'header'       => [
				'required'    => false,
				'name'        => tra('Display page header'),
				'description' => tra('Set to No, to hide header on the page'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],

				],
			],
			'footer'       => [
				'required'    => false,
				'name'        => tra('Display Page Footer'),
				'description' => tra('Set to No, to hide header on the page'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'leftbar'      => [
				'required'    => false,
				'name'        => tra('Display Page Left Bar'),
				'description' => tra('Set to No, to hide left on the page'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'rightbar'     => [
				'required'    => false,
				'name'        => tra('Display Page Right Bar'),
				'description' => tra('Set to No, to hide right on the page'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'fullwidth'    => [
				'required'    => false,
				'name'        => tra('Page Full Width'),
				'description' => tra('100% Page width'),
				'filter'      => 'alpha',
				'default'     => 'n',
				'since'       => '19.0',
				'options'     => [
					['text' => 'No ', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'contentwidth' => [
				'required'    => false,
				'name'        => tra('Page Content Width'),
				'description' => tra(
					'Enter page content width in px or % for example 1000px, leave blank for same width as page body.)'
				),
				'filter'      => 'text',
				'default'     => '',
				'advanced'    => true,
				'since'       => '19.0',
			],

			'bgimage' => [
				'required'    => false,
				'name'        => tra('Page Background Image URL'),
				'description' => tra(
					'Enter image url, in case of single image'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'bgrepeat'    => [
				'required'    => false,
				'name'        => tra('Background Repear'),
				'description' => tra('Cover,Repeat,no-repeat'),
				'filter'      => 'alpha',
				'default'     => 'n',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Repeat', 'value' =>'repeat'],
					['text' => 'Cover', 'value' =>'cover'],
					['text' => 'No Repeat ', 'value' =>'norepeat'],
				],
			],

			'fgalId'              => [
				'required'          => false,
				'name'              => tra('Page Background Sliding Images'),
				'description'       => tra(
					'Enter file gallery id for fading background'
				),
				'since'             => '19',
				'separator'         => ':',
				'profile_reference' => 'file_gallery',
			],
			'fileIds'             => [
				'required'    => false,
				'name'        => tra('File IDs'),
				'description' => tra(
					'List of IDs of images from the File Galleries separated by commas.'
				),
				'filter'      => 'striptags',
				'default'     => '',
			],
			'topmargin'  => [
				'required'    => false,
				'name'        => tra('Page Content Top Margin'),
				'description' => tra(
					'Enter value in % or px for example 30%, 300px, default will be 0'
				),
				'filter'      => 'text',
				'default'     => '0',
				'advanced'    => true,
				'since'       => '19.0',
			],
			'headerwidth'     => [
				'required'    => false,
				'name'        => tra('Page Header Width'),
				'description' => tra(
					'Enter page header width in px or %, leave blank for same width as page body.)'
				),
				'filter'      => 'text',
				'default'     => 0,
				'advanced'    => true,
				'since'       => '19.0',
			],
			'footerwidth'     => [
				'required'    => false,
				'name'        => tra('Page Footer Width'),
				'description' => tra(
					'Enter page footer width in px or %, leave blank for same width as page body.)'
				),
				'filter'      => 'text',
				'default'     => 0,
				'advanced'    => true,
				'since'       => '19.0',
			],
			'bgcolor' => [
				'required'    => false,
				'name'        => tra('Page Background Color'),
				'description' => tra(
					'Enter a valid CSS color code, or an rgba value if opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'
				),
				'filter'      => 'text',
				'default'     => '',
				'advanced'    => true,
				'since'       => '19.0',
			],
			'contentbg'           => [
				'required'    => false,
				'name'        => tra('Content Background Color'),
				'description' => tra(
					'Enter a valid CSS color code, or an rgba value if opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'contenttextcolor'    => [
				'required'    => false,
				'name'        => tra('Content Text Color'),
				'description' => tra(
					'Enter a valid CSS color code, for example #000,#fff,#ccc'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'contentradius'    => [
				'required'    => false,
				'name'        => tra('Content Border Radius'),
				'description' => tra(
					'To make content div round cornered, for example 10px'
				),
				'filter'      => 'text',
				'advance'     => true,
				'default'     => '',
				'since'       => '19.0',
			],

			'transitiondelay'   => [
				'required'    => false,
				'name'        => tra('Transition Delay'),
				'description' => tra(
					'Time interval to pause before moving to next slide in seconds.'
				),
				'filter'      => 'digits',
				'default'     => '5',
				'since'       => '19.0',
			],
			'actionbuttons' => [
				'required'    => false,
				'name'        => tra('Display Page Action Buttons'),
				'description' => tra(
					'Set to No, to hide page action buttons, with and under content'
				),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'advanced'    => true,
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],


		],
	];
}

function wikiplugin_layout($data, $params)
{
	$headerlib = TikiLib::lib('header');
	$headerlib->add_css("#row-middle{display:none} #show-errors-button{display:none}");
	$headerlib->add_js('$( document ).ready(function() {$(\'#row-middle\').attr("style","display:flex").fadeIn(1000); });');
	if ($params['header'] == 'n') {
		$headerlib->add_css("#page-header{display:none}");
	}
	if ($params['footer'] == 'n') {
		$headerlib->add_css("#footer{display:none}");
	}
	if ($params['leftbar'] == 'n') {
		$headerlib->add_css("#col2{display:none} .toggle_zone.left{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-8" )) {$("#col1").removeClass("col-lg-8").addClass("col-lg-10");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['rightbar'] == 'n') {
		$headerlib->add_css("#col3{display:none} .toggle_zone.right{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-10" )) {$("#col1").removeClass("col-lg-10").addClass("col-lg-12");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['actionbuttons'] == 'n') {
		$headerlib->add_css("#page-bar{display:none} ");
		$headerlib->add_js("$('.page_actions a').removeClass('btn btn-primary dropdown-toggle');"); //making action toggle smaller
	}

	if (isset($params['bgimage'])) {
		$backgroundOption="background-size:cover";
		if($params['bgrepeat']){
			if($params['bgrepeat']=="repeat"){$backgroundOption="background-repeat:repeat";}
			elseif($params['bgrepeat']=="norepeat"){$backgroundOption="background-repeat:no-repeat;background-position:center center;";}
		}

		$headerlib->add_css(
			"body{background-image:  url(" . $params["bgimage"]
			. ");".$backgroundOption."}"
		);
	}
	if ($params['fullwidth'] == 'y') {
		$headerlib->add_js(
			'$(".container").addClass("container-fluid").removeClass("container");'
		);
	}
	if (isset($params['contentwidth'])
		|| isset($params['topmargin']) || isset($params['contentradius'])
	) {
		$headerlib->add_css(
			"#row-middle{width:" . $params["contentwidth"]
			. ";margin:auto;margin-top:" . $params['topmargin']
			. ";min-width:380px;border-radius:".$params['contentradius']."} #col1{min-width:380px;margin:auto}"
		);

	}
	if (isset($params['headerwidth'])) {
		$headerlib->add_css(
			"#page-header{width:" . $params["headerwidth"] . ";margin:auto}"
		);
	}
	if (isset($params['footerwidth'])) {
		$headerlib->add_css(
			"#footer{width:" . $params["footerwidth"] . ";margin:auto}"
		);
	}
	if (isset($params['fgalId']) || $params['fileIds']) {
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
		$imageArr = '';
		$defaultImage = '';
		foreach ($files['data'] as $file) {
			if ($defaultImage == '') {
				$defaultImage = '"tiki-download_file.php?fileId='
					. $file['fileId'] . '&amp;display"';
			}
			$imageArr .= '"tiki-download_file.php?fileId=' . $file['fileId']
				. '&amp;display",';
		}

		$transitionDelay = (isset($params['transitiondelay'])
			? $params['transitiondelay'] * 1000 : 5000);

		$headerlib->add_css(
			'.bgdiv{ 
			background-image: url(' . $defaultImage . ');
			transition: background 1s linear;
			background-color:'.$params["bgcolor"].';
			height: 100%; 
			min-height: 100%; 
			position: absolute; 
			top: 0; 
			right: 0; 
			bottom: 0; 
			width:100%; 
			z-index:-100; 
		 }
		 body{background-image:url('.$defaultImage.')}'
		);
		$headerlib->add_js(
			'var bgDiv=new Array();
			var imageArr=new Array(' . $imageArr . ');
			var image_preload=new Array(); //preloading and appending background images
			for(i=0; i<imageArr.length; i++) { 
  				image_preload[i] = new Image();
  				image_preload[i].src = imageArr[i];
  				bgDiv[i] = document.createElement("div");
				$(bgDiv[i]).addClass(\'bgdiv\');
				$(bgDiv[i]).attr("style","background-image:url(\'" + imageArr[i] + "\')");
				$(bgDiv[i]).css("z-index",-100+i);
				$(bgDiv[i]).css("display","none");
				$("body").append(bgDiv[i]);
  			}
			$(document).ready(function() {
				var position=1;
				$(bgDiv[0]).fadeIn("slow"); //making first div appear
				setInterval(function () {
					if(position!=0) {
						$(bgDiv[position-1]).fadeOut();
					}
					else {
						$(bgDiv[imageArr.length-1]).fadeOut("slow");
					}
					$(bgDiv[position]).fadeIn("2000");
					position++;
					if(position==imageArr.length){
						position=0;
					}
				}, ' . $transitionDelay . ');
			});'
		);
	}
	if (isset($params['contentbg']) || isset($params['contenttextcolor'])) {
		$headerlib->add_css(
			"#row-middle{background-color:" . $params["contentbg"] . ";color:"
			. $params["contenttextcolor"] . "}"
		);
	}
	if (isset($params['bgcolor'])) {
		$headerlib->add_css(
			"body{background-color:" . $params["bgcolor"] . "}"
		);
	}
	return '';
}
