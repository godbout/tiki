<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
	die('This script may only be included.');
}

function svn_last_update()
{
	$cachelib = TikiLib::lib('cache');
	$cache = $cachelib->getSerialized('svn_last_update');
	
	if ($cache && is_readable('.svn') && $cache['wcdb_mtime'] < filemtime('.svn/wc.db')) {
		$cache = false;
	}

	if ($cache) {
		return $cache;
	} else {
		$cache = [];
	}

	if (is_readable('.svn')) {
		$svn = [];
		if (is_readable('.svn/entries')) {
			$fp = fopen('.svn/entries', 'r');
			for ($i = 0; 10 > $i && $line = fgets($fp, 80); ++$i) {
				$svn[] = $line;
			}
			fclose($fp);
		}

		if (count($svn) > 2) {
			// Standard SVN client
			$cache['svnrev'] = $svn[3];
			$cache['lastup'] = strtotime($svn[9]);
		} else {
			// Check for Tortoise 1.7+ SVN client, if sqlite3 is present
			if (extension_loaded('sqlite3')) {
				$location = '.svn/wc.db';
				if (is_file($location)) {
					$handle = new SQLite3($location);

					// Assign svnrev
					$query = "select max(changed_revision) as svnrev from nodes";
					$result = $handle->query($query);
					$svnrev = $lastupTime = $strDT = '';
					if ($result) {
						$resx = $result->fetchArray(SQLITE3_ASSOC);
						$cache['svnrev'] = $resx['svnrev'];
					}

					// Assign lastup
					$query = "select max(changed_date)/1000000 as lastup from nodes";
					$result = $handle->query($query);
					if ($result) {
						$resx = $result->fetchArray(SQLITE3_ASSOC);
						$lastupTime = (int)$resx['lastup'];
						$dt = new DateTime();
						$dt->setTimestamp($lastupTime);
						$cache['lastup'] = $dt->format(DateTime::ISO8601);
					}

					// Release/Unlock the database afterwards
					$handle->close();
				}
			}
		}
		$cache['wcdb_mtime'] = filemtime('.svn/wc.db');
	}

	if (! $cache) {
		$cache['lastup'] = null;
		$cache['svnrev'] = null;
		$cache['wcdb_mtime'] = null;
	}

	$cachelib->cacheItem('svn_last_update', serialize($cache));

	return $cache;
}
