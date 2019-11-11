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
			'Configure display details of the page header, footer, and side columns, as well as content width and background, etc.; helpful in creating landing/splash pages, etc.'
		),
		'prefs'         => ['wikiplugin_layout'],
		'iconname'      => 'tv',
		'introduced'    => 19,
		'tags'          => 'basic',
		'params'        => [
			'header'       => [
				'required'    => false,
				'name'        => tra('Display page header'),
				'description' => tra('Set to No to hide the page header (top module zone).'),
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
				'name'        => tra('Display page footer'),
				'description' => tra('Set to No to hide the footer (bottom module zone).'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'leftcolumn'      => [
				'required'    => false,
				'name'        => tra('Display page left column'),
				'description' => tra('Set to No to hide the left column.'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],
				],
			],
			'rightcolumn'     => [
				'required'    => false,
				'name'        => tra('Display page right column'),
				'description' => tra('Set to No to hide the right column.'),
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
				'name'        => tra('Full-width page'),
				'description' => tra('Override fixed width, if set, to have liquid layout.'),
				'filter'      => 'alpha',
				'default'     => 'n',
				'since'       => '19.0',
				'options'     => [
					['text' => 'No ', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'nosidemargins'    => [
				'required'    => false,
				'name'        => tra('Remove full-width content container side margins'),
				'description' => tra('Enable background images, etc. to span the complete width of the page.'),
				'filter'      => 'alpha',
				'default'     => 'n',
				'since'       => '20.0',
				'options'     => [
					['text' => 'No ', 'value' => 'n'],
					['text' => 'Yes', 'value' => 'y'],
				],
			],
			'contentwidth' => [
				'required'    => false,
				'name'        => tra('Page content width'),
				'description' => tra(
					'Enter page content width in px or %; for example, 1000px, leave blank for same width as page body.)'
				),
				'filter'      => 'text',
				'default'     => '',
				'advanced'    => true,
				'since'       => '19.0',
			],

			'bgimage' => [
				'required'    => false,
				'name'        => tra('Page background image URL'),
				'description' => tra(
					'Enter image URL, in the case of a single image.'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'bgrepeat'    => [
				'required'    => false,
				'name'        => tra('Background repeat'),
				'description' => tra('Options are cover, repeat, no-repeat'),
				'filter'      => 'alpha',
				'default'     => 'n',
				'since'       => '19.0',
				'options'     => [
					['text' => 'repeat', 'value' =>'repeat'],
					['text' => 'cover', 'value' =>'cover'],
					['text' => 'no repeat ', 'value' =>'norepeat'],
				],
			],

			'fgalId'              => [
				'required'          => false,
				'name'              => tra('Page background slideshow images'),
				'description'       => tra(
					'Enter file gallery ID for slideshow background.'
				),
				'since'             => '19',
				'separator'         => ':',
				'profile_reference' => 'file_gallery',
			],
			'fileIds'             => [
				'required'    => false,
				'name'        => tra('File IDs'),
				'description' => tra(
					'List of IDs of images from the file galleries, separated by commas.'
				),
				'filter'      => 'striptags',
				'default'     => '',
			],
			'topmargin'  => [
				'required'    => false,
				'name'        => tra('Page content top margin'),
				'description' => tra(
					'Enter value in % or px; for example, 30%, 300px. Default is 0.'
				),
				'filter'      => 'text',
				'default'     => '0',
				'advanced'    => true,
				'since'       => '19.0',
			],
			'headerwidth'     => [
				'required'    => false,
				'name'        => tra('Page header width'),
				'description' => tra(
					'Enter page header width in px or %; leave blank for same width as page body.'
				),
				'filter'      => 'text',
				'default'     => 0,
				'advanced'    => true,
				'since'       => '19.0',
			],
			'footerwidth'     => [
				'required'    => false,
				'name'        => tra('Page footer width'),
				'description' => tra(
					'Enter page footer width in px or %; leave blank for same width as page body.'
				),
				'filter'      => 'text',
				'default'     => 0,
				'advanced'    => true,
				'since'       => '19.0',
			],
			'bgcolor' => [
				'required'    => false,
				'name'        => tra('Page background color'),
				'description' => tra(
					'Enter a valid CSS color hex code, or an RGBA value if setting opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'
				),
				'filter'      => 'text',
				'default'     => '',
				'advanced'    => true,
				'since'       => '19.0',
			],
			'contentbg'           => [
				'required'    => false,
				'name'        => tra('Content background color'),
				'description' => tra(
					'Enter a valid CSS color hex code, or an RGBA value if setting opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'contenttextcolor'    => [
				'required'    => false,
				'name'        => tra('Content text color'),
				'description' => tra(
					'Enter a valid CSS color hex code; for example, #000, #fff, #ccc.'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'contentradius'    => [
				'required'    => false,
				'name'        => tra('Content border radius'),
				'description' => tra(
					'Enter px or % to give the content round corners; for example, 10px.'
				),
				'filter'      => 'text',
				'advance'     => true,
				'default'     => '',
				'since'       => '19.0',
			],
			'contentboxshadow'    => [
				'required'    => false,
				'name'        => tra('Content border shadow'),
				'description' => tra(
					'To create a shadow around the content, for example: 10px 10px 5px grey, 1px 2px 4px rgba(0, 0, 0, .5), 0 4px 8px 0 rgba(0, 0, 0, 0.2).'
				),
				'filter'      => 'text',
				'advance'     => true,
				'default'     => '',
				'since'       => '19.0',
			],

			'transitiondelay'   => [
				'required'    => false,
				'name'        => tra('Transition delay'),
				'description' => tra(
					'Time interval to pause before moving to the next slide, in seconds.'
				),
				'filter'      => 'digits',
				'default'     => '5',
				'since'       => '19.0',
			],
			'actionbuttons' => [
				'required'    => false,
				'name'        => tra('Display page action buttons'),
				'description' => tra(
					'Set to No to hide the page action buttons.'
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
			'topbar'       => [
				'required'    => false,
				'name'        => tra('Display topbar (below page header)'),
				'description' => tra('Set to No to hide the topbar (top module zone).'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'advanced'    => true,
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],

				],
			],
			'pagetopbar'       => [
				'required'    => false,
				'name'        => tra('Display page-top zone (above page content)'),
				'description' => tra('Set to No to hide the pagetop module zone.'),
				'filter'      => 'alpha',
				'default'     => 'y',
				'since'       => '19.0',
				'advanced'    => true,
				'options'     => [
					['text' => 'Yes', 'value' => 'y'],
					['text' => 'No ', 'value' => 'n'],

				],
			],
			'pagebottombar'       => [
				'required'    => false,
				'name'        => tra('Display page bottom bar (below page content.)'),
				'description' => tra('Set to No to hide the pagebottom module zone.'),
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
	if ($params['leftcolumn'] == 'n') {
		$headerlib->add_css("#col2{display:none} .toggle_zone.left{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-8" )) {$("#col1").removeClass("col-lg-8").addClass("col-lg-10");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['rightcolumn'] == 'n') {
		$headerlib->add_css("#col3{display:none} .toggle_zone.right{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-10" )) {$("#col1").removeClass("col-lg-10").addClass("col-lg-12");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['topbar'] == 'n') {
		$headerlib->add_css("#topbar_modules{display:none !important}");
	}
	if ($params['pagetopbar'] == 'n') {
		$headerlib->add_css("#pagetop_modules{display:none !important}");
	}
	if ($params['pagebottombar'] == 'n') {
		$headerlib->add_css("#pagebottom_modules{display:none !important}");
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
			'$(".container.container-std").addClass("container-fluid").removeClass("container");'
		);
	}
	if (isset($params['contentwidth'])
		|| isset($params['topmargin']) || isset($params['contentradius']) || isset($params['contentboxshadow'])
	) {
		$headerlib->add_css(
			"#row-middle{width:" . $params["contentwidth"]
			. " !important;margin:auto;margin-top:" . $params['topmargin']
			. ";min-width:380px;border-radius:".$params['contentradius'].";box-shadow:".$params['contentboxshadow']."} #col1{min-width:380px;margin:auto}"
		);

	}
	if (isset($params['headerwidth'])) {
		$headerlib->add_css(
			"#page-header{width:" . $params["headerwidth"] . " !important;margin:auto }"
		);
	}
	if (isset($params['footerwidth'])) {
		$headerlib->add_css(
			"#footer{width:" . $params["footerwidth"] . " !important;margin:auto}"
		);
	}
	if (isset($params['nosidemargins'])) {
		$headerlib->add_css(
			".container-std.container-fluid #page-data {margin-left: -15px; margin-right: -15px} .container-std.container-fluid #page-data > .row {margin-left: 0; margin-right: 0;} "
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
