<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'Polish';
$conf['lang']['meta_content'] = 'pl';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'ISO-8859-1';
$conf['lang']['locale']       = 'pl_PL';


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
    'Ą'=>'A', 'Ć'=>'C', 'Ę'=>'E', 'Ł'=>'L', 'Ń'=>'N',   
    'Ó'=>'O', 'Ś'=>'S', 'Ź'=>'Z', 'Ż'=>'Z',
    'ą'=>'a', 'ć'=>'c', 'ę'=>'e', 'ł'=>'l', 'ń'=>'n',   
    'ó'=>'o', 'ś'=>'s', 'ź'=>'z', 'ż'=>'z'
);


// LOCALE //
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use locale -a command
setlocale(LC_ALL, 'pl_PL.utf8', 'pl_PL.utf-8', 'pl_PL.iso-8859-1', 'pl_PL');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);
?>