<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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


/**
 * AppObj is used for all object classes (User, Item, etc.), 
 *
 * @since 21/03/2003
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public
 */

 
class AppObj extends BaseObj
{
    
    var $properties = array();
    var $js;                     // jscript to validate form
    var $errors = false;
    var $hidden = array('id');
    var $reset_on_clone = array(); // these will be set the same as new values
    var $reset_on_update = array(); // these will be set to 0 in form hidden field 
    var $manager;

    
    // $type = array('key', 'custom', 'formatted');
    function setError($msg_key, $type = 'key') {
        $this->errors[$type][]['msg'] = $msg_key;
    }
    
    
    function setHtmlValues($key) {
        $this->html_values[$key] = &$this->properties[$key];
    }
    
    
    function setDefault($property, $val) {
        $this->set($property, $val);
    }
    
    
    function &setManager($manager) {
        return $manager;
    }
    
    
    // reassign this func to make some formatting for certain obj
    function _callBack($property, $val) {
        return $val;
    }
    

    function set($key_or_arr, $value = false, $action = false) {
        if(is_array($key_or_arr)) {
            foreach(array_keys($key_or_arr) as $k) {
                if(!array_key_exists ($k, $this->properties)) { continue; }
                $this->properties[$k] = $this->_callBack($k, $key_or_arr[$k]);
            }
        } else {
            $this->properties[$key_or_arr] = $this->_callBack($key_or_arr, $value);
        }
        
        if($action === 'clone') {
            $this->resetOnClone($key_or_arr);
            
        } elseif($action === 'draft') {
            // $this->resetOnDraft($key_or_arr);
        }
    }
    
    
    function getPrivateValue($val) {
        if(!AppPlugin::isPlugin('private')) {
            $val = 0;
        }
    
        if(is_array($val)) {
            $val = PrivatePlugin::getPrivateValue($val);
        }
        
        return $val;
    }
    
    
    // called when duplicating an item
    function resetOnClone($arr) {
        foreach($this->reset_on_clone as $k) {
            $v = NULL;
            if(in_array($k, array('title', 'name'))) {
                if(!empty($arr[$k])) {
                    $v = '[COPY] ' . $arr[$k];
                }
            }
            
            $this->properties[$k] = $this->_callBack($k, $v);
        }
    }
    
    
    // called when want create a draft
    function resetOnDraft($arr) {
        foreach($this->reset_on_clone as $k) {
            $v = NULL;
            if(in_array($k, array('title', 'name'))) {
                $v = '[DRAFT] ' . $arr[$k];
            }
            
            $this->properties[$k] = $this->_callBack($k, $v);
        }
    }
    
    
    // this could be for unset some properties
    // to generate right sql
    function unsetProperties($val) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            unset($this->properties[$v]);
        }
    }
    
    
    function get($property = false) {
        return ($property) ? $this->properties[$property] : $this->properties;
    }
    
    
    function exist($key, $arr = 'properties') {
        return (isset($this->$arr[$key]));
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        $ret['options'] = array($values);
        
        $fct = new ReflectionMethod(get_class($this), 'validate');
        if($fct->getNumberOfParameters() > 1) {
            $ret['options'][] = 'manager';
        }
        
        // return AppView::ajaxValidateForm($func, $options);
        return $ret;
    }
}


if(!defined('KBP_LICENSE_LOADED')) {
    exit('kbp');
}
    
?>