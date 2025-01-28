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
// $go = new Redirect();
// $go->setLocation('index.php', array('a'=>'15', 'b'=>'15')) {
// $go->go(); // do redirect

//

class Redirect
{
    var $location;
    var $params = array();
    var $defaults = array();
    var $defaults_rewrite = false; // false - all setted params will be added to defaults
                                   // true - all setted params will be ignored


    function __construct($page = null, $param = array()) {
        $page = ($page) ? $page : $_SERVER['PHP_SELF'];
        $this->setLocation($page, $param);
    }

    function setDefaults($name, $page, $param = array()) {
        $this->defaults[$name] = array('location'=>$page, 'param'=>$param);
    }

    function setLocation($page, $param = array()) {
        $this->location = $page;
        $this->setParams($param);
    }

    function setParams($param) {
        if($param) {
            $this->params[] = (is_array($param)) ? http_build_query($param) : $param;
        }
    }

    function go($param = array()) {
        header("location:" . $this->getLocation($param));
        exit();
    }

    function getJs($param = array()) {
        $js = "document.location.href='" . $this->getLocation($param) . "'";
        return $js;
    }

    function getLocation($param = array()) {
        if($param && isset($this->defaults[$param])) {

            if($this->defaults_rewrite == true) {
                $this->params = array();// unset all setted params
            }
            $this->setLocation($this->defaults[$param]['location'], $this->defaults[$param]['param']);

        } elseif($param) {
            $this->setParams($param);
        }

        return $this->format($this->location . implode('&', $this->params));
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


//$go = new Redirect();
//$go->setDefaults('deleted', 'some_page.php', array('good_action'=>'deleted'));
//$go->setParams(array('a'=>'1', 'a'=>'1'));
//echo $go->getLocation();
//echo "<pre>"; print_r($go); echo "</pre>";
?>
