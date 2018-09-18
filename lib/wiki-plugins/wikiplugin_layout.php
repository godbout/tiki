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
			'leftBar'      => [
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
			'rightBar'     => [
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
			'fullWidth'    => [
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
			'contentWidth' => [
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

			'bgImage' => [
				'required'    => false,
				'name'        => tra('Page Background Image URL'),
				'description' => tra(
					'Enter image url, in case of single image'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
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
			'topMargin'  => [
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
			'headerWidth'     => [
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
			'footerWidth'     => [
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
			'bgColor' => [
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
			'contentBg'           => [
				'required'    => false,
				'name'        => tra('Content Background Color'),
				'description' => tra(
					'Enter a valid CSS color code, or an rgba value if opacity is desired; for example: #000 or rgba(00, 00, 00, 0.5).'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],
			'contentTextColor'    => [
				'required'    => false,
				'name'        => tra('Content Text Color'),
				'description' => tra(
					'Enter a valid CSS color code, for example #000,#fff,#ccc'
				),
				'filter'      => 'text',
				'default'     => '',
				'since'       => '19.0',
			],

			'transitionDelay'   => [
				'required'    => false,
				'name'        => tra('Transition Delay'),
				'description' => tra(
					'Time interval to pause before moving to next slide in seconds.'
				),
				'filter'      => 'digits',
				'default'     => '5',
				'since'       => '19.0',
			],
			'actionButtons' => [
				'required'    => false,
				'name'        => tra('Display Page Action Buttons'),
				'description' => tra(
					'Set to No, to hide page action buttons displayed under page content'
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
	$headerlib->add_css("#col1{display:none}");
	$headerlib->add_js('$( document ).ready(function() {$(\'#col1\').fadeIn(3000); });');
	if ($params['header'] == 'n') {
		$headerlib->add_css("#page-header{display:none}");
	}
	if ($params['footer'] == 'n') {
		$headerlib->add_css("#footer{display:none}");
	}
	if ($params['leftBar'] == 'n') {
		$headerlib->add_css("#col2{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-8" )) {$("#col1").removeClass("col-lg-8").addClass("col-lg-10");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['rightBar'] == 'n') {
		$headerlib->add_css("#col3{display:none}");
		$headerlib->add_js(
			'if ($( "#col1" ).hasClass( "col-lg-10" )) {$("#col1").removeClass("col-lg-10").addClass("col-lg-12");}if($( "#col1" ).hasClass( "col-lg-9" )) {$("#col1").removeClass("col-lg-9").addClass("col-lg-12");}'
		);
	}
	if ($params['actionButtons'] == 'n') {
		$headerlib->add_css("#page-bar{display:none}");
	}

	if (isset($params['bgImage'])) {
		$headerlib->add_css(
			"body{background-image:  url(" . $params["bgImage"]
			. ");background-size:cover}"
		);
	}
	if ($params['fullWidth'] == 'y') {
		$headerlib->add_js(
			'$(".container").addClass("container-fluid").removeClass("container");'
		);
	}
	if (isset($params['contentWidth'])
		|| isset($params['topMargin'])
	) {
		$headerlib->add_css(
			"#row-middle{width:" . $params["contentWidth"]
			. ";margin:auto;margin-top:" . $params['topMargin']
			. ";min-width:380px} #col1{min-width:380px;margin:auto}"
		);

	}
	if (isset($params['headerWidth'])) {
		$headerlib->add_css(
			"#page-header{width:" . $params["headerWidth"] . ";margin:auto}"
		);
	}
	if (isset($params['footerWidth'])) {
		$headerlib->add_css(
			"#footer{width:" . $params["footerWidth"] . ";margin:auto}"
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

		$transitionDelay = (isset($params['transitionDelay'])
			? $params['transitionDelay'] * 1000 : 5000);

		$headerlib->add_css(
			'.bgdiv{ 
			background-image: url(' . $defaultImage . ');
		   
			transition: background 1s linear; 
			height: 100%; 
			min-height: 100%; 
			position: absolute; 
			top: 0; 
			right: 0; 
			bottom: 0; 
			width:100%; 
			z-index:-10; 
		 }'
		);
		$headerlib->add_js(
			'$(document).ready(function() {
			var bgDiv = document.createElement("div");   // Create with DOM
			$(bgDiv).addClass(\'bgdiv\');
			bgDiv.innerHTML = "";
			$("body").append(bgDiv);      // Insert new elements after img
			var position=0;
			var imageArr=new Array(' . $imageArr . ');
			setInterval(function () {
				$(bgDiv).css("background-image", "url(\'" + imageArr[position] + "\')");
				position++;
				if(position==imageArr.length){
					position=0;
				}
			}, ' . $transitionDelay . ');

		});'
		);
	}
	if (isset($params['contentBg']) || isset($params['contentTextColor'])) {
		$headerlib->add_css(
			"#row-middle{background-color:" . $params["contentBg"] . ";color:"
			. $params["contentTextColor"] . "}"
		);
	}
	if (isset($params['bgColor'])) {
		$headerlib->add_css(
			"body{background-color:" . $params["bgColor"] . "}"
		);
	}
}
