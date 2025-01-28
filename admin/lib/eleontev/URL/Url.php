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

// example
// $go = new Url();
// $go->set('index.php', array('a'=>'15', 'b'=>'15')) {
// $go->go(); // do redirect

//

class Url 
{
    var $page;
    var $params = array();
    var $defaults = array();
    var $defaults_rewrite = true; // false - all setted params will be added to defaults
                                   // true - all setted params will be ignored
    
    
    function __construct($page = false, $param = array()) {
        $this->set($page, $param);
    }
    
    
    function setDefaults($name, $page, $param = array()) {
        $this->defaults[$name] = array('page'=>$page, 'param'=>$param);
    }
    
    
    function set($page, $param = array()) {
        $this->setPage($page);
        $this->setParams($param);
    }
    
    
    function setParams($param) {
        if($param) {
            $this->params[] = (is_array($param)) ? urldecode(http_build_query($param)) : urldecode($param);
        } //else {
            //$this->params[] = urldecode(http_build_query($_SERVER['QUERY_STRING']));
        //}
    }
    
    
    function setPage($page = false) {
        $this->page = ($page) ? $page : $_SERVER['PHP_SELF'];
    }
    
    
    function getParams() {
        parse_str(implode('&', $this->params), $output);
        return http_build_query($output);
    }
    
    
    // redirect
    function go($param = array()) {
        header("Location:" . $this->get($param));
        exit();
    }
    
    
    function getJs($param = array()) {
        $js = "document.page.href='" . $this->get($param) . "'";
        return $js;
    }
    
    
    //function getLink($param = array()) {
    //    $js = "document.page.href='" . $this->get($param) . "'";
    //    return $js;
    //}
    
    
   /**
    * Set the Url to the URL of the current page; this can be either the full
    * URL (with parameters) or just the basename.
    * @param $completeUrl whether to use the full URL or just the basename
    * @returns void
    ***/
    function setCurrent() {
        $this->params = array();
        $this->set($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);
    }
    
    
    function getCurrent() {
        $this->setCurrent();
        return $this->get();
    }
    
    
    function get($param = array()) {
        if($param && isset($this->defaults[$param])) {
        
            if($this->defaults_rewrite == true) { 
                $this->params = array();// unset all setted params
            } 
            $this->set($this->defaults[$param]['page'], $this->defaults[$param]['param']);                    
        
        } elseif($param) {
            $this->setParams($param);
        }
        
        return ($this->format($this->page . $this->getParams()));
    }
    
    //function parse($param) {
    //    echo "<pre>"; print_r($param); echo "</pre>";
    //    return http_build_query($param);
    //}
    
    function format($str) {
    
        $str = $this->formatPage($str);
        if(strpos($str, '&') !== false) {
            $search[] = '/\?\?/'; $replace[] = '?'; 
            $search[] = '/&&/'; $replace[] = '&'; 
            $search[] = '/\?&/'; $replace[] = '?';
            $str = preg_replace($search, $replace, $str); 
        }
        
        return $str;
    }
    
    function formatPage($str) {
        if(strpos($str, '?') === false) {
            $pattern = '#\.(php|asp|jsp)[^\?]#';
            if(preg_match($pattern, $str, $match)) {
                $str = preg_replace('#'.$match[1].'#', $match[1].'?', $str); 
            }
        }
        
        return $str;
    }
}


/*
$url = new Url();
$url->setDefaults('deleted', 'some_page.php', array('good_action'=>'deleted'));
$url->setParams(array('a'=>'1', 'a'=>'1'));
$url->setParams('iuy=125&fsd[1]=15');
$url->setParams(array('iuy'=>125, 'fsd[15]'=>15));

echo $url->get();
echo "<pre>"; print_r($url); echo "</pre>";
*/

?>