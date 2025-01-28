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
 * SortOrder is a class to generate sort links.
 *
 * @version 1.0
 * @since 06/15/2004
 * @author Evgeny Leontev 
 * @access public
 *
 * EXAMPLE
 * 
 *
 * CHANGELOG
 * 06/15/2004 - release
 */
class SortOrder
{
    
    var $use_session = false;
    var $sql;
    var $query_string;
    // var $action_page;
    
    var $sort_param = 'sort';
    var $order_param = 'order';
    var $sort_order = array(1=>'asc', 2=>'desc');
    var $default_order = 2;
    var $custom_default_order = array(); 
    var $default_sort = array(); 
    
    var $title_msg = array(1=>'Sort ascending', 2=>'Sort descending');
    
    var $a_class = 'sort';
    var $img_path = APP_ADMIN_PATH . 'images/icons/';
    var $spacer_img_path = 'images/s.gif';
    var $img_asc = 'sort_asc.gif';
    var $img_desc = 'sort_desc.gif';
    var $img_asc_active = 'sort_asc_active.gif';
    var $img_desc_active = 'sort_desc_active.gif';    
    
    var $sort_ar = array();
    var $sql_command = 'ORDER BY';
    
    
   /**
    * constructor 
    */
    function __construct($vars) {
        $this->setQueryString($vars);
    }
    
    
   /**
    * setSortItem -- set sort item
    *
    * @param    string   $tpl_var      name of tpl var in template    
    * @param    string   $get_param    what apears in $_GET string
    * @param    string   $sql_param    sql params use in sql (for instance i.date)
    * @param    string   $field_name   field name(what in template as collumn name)
    * @param    string   $order        (optional) if specify it will be a default sort param
    *
    * @return   void
    * @access   public    
    */
    function setSortItem($tpl_var, $get_param, $sql_param, $field_name = false, $order = false) {
        
        $this->sort_ar[$tpl_var] = array('get_param'=>$get_param, 
                                         'sql_param'=>$sql_param,
                                         'order'=>$order,
                                         'field_name'=>$field_name);
    }
    
    
  /**
    * setQueryString -- set string that appends in sort links
    *
    * @param    array    $arr       usually $_GET
    *
    * @return   void
    * @access   public    
    */
    function setQueryString($arr) {
        
        //// TODO: maket it better
        // this is hack to avoid PHP_SELF
        $self = $_SERVER['PHP_SELF'];
        // if(!empty($options['action_page'])) {
            // $this->action_page = $options['action_page'];
        if(defined('APP_ADMIN_PATH')) {
            $self = APP_ADMIN_PATH . 'index.php';
        }
        
        unset($arr[$this->sort_param], $arr[$this->order_param]);// skip $_GET['sort']
        $this->query_string = $self. '?' . http_build_query($arr);
    }
    
    
   /**
    * getLink -- get link for order item
    *
    * @param    sort     $sort     param for sorting
    * @param    int      $order    param for order (1 or 2)    
    *
    * @return   string   asc or desc
    * @access   private    
    */
    function getLink($sort, $order) {
        // $link = $this->query_string . '&' . $this->sort_param.'='.$sort.'&'.$this->order_param.'='. $order;
        $link = $this->query_string.'&amp;'.$this->sort_param.'='.$sort.'&amp;'.$this->order_param.'='.$order;
        return $link;
    }
    
    
   /**
    * getOrder -- get order string
    *
    * @param    mixed    $val     asc or desc or 1 or 2
    *
    * @return   string   asc or desc
    * @access   private    
    */
    function _getOrder($val) {
        $order = (is_numeric($val)) ? $this->sort_order[$val] : $val;
        return $order;
    }    
    

