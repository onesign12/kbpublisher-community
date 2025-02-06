<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
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

#[AllowDynamicProperties]

class RequestData
{
    
    var $int_keys = array();
    var $skip_keys = array();
    var $html_keys = array();
    var $encode_keys = array();
    var $substr_keys = array();
    var $html_values = array();
    var $curly_braces = array();
    
    
    // array: key is that we can access it, value is custom real param
    // for example: real $_GET['custom'] = 20; we can access it like $rq->action, 
    // so on page we always can write $rq->action
    // access any var we want just change value for $predefined array for that key
    //var $predefined = array('action'=>'_a');
    var $predefined = array();
    var $vars = array();
    
    var $action; // to have it on page
    
    
    function __construct(&$var, $int_keys = array()) {
        $this->setIntKeys($int_keys);
        $this->setVars($var);
    }
    
    function setIntKeys($key) {
        $this->setKeys($key, 'int_keys');
    }    
    
    function setSkipKeys($key) {
        $this->setKeys($key, 'skip_keys');
    }

    function setHtmlKeys($key) {
        $this->setKeys($key, 'html_keys');
    }
    
    // the same as setHtmlKeys 
    function setHtmlValues($key) {
        $this->setHtmlKeys($key);
    }
    
    function getHtmlValues() {
        return $this->getValues('html_keys');
    }
    
    function setCurlyBracesValues($key) {
        $this->setKeys($key, 'curly_braces');
    }
    
    function getCurlyBracesValues() {
        return $this->getValues('curly_braces');
    }
    
    // function setEncodeValues($key) {
    //     $this->setKeys($key, 'encode_keys');
    // }
    // 
    // function getEncodeValues() {
    //     return $this->getValues('encode_keys');
    // }
    
    function setSubstrValues($keys_to_num) {
        foreach($keys_to_num as $k => $v) {        
            $this->substr_keys[$k] = $v;
        }
    }
    
    function getSubstrKeys() {
        return $this->substr_keys;
    }
    
    
    function setKeys($key, $name) {
        $key = (is_array($key)) ? $key : array($key);
        foreach($key as $k) {        
            $this->$name[] = $k;
        }
    }
    
    function getKeys($name) {
        return $this->$name;
    }
    
    function getValues($name) {
        $arr = array();
        foreach($this->$name as $v) {
            if(isset($this->vars[$v])) {
                $arr[$v] = &$this->vars[$v];
            }
        }

        return $arr;
    }
    
    
    
    function &setVars(&$var) {
        $this->setVarsToObj($var);
        $this->vars =& $var;
        $this->toInt();
        
        return $this->vars;
    }
    
    
    function setVarsToObj(&$arr) {
        foreach($arr as $k => $v){
            $this->$k =& $arr[$k];
        }
    }
        
     
    function toInt($int_keys = array()) {
        $int_keys = array_unique(array_merge($this->int_keys, $int_keys));        
        return RequestDataUtil::toInt($this->vars, $int_keys);
    }
    
    
    // $param true, false (for server check) or real values
    function stripVars($server_check = false) {
        
        // $skip = array_merge($this->skip_keys, $this->html_keys, $this->encode_keys);
        $skip = array_merge($this->skip_keys, $this->html_keys);
        $html_values = $this->getHtmlValues();
        $cb_values = $this->getCurlyBracesValues();
        // $encode_values = $this->getEncodeValues();
        
        RequestDataUtil::stripVars($this->vars, $skip, $server_check);
        RequestDataUtil::stripVarsHtml($html_values, array(), $server_check);
        RequestDataUtil::stripVarsCurlyBraces($cb_values, $server_check);
        RequestDataUtil::substrVars($this->vars, $this->getSubstrKeys());
        // RequestDataUtil::stripVarsEncode($encode_values, $server_check);
    }
    
    
    // $param true, false (for server check) or real values
    // function &stripVarsValues(&$values, $server_check = 'display') {
    function stripVarsValues(&$values, $server_check = 'display') {
        $this->vars = &$values;
        $this->stripVars($server_check);
        
        return $this->vars;
    }
}
?>