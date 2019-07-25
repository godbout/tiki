<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

/**
 * @package   Tiki
 * @subpackage    Language
 *
 * Generic methods for managing languages in Tiki
 */
class Language extends TikiDb_Bridge
{

	/**
	 * Characters at end of translation string that are not a part of the translation
	 */
	const punctuations = [':', '!', ';', '.', ',', '?'];

	/**
	 * Return a list of languages available in Tiki
	 *
	 * @return array list of languages
	 */
	public static function getLanguages()
	{
		require_once('lib/init/tra.php');
		global $langmapping;
		require_once('lang/langmapping.php');
		return array_keys($langmapping);
	}

	/**
	 * Return a list of languages with translations
	 * in the database
	 *
	 * @return array list of languages with at least one string translated
	 */
	public static function getDbTranslatedLanguages()
	{
		$lang = new Language();
		$languages = [];
		$result = $lang->fetchAll('SELECT DISTINCT `lang` FROM `tiki_language` ORDER BY `lang` asc');

		foreach ($result as $res) {
			$languages[] = $res['lang'];
		}

		return $languages;
	}

	/**
	 * Translate characters for usage in a double-quoted PHP string (per "Escaped characters" table in http://php.net/manual/language.types.string.php#language.types.string.syntax.doublein )
	 * $string = str_replace ("\n", '\n',   $string);
	 * $string = str_replace ("\r", '\r',   $string);
	 * $string = str_replace ("\t", '\t',   $string);
	 * $string = str_replace ('\\', '\\\\', $string);
	 * $string = str_replace ('$',  '\$',   $string);
	 * $string = str_replace ('"',  '\"',   $string);
	 * We skip the exotic regexps for octal an hexadecimal
	 * notation - \{0-7]{1,3} and \x[0-9A-Fa-f]{1,2} -
	 * since they should not apper in english strings.
	 *
	 * @param string $string
	 * @return string modified string;
	 */
	public static function addPhpSlashes($string)
	{
		$addPHPslashes = [
			"\n" => '\n',
			"\r" => '\r',
			"\t" => '\t',
			'\\' => '\\\\',
			'$'  => '\$',
			'"'  => '\"'
		];

		return strtr($string, $addPHPslashes);
	}

	/**
	 * $string = str_replace ('\n',   "\n", $string);
	 * $string = str_replace ('\r',   "\r", $string);
	 * $string = str_replace ('\t',   "\t", $string);
	 * $string = str_replace ('\\\\', '\\', $string);
	 * $string = str_replace ('\$',   '$',  $string);
	 * $string = str_replace ('\"',   '"',  $string);
	 * We skip the exotic regexps for octal an hexadecimal
	 * notation - \{0-7]{1,3} and \x[0-9A-Fa-f]{1,2} - since they
	 * should not appear in english strings.
	 */
	public static function removePhpSlashes($string)
	{
		$removePHPslashes = [
			'\n'   => "\n",
			'\r'   => "\r",
			'\t'   => "\t",
			'\\\\' => '\\',
			'\$'   => '$',
			'\"'   => '"'
		];

		if (preg_match('/\{0-7]{1,3}|\x[0-9A-Fa-f]{1,2}/', $string, $match)) {
			trigger_error("Octal or hexadecimal string '" . $match[1] . "' not supported", E_WARNING);
		}

		return strtr($string, $removePHPslashes);
	}

	/**
	 * isLanguageRTL
	 * Determine if a language is an RTL language
	 *
	 * @param mixed $langCode Language code to check, e.g. "en"
	 * @return bool true if the language is RTL, otherwise false
	 *
	 */
	public static function isLanguageRTL($langCode)
	{
		switch ($langCode) {
			case 'ar':
			case 'fa':
			case 'he':
			case 'ku':
			case 'ug':
				return true;
		}
		return false;
	}


	/**
	 * isRTL
	 * Determine if the current language is RTL
	 * @return bool true if the language is RTL, otherwise false
	*/
	public static function isRTL()
	{
		global $prefs;
		return self::isLanguageRTL($prefs['language']);
	}

	/**
	 * @param bool $path
	 * @param null $short
	 * @param bool $all
	 * @return array|mixed
	 */
	static function list_languages($path = false, $short = null, $all = false)
	{
		global $prefs;

		$args = func_get_args();
		$key = 'disk_languages' . implode(',', $args) . $prefs['language'];
		$cachelib = TikiLib::lib('cache');

		if (! $languages = $cachelib->getSerialized($key)) {
			$languages = self::list_disk_languages($path);
			$languages = self::format_language_list($languages, $short, $all);

			$cachelib->cacheItem($key, serialize($languages));
		}

		return $languages;
	}

	/**
	 * @param $path
	 * @return array
	 */
	private static function list_disk_languages($path)
	{
		$languages = [];

		if (! $path) {
			$path = "lang";
		}

		if (! is_dir($path)) {
			return [];
		}

		$h = opendir($path);

		while ($file = readdir($h)) {
			if (strpos($file, '.') === false && $file != 'CVS' && $file != 'index.php' && is_dir("$path/$file") && file_exists("$path/$file/language.php")) {
				$languages[] = $file;
			}
		}

		closedir($h);

		return $languages;
	}

	/**
	 * @return array
	 */
	static function get_language_map()
	{
		$languages = self::list_languages();

		$map = [];
		foreach ($languages as $lang) {
			$map[$lang['value']] = $lang['name'];
		}

		return $map;
	}

