<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


//mbstring
if(extension_loaded('mbstring')) {
    
    function _strlen($str) { return mb_strlen($str); }
    function _strtolower($str) { return mb_strtolower($str); }
    function _strtoupper($str) { return mb_strtoupper($str); }
    function _substr($str, $start, $length = NULL) { 
        if($length === NULL) { return mb_substr($str, $start); }
        else                 { return mb_substr($str, $start, $length); }
    }
    function _strpos($haystack, $needle, $offset = 0) { return mb_strpos($haystack, $needle, $offset); }
    function _strrpos($haystack, $needle) { return mb_strrpos($haystack, $needle); }

} else {
    
    function _strlen($str) { return strlen($str); }
    function _strtolower($str) { return strtolower($str); }
    function _strtoupper($str) { return strtoupper($str); }
    function _substr($str, $start, $length = NULL) { 
        if($length === NULL) { return substr($str, $start); }
        else                 { return substr($str, $start, $length); }
    }
    function _strpos($haystack, $needle, $offset = 0) { return strpos($haystack, $needle, $offset); }
    function _strrpos($haystack, $needle) { return strrpos($haystack, $needle); }
}


//strftime fix php 8.1
//04-10-2022 strftime replaced for _strftime, need testing 
//stftime will be out in php 9.0 need to implement

// IntlDateFormatter::NONE     -1
// IntlDateFormatter::FULL      0
// IntlDateFormatter::LONG      1 
// IntlDateFormatter::MEDIUM    2
// IntlDateFormatter::SHORT     3

function _strftime($format, $timestamp = null) {
    // return strftime($format, $timestamp);
    
    static $locale = null;
    static $formatter = [];
    $timestamp = ($timestamp) ? $timestamp : time(); 
    
    if($locale === null) {
        $reg = &Registry::instance();
        $locale = $reg->getEntry('conf')['lang']['locale'];
    }
    
    if(is_array($format)) { // intl
        
        $f = md5(serialize($format));
        if(empty($formatter[$f])) {
            $fdate = (isset($format['date'])) ? $format['date'] : -1;
            $ftime = (isset($format['time'])) ? $format['time'] : -1;
            $pattern = (isset($format['pattern'])) ? $format['pattern'] : null;
            $formatter[$f] = new IntlDateFormatter($locale, $fdate, $ftime, null, null, $pattern);
        } 
        
        $date = $formatter[$f]->format($timestamp);
        
    } elseif(strpos($format, '%') === false) {
        $f = $format;
        if(empty($formatter[$f])) {
            $formatter[$f] = new IntlDateFormatter($locale, -1, -1, null, null, $format);
        }
        
        $date = $formatter[$f]->format($timestamp);
        
    } else {
        $date = StrftimeFix::strftime($format, $timestamp, $locale);
    }
    
    return $date;
}
?>