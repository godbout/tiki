<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: 20160604_remove_unwanted_files_tiki.php 66117 2018-04-19 19:10:07Z luciash $

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * We change the client_charset value from 'utf8' to 'utf8mb4' if it is still 'utf8'.
 * Since db/local.php is a very important file, we keep a backup if we edit it.
 *
 * @param $installer
 */
function upgrade_20181127_convert_db_local_to_utf8mb4_tiki($installer)
{

	$localfile = "db/local.php";
	$date = date("Ymd");
	$time = date("His");
	// Unique name so as to avoid losing the backup in case something goes wrong
	$backuplocalfile = "db/obsolete_${date}-${time}_local.php";

	// Parse local.php file and look for obsolete 'utf8' client_charset value
	$contents = @file($localfile);

	if ( $contents !== false ) {
		$last_matched_line = "";
		$last_matched_charset = "";
		foreach($contents as $key => $line) {
			// Detect last value and line for client_charset
			$extract = preg_match("/^[ 	]*[$]client_charset[ ]*=[ ]*'(.*)';/", $line, $match );
			if (isset($match[1])) {
				// echo "Match: " . $match[1] . PHP_EOL;
				$last_matched_line = $key;
				$last_matched_charset = $match[1];
			}
		}
	} else {
		echo "Failed to read 'db/local.php'" . PHP_EOL;
		echo "Please edit db/local.php manually and change the \$client_charset value from 'utf8' to 'utf8mb4'" . PHP_EOL;
		return FALSE;
	}

	// If obsolete 'utf8' client_charset value was found, backup and edit
	if ($last_matched_charset == 'utf8') {
		// Backup db/local.php
		if (!rename($localfile,$backuplocalfile)) {
			echo "Failed to backup 'db/local.php'" . PHP_EOL;
			echo "Please edit db/local.php manually and change the \$client_charset value from 'utf8' to 'utf8mb4'" . PHP_EOL;
			return FALSE;
		}
		// Rewrite the new db/local.php
		$handle = fopen($localfile,'xb');
		$contents[$last_matched_line] = "// Commented by installer on ${date} // " . $contents[$last_matched_line] . "\$client_charset='utf8mb4';" . PHP_EOL;
		reset($contents);
		foreach($contents as $key => $line) {
			fwrite($handle, $line);
		}
		fclose($handle);
		return TRUE;
	} else if ($last_matched_charset == 'utf8mb4') {
		// Nothing to do, leave with success return code
		// echo "debug nothing to do\n";
		return TRUE;
	}

	// This place should not be reached
	return FALSE;

}
