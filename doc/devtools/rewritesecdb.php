<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


/**
 *
 * Currently not in use. Please use php release.php --only-secdb to generate a secdb file.
 *
 */
$version = $_SERVER['argv'][1];

rewriteSecdb('tiki-' . $version . '/db/tiki-secdb_' . $version . '_mysql.sql', 'tiki-' . $version, $version);

/**
 * @param $file
 * @param $root
 * @param $version
 */
function rewriteSecdb($file, $root, $version)
{
    $file_exists = @file_exists($file);
    $fp = @fopen($file, 'w+') or print("The SecDB file $file is not writable or can't be created.");
    $queries = [];
    md5CheckDir($root, $root, $version, $queries);

    if (! empty($queries)) {
        sort($queries);
        fwrite($fp, "start transaction;\n");
        fwrite($fp, "DELETE FROM `tiki_secdb` WHERE `tiki_version` = '$version';\n\n");
        foreach ($queries as $q) {
            fwrite($fp, "$q\n");
        }
        fwrite($fp, "commit;\n");
    }

    fclose($fp);

    if ($file_exists) {
        echo(">> Existing SecDB file '$file' has been updated.\n\n");
    }
}

/**
 * @param $root
 * @param $dir
 * @param $version
 * @param $queries
 */
function md5CheckDir($root, $dir, $version, &$queries)
{
    $d = dir($dir);
    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_dir($entry)) {
            // do not descend and no CVS/Subversion files
            if ($e != '..' && $e != '.' && $e != 'CVS' && $e != '.svn') {
                md5CheckDir($root, $entry, $version, $queries);
            }
        } else {
            if (preg_match('/\.(sql|css|tpl|js|php)$/', $e) && realpath($entry) != __FILE__ && $entry != './db/local.php') {
                $file = '.' . substr($entry, strlen($root));
                $hash = md5_file($entry);
                $queries[] = "INSERT INTO `tiki_secdb` (`filename`, `md5_value`, `tiki_version`) VALUES('$file', '$hash', '$version');";
            }
        }
    }
    $d->close();
}