	/**
	 * @param $language
	 * @return bool
	 */
	function is_valid_language($language)
	{
		return preg_match("/^[a-zA-Z-_]*$/", $language)
			&& file_exists('lang/' . $language . '/language.php');
	}

	/**
	 * Comparison function used to sort languages by their name in the current locale.
	 * @param $a
	 * @param $b
	 * @return int
	 */
	static function formatted_language_compare($a, $b)
	{
		return strcasecmp($a['name'], $b['name']);
	}

	/**
	 * Returns a list of languages formatted as a twodimensionel array with 'value' being the language code and 'name' being the name of the language.
	 * @param $languages
	 * @param null|string $short If = 'y' returns only the localized language names array
	 * @param bool $all
	 * @return array
	 */
	static function format_language_list($languages, $short = null, $all = false)
	{
		// The list of available languages so far with both English and
		// translated names.
		global $langmapping, $prefs;
		include("lang/langmapping.php");
		$formatted = [];

		// run through all the language codes:
		if (isset($short) && $short == "y") {
			foreach ($languages as $lc) {
				if ($prefs['restrict_language'] === 'n' || empty($prefs['available_languages']) || (! $all and in_array($lc, $prefs['available_languages']))) {
					if (isset($langmapping[$lc])) {
						$formatted[] = ['value' => $lc, 'name' => $langmapping[$lc][0]];
					} else {
						$formatted[] = ['value' => $lc, 'name' => $lc];
					}
				}
				usort($formatted, ['language', 'formatted_language_compare']);
			}
			return $formatted;
		}
		foreach ($languages as $lc) {
			if ($prefs['restrict_language'] === 'n' || empty($prefs['available_languages']) || (! $all and in_array($lc, $prefs['available_languages'])) or $all) {
				if (isset($langmapping[$lc])) {
					// known language
					if ($langmapping[$lc][0] == $langmapping[$lc][1]) {
						// Skip repeated text, 'English (English, en)' looks silly.
						$formatted[] = [
								'value' => $lc,
								'name' => $langmapping[$lc][0] . " ($lc)"
								];
					} else {
						$formatted[] = [
								'value' => $lc,
								'name' => $langmapping[$lc][1] . " (" . $langmapping[$lc][0] . ', ' . $lc . ")"
								];
					}
				} else {
					// unknown language
					$formatted[] = [
							'value' => $lc,
							'name' => tra("Unknown language") . " ($lc)"
							];
				}
			}
		}

		// Sort the languages by their name in the current locale
		usort($formatted, ['language', 'formatted_language_compare']);
		return $formatted;
	}

	/**
	 * @param $lg
	 * @param bool $useCache
	 * @return array|bool|false|string
	 * @throws Exception
	 */
	public static function loadExtensions($lg, $useCache = true){
		$language = [];
		foreach(\Tiki\Package\ExtensionManager::getEnabledPackageExtensions() as $package) {
			$lang = null;

			$file = sprintf('%s/lang/%s/language.php', $package['path'], $lg);
			if (!file_exists($file)) {
				continue;
			}

			include $file;

			if (!isset($lang) || !is_array($lang)) {
				continue;
			}

			$language = array_merge($language, $lang);
		}

		return $language;
	}

	/**
	 * Load additional language files that are located in theme folder
	 *
	 * @param $lang
	 * @param $themeName
	 * @throws Exception
	 */
	public function loadThemeOverrides($lang, $themeName)
	{
		global ${"lang_$lang"};

		$themeLib = TikiLib::lib('theme');
		$themePath = rtrim($themeLib->get_theme_path($themeName), '/');
		$themeLangPath = implode('/', [$themePath, 'lang', $lang, 'language.php']);

		if (file_exists($themeLangPath)) {
			require $themeLangPath;

			if (isset($language) && is_array($language)) {
				${"lang_$lang"} = array_merge(${"lang_$lang"}, $language);
			}
		}
	}

	/**
	 * Provides a method of retrieving language code information. Intended to be used to retrieve ISO codes and names.
	 * Adapted to include Tesseract OCR Specific language codes.
	 *
	 * @param null|string $iso	Specifies what language code is used. Loosly based on ISO 639-1 & ISO 639-2 values. Valid options = (iso2, iso3 | null)
	 *                         'iso2'			2 string language code. Will replace the default 3 string language key.
	 *                         					Selecting this option will remove all languages without a 2-digit code.
	 *                         'iso3'			3+ string language code. WIll remove the 2 string language code.
	 *                         null				Will return both language codes with a 3 string language code as the key.
	 * @param null|string $name Specifies how names are formatted. Valid options = (translated, native, string | null)
	 *                          'translated' 	Only the translatable keys will be passed
	 *                          'native' 		Only the title in the native language will be passed
	 *                          'string' 		The translated and native will be combined into a single string.
	 *                          				This also removes the un-selected iso and returns the name as a 2 dimensional array
	 *                          null			Will return both language values as separate array values
	 *
	 * @return array Formatted associative array of languages
	 *
	 * @throws Exception if invalid options are selected
	 */