    // will be in foreach return number or false
    function getOrder($order, $get_param) {
            
        // default
        if($order !== false && !isset($_GET[$this->sort_param])) {
            $order = $this->_getOrder($order);
        
        // default set with setDefaultSortItem
        } elseif(isset($this->default_sort[$get_param]) && !isset($_GET[$this->sort_param])) {
            $order = $this->_getOrder($this->default_sort[$get_param]);
        
        // have a $_GET['sort']
        } elseif ($get_param == @$_GET[$this->sort_param]) {
            if(isset($this->sort_order[$_GET[$this->order_param]])) {
                $order = $_GET[$this->order_param];
            } else {
                $order = $this->default_order;
            }
            
        } else {
            $order = false;
        }
        
        return $order;
    }
    
    
    function setDefaultOrder($order) {
        $this->default_order = $order;
    }
    
    
    // set custom order for some filed
    // rewrite $this->default_order
    function setCustomDefaultOrder($name, $order) {
        $this->custom_default_order[$name] = $order;
    }
    
    
    // set default sorting field, it has less priority 
    // than order in array list 
    // name and order or array $name => $order
    function setDefaultSortItem($name, $order = false) {
        if(is_array($name)) {
            foreach($name as $k => $v) {
                $this->default_sort[$k] = $v;                
            }
        } else {
            $this->default_sort[$name] = $order;
        }
    }    
    
    
    function resetDefaultSortItem() {
        $this->default_sort = array();
    }
    
    
    function setSql($sort, $order) {
        $this->sql[] = "{$sort} {$order}";
    }
    
    
    function setOrder($sort, $order, $constant = true) {
        $this->sql[] = sprintf("%s %s", $sort, $this->_getOrder($order));
    }
    
    
    function getSql() {
        
        foreach($this->sort_ar AS $tpl_var => $v) {
            
            $order = $this->getOrder($v['order'], $v['get_param']);
            if($order !== false) { 
                $sql = explode(',', $v['sql_param']);
                foreach($sql as $param) {
                    $this->setSql($param, $this->_getOrder($order));
                } 
            }
        }
        
        if($this->sql) {
            return 'ORDER BY ' . implode(', ', $this->sql);
        }
    }
    
    
    function setTitleMsg($type, $msg) {
        $order = array_search($type, $this->sort_order);
        $this->title_msg[$order] = $msg;
    }
}


/*
class TwoWaySort extends SortOrder
{
    
    function & toHtml($sort, $active = false) {
        
        if($active) {
            $img = ($active == 'asc') ? $this->img_asc_active : $this->img_desc_active;
            $order = ($active == 'asc') ? 1 : 2;
            $html[$order] = '<img src="'.$this->img_path.$img.'" border=0>';
            
            $img = ($active == 'asc') ? $this->img_desc : $this->img_asc;
            $order = ($active == 'asc') ? 2 : 1;
            $html[$order] = '<a href="'.$this->getLink($sort, $order).'"><img src="'.$this->img_path.$img.'" border=0></a>';
        } else {
            $html[1] = '<a href="'.$this->getLink($sort, 1).'"><img src="'.$this->img_path.$this->img_asc.'" border=0></a>';
            $html[2] = '<a href="'.$this->getLink($sort, 2).'"><img src="'.$this->img_path.$this->img_desc.'" border=0></a>';
        }
    
        
        ksort($html);
        $html = '<div align="right">' . implode('', $html) . '</div>';
        return $html;
    }
    
    
    function & getVars() {
    
        foreach($this->sort_ar AS $tpl_var => $v) {
            
            if($v['order'] !== false && !isset($_GET[$this->sort_param])) {
                $order = $this->getOrder($v['order']);
                $this->setSql($v['sql_param'], $order);
            
            } elseif ($v['get_param'] == @$_GET[$this->sort_param]) {
                if(isset($this->sort_order[$_GET[$this->order_param]])) {
                    $order = $this->sort_order[$_GET[$this->order_param]];
                } else {
                    $order = $this->sort_order[2]; // default order desc
                }
                $this->setSql($v['sql_param'], $order);
                
            } else {
                $order = false;
            } 
            
            
            $ar[$tpl_var] = $this->toHtml($v['get_param'], $order);
        }
        
        return $ar;
    }

}
*/

?>