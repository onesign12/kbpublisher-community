<?php
//------------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                               |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

class Validate
{

    function __construct() {

    }

    // return true if email correct
    static function email($email, $required = true){
        
        // return Validate::regex('email', $email, $required);
        
        if(!$required && empty($email)) {
            return true;
        } else {
            return (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) ? true : false;
        }
    }
    

    static function getRegexValues() {
        $regex = array('email'         => '/^([a-zA-Z0-9_\-\.\']+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/',
                       'lettersonly'   => '/^[a-zA-Z]+$/',
                       'alphanumeric'  => '/^[a-zA-Z0-9]+$/',
                       'numeric'       => '/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/',
                       'nopunctuation' => '/^[^().\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$/',
                       'nonzero'       => '/^-?[1-9][0-9]*/',
                       'ip'            => '/^(25[0-5]|2[0-4][0-9]|1[0-9]{2}|0?[1-9][0-9]|0?0?[1-9])([.](25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{1,2})){3}$/'
                       );
        return $regex;
    }


    static function getRegex($rule) {
        $regex = Validate::getRegexValues();

        if(!isset($regex[$rule])) {
            trigger_error(ucfirst(__CLASS__) ."::regex  Unspecified rule: {$rule}");
            return false;
        } else {
            return $regex[$rule];
        }
    }


    // return true if ok
    static function regex($rule, $val, $required = true) {

        // special function for email
        if($rule == 'email') {
            return self::email($val, $required);
        }


        $val = trim($val);
        $regex = Validate::getRegex($rule);
        if(!$regex) { return false; }

        if(!$required && empty($val)) {
            return true;
        } else {
            return (bool) (preg_match($regex, $val));
        }
    }


    // return true if ok
     static function between($val, $min, $max = false, $required = true) {

        if(!$required && empty($val)) {
            return true;
        } else {
            if($max && $max) { return (bool) ($val >= $min && $val <= $max); }
            elseif($min)     { return (bool) ($val >= $min); }
            elseif($max)     { return (bool) ($val <= $max); }
            else             { return false; }
        }
    }


    // return true if ok
    static function required($value) {
        $value = (is_array($value)) ? $value : trim($value);
        return (bool) (!empty($value));
    }


    // return true if ok    
    static function compare($check_val, $with_val, $operator = '==') {
        return (bool) self::compareVars($operator, $check_val, $with_val);
    }


    // return true if ok
    static function writeable($check_val) {
        $ret = true;

        if(!file_exists($check_val) || !is_writeable($check_val)) {
            $ret = false;
        }

        return $ret;
    }
    
    
    // static function compareVars($condition = "==", $var1, $var2 = false){
    static function compareVars($condition, $var1, $var2 = false){
        if($condition == "is_empty")
            return empty($var1);
        else if($condition == "is_filled")
            return !empty($var1);
        else if($condition == "==")
            return $var1 == $var2;
        else if($condition == "!=")
            return $var1 != $var2;
        else if($condition == ">")
            return $var1 > $var2;
        else if($condition == "<")
            return $var1 < $var2;
        else if($condition == ">=")
            return $var1 >= $var2;
        else if($condition == "<=")
            return $var1 <= $var2;
        else if($condition == "in_array")
            return in_array($var1, $var2);
        else if($condition == "contains")
            return strpos($var1, $var2);
        else if($condition == "starts_with")
            return substr($var1, 0, strlen($var2)) === $var2;
        else if($condition == "ends_width"){
            $length = strlen($var2);
            return !$length || substr($var1, - $length) === $var2;
        }
    }
}
?>