<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @param $string string            String to output
 * @param $color string                The colour of the string
 * @return string                    The formatted string to output to the console
 */
function color($string, $color)
{
	$avail = [
		'red' => 31,
		'green' => 32,
		'yellow' => 33,
		'blue' => 34,
		'purple' => 35,
		'cyan' => 36,
		'gray' => 37,
	];

	if (! isset($avail[$color])) {
		return $string;
	}

	return "\033[{$avail[$color]}m$string\033[0m";
}

/**
 * @param $message
 */
function error($message)
{
	die(color($message, 'red') . "\n");
}

/**
 * @param $message
 */
function info($message)
{
	echo color($message, 'blue') . "\n";
}

/**
 * @param $message
 */
function important($message)
{
	echo color($message, 'green') . "\n";
}
