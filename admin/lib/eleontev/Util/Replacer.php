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

class Replacer
{

    var $strip_var = true;       // strip unsigned vars 
    var $strip_var_sign = '';    // strip unsigned vars with this 
    var $vars = array();
    var $s_var_tag = "{";         // var tags
    var $e_var_tag = "}";
    var $s_loop_tag = false;
    var $e_loop_tag = false; 

    
    function &parse($string, $vars_ar = array()) {
        $vars = array_merge($vars_ar, $this->vars);
        
        if ($this->s_loop_tag && $this->e_loop_tag) {
            $string = $this->_parseLoop($string, $vars);
            unset($vars['loop']);
        }
        
        $string = $this->_parse($string, $vars);
        return $string;
    }
    
    
    function _parse($string, $vars) {
        if (strpos($string, $this->s_var_tag) !== false && strpos($string, $this->e_var_tag ) !== false) {
            foreach($vars as $k => $v) {
                $search[] = $this->s_var_tag . $k . $this->e_var_tag;
                $replace[] = $v;
            }
            
            $string = (isset($search)) ? str_replace($search, $replace, $string) : $string;
            $string = ($this->strip_var) ? $this->stripVars($string) : $string;            
        }
        
        return $string;
    }
    
    
    function _parseLoop($string, $vars) {
        $loop_start = strpos($string, $this->s_loop_tag);
        $loop_end = strpos($string, $this->e_loop_tag);
        
        if ($loop_start !== false && $loop_end !== false) { // there is a loop
            $loop_string = substr(
                $string,
                $loop_start + strlen($this->s_loop_tag),
                $loop_end - $loop_start - strlen($this->s_loop_tag));
            
            $data = array();
            foreach ($vars['loop'] as $v) {
                $data[] = $this->_parse($loop_string, $v);
            }
            
            $string = str_replace($this->s_loop_tag . $loop_string . $this->e_loop_tag, implode("\n", $data), $string);
        }
        
        return $string;
    }
    
    
    static function doParse($string, $vars_ar) {
        $r = new Replacer;
        return $r->parse($string, $vars_ar);
    }
    
    
   /**
    * FUNCTION: stripVars -- Strips unassigned vars {var}.
    *
    * @access public
    */
    function stripVars($string) {
        $search = "#".preg_quote($this->s_var_tag)."(\w+)".preg_quote($this->e_var_tag)."#"; 
        $replace = $this->strip_var_sign;
        return preg_replace($search, $replace, $string);    
    } 
    
     
   /**
    * FUNCTION: assignVars -- assigns variables to be used by the messages.
    *
    * If $var is an array, then it will treat it as an associative array
    * using the keys as variable names and the values as variable values.
    *
    * @param mixed $var to define variable name
    * @param mixed $value to define variable value
    * @access public
    */
    function assignVars($var, $value = false) {
        
        if(is_array($var)) {
            foreach($var as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            if($value === false) {
                trigger_error("<b>" .__CLASS__. "</b> Can't assign value for var - <b>".$var."</b>. Missing argument 2 for tplAssign();");
            } else { 
                $this->vars[$var] = $value; 
            }
        }
    }
    
    
    
    function assign($var, $value = false) {
        $this->assignVars($var, $value);
    }
    
    
    function &substrReplace($string, $replace, $s_delim, $e_delim, $with_delims = true) { 
        
        // if not to check, can lead to unexpected result
        if (strpos($string, $s_delim) !== false && strpos($string, $e_delim) !== false) {
            $s_point = strpos($string, $s_delim); // number
            $e_point = strpos($string, $e_delim); // number
            
            if ($with_delims) {
                $string = substr_replace($string, $replace, $s_point + strlen($s_delim), $e_point - $s_point - strlen($s_delim)); // delims will stay in string         
            } else {
                $string = substr_replace($string, $replace, $s_point, ($e_point - $s_point + strlen($e_delim))); // delims will be replaced as well
            }
        } 
        return $string;
    } 

    
    function &substr($string, $s_delim, $e_delim, $with_delims = true) {
    
        // if not to check, can lead to unexpected result
        if (strpos($string, $s_delim) !== false && strpos($string, $e_delim) !== false) {
            $s_point = strpos($string, $s_delim); // number
            $e_point = strpos($string, $e_delim); // number
            
            if ($with_delims) {
                $string = substr($string, $s_point, ($e_point - $s_point + strlen($e_delim))); // with delims
            } else {
                $string = substr($string, $s_point + strlen($s_delim), $e_point - $s_point - strlen($s_delim)); // without delims
            }
        }
        return $string;
    } 
}


//$r = new Replacer();
//echo $r->parse('Good example, with {var}', array('var'=>'<b>var</b>'));



//echo Replacer::substr('Good example, with {var}', '{', '}', false);
//echo "<br>";
//echo Replacer::substrReplace('Good example, with {var}', '111', '{', '}', false);
?>