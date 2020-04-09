<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/*
 * This script was created to get the translation percentage for each language.php file.
 *
 * Before calculating the percentage, it will run get_strings.php to make sure all language.php
 * files are up to date.
 *
 * The output is in wiki syntax and if a page name is provided as the second parameter,
 * it will be updated.
 */


if (! isset($argv[1])) {
	echo "\nUsage: php get_translation_percentage.php pathToTikiRootDir wikiPageName\n";
	echo "Example: php get_translation_percentage.php /home/user/public_html/tiki i18nStats\n";
	echo "The second parameter is optional\n\n";
	die;
}

$tikiPath = $argv[1];

if (substr($tikiPath, -1) != '/') {
	$tikiPath .= '/';
}

if (isset($argv[2])) {
	$wikiPage = $argv[2];
}

if (! file_exists($tikiPath)) {
	die("\nERROR: $tikiPath doesn't exist\n\n");
} elseif (! file_exists($tikiPath . 'db/local.php')) {
	die("\nERROR: $tikiPath doesn't seem to be a valid Tiki installation\n\n");
}

chdir($tikiPath);
require_once('tiki-setup.php');
require_once('lang/langmapping.php');
require_once('lib/language/File.php');

if (isset($wikiPage) && ! $tikilib->page_exists($wikiPage)) {
	die("\nERROR: $wikiPage doesn't exist\n\n");
}

// update all language.php files by calling get_strings.php
$output = [];
$return_var = null;

exec('php get_strings.php', $output, $return_var);

if ($return_var == 1) {
	die("\nCouln't execute get_strings.php\n\n");
}

// calculate the percentage for each language.php
$outputData = [];
$globalStats = [];

// $langmapping is set on lang/langmapping.php
foreach ($langmapping as $lang => $null) {
	$filePath = "lang/$lang/language.php";
	if (file_exists($filePath) && $lang != 'en') {
		$parseFile = new Language_File($filePath);
		$stats = $parseFile->getStats();

		$outputData[$lang] = [
			'total' => $stats['total'],
			'untranslated' => $stats['untranslated'],
			'translated' => $stats['translated'],
			'percentage' => $stats['percentage'],
		];

		if ($stats['percentage'] >= 70) {
			$globalStats['70+']++;
		} elseif ($stats['percentage'] >= 30) {
			$globalStats['30+']++;
		} elseif ($stats['percentage'] < 30) {
			$globalStats['0+']++;
		}
	}
}

if (! isset($globalStats['70+'])) {
	$globalStats['70+'] = 0;
} elseif (! isset($globalStats['30+'])) {
	$globalStats['30+'] = 0;
} elseif (! isset($globalStats['0+'])) {
	$globalStats['0+'] = 0;
}

// output translation percentage to terminal or to a wiki page
if (! isset($wikiPage)) {
	$output = "! Status of Tiki translations\n";
	$output .= "Page last modified on " . $tikilib->date_format($prefs['long_date_format']) . "\n\n";
	$output .= "This page is generated automatically. Please do not change it.\n\n";
	$output .= "The total number of strings is different for each language due to unused translations present in the language.php files.\n\n";
	$output .= "__Global stats:__\n* {$globalStats['70+']} languages with more than 70% translated\n* {$globalStats['30+']} languages with more than 30% translated\n* {$globalStats['0+']} languages with less than 30% translated\n\n";
} else {
	$output = "{HTML()}  <h1 class='text-center text-info'> {HTML}{TR()}Status of Tiki translations{TR}{HTML()}</h1> {HTML}";
	$output .= "{HTML()} <p class='text-center text-info'>{HTML}{TR()}Page last modified on " . $tikilib->date_format($prefs['long_date_format']) . " {TR}{HTML()}</p><br/> {HTML}";
	$output .= "{HTML()} <p class='text-danger'>{HTML}{TR()}This page is generated automatically. Please do not change it. {TR}{HTML()}</p> {HTML}";
	$output .= "{HTML()} <p class='text-info'>{HTML}{TR()}The total number of strings is different for each language due to unused translations present in the language.php files. {TR}{HTML()}</p> {HTML}";
	$output .= " {HTML()} <h3 class='text-capitalize text-info'>{HTML}{TR()}Global stats : {TR}{HTML()}</h3> {HTML}";
	$output .= " {HTML()} <ul class='list-group col-6 mb-2'>
 					<li class='list-group-item'><span class='text-success'>{$globalStats['70+']}</span>  {HTML}{TR()} languages with more than {TR}{HTML()}<span class='text-success'> 70%</span> {HTML}{TR()} translated{TR}{HTML()}</li>
 					<li class='list-group-item'><span class='text-success'>{$globalStats['30+']} </span> {HTML}{TR()} languages with more than {TR}{HTML()}<span class='text-success'> 30%</span> {HTML}{TR()} translated{TR}{HTML()}</li>
 					<li class='list-group-item'><span class='text-success'>{$globalStats['0+']} </span> {HTML}{TR()} languages with less than {TR}{HTML()}<span class='text-success'> 30%</span>  {HTML}{TR()}translated{TR}{HTML()}</li>
				</ul>{HTML}";
	$output .= "{FANCYTABLE(head=\"Language code (ISO)|English name|Native Name|Completion|Percentage|Number of strings\" sortable=\"y\")}\n";
}
foreach ($outputData as $lang => $data) {
	$output .= "$lang | {$langmapping[$lang][1]} | {$langmapping[$lang][0]} | {Gauge value=\"{$data['percentage']}\" max=\"100\" size=\"200\" color=\"#00C851\" bgcolor=\"#eceff1\" height=\"20\" perc=\"true\" showvalue=\"false\"} | ";
	$output .= "{$data['percentage']}% | Total: {$data['total']} %%% Translated: {$data['translated']} %%% Untranslated: {$data['untranslated']} \n";
}

$output .= '{FANCYTABLE}';

if (isset($wikiPage)) {
	$tikilib->update_page($wikiPage, $output, 'Updating translation stats', 'i18nbot', '127.0.0.1');
} else {
	echo $output;
}
