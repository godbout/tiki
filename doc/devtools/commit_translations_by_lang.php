<?php

/**
 * Created by PhpStorm.
 * User: Alexandre
 * Date: 2/22/2019
 * Time: 12:28 PM
 */
//die("REMOVE THIS LINE TO USE THE SCRIPT.\n");

//if (! isset($argv[1])) {
//    echo "\nUsage: php export_all_translations_to_file.php\n";
//    echo "Example: php export_translations_to_file.php\n";
//    die;
//}

require_once('tiki-setup.php');
require_once('lang/langmapping.php');
require_once('lib/language/Language.php');
require_once('lib/language/LanguageTranslations.php');
require_once('svntools.php');


$langlib = new LanguageTranslations();
$langguage = new Language();
$retour = $langlib->getAllDbTranslations();
$user_translations = array();

function  rangeByLang($changes, $lang) {
	$usernames = array();
	foreach ($changes['translations'] as $change) {
		if ($change['lang'] == $lang){
			array_push($usernames, $change['user']);
		}
	}
	$usernames = array_unique($usernames, SORT_REGULAR);
	$specific_tring =join(",", $usernames);
	return $specific_tring;
}

$final_commit_list = array();

foreach ($retour['translations'] as $current_lang) {
	$usernames = rangeByLang($retour, $current_lang['lang']);
	array_push($final_commit_list, array('lang'=> $current_lang['lang'], 'usernames' => $usernames));
}
$final_commit_list = array_unique($final_commit_list, SORT_REGULAR);



foreach ($final_commit_list as $trans) {
	$langmap = $langguage::get_language_map();
	$lang_found = $langmap[$trans['lang']];
	array_push($user_translations, array("user" => $trans['usernames'], "lang" => $lang_found, "langdir"=>$trans['lang']));
}
if (has_uncommited_changes(".")) {
	echo "there is uncommitted changes \n";
	foreach ($user_translations as $all_translations) {
		$final_phrase = "[TRA] Automatic commit of $all_translations[lang] translation contributed By $all_translations[user] to http://i18n.tiki.org";
		$return_value = commit_specific_lang($all_translations['langdir'], $final_phrase);
		echo "commit :  " . $return_value;
	}
} else {
	echo "There is not translation to commit";
}
