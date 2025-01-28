<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'Russian';
$conf['lang']['meta_content'] = 'ru';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'cp1251';
$conf['lang']['locale']       = 'ru_RU';


// This is the default date format.
// It will be used everywhere unless you specify a
$conf['lang']['time_format']   = 'hh:mm';
$conf['lang']['sec_format']    = 'hh:mm:ss';

// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 1;

// Fields delimeter for excel files, used in exporting to excel
$conf['lang']['excel_delim'] = ";";


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array(
	"а" => "a",  "б" => "b",  "в" => "v",   "г" => "g",
	"д" => "d",  "е" => "e",  "ё" => "e",   "ж" => "zh",
	"з" => "z",  "и" => "i",  "й" => "j",   "к" => "k",
	"л" => "l",  "м" => "m",  "н" => "n",   "о" => "o",
	"п" => "p",  "р" => "r",  "с" => "s",   "т" => "t",
	"у" => "u",  "ф" => "f",  "х" => "h",   "ц" => "c",
	"ч" => "ch", "ш" => "sh", "ъ" => "6",   "ы" => "y",
	"ь" => "6",  "э" => "e",  "ю" => "yu",  "я" => "ya",
	"щ" => "sh",
	"А" => "A",  "Б" => "B",  "В" => "V",   "Г" => "G",
	"Д" => "D",  "Е" => "E",  "Ё" => "E",   "Ж" => "ZH",
	"З" => "Z",  "И" => "I",  "Й" => "J",   "К" => "K",
	"Л" => "L",  "М" => "M",  "Н" => "N",   "О" => "O",
	"П" => "P",  "Р" => "R",  "С" => "S",   "Т" => "T",
	"У" => "U",  "Ф" => "F",  "Х" => "H",   "Ц" => "C",
	"Ч" => "CH", "Ш" => "SH", "Ъ" => "6",   "Ы" => "Y",
	"Ь" => "6",  "Э" => "E",  "Ю" => "YU",  "Я" => "YA",
	"Щ" => "SH"
);


// LOCALE //
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use locale -a command
setlocale(LC_ALL, 'ru_RU.utf-8', 'ru_RU.utf8', 'ru_RU.UTF-8', 'ru_RU');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);


// MBSTRING //
// set mbstring if charset utf8
$conf['lang']['mbstring'] = true;
// ini_set('mbstring.http_input', 'pass');
// ini_set('mbstring.http_output', 'pass');
// ini_set('mbstring.encoding_translation', 0);
ini_set('default_charset', 'UTF-8');
ini_set('mbstring.language', 'Russian');
if(version_compare(phpversion(), '5.6', '<' )) {
    ini_set('mbstring.internal_encoding', 'UTF-8'); //  deprecated in 5.6
}

?>