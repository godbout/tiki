<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_alchemy_list()
{
	return [
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
	];
}
