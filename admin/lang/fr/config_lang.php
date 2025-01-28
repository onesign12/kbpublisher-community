<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'French';
$conf['lang']['meta_content'] = 'fr';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'ISO-8859-1';
$conf['lang']['locale']       = 'fr_FR';


// This is the default time format.
$conf['lang']['time_format']   = 'hh:mm';
$conf['lang']['sec_format']    = 'hh:mm:ss';

// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 1;

// Fields delimeter for excel files, used in exporting to excel
$conf['lang']['excel_delim'] = ","; 


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array(
	'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u',
	'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'Ü'=>'U',
	'è'=>'e', 'È'=>'E', 'ê'=>'e', 'Ê'=>'e', 'ï'=>'i', 'Ï'=>'I'
	);



// LOCALE //
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use locale -a command
setlocale(LC_ALL, 'fr_FR.utf8', 'fr_FR.utf-8', 'fr_FR.UTF-8', 'fr_FR');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);
?>