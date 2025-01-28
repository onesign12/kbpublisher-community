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
//
// $Id: tpl_templatez.php,v 1.1.1.1 2004/09/15 16:57:44 root Exp $

/**
 * MultiInsert is a class used to generate sql for multiinsert, replace.
 *
 * @since 30/05/2004
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public
 * 
 * EXAMPLE:
 * $_GET['data'][1] = array('ljlkjk', 55);
 * $_GET['data'][2] = array('ljlkjk', 55);

 * $ins = new MultiInsert;
 * $ins->setFields(array('name', 'family'), 'id');
 * $ins->setValues($_GET['data'], 'NOW()');
 * $sql = $ins->getSql('my_table');
 *
 * QUICK WAY:
 * $sql = MultiInsert::get("REPLACE table (id,ou,date, fdr) VALUES ?", $_GET['data'][2], array('NOW()', 321));
 */

class MultiInsert {
    
    var $fields;
    var $values;
    var $set_quotes = true;                            // set quotes for int
    var $special_values = array("NULL", "NOW()");
    var $replace_bracket = array('{{#', '#}}');
    var $special_prefixes = array('INET_ATON', 'INET_NTOA', 'CONCAT');
    
    
    function __construct() {}
    
    
    function setFields($values, $other_values = array()) {
        $arr = $this->_getArrays($values, $other_values);
        $arr = $this->_appendToArray($arr[0], $arr[1]);
        $this->fields = $this->_arrayToStringFields($arr);
    }
    
    
    function setValues($values, $other_values = array()) {
        $arr = $this->_getArrays($values, $other_values);
        $arr = $this->_appendToArray($arr[0], $arr[1]);
        $arr = $this->_setQuotes($arr);
        $this->values = $this->_arrayToString($arr);    
    }
    
    
    function getSql($table, $sql_command = 'INSERT') {
        return "{$sql_command} INTO {$table} ({$this->fields}) VALUES {$this->values}";
    }
    

    static function get($sql, $values, $other_values = array()) {
        $a = new MultiInsert();
        $a->setValues($values, $other_values);
        return str_replace('?', $a->values, $sql);
    }
    
    
    function _getArrays($val) {
        $val = func_get_args();
        foreach($val as $k => $v) {
            if($v === false) { continue; }
            $val[$k] = (!is_array($v)) ? array($v) : $v;
         }
        
        return $val;
    }
    
    
    function _appendToArray(&$arr, &$arr1) {
        foreach($arr1 as $k1 => $v1) {
            foreach($arr as $k => $v) {
                
                if(is_array($v)) {
                    array_push($arr[$k], $v1); 
                } else {
                    array_push($arr, $v1); 
                    break;
                }
            }
        }    
        
        return $arr;
    }

    
    function _arrayToStringFields(&$arr, $glue=',') {
        foreach($arr as $k => $v) {
            if(is_array($v)) { 
                $arr[$k] = $this->_arrayToString($v, $glue);
            } 
        }
        
        return join($glue, $arr);
    }    

    
    function _arrayToString(&$arr, $glue=',') {
        foreach($arr as $k => $v) {
            if(is_array($v)) { 
                $arr[$k] = $this->_arrayToString($v, $glue);
            } 
        }
        
        $a = join($glue, $arr);
        $a = (strpos(str_replace('()', '', $a), ')') === false) ? '('.$a.')' : $a;
        return str_replace($this->replace_bracket, array('(', ')'), $a);
    }
    
    
    function _setQuotes(&$arr) {
        
        foreach($arr as $k => $v) {
            if(is_array($v)) {
                $arr[$k] = $this->_setQuotes($v);
            } else {
                if($v === NULL) { 
                    $arr[$k] = "NULL";
                } elseif(is_int($v)) {
                    $arr[$k] = $v;                
				} elseif(in_array($v, $this->special_values)) {
                    $arr[$k] = $v;
                } else {
                    $prefix_found = false;
                    foreach ($this->special_prefixes as $prefix) {
                        // if (strpos($v, $prefix) !== false) {
                        if (strpos(trim($v), $prefix) === 0) { // only if on string start
                            $prefix_found = true;
                            break;
                        }
                    }
                    
                    if ($prefix_found) {
                        $v = str_replace(array('(', ')'), $this->replace_bracket, $v);
                        $arr[$k] = $v;
                        
                    } else {
                        $v = str_replace(array('(', ')'), $this->replace_bracket, $v);
                        $arr[$k] = (is_numeric($v) && !$this->set_quotes) ? $v : "'" . $v . "'";
                    }
                }
            }
        }
        
        return $arr;
    }
}


/*
//EXAMPLE:
$_GET['data'][1] = array(55, 'sss(s)');
$_GET['data'][2] = array(56, 'ddd(d)');

$ins = new MultiInsert;
$ins->setFields(array('name', 'family'), 'id');
$ins->setValues($_GET['data'], 'NOW()');
$sql = $ins->getSql('my_table');

//QUICK WAY:
//$sql = MultiInsert::get("REPLACE table (id,date) VALUES ?", $_GET['data'], array('NOW()'));
echo "<pre>"; print_r($sql); echo "</pre>";
*/

?>