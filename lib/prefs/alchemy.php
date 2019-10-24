<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_alchemy_list()
{
	$prefs = [
		'alchemy_ffmpeg_path' => [
			'name' => tra('ffmpeg path'),
			'description' => tra('Path to the location of the ffmpeg binary'),
			'type' => 'text',
			'help' => 'https://www.ffmpeg.org/',
			'size' => '256',
			'default' => '/usr/bin/ffmpeg',
		],
		'alchemy_ffprobe_path' => [
			'name' => tra('ffprobe path'),
			'description' => tra('Path to the location of the ffprobe binary'),
			'type' => 'text',
			'help' => 'https://ffmpeg.org/ffprobe.html',
			'size' => '256',
			'default' => '/usr/bin/ffprobe',
		],
		'alchemy_imagine_driver' => [
			'name' => tra('Alchemy Image library'),
			'description' => tra('Select either Image Magick or GD Graphics Library.'),
			'type' => 'list',
			'options' => [
				'imagick' => tra('Imagemagick'),
				'gd' => tra('GD')
			],
			'default' => 'imagick',
		],
		'alchemy_unoconv_path' => [
			'name' => tra('unoconv path'),
			'description' => tra('Path to the location of the unoconv binary.'),
			'type' => 'text',
			'size' => '256',
			'default' => '/usr/bin/unoconv',
		],
		'alchemy_gs_path' => [
			'name' => tra('ghostscript path'),
			'description' => tra('Path to the location of the ghostscript binary.'),
			'type' => 'text',
			'size' => '256',
			'default' => '/usr/bin/gs',
		],
		'alchemy_unoconv_timeout' => [
			'name' => tra('unoconv timeout'),
			'description' => tra('The maximum amount of time for unoconv to execute.'),
			'filter' => 'digits',
			'type' => 'text',
			'default' => 60,
			'units' => tra('seconds'),
		],
		'alchemy_unoconv_port' => [
			'name' => tra('unoconv port'),
			'description' => tra('unoconv running port.'),
			'type' => 'text',
			'size' => '5',
			'filter' => 'digits',
			'default' => 2002,
		],
	];

	if (!class_exists('\Imagick')) {
		$prefs['alchemy_imagine_driver']['options']['imagick'] .=  tr(' (Extension not loaded)');
	}

	if (!extension_loaded('gd')) {
		$prefs['alchemy_imagine_driver']['options']['gd'] .=  tr(' (Extension not loaded)');
	}

	return $prefs;
}
