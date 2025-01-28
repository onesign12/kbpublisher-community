<?php
class KBApiUtil
{
    
    static function camelizeString($string, $lazy = true) {
        $ret = preg_replace_callback("#(?<=[a-z0-9])(_([a-z0-9]))#", 
            function ($matches) {
                return strtoupper($matches[2]);
            }, $string);

        $ret = ($lazy) ? lcfirst($ret) : ucfirst($ret);
        return $ret;
    }
    

    static function camelize($arr, $lazy = true, $new_arr = array()) {

        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $kCamel = self::camelizeString($k);
                $new_arr[$kCamel] = self::camelize($arr[$k], $lazy);

            } else { 
                $kCamel = self::camelizeString($k);
                $new_arr[$kCamel] = $arr[$k];
            }
        }
        
        return $new_arr;        
    }

}
?>