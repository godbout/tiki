<?php // -*- coding:utf-8 -*-
/** \brief this table associates language extension and language name in the current language and language name in the native language
* CAUTION: it is utf-8 encoding used here too
* PLEASE : translators, please, update this file with your language name in your own language
**/

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}

$langmapping = array(
    'ar' => array ( 'العربية', tra("Arabic") ),
    'as' => array ( 'Assamese', tra("Assamese") ),
    'bn-IN' => array(  'বাংলা (ভারত)',      tra("Bengali (India)")      ),
    'bn-BD' => array(  'বাংলা (বাংলাদেশ)',      tra("Bengali (Bangladesh)")      ),
    'ca' => array(  'Català',      tra("Catalan")       ),
    'cn' => array(  '中文(簡体字)',      tra("Simplified Chinese")        ),
    'cy' => array(  'Cymraeg',      tra("Welsh")      ),
    'zh' => array(  'Chinese',      tra("Chinese")      ),
    'cs' => array(  'Česky',      tra("Czech")        ),
    'da' => array(  'Dansk',        tra("Danish")       ),
    'de' => array(  'Deutsch',      tra("German")       ),
    'en' => array(  'English',      tra("English")      ),
    'en-uk' => array( 'English British',  tra("English British")	),
    'eo' => array(  'Esperanto',      tra("Esperanto")      ),
    'es' => array(  'Español',     tra("Spanish")      ),
    'et' => array(  'Estonian',     tra("Estonian")      ),
    'el' => array(  'Greek',        tra("Greek")        ),
    'eu' => array(  'Basque',        tra("Basque")        ),
    'fa' => array(  'فارسي',        tra("Farsi")        ),
    'fi' => array(  'Finnish',        tra("Finnish")        ),
    'fj' => array(  'Fijian',       tra("Fijian")      ),
    'fr' => array(  'Français',    tra("French")       ),
    'fur' => array(  'Furlan',       tra("Friulian")      ),
    'fy-NL' => array(  'Frisian Netherlands',    tra("Frisian Netherlands")       ),
    'ga-IE' => array(  'Irish',    tra("Irish")       ),
    'gu-IN' => array(  'Gujarati India',    tra("Gujarati India")       ),
    'gl' => array(  'Galego',    tra("Galician")       ),
    'he' => array(  'עברית',    tra("Hebrew")       ),
    'hi-IN' => array(  'हिन्दी',      tra("Hindi")      ),
    'hr' => array(  'Hrvatski',     tra("Croatian")   ),
    'is' => array(  'íslenska',     tra("Icelandic")      ),
    'it' => array(  'Italiano',     tra("Italian")      ),
    'ja' => array(  '日本語',    tra("Japanese")     ),
    'kk' => array(  'Қазақ тілі',      tra("Kazakh")      ),
    'kn' => array(  'Kannada',     tra("Kannada")      ),
    'ko' => array(  '한국말',    tra("Korean")     ),
    'hu' => array(  'Magyar',   tra("Hungarian")   ),
    'lt' => array(  'Lithuanian',   tra("Lithuanian")   ),
    'lv' => array(  'Latvian',   tra("Latvian")   ),
    'mk' => array(  'Macedonian', tra("Macedonian")   ),
    'mn' => array(  'Mongolian',   tra("Mongolian")   ),
    'mr' => array(  'मराठी',      tra("Marathi")   ),
    'ms' => array(  'Bahasa Melayu',      tra("Malay")      ),
    'nl' => array(  'Nederlands',   tra("Dutch")        ),
    'no' => array(  'Norwegian',    tra("Norwegian")    ),
    'nn-NO' => array(  'Norwegian Nynorsk',    tra("Norwegian Nynorsk")    ),
    'oc' => array(  'Occitan (Lengadocian)',	tra("Occitan")	),
    'or' => array(  'ଓଡ଼ିଆ',      tra("Oriya")      ),
    'pa-IN' => array(  'Punjabi India',    tra("Panjabi India")    ),
    'pl' => array(  'Polish',       tra("Polish")       ),
    'pt' => array(  'Portuguese',       tra("Português")       ),
    'pt-br' => array(  'Português Brasileiro',  tra("Brazilian Portuguese")  ),
    'ro' => array(  'Romanian',      tra("Romanian")      ),
    'rm' => array(  'Rumantsch',      tra("Romansh")      ),
    'ru' => array(  'Русский',      tra("Russian")      ),
    'rw' => array(  'Kinyarwanda',      tra("Kinyarwanda")      ),
    'sb' => array(  'Pijin Solomon', tra("Pijin Solomon")    ),
    'si' => array(   'Sinhala',  tra("Sinhala")       ),
    'sk' => array(   'Slovenský',  tra("Slovak")       ),
    'sl' => array(   'Slovenščina', tra('Slovene')       ),
    'sq' => array(  'Albanian', tra("Albanian")    ),
    'sr' => array(   'Српски',  tra("Serbian")       ),
    'sr-latn' => array(   'Srpski',  tra("Serbian Latin")       ),
    'sv' => array(  'Svenska',      tra("Swedish")      ),
    'tv' => array(  'Tuvaluan',      tra("Tuvaluan")      ),
    'th' => array(  'ไทย',      tra("Thai")      ),
    'te' => array(  'తెలుగు',      tra("Telugu")      ),
    'ta-LK' => array(  'தமிழ் (இலங்கை)',      tra("Tamil (Sri Lanka)")      ),
    'ta' => array(  'Tamil',      tra("Tamil")      ),
    'tr' => array(  'Turkish',      tra("Turkish")      ),
    'tw' => array(  '正體中文',          tra("Traditional Chinese")          ),
    'uk' => array( 'Українська',     tra("Ukrainian")    ),
    'vi' => array(  'Tiếng Việt',      tra("Vietnamese")      ),
);