	public function listISOLangs($iso = null, $name = null)
	{
		if ($iso !== null && $iso !== 'iso2' && $iso !== 'iso3'){
			throw new Exception('invalid option for iso');
		}

		if ($name !== null && $name !== 'translated' && $name !== 'native' && $name !== 'string'){
			throw new Exception('invalid option for name');
		}

		$langArray = [
			'abk' => [ 'iso2' =>'ab', 'tra' => tr('аҧсуа бызшәа, аҧсшәа'), 'nat' => 'ab'],
			'aar' => [ 'iso2' =>'aa', 'tra' => tr('Afar'), 'nat' => 'Afaraf'],
			'afr' => [ 'iso2' =>'af', 'tra' => tr('Afrikaans'), 'nat' => 'Afrikaans'],
			'aka' => [ 'iso2' =>'ak', 'tra' => tr('Akan'), 'nat' => 'Akan'],
			'sqi' => [ 'iso2' =>'sq', 'tra' => tr('Albanian'), 'nat' => 'Shqip'],
			'amh' => [ 'iso2' =>'am', 'tra' => tr('Amharic'), 'nat' => 'አማርኛ'],
			'ara' => [ 'iso2' =>'ar', 'tra' => tr('Arabic'), 'nat' => 'العربية'],
			'arg' => [ 'iso2' =>'an', 'tra' => tr('Aragonese'), 'nat' => 'aragonés'],
			'hye' => [ 'iso2' =>'hy', 'tra' => tr('Armenian'), 'nat' => 'Հայերեն'],
			'asm' => [ 'iso2' =>'as', 'tra' => tr('Assamese'), 'nat' => 'অসমীয়া'],
			'ava' => [ 'iso2' =>'av', 'tra' => tr('Avaric'), 'nat' => 'авар мацӀ, магӀарул мацӀ'],
			'ave' => [ 'iso2' =>'ae', 'tra' => tr('Avestan'), 'nat' => 'avesta'],
			'aym' => [ 'iso2' =>'ay', 'tra' => tr('Aymara'), 'nat' => 'aymar aru'],
			'aze' => [ 'iso2' =>'az', 'tra' => tr('Azerbaijani'), 'nat' => 'azərbaycan dili'],
			'bam' => [ 'iso2' =>'bm', 'tra' => tr('Bambara'), 'nat' => 'bamanankan'],
			'bak' => [ 'iso2' =>'ba', 'tra' => tr('Bashkir'), 'nat' => 'башҡорт теле'],
			'eus' => [ 'iso2' =>'eu', 'tra' => tr('Basque'), 'nat' => 'euskara, euskera'],
			'bel' => [ 'iso2' =>'be', 'tra' => tr('Belarusian'), 'nat' => 'беларуская мова'],
			'ben' => [ 'iso2' =>'bn', 'tra' => tr('Bengali'), 'nat' => 'বাংলা'],
			'bih' => [ 'iso2' =>'bh', 'tra' => tr('Bihari languages'), 'nat' => 'भोजपुरी'],
			'bis' => [ 'iso2' =>'bi', 'tra' => tr('Bislama'), 'nat' => 'Bislama'],
			'bos' => [ 'iso2' =>'bs', 'tra' => tr('Bosnian'), 'nat' => 'bosanski jezik'],
			'bre' => [ 'iso2' =>'br', 'tra' => tr('Breton'), 'nat' => 'brezhoneg'],
			'bul' => [ 'iso2' =>'bg', 'tra' => tr('Bulgarian'), 'nat' => 'български език'],
			'mya' => [ 'iso2' =>'my', 'tra' => tr('Burmese'), 'nat' => 'ဗမာစာ'],
			'cat' => [ 'iso2' =>'ca', 'tra' => tr('Catalan, Valencian'), 'nat' => 'català, valencià'],
			'cha' => [ 'iso2' =>'ch', 'tra' => tr('Chamorro'), 'nat' => 'Chamoru'],
			'che' => [ 'iso2' =>'ce', 'tra' => tr('Chechen'), 'nat' => 'нохчийн мотт'],
			'nya' => [ 'iso2' =>'ny', 'tra' => tr('Chichewa, Chewa, Nyanja'), 'nat' => 'chiCheŵa, chinyanja'],
			'zho' => [ 'iso2' =>'zh', 'tra' => tr('Chinese'), 'nat' => '中文; 汉语; 漢語'],
			'chi' => [ 'iso2' =>'zh', 'tra' => tr('Chinese'), 'nat' => '中文; 汉语; 漢語'],
			'chi_sim' => [ 'iso2' =>'zh', 'tra' => tr('Chinese Simplified'), 'nat' => '中文; 汉语; 漢語'],
			'chi_sim_vert' => [ 'iso2' =>'zh', 'tra' => tr('Chinese Simplified Vertical'), 'nat' => '中文; 汉语; 漢語'],
			'chi_tra' => [ 'iso2' =>'zh', 'tra' => tr('Chinese Traditional'), 'nat' => '中文; 汉语; 漢語'],
			'chi_tra_vert' => [ 'iso2' =>'zh', 'tra' => tr('Chinese Traditional Vertical'), 'nat' => '中文; 汉语; 漢語'],
			'chv' => [ 'iso2' =>'cv', 'tra' => tr('Chuvash'), 'nat' => 'чӑваш чӗлхи'],
			'cor' => [ 'iso2' =>'kw', 'tra' => tr('Cornish'), 'nat' => 'Kernewek'],
			'cos' => [ 'iso2' =>'co', 'tra' => tr('Corsican'), 'nat' => 'corsu, lingua corsa'],
			'cre' => [ 'iso2' =>'cr', 'tra' => tr('Cree'), 'nat' => 'ᓀᐦᐃᔭᐍᐏᐣ'],
			'hrv' => [ 'iso2' =>'hr', 'tra' => tr('Croatian'), 'nat' => 'hrvatski jezik'],
			'ces' => [ 'iso2' =>'cs', 'tra' => tr('Czech'), 'nat' => 'čeština, český jazyk'],
			'dan' => [ 'iso2' =>'da', 'tra' => tr('Danish'), 'nat' => 'dansk'],
			'div' => [ 'iso2' =>'dv', 'tra' => tr('Divehi, Dhivehi, Maldivian'), 'nat' => 'ދިވެހި'],
			'nld' => [ 'iso2' =>'nl', 'tra' => tr('Dutch, Flemish'), 'nat' => 'Nederlands, Vlaams'],
			'dzo' => [ 'iso2' =>'dz', 'tra' => tr('Dzongkha'), 'nat' => 'རྫོང་ཁ'],
			'eng' => [ 'iso2' =>'en', 'tra' => tr('English'), 'nat' => 'English'],
			'epo' => [ 'iso2' =>'eo', 'tra' => tr('Esperanto'), 'nat' => 'Esperanto'],
			'est' => [ 'iso2' =>'et', 'tra' => tr('Estonian'), 'nat' => 'eesti, eesti keel'],
			'ewe' => [ 'iso2' =>'ee', 'tra' => tr('Ewe'), 'nat' => 'Eʋegbe'],
			'fao' => [ 'iso2' =>'fo', 'tra' => tr('Faroese'), 'nat' => 'føroyskt'],
			'fij' => [ 'iso2' =>'fj', 'tra' => tr('Fijian'), 'nat' => 'vosa Vakaviti'],
			'fin' => [ 'iso2' =>'fi', 'tra' => tr('Finnish'), 'nat' => 'suomi, suomen kieli'],
			'fra' => [ 'iso2' =>'fr', 'tra' => tr('French'), 'nat' => 'français, langue française'],
			'ful' => [ 'iso2' =>'ff', 'tra' => tr('Fulah'), 'nat' => 'Fulfulde, Pulaar, Pular'],
			'glg' => [ 'iso2' =>'gl', 'tra' => tr('Galician'), 'nat' => 'Galego'],
			'kat' => [ 'iso2' =>'ka', 'tra' => tr('Georgian'), 'nat' => 'ქართული'],
			'deu' => [ 'iso2' =>'de', 'tra' => tr('German'), 'nat' => 'Deutsch'],
			'ell' => [ 'iso2' =>'el', 'tra' => tr('Greek, Modern (1453-)'), 'nat' => 'ελληνικά'],
			'grn' => [ 'iso2' =>'gn', 'tra' => tr('Guarani'), 'nat' => 'Avañeẽ'],
			'guj' => [ 'iso2' =>'gu', 'tra' => tr('Gujarati'), 'nat' => 'ગુજરાતી'],
			'hat' => [ 'iso2' =>'ht', 'tra' => tr('Haitian, Haitian Creole'), 'nat' => 'Kreyòl ayisyen'],
			'hau' => [ 'iso2' =>'ha', 'tra' => tr('Hausa'), 'nat' => '(Hausa) هَوُسَ'],
			'heb' => [ 'iso2' =>'he', 'tra' => tr('Hebrew'), 'nat' => 'עברית'],
			'her' => [ 'iso2' =>'hz', 'tra' => tr('Herero'), 'nat' => 'Otjiherero'],
			'hin' => [ 'iso2' =>'hi', 'tra' => tr('Hindi'), 'nat' => 'हिन्दी, हिंदी'],
			'hmo' => [ 'iso2' =>'ho', 'tra' => tr('Hiri Motu'), 'nat' => 'Hiri Motu'],
			'hun' => [ 'iso2' =>'hu', 'tra' => tr('Hungarian'), 'nat' => 'magyar'],
			'ina' => [ 'iso2' =>'ia', 'tra' => tr('Interlingua (International Auxiliary Language Association)'), 'nat' => 'Interlingua'],
			'ind' => [ 'iso2' =>'id', 'tra' => tr('Indonesian'), 'nat' => 'Bahasa Indonesia'],
			'ile' => [ 'iso2' =>'ie', 'tra' => tr('Interlingue, Occidental'), 'nat' => '(originally:) Occidental, (after WWII:) Interlingue'],
			'gle' => [ 'iso2' =>'ga', 'tra' => tr('Irish'), 'nat' => 'Gaeilge'],
			'ibo' => [ 'iso2' =>'ig', 'tra' => tr('Igbo'), 'nat' => 'Asụsụ Igbo'],
			'ipk' => [ 'iso2' =>'ik', 'tra' => tr('Inupiaq'), 'nat' => 'Iñupiaq, Iñupiatun'],
			'ido' => [ 'iso2' =>'io', 'tra' => tr('Ido'), 'nat' => 'Ido'],
			'isl' => [ 'iso2' =>'is', 'tra' => tr('Icelandic'), 'nat' => 'Íslenska'],
			'ita' => [ 'iso2' =>'it', 'tra' => tr('Italian'), 'nat' => 'Italiano'],
			'iku' => [ 'iso2' =>'iu', 'tra' => tr('Inuktitut'), 'nat' => 'ᐃᓄᒃᑎᑐᑦ'],
			'jpn' => [ 'iso2' =>'ja', 'tra' => tr('Japanese'), 'nat' => '日本語 (にほんご)'],
			'jav' => [ 'iso2' =>'jv', 'tra' => tr('Javanese'), 'nat' => 'ꦧꦱꦗꦮ, Basa Jawa'],
			'kal' => [ 'iso2' =>'kl', 'tra' => tr('Kalaallisut, Greenlandic'), 'nat' => 'kalaallisut, kalaallit oqaasii'],
			'kan' => [ 'iso2' =>'kn', 'tra' => tr('Kannada'), 'nat' => 'ಕನ್ನಡ'],
			'kau' => [ 'iso2' =>'kr', 'tra' => tr('Kanuri'), 'nat' => 'Kanuri'],
			'kas' => [ 'iso2' =>'ks', 'tra' => tr('Kashmiri'), 'nat' => 'कश्मीरी, ‫كشميري‬‎'],
			'kaz' => [ 'iso2' =>'kk', 'tra' => tr('Kazakh'), 'nat' => 'қазақ тілі'],
			'khm' => [ 'iso2' =>'km', 'tra' => tr('Central Khmer'), 'nat' => 'ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ'],
			'kik' => [ 'iso2' =>'ki', 'tra' => tr('Kikuyu, Gikuyu'), 'nat' => 'Gĩkũyũ'],
			'kin' => [ 'iso2' =>'rw', 'tra' => tr('Kinyarwanda'), 'nat' => 'Ikinyarwanda'],
			'kir' => [ 'iso2' =>'ky', 'tra' => tr('Kirghiz, Kyrgyz'), 'nat' => 'Кыргызча, Кыргыз тили'],
			'kom' => [ 'iso2' =>'kv', 'tra' => tr('Komi'), 'nat' => 'коми кыв'],
			'kon' => [ 'iso2' =>'kg', 'tra' => tr('Kongo'), 'nat' => 'Kikongo'],
			'kor' => [ 'iso2' =>'ko', 'tra' => tr('Korean'), 'nat' => '한국어'],
			'kur' => [ 'iso2' =>'ku', 'tra' => tr('Kurdish'), 'nat' => 'Kurdî, ‫کوردی‬‎'],
			'kua' => [ 'iso2' =>'kj', 'tra' => tr('Kuanyama, Kwanyama'), 'nat' => 'Kuanyama'],
			'lat' => [ 'iso2' =>'la', 'tra' => tr('Latin'), 'nat' => 'latine, lingua latina'],
			'ltz' => [ 'iso2' =>'lb', 'tra' => tr('Luxembourgish, Letzeburgesch'), 'nat' => 'Lëtzebuergesch'],
			'lug' => [ 'iso2' =>'lg', 'tra' => tr('Ganda'), 'nat' => 'Luganda'],
			'lim' => [ 'iso2' =>'li', 'tra' => tr('Limburgan, Limburger, Limburgish'), 'nat' => 'Limburgs'],
			'lin' => [ 'iso2' =>'ln', 'tra' => tr('Lingala'), 'nat' => 'Lingála'],
			'lao' => [ 'iso2' =>'lo', 'tra' => tr('Lao'), 'nat' => 'ພາສາລາວ'],
			'lit' => [ 'iso2' =>'lt', 'tra' => tr('Lithuanian'), 'nat' => 'lietuvių kalba'],
			'lub' => [ 'iso2' =>'lu', 'tra' => tr('Luba-Katanga'), 'nat' => 'Kiluba'],
			'lav' => [ 'iso2' =>'lv', 'tra' => tr('Latvian'), 'nat' => 'latviešu valoda'],
			'glv' => [ 'iso2' =>'gv', 'tra' => tr('Manx'), 'nat' => 'Gaelg, Gailck'],
			'mkd' => [ 'iso2' =>'mk', 'tra' => tr('Macedonian'), 'nat' => 'македонски јазик'],
			'mlg' => [ 'iso2' =>'mg', 'tra' => tr('Malagasy'), 'nat' => 'fiteny malagasy'],
			'msa' => [ 'iso2' =>'ms', 'tra' => tr('Malay'), 'nat' => 'Bahasa Melayu, ‫بهاس ملايو‬‎'],
			'mal' => [ 'iso2' =>'ml', 'tra' => tr('Malayalam'), 'nat' => 'മലയാളം'],
			'mlt' => [ 'iso2' =>'mt', 'tra' => tr('Maltese'), 'nat' => 'Malti'],
			'mri' => [ 'iso2' =>'mi', 'tra' => tr('Maori'), 'nat' => 'te reo Māori'],
			'mar' => [ 'iso2' =>'mr', 'tra' => tr('Marathi'), 'nat' => 'मराठी'],
			'mah' => [ 'iso2' =>'mh', 'tra' => tr('Marshallese'), 'nat' => 'Kajin M̧ajeļ'],
			'mon' => [ 'iso2' =>'mn', 'tra' => tr('Mongolian'), 'nat' => 'Монгол хэл'],
			'nau' => [ 'iso2' =>'na', 'tra' => tr('Nauru'), 'nat' => 'Dorerin Naoero'],
			'nav' => [ 'iso2' =>'nv', 'tra' => tr('Navajo, Navaho'), 'nat' => 'Diné bizaad'],
			'nde' => [ 'iso2' =>'nd', 'tra' => tr('North Ndebele'), 'nat' => 'isiNdebele'],
			'nep' => [ 'iso2' =>'ne', 'tra' => tr('Nepali'), 'nat' => 'नेपाली'],
			'ndo' => [ 'iso2' =>'ng', 'tra' => tr('Ndonga'), 'nat' => 'Owambo'],
			'nob' => [ 'iso2' =>'nb', 'tra' => tr('Norwegian Bokmål'), 'nat' => 'Norsk Bokmål'],
			'nno' => [ 'iso2' =>'nn', 'tra' => tr('Norwegian Nynorsk'), 'nat' => 'Norsk Nynorsk'],
			'nor' => [ 'iso2' =>'no', 'tra' => tr('Norwegian'), 'nat' => 'Norsk'],
			'iii' => [ 'iso2' =>'ii', 'tra' => tr('Sichuan Yi, Nuosu'), 'nat' => 'ꆈꌠ꒿ Nuosuhxop'],
			'nbl' => [ 'iso2' =>'nr', 'tra' => tr('South Ndebele'), 'nat' => 'isiNdebele'],
			'oci' => [ 'iso2' =>'oc', 'tra' => tr('Occitan'), 'nat' => 'occitan, lenga d‘òc'],
			'oji' => [ 'iso2' =>'oj', 'tra' => tr('Ojibwa'), 'nat' => 'ᐊᓂᔑᓈᐯᒧᐎᓐ'],
			'chu' => [ 'iso2' =>'cu', 'tra' => tr('Church Slavic, Old Slavonic, Church Slavonic, Old Bulgarian, Old Church Slavonic'), 'nat' => 'ѩзыкъ словѣньскъ'],
			'orm' => [ 'iso2' =>'om', 'tra' => tr('Oromo'), 'nat' => 'Afaan Oromoo'],
			'ori' => [ 'iso2' =>'or', 'tra' => tr('Oriya'), 'nat' => 'ଓଡ଼ିଆ'],
			'oss' => [ 'iso2' =>'os', 'tra' => tr('Ossetian, Ossetic'), 'nat' => 'ирон æвзаг'],
			'pan' => [ 'iso2' =>'pa', 'tra' => tr('Punjabi, Panjabi'), 'nat' => 'ਪੰਜਾਬੀ, ‫پنجابی‬‎'],
			'pli' => [ 'iso2' =>'pi', 'tra' => tr('Pali'), 'nat' => 'पालि, पाळि'],
			'fas' => [ 'iso2' =>'fa', 'tra' => tr('Persian'), 'nat' => 'فارسی'],
			'pol' => [ 'iso2' =>'pl', 'tra' => tr('Polish'), 'nat' => 'język polski, polszczyzna'],
			'pus' => [ 'iso2' =>'ps', 'tra' => tr('Pashto, Pushto'), 'nat' => 'پښتو'],
			'por' => [ 'iso2' =>'pt', 'tra' => tr('Portuguese'), 'nat' => 'Português'],
			'que' => [ 'iso2' =>'qu', 'tra' => tr('Quechua'), 'nat' => 'Runa Simi, Kichwa'],
			'roh' => [ 'iso2' =>'rm', 'tra' => tr('Romansh'), 'nat' => 'Rumantsch Grischun'],
			'run' => [ 'iso2' =>'rn', 'tra' => tr('Rundi'), 'nat' => 'Ikirundi'],
			'ron' => [ 'iso2' =>'ro', 'tra' => tr('Romanian, Moldavian, Moldovan'), 'nat' => 'Română'],
			'rus' => [ 'iso2' =>'ru', 'tra' => tr('Russian'), 'nat' => 'русский'],
			'san' => [ 'iso2' =>'sa', 'tra' => tr('Sanskrit'), 'nat' => 'संस्कृतम्'],
			'srd' => [ 'iso2' =>'sc', 'tra' => tr('Sardinian'), 'nat' => 'sardu'],
			'snd' => [ 'iso2' =>'sd', 'tra' => tr('Sindhi'), 'nat' => 'सिन्धी, ‫سنڌي، سندھی‬‎'],
			'sme' => [ 'iso2' =>'se', 'tra' => tr('Northern Sami'), 'nat' => 'Davvisámegiella'],
			'smo' => [ 'iso2' =>'sm', 'tra' => tr('Samoan'), 'nat' => 'gagana fa‘a Samoa'],
			'sag' => [ 'iso2' =>'sg', 'tra' => tr('Sango'), 'nat' => 'yângâ tî sängö'],
			'srp' => [ 'iso2' =>'sr', 'tra' => tr('Serbian'), 'nat' => 'српски језик'],
			'gla' => [ 'iso2' =>'gd', 'tra' => tr('Gaelic, Scottish Gaelic'), 'nat' => 'Gàidhlig'],
			'sna' => [ 'iso2' =>'sn', 'tra' => tr('Shona'), 'nat' => 'chiShona'],
			'sin' => [ 'iso2' =>'si', 'tra' => tr('Sinhala, Sinhalese'), 'nat' => 'සිංහල'],
			'slk' => [ 'iso2' =>'sk', 'tra' => tr('Slovak'), 'nat' => 'Slovenčina, Slovenský Jazyk'],
			'slv' => [ 'iso2' =>'sl', 'tra' => tr('Slovenian'), 'nat' => 'Slovenski Jezik, Slovenščina'],
			'som' => [ 'iso2' =>'so', 'tra' => tr('Somali'), 'nat' => 'Soomaaliga, af Soomaali'],
			'sot' => [ 'iso2' =>'st', 'tra' => tr('Southern Sotho'), 'nat' => 'Sesotho'],
			'spa' => [ 'iso2' =>'es', 'tra' => tr('Spanish, Castilian'), 'nat' => 'Español'],
			'sun' => [ 'iso2' =>'su', 'tra' => tr('Sundanese'), 'nat' => 'Basa Sunda'],
			'swa' => [ 'iso2' =>'sw', 'tra' => tr('Swahili'), 'nat' => 'Kiswahili'],
			'ssw' => [ 'iso2' =>'ss', 'tra' => tr('Swati'), 'nat' => 'SiSwati'],
			'swe' => [ 'iso2' =>'sv', 'tra' => tr('Swedish'), 'nat' => 'Svenska'],
			'tam' => [ 'iso2' =>'ta', 'tra' => tr('Tamil'), 'nat' => 'தமிழ்'],
			'tel' => [ 'iso2' =>'te', 'tra' => tr('Telugu'), 'nat' => 'తెలుగు'],
			'tgk' => [ 'iso2' =>'tg', 'tra' => tr('Tajik'), 'nat' => 'тоҷикӣ, toçikī, ‫تاجیکی‬‎'],
			'tha' => [ 'iso2' =>'th', 'tra' => tr('Thai'), 'nat' => 'ไทย'],
			'tir' => [ 'iso2' =>'ti', 'tra' => tr('Tigrinya'), 'nat' => 'ትግርኛ'],
			'bod' => [ 'iso2' =>'bo', 'tra' => tr('Tibetan'), 'nat' => 'བོད་ཡིག'],
			'tuk' => [ 'iso2' =>'tk', 'tra' => tr('Turkmen'), 'nat' => 'Türkmen, Түркмен'],
			'tgl' => [ 'iso2' =>'tl', 'tra' => tr('Tagalog'), 'nat' => 'Wikang Tagalog'],
			'tsn' => [ 'iso2' =>'tn', 'tra' => tr('Tswana'), 'nat' => 'Setswana'],
			'ton' => [ 'iso2' =>'to', 'tra' => tr('Tonga (Tonga Islands)'), 'nat' => 'Faka Tonga'],
			'tur' => [ 'iso2' =>'tr', 'tra' => tr('Turkish'), 'nat' => 'Türkçe'],
			'tso' => [ 'iso2' =>'ts', 'tra' => tr('Tsonga'), 'nat' => 'Xitsonga'],
			'tat' => [ 'iso2' =>'tt', 'tra' => tr('Tatar'), 'nat' => 'татар теле, tatar tele'],
			'twi' => [ 'iso2' =>'tw', 'tra' => tr('Twi'), 'nat' => 'Twi'],
			'tah' => [ 'iso2' =>'ty', 'tra' => tr('Tahitian'), 'nat' => 'Reo Tahiti'],
			'uig' => [ 'iso2' =>'‬ug‬', 'tra' => tr('Uighur, Uyghur'), 'nat' => '‫ئۇيغۇرچە‎, Uyghurche‬'],
			'ukr' => [ 'iso2' =>'uk', 'tra' => tr('Ukrainian'), 'nat' => 'Українська'],
			'urd' => [ 'iso2' =>'ur', 'tra' => tr('Urdu'), 'nat' => 'اردو'],
			'uzb' => [ 'iso2' =>'uz', 'tra' => tr('Uzbek'), 'nat' => 'Oʻzbek, Ўзбек, ‫أۇزبېك‬‎'],
			'ven' => [ 'iso2' =>'ve', 'tra' => tr('Venda'), 'nat' => 'Tshivenḓa'],
			'vie' => [ 'iso2' =>'vi', 'tra' => tr('Vietnamese'), 'nat' => 'Tiếng Việt'],
			'vol' => [ 'iso2' =>'vo', 'tra' => tr('Volapük'), 'nat' => 'Volapük'],
			'wln' => [ 'iso2' =>'wa', 'tra' => tr('Walloon'), 'nat' => 'Walon'],
			'cym' => [ 'iso2' =>'cy', 'tra' => tr('Welsh'), 'nat' => 'Cymraeg'],
			'wol' => [ 'iso2' =>'wo', 'tra' => tr('Wolof'), 'nat' => 'Wollof'],
			'fry' => [ 'iso2' =>'fy', 'tra' => tr('Western Frisian'), 'nat' => 'Frysk'],
			'xho' => [ 'iso2' =>'xh', 'tra' => tr('Xhosa'), 'nat' => 'isiXhosa'],
			'yid' => [ 'iso2' =>'yi', 'tra' => tr('Yiddish'), 'nat' => 'ייִדיש'],
			'yor' => [ 'iso2' =>'yo', 'tra' => tr('Yoruba'), 'nat' => 'Yorùbá'],
			'zha' => [ 'iso2' =>'za', 'tra' => tr('Zhuang, Chuang'), 'nat' => 'Saɯ cueŋƅ, Saw cuengh'],
			'zul' => [ 'iso2' =>'zu', 'tra' => tr('Zulu'), 'nat' => 'isiZulu'],
			'script/Arabic' => ['tra' => tr('Arabic') . ' ' . tr('script')],
			'script/Armenian' => ['tra' => tr('Armenian') . ' ' . tr('script')],
			'script/Bengali' => ['tra' => tr('Bengali') . ' ' . tr('script')],
			'script/Canadian_Aboriginal' => ['tra' => tr('Canadian Aboriginal') . ' ' . tr('script')],
			'script/Cherokee' => ['tra' => tr('Cherokee') . ' ' . tr('script')],
			'script/Cyrillic' => ['tra' => tr('Cyrillic') . ' ' . tr('script')],
			'script/Devanagari' => ['tra' => tr('Devanagari') . ' ' . tr('script')],
			'script/Ethiopic' => ['tra' => tr('Ethiopic') . ' ' . tr('script')],
			'script/Fraktur' => ['tra' => tr('Fraktur') . ' ' . tr('script')],
			'script/Georgian' => ['tra' => tr('Georgian') . ' ' . tr('script')],
			'script/Greek' => ['tra' => tr('Greek') . ' ' . tr('script')],
			'script/Gujarati' => ['tra' => tr('Gujarati') . ' ' . tr('script')],
			'script/Gurmukhi' => ['tra' => tr('Gurmukhi') . ' ' . tr('script')],
			'script/HanS' => ['tra' => tr('Hangul') . ' ' . tr('script')],
			'script/HanS_vert' => ['tra' => tr('Hangul (vertical)') . ' ' . tr('script')],
			'script/HanT' => ['tra' => tr('Han - Simplified') . ' ' . tr('script')],
			'script/HanT_vert' => ['tra' => tr('Han - Simplified (vertical)') . ' ' . tr('script')],
			'script/Hangul' => ['tra' => tr('Han - Traditional') . ' ' . tr('script')],
			'script/Hangul_vert' => ['tra' => tr('Han - Traditional (vertical)') . ' ' . tr('script')],
			'script/Hebrew' => ['tra' => tr('Hebrew') . ' ' . tr('script')],
			'script/Japanese' => ['tra' => tr('Japanese') . ' ' . tr('script')],
			'script/Japanese_vert' => ['tra' => tr('Japanese (vertical)') . ' ' . tr('script')],
			'script/Kannada' => ['tra' => tr('Khmer') . ' ' . tr('script')],
			'script/Khmer' => ['tra' => tr('Kannada') . ' ' . tr('script')],
			'script/Lao' => ['tra' => tr('Lao') . ' ' . tr('script')],
			'script/Latin' => ['tra' => tr('Latin') . ' ' . tr('script')],
			'script/Malayalam' => ['tra' => tr('Malayalam') . ' ' . tr('script')],
			'script/Myanmar' => ['tra' => tr('Myanmar') . ' ' . tr('script')],
			'script/Oriya' => ['tra' => tr('Oriya (Odia)') . ' ' . tr('script')],
			'script/Sinhala' => ['tra' => tr('Sinhala') . ' ' . tr('script')],
			'script/Syriac' => ['tra' => tr('Syriac') . ' ' . tr('script')],
			'script/Tamil' => ['tra' => tr('Tamil') . ' ' . tr('script')],
			'script/Telugu' => ['tra' => tr('Telugu') . ' ' . tr('script')],
			'script/Thaana' => ['tra' => tr('Thaana') . ' ' . tr('script')],
			'script/Thai' => ['tra' => tr('Thai') . ' ' . tr('script')],
			'script/Tibetan' => ['tra' => tr('Tibetan') . ' ' . tr('script')],
			'script/Vietnamese' => ['tra' => tr('Vietnamese') . ' ' . tr('script')],
			'syr' => ['tra' => tra('Syriac')],
			'kmr' => ['tra' => tr('Kurmanji (Latin)')],
			'grc' => ['tra' => tr('Greek, Ancient (to 1453)')],
			'frm' => ['tra' => tr('French, Middle (ca.1400-1600)')],
			'frk' => ['tra' => tr('German (Fraktur)')],
			'fil' => ['tra' => tr('Filipino')],
			'enm' => ['tra' => tr('English, Middle (1100-1500)')],
			'chr' => ['tra' => tr('Cherokee')],
			'ceb' => ['tra' => tr('Cebuano')],
			'osd' => ['tra' => tr('Auto detect languages')],
			'snum' => ['tra' => tr('Serial number')]
		];

		switch ($iso){
			case 'iso2':
				foreach ($langArray as $lang => $key) {
					$langArray[$key['iso2']] = $key;
					unset($langArray[$lang]);
					unset($langArray[$key['iso2']]['iso2']);
				}
				break;
			case 'iso3':
				foreach ($langArray as $lang => &$key) {
					unset($key['iso2']);
				}
		}

		switch ($name){
			case 'translated':
				foreach ($langArray as $lang => &$key) {
					$key = $key['tra'];
				}
				break;
			case 'native':
				foreach ($langArray as $lang => &$key) {
					$key = $key['nat'];
				}
				break;
			case 'string':
				foreach ($langArray as $lang => &$key) {
					if (empty($key['nat'])){
						$key = $key['tra'];
					}else{
						$key = $key['tra'] . ' (' . $key['nat'] . ')';
					}
				}
		}

		return $langArray;
	}

