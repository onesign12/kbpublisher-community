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


class Utf8
{
    
    // in KBP 8.0 Intl extension is a must, it is safe to use UConverter
    // static function encode($string, $from = 'ISO-8859-1', $options = ['to_subst' => '?']) {
    static function encode($string, $from = null, $options = ['to_subst' => '?']) {
        if(function_exists('mb_convert_encoding') && $from === null) {
            $str = mb_convert_encoding($string, "UTF-8");
        } else {
            $from = self::_getEncoding($from);
            $str =  UConverter::transcode($string, 'UTF8', $from, $options);
        }
        
        return $str;
    }

    
    static function decode($string, $to = null, $options = ['to_subst' => '?']) {
        $to = self::_getEncoding($to);
        return UConverter::transcode($string, $to, 'UTF8', $options);
    }
    
    
    static function _getEncoding($encoding) {
        static $iso_charset;
        if($encoding === 'lang') { // from config
            if($iso_charset === null) {
                $reg = &Registry::instance();
                $iso_charset = $reg->getEntry('conf')['lang']['iso_charset'];
            }
            
            $encoding = $iso_charset;
        
        } elseif($encoding === 'latin') {
            $encoding = 'ISO-8859-1';
        
        } elseif($encoding === null) {
            $encoding = 'ISO-8859-1';
        } 
        
        return $encoding;
    }
    
    
    
    // load lib if $encoding is utf-8, mostly specified defined in config_lang.php
    static function badUtfLoad($encoding) {
        static $loaded = false;
        if(strtolower($encoding) != 'utf-8') {        
            return false;
        }
        
        if($loaded === false) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';
            $loaded = true;
        }
        
        return true;
    }
    
    
    // replace bad UTF8 to ?, required for ajax
    static function _stripBadUtf($value) {
        if(!utf8_compliant($value)) {
            $value = utf8_bad_replace($value, '?');
        }
        
        return $value;
    }

    
    static function &stripBadUtf(&$arr, $encoding, $skip_keys = array(), $load = true) {

        if($load && !Utf8::badUtfLoad($encoding)) {
            return $arr;
        }
        
        if(!is_array($arr)) {
            $arr = Utf8::_stripBadUtf($arr);
            return $arr;
        }        
                
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $arr[$k] = Utf8::stripBadUtf($arr[$k], $encoding, $skip_keys, false);
            
            } elseif(!in_array($k, $skip_keys, 1)) {        
                $arr[$k] = Utf8::_stripBadUtf($arr[$k]);
            }
        }
        
        return $arr;
    }
    
}

?>