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
$user_translations = [];
foreach ($retour['translations'] as $trans) {
	$langmap = $langguage::get_language_map();
	$lang_found = $langmap[$trans['lang']];
	array_push($user_translations, ["user" => $trans['user'], "lang" => $lang_found]);
}
$user_translations = array_unique($user_translations, SORT_REGULAR);
$final_phrase = "";

foreach ($user_translations as $all_translations) {
	$final_phrase .= "[TRA] Automatic commit of $all_translations[lang] translation contributed By $all_translations[user] to http://i18n.tiki.org \n";
}

if (has_uncommited_changes(".")) {
	$langlib = new LanguageTranslations();
	echo "there is uncommitted changes \n";
	$retour = $langlib->getDbTranslations();
	 $return_value = commit_lang($final_phrase);
	 echo "commit :  " . $return_value;
} else {
	echo "There is not translation to commit";
}