	/**
	 * Looks up language name(s) from a language code-name list.
	 *
	 * @param array  $LangCodes		A list of all language codes you want to match, or an empty array
	 * @param string $format		Defines how the language name is returned.
	 *                              Valid options are the same as listISOLangs() $name parameter
	 * @param string $iso			if one should match 2 or 3 character language codes
	 *
	 * @return array				A 2 dimensional associative array of language codes and names
	 * @throws Exception			Upon invalid arguments
	 */

	public function findLanguageNames( array $LangCodes, string $format='string', string $iso = 'iso3'): array
	{
		if ($iso !== 'iso2' && $iso !== 'iso3'){
			throw new Exception('invalid option for iso');
		}

		$languageNames = $this->listISOLangs($iso, $format);
		$LangCodes = array_flip($LangCodes);
		// now lets migrate the language data with the returned supported languages
		foreach ($LangCodes as $languageCode => &$languageName) {
			if (! empty($languageNames[$languageCode])) {
				$languageName = $languageNames[$languageCode];
				// if the language is not found, but a _ seperates and appended section, presume the first three letters will match a known language and append the rest.
			} elseif (isset($languageCode[3]) && $languageCode[3] === '_') {
				$append = substr($languageCode, 4);
				$languageCode = substr($languageCode, 0, 3);
				$languageName = $languageNames[$languageCode] . ' (' . $append . ')';
			// if the language is not otherwise found, use the code so we do not end with blank data.
			} else {
				$languageName = $languageCode;
			}
		}
		// now lets sort the list so it comes back all pretty :)
		asort($LangCodes);
		return $LangCodes;
	}
}