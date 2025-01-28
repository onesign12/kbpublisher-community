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

/**
 * Navigation is a class to generate navigation menu.
 *
 * @version 1.0
 * @since 01/15/2004
 * @author Evgeny Leontev 
 * @access public
 *
 * EXAMPLE
 * 
 *
 * CHANGELOG
 * 01/15/2004 - release
 */
 
class Navigation
{

    var $equal_type;        // PAGE or GET or CATALOG
    var $equal_value;    
    
    var $sub_equal_type;        // PAGE or GET or CATALOG
    var $sub_equal_value;    
    
    var $order = 0;
    var $max_order = 0;
    var $do_check_value = true;
    
    var $menu_name = 'default';
    var $menu_array = array();
    var $auxilary = array();
    var $template;
    var $template_type;
    var $default_value;
    var $custom_rule;
    var $ext = '.php';
    var $get_params = array();
    var $options = array(
        'target'   => '_self', 
        'a_style'  => '',  
        'a_extra'  => '',
        'td_style' => '', 
        'td_extra' => ''
        );    
    
    // to map menu items to correct highlight in menu
    var $highlight_menu_item = array();
    
    
    function setMenuName($menu_name) {
        $this->menu_name = $menu_name;
    }
    

    // $type = PAGE or GET or CATALOG
    function setEqualAttrib($type, $value = false) {
        $this->equal_type = $type;
        $this->equal_value = $value;
    }

    
    function setOption($name, $value) {
        $this->options[$name] = $value;
    }
        
    
    // $type = PAGE or GET or CATALOG
    function setSubEqualAttrib($type, $value = false, $reset = false) {
        if($reset) {
            $this->sub_equal = array();
        }
        
        $this->sub_equal[] = array('type'=>$type, 'value'=>$value);
    }    
    

    function setDefault($value) {
        $this->default_value = $value;
    }
    
    // check value or not by _getCheckValue and insert it to auxilary array
    function setDoCheckValue($val) {
        $this->do_check_value = $val;
    }
    
    
    function setOrder($val) {
        $this->order = $val;
    }
    
    function countMenuItems($menu_name = false) {
        
        $num = 0;
        if($menu_name == 'all') {
            foreach($this->menu_array as $v) {
                $num += count($v); 
            }
        } elseif(!$menu_name) {
            $num = count($this->menu_array[$this->menu_name]);
        } else {
            $num = count($this->menu_array[$menu_name]);
        }
        
        return $num;
    }
    
    
    function reassignOrder(&$val) {
        if(isset($this->menu_array[$this->menu_name][$val])) {
            
            $num = count($this->menu_array[$this->menu_name]);
            
            for($i=$val,$j=0;$i<$num;$i++,$j++){
                $key = $num-$j-1;
                $this->menu_array[$this->menu_name][$key+1] = $this->menu_array[$this->menu_name][$key];
                unset($this->menu_array[$this->menu_name][$key]);
                
                if(isset($this->auxilary[$this->menu_name][$val])) {
                    $this->auxilary[$this->menu_name][$key+1] = $this->auxilary[$this->menu_name][$key];
                    unset($this->auxilary[$this->menu_name][$key]);    
                }
            }
        }
    }    
    
    
    function setMenuItem($menu_item, $menu_link, $options = array(), $menu_name = false) {
        
        $menu_name = ($menu_name === false) ? $this->menu_name : $menu_name;
        $order = (isset($this->menu_array[$menu_name])) ? count($this->menu_array[$menu_name]) : 0;
        
        if($this->order) {
            $order = $this->order;
            $this->reassignOrder($order);
        }
    
        $this->menu_array[$menu_name][$order] = array(
            'menu_item' => $menu_item, 
            'menu_link' => $menu_link
            );
            
        if($this->do_check_value) {
            $this->auxilary[$menu_name][$order] = $this->_getCheckValue($menu_link);
        }
        
        if($options) {
            foreach($options as $k => $v) {
                $this->menu_array[$menu_name][$order][$k] = $v;
            }
        }
        
        $this->order = 0;
    }
    
    
    function updateMenuItem($menu_item_code, $menu_item, $menu_link, $options = array()) {
        $key = array_search($menu_item_code, $this->auxilary[$this->menu_name]);
        if($key !== false) {
            $menu_item = ($menu_item != false) ?  $menu_item : $this->menu_array[$this->menu_name][$key]['menu_item'];
            $menu_link = ($menu_link != false) ?  $menu_link : $this->menu_array[$this->menu_name][$key]['menu_link'];            
        
            unset($this->auxilary[$this->menu_name][$key]);
            unset($this->menu_array[$this->menu_name][$key]);
            
            if(!$this->order) { $this->setOrder($key); }
            $this->setMenuItem($menu_item, $menu_link, $options);
        
            ksort($this->auxilary[$this->menu_name]);
            ksort($this->menu_array[$this->menu_name]);
        }
    }
    
    
    function unsetMenuItem($menu_item_code, $menu_name = false) {
        $menu_name = ($menu_name) ? $menu_name : $this->menu_name;
        if(isset($this->auxilary[$menu_name])) {
            $key = array_search($menu_item_code, $this->auxilary[$menu_name]);
            if($key !== false) {
                unset($this->auxilary[$menu_name][$key]);
                unset($this->menu_array[$menu_name][$key]);
            }    
        }
    }
    
    
    function sethighlightMenuItem($menu_item_code, $menu_item_code_map, $menu_name = false) {
        $menu_name = ($menu_name === false) ? $this->menu_name : $menu_name;
        $this->highlight_menu_item[$menu_name][$menu_item_code] = $menu_item_code_map;
    }
    
    
    function setTemplate($value, $type = 'NORMAL') {
        $this->template = $value;
        $this->template_type = $type;
    }
    
    
    function setTemplateCustomRule($value = true) {
        $this->custom_rule = $value;
    }
    
    
    function setGetParams($get_params) {
        $this->get_params[] = $get_params;
    }
    
    
    function generate($menu_name = 'default') {
        
        if(!isset($this->menu_array[$menu_name])) { return; }
        ksort($this->menu_array[$menu_name]);
        
        $tpl = new tplTemplatez($this->template);
        
        // only if template with row/active and row/nonactive
        if($this->template_type == 'NORMAL') {
            $page_val = $this->_getCheckValue($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $menu_key = $this->_getCurrentMenuItem($page_val, $menu_name);
            
            foreach($this->menu_array[$menu_name] as $k => $v){
                if($k == $menu_key){ 
                    $tpl->tplSetNeeded('row/active'); 
                } else { 
                    $tpl->tplSetNeeded('row/nonactive'); 
                }
                $tpl->tplParse(array_merge($this->options,$v),'row');
            }
        } else {
            foreach($this->menu_array[$menu_name] as $k => $v){
                $tpl->tplParse($v,'row');
            }
        }
        
        if($this->custom_rule) { $tpl->tplSetNeeded('/line'); }
        //else                   { $tpl->tplSetNeeded('/not_line'); }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
    function display($menu_name = 'default') {
        echo $this->generate($menu_name);
    }
    
    
    // get key of current menu
    function _getCurrentMenuItem($page_val, $menu_name) {
        
        if($this->default_value && !in_array($page_val, $this->auxilary[$menu_name])) {
            $menu_key = array_search($this->default_value, $this->auxilary[$menu_name]);
        } else {
            $menu_key = array_search($page_val, $this->auxilary[$menu_name]);
        }
        
        $menu_key = ($menu_key !== false) ? $menu_key : '654987654';
        return $menu_key;
    }
    
    
    function _getCheckValue($string, $equal_value = false) {
        
        $equal_value = ($equal_value) ? $equal_value : $this->equal_value;
        $value = false;;
        // echo $equal_value, '<br>';
        
        if($this->equal_type == 'LAST') {
            $value = explode('/', $string);    
            $value = ($value[count($value)-1]) ? $value[count($value)-1] : $value[count($value)-2];
        
        } elseif($this->equal_type == 'GET') {
            $string = str_replace('&amp;', '&', $string);
            $get_str = (($pos = strpos($string, '?')) !== false) ? substr($string, $pos+1) : '';
            parse_str($get_str, $output);
            
            if(is_array($equal_value)) {
                @$value = $output[$equal_value[0]][$equal_value[1]]; //
            } else {
                @$value = $output[$equal_value]; // 
            }
        
        } elseif($this->equal_type == 'PAGE') {
            $value = substr($string, 0, strpos($string, '.')); // delete ext
            $value = (($pos = strrpos($value, '/')) !== false) ? substr($value, $pos+1) : $value; // delete before /
        
        } elseif($this->equal_type == 'DIR') {
            if(strpos($string, '/') === 0) {
                $string = substr($string, 1); // delete first /
            }
            
            // dirname(__FILE__);
            $string = substr($string, 0, strrpos($string, '/')); // delete after last
            
            $value = explode('/', $string);
            krsort($value);
            $value = implode('/', $value);
            $value = explode('/', $value);
            
            $equal = (!$equal_value) ? 0 : $equal_value - 1;
            $value = $value[$equal]; // 
        }
        
        return $value;exit;
    }
}

/*
$nav = new Navigation;

$nav->setEqualAttrib('GET', 'view');
$nav->setTemplate('../template/sub_menu1.html'); 

$nav->setDefault('history');

$nav->setMenuItem('Basic info', $_SERVER['PHP_SELF'].'?view=basic');
$nav->setMenuItem('History', $_SERVER['PHP_SELF'].'?view=history');
$nav->setMenuItem('Prescriptions', $_SERVER['PHP_SELF'].'?view=prescriptions');
$nav->setMenuItem('Account', $_SERVER['PHP_SELF'].'?view=account');

//$nav->setEqualAttrib('PAGE');
$nav->setMenuItem('Insurance', $_SERVER['PHP_SELF']);
$nav->setMenuItem('Recalls', $_SERVER['PHP_SELF'].'?view=recalls');
$nav->setMenuItem('Notes', $_SERVER['PHP_SELF'].'?view=notes');


$nav->display();
echo "<pre>"; print_r($nav); echo "</pre>";
*/


?>