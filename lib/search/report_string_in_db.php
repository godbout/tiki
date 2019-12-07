<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

if (! empty($_POST['string_in_db_search'])) {
	$searchString = $_POST['string_in_db_search'];
	$result = searchAllDB($searchString);
	$tableCount = tableCount($result);
	$smarty->assign('searchResult', $result);
	$smarty->assign('tableCount', $tableCount);
} elseif (! empty($_POST['query'])) {
	$query = $_POST['query'];
	$table = $_POST['table'];
	sanitizeTableName($table);
	$column = $_POST['column'];
	sanitizeColumnName($column, $table);

	$headers = [];
	$sql2 = "SHOW COLUMNS FROM " . $table;
	$rs2 = $tikilib->fetchAll($sql2);
	foreach ($rs2 as $key2 => $val2) {
		$vals2 = array_values($val2);
		$colum = $vals2[0];
		$type = $vals2[1];
		$headers[] = $colum;
	}
	$smarty->assign('tableHeaders', $headers);

	$tableData = [];
	$qrySearch = '%' . $query . '%';
	$args = [$qrySearch];
	$sql = "select * from `" . $table . "` where `" . $column . "` like ?";
	$rs = $tikilib->fetchAll($sql, $args);
	foreach ($rs as $row) {
		if ($table == 'tiki_pages') {
			$stringpos = strpos($row['data'], $_POST['query']);
			$stringend = $stringpos + strlen($_POST['query']);
			$startsnip = max($stringpos - 100, 0);
			$endsnip = $stringend + 100;
			$length = ($endsnip - $startsnip);
			$snippet = substr($row['data'], $startsnip, $length);
			$snippet = str_replace($_POST['query'],"<span class='highlight'>".$_POST['query']."</span>", $snippet);
			if ($startsnip > 0) {
				$snippet = '...' . $snippet;
			}
			if ($endsnip < strlen($row['data'])) {
				$snippet = $snippet . '...';
			}
			$row['snippet'] = $snippet;
		}
		$tableData[] = $row;
	}
	$smarty->assign('tableData', $tableData);
	$smarty->assign('tableName', $table);
	$smarty->assign('columnName', $column);
}

/**
*	return array (table, attribute, occurrence count)
*/
function searchAllDB($search)
{
	global $tikilib, $prefslib;

	$result = [];
	$out = '';

	$sql = "show tables";
	$rs = $tikilib->fetchAll($sql);
	$preferenceTables = [
		'tiki_preferences'      => 'name',
		'tiki_user_preferences' => 'prefName',
	];

	foreach ($rs as $key => $val) {
		$vals = array_values($val);
		$table = $vals[0];
		if (substr($table, 0, 6) == 'index_' && substr($table,0, 10) !== 'index_pref') {
			continue;
		}
		$sql2 = "SHOW COLUMNS FROM `$table`";
		$rs2 = $tikilib->fetchAll($sql2);
		foreach ($rs2 as $key2 => $val2) {
			$toExclude = [];
			$vals2 = array_values($val2);
			$colum = $vals2[0];
			$type = $vals2[1];

			if (in_array($table, array_keys($preferenceTables)) && $colum == $preferenceTables[$table]) {
				$preferences = TikiDb::get()->fetchAll("SELECT $preferenceTables[$table] FROM $table");
				$toExclude = $prefslib->filterHiddenPreferences($preferences);
			}

			if (isTextType($type)) {
				$sql_search_fields = [];
				$qrySearch = '%' . $search . '%';
				$args = [$qrySearch];
				$sql_search_fields[] = "`" . $colum . "` like ?"; // '%" . str_replace("'", "''", $search) . "%'";
				$sql_search = "select * from `$table` where (";
				$sql_search .= implode(" OR ", $sql_search_fields) . ')';

				if (! empty($toExclude)) {
					$sql_search .= ' AND `' . $colum . '` NOT IN (?)';
					$args[] = implode(', ', $toExclude);
				}

				$rs3 = $tikilib->fetchAll($sql_search, $args);
				if (! empty($rs3)) {
					$result[] = ['table' => $table, 'column' => $colum, 'occurrences' => count($rs3)];
				}
			}
		}
	}
	return $result;
}

/**
*	return array (table, occurrence count)
*/
function tableCount($searchResult)
{
	$tableCount = [];
	$countLast = 0;
	$last = '';
	foreach ($searchResult as $thisResult) {
		$table = $thisResult['table'];
		if ($table <> $last && $last <> '') {
			$tableCount["$last"] = $countLast;
			$countLast = 0;
		}
		$last = $table;
		$countLast++;
	}
	$tableCount["$last"] = $countLast;

	return $tableCount;
}


function isTextType($type)
{
	if (strpos($type, 'char') !== false) {
		return true;
	}
	if (strpos($type, 'text') !== false) {
		return true;
	}
	return false;
}


function sanitizeTableName($table)
{
	global $tikilib;
	$validTables = $tikilib->listTables();
	if (! in_array($table, $validTables)) {
		throw new Exception(tra('Invalid table name:') . ' ' . htmlentities($table));
	}
}

function sanitizeColumnName($column, $table)
{
	global $tikilib;
	$colsinfo = $tikilib->fetchAll("SHOW COLUMNS FROM $table");
	foreach ($colsinfo as $col) {
		$colnames[] = $col['Field'];
	}
	if (! in_array($column, $colnames)) {
		throw new Exception(tra('Invalid column name:') . ' ' . htmlentities($column));
	}
}
