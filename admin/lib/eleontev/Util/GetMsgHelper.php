<?php

// helpers
class GetMsgHelper
{

    static function &parseIni($file, $key = false, $process_sections = 1) {
        $arr = parse_ini_file($file, $process_sections);
        $arr = ($key) ? (!empty($arr[$key])) ? $arr[$key] : array() : $arr;
        return $arr;
    }
    

    // parse multilines ini file
    // it will skip all before defining first [block]
    static function &parseMultiIni($file, $key = false) {
        $s_delim = '[';
        $e_delim = ']'; 
        
        $str = implode('',file($file));
        if($key && strpos($str, $s_delim . $key . $e_delim) === false) { 
            $t = array();
            return $t; 
        } 
        
        $arr = array();
        $str = explode($s_delim, $str);
        $num = count($str);
            
        for($i=1;$i<$num;$i++){
            $section = substr($str[$i], 0, strpos($str[$i], $e_delim));
            $arr[$section] = substr($str[$i], strpos($str[$i], $e_delim)+strlen($e_delim));
            $arr[$section] = rtrim($arr[$section]);
        }
        
        $arr = ($key) ? (!empty($arr[$key])) ? $arr[$key] : array() : $arr;
        return $arr;
    }
    
    
    // just require php file with array in it
    static function & parsePhp($file, $arr, $key = false) {
        require_once ($file);
        $arr =& $$arr;
        return ($key) ? $arr[$key] : $arr;
    }
}

?>