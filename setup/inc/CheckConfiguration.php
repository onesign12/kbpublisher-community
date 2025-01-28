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


class CheckConfiguration
{
    
    var $ini_values = array();
    
    
    function parseSetting($option) {
        if($option == 1)       { $option ='ON'; }
        elseif(empty($option)) { $option ='OFF'; }
        
        return $option;
    }
    
    
    function getIniSetting($values) {
        $ret = array();
        foreach($values as $setting => $recomended) {
            if(is_integer($setting)) {
                $setting = $recomended;
                $recomended = 'NO_MATTER';
            }
            
            $real = CheckConfiguration::parseSetting(ini_get($setting));
            $recomended = CheckConfiguration::parseSetting($recomended);
            $ret[$setting]['current'] = $real;
            $ret[$setting]['recomended'] = $recomended;
        }
        
        return $ret;
    }
    
    
    function getExtension($values) {
        $ret = array();
        foreach($values as $v) {
            $ret[$v] = extension_loaded($v);
        }
        
        return $ret;
    }
    
    
    function getWriteability($values) {
        $ret = array();
        clearstatcache();
        foreach($values as $v) {
            $ret[$v] = is_writeable($v);
        }
        
        return $ret;    
    }    
    
    
    /*
    [GD Version] => bundled (2.0 compatible)
    [FreeType Support] => 1
    [FreeType Linkage] => with freetype
    [T1Lib Support] => 
    [GIF Read Support] => 
    [GIF Create Support] => 
    [JPG Support] => 1
    [PNG Support] => 1
    [WBMP Support] => 1
    [XBM Support] => 
    */    
    static function getGD() {
    
        if (!extension_loaded('gd')) {
            return false;
        }
    
        if(function_exists('gd_info')) {
            return gd_info();
        }
        
        return false;
    }
    
    
    static function getPHPVersion() {
        return PHP_VERSION;
    }
    
    
    static function checkPHPVersion($required) {
        if(is_array($required)) {
            $ret1 = version_compare(PHP_VERSION, $required[0], '>=');
            $ret2 = version_compare(PHP_VERSION, $required[1], '<=');
            $ret = ($ret1 && $ret2);
        } else {
            $ret = version_compare(PHP_VERSION, $required, '>=');
        }
        
        return $ret;
    }
}

?>