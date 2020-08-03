<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Update lang/xx/language.php files
 *
 * Scans a directory (its files) and a set of (individual) files
 * By default, the directory scanned is the Tiki root, excluding $excludeDirs. By default, the individual files scanned are files in these otherwise excluded directories.
 *
 * Examples:
 * 		- http://localhost/pathToTiki/get_strings.php -> update all language.php files
 * 		- http://localhost/pathToTiki/get_strings.php?lang=fr -> update just lang/fr/language.php file
 * 		- http://localhost/pathToTiki/get_strings.php?lang[]=fr&lang[]=pt-br&outputFiles -> update both French
 * 		  and Brazilian Portuguese language.php files and for each string add a line with
 * 		  the file where it was found.
 *
 * Command line examples:
 * 		- php get_strings.php
 * 		- php get_strings.php lang=pt-br outputFiles=true
 *
 * 		Only scan lib/, and only part of lib/ (exclude lib/core/Zend and lib/captcha), but still include captchalib.php and index.php
 * 		This FAILS as of 2017-09-15, since the language files (for output) are looked for in baseDir.
 * 		- php get_strings.php baseDir=lib/ excludeDirs=lib/core/Zend,lib/captcha includeFiles=captchalib.php,index.php fileName=language_r.php
 *
 * Note: Parameters controlling scanned files (baseDir, excludeDirs, includeFiles) and fileName are available in command line mode only.
 *
 *
 */
echo "\nUse of this file is now deprecated. use php console.php translation:getstrings instead.\n";

if (php_sapi_name() != 'cli') {
    require_once('tiki-setup.php');
    $access->check_permission('tiki_p_admin');
}

require_once('lib/init/initlib.php');
require_once('lib/setup/timer.class.php');

$timer = new timer();
$timer->start();

$options = [];

$request = new Tiki_Request();

if ($request->hasProperty('lang')) {
    $options['lang'] = $request->getProperty('lang');
}

if ($request->hasProperty('outputFiles')) {
    $options['outputFiles'] = $request->getProperty('outputFiles');
}

$excludeDirs = [
    'dump' , 'img', 'lang', 'bin', 'installer/schema',
    'vendor_bundled', 'vendor', 'vendor_extra', 'vendor_custom',
     'lib/test',	'temp', 'permissioncheck',
    'storage',	'tiki_tests', 'doc', 'db', 'lib/openlayers', 'tests', 'modules/cache'
];
$excludeDirs = array_filter($excludeDirs, 'is_dir'); // only keep in the exclude list if the dir exists

// Files are processed after the base directory, so adding a file here allows to scan it even if its directory was excluded.
$includeFiles = [
    './lang/langmapping.php', './img/flags/flagnames.php'
];

// command-line only options
if (php_sapi_name() == 'cli') {
    if ($request->hasProperty('baseDir')) {
        $options['baseDir'] = $request->getProperty('baseDir');

        // when a custom base dir is set, default $includeFiles and $excludeDirs are not used
        $includeFiles = [];
        $excludeDirs = [];
    }

    if ($request->hasProperty('excludeDirs')) {
        $excludeDirs = explode(',', $request->getProperty('excludeDirs'));
    }

    if ($request->hasProperty('includeFiles')) {
        $includeFiles = explode(',', $request->getProperty('includeFiles'));
    }

    if ($request->hasProperty('fileName')) {
        $options['fileName'] = $request->getProperty('fileName');
    }
}

$getStrings = new Language_GetStrings(new Language_CollectFiles, new Language_WriteFile_Factory, $options);

$getStrings->addFileType(new Language_FileType_Php);
$getStrings->addFileType(new Language_FileType_Tpl);

// skip the following directories
$getStrings->collectFiles->setExcludeDirs($excludeDirs);

// manually add the following files from skipped directories
$getStrings->collectFiles->setIncludeFiles($includeFiles);

echo formatOutput("Languages: " . implode(' ', $getStrings->getLanguages()) . "\n");

$getStrings->run();

echo formatOutput("\nTotal time spent: " . $timer->stop() . " seconds\n");

/**
 * @param $string
 * @return string
 */
function formatOutput($string)
{
    if (php_sapi_name() == 'cli') {
        return $string;
    }

    return nl2br($string);
}
