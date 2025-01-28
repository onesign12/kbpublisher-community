<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'German';
$conf['lang']['meta_content'] = 'de';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'ISO-8859-1';
$conf['lang']['locale']       = 'de_DE';


// This is the default time format.
$conf['lang']['time_format']   = 'hh:mm';
$conf['lang']['sec_format']    = 'hh:mm:ss';


// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 1;

// LOCALE //
// http://php.net/setlocale
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use command: locale -a | grep de
setlocale(LC_ALL, 'de_DE.utf-8', 'de_DE.utf8', 'de_DE.ISO8859-1', 'de_DE.ISO8859-15', 'de_DE', 'de_DE@euro');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array('Ü'=>'U','Ö'=>'O','Ä'=>'A','ä'=>'a','ö'=>'o','ü'=>'u','ß'=>'B');
?>