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

// note: ini_set('arg_separator.output', '&amp;'); required for IIS in some configurations
require_once "xajax/xajax.inc.php";


class Ajax
{
    
    static function processRequests($xajax = false, $post_action = false) {
        if(!$xajax) {
            $reg = &Registry::instance();
            if($reg->isEntry('ajax')) {
                $xajax = &$reg->getEntry('ajax');
            }
        }
    
        if($xajax) {
            
            // not rewrite if set 
            if(empty($xajax->post_action)) {
                $xajax->post_action = $post_action;
            }
            
            $xajax->processRequests();
            return $xajax->getJavascript();
        }
    
        return false;
    }
    
    
    static function isAjaxRequest() {
        $ret = (isset($_GET['ajax']) && isset($_POST['xajax'])) ? true : false;
        return $ret;
    }    
}



class AppAjax extends Ajax
{

    var $controller;
    var $encoding;
    var $js_dir;

    static function &factory($obj_name = false) {
        
        $reg = &Registry::instance();
        // acontroller set in client to separete controllers
        $key = ($reg->isEntry('acontroller')) ? 'acontroller' : 'controller'; 
        $controller = &$reg->getEntry($key);
        // $controller = &$reg->getEntry('controller');
        
        $class = 'AppAjax';
        if($obj_name) {
            $class = $obj_name . '_ajax';
            $file = $obj_name . '_ajax.php';
            require_once $controller->working_dir . 'inc/' . $file;            
        }

        $ajax = new $class;
        $ajax->setVars($controller);
        return $ajax;
    }
    

    function setVars(&$controller) {
        $this->controller = &$controller;
        $this->encoding = $controller->encoding;
        $this->js_dir = APP_ADMIN_PATH . 'jscript/';
    }

    
    function &getAjax($debug = false) {
        
        $reg = &Registry::instance();
        if($reg->isEntry('ajax')) {
            $xajax = &$reg->getEntry('ajax');
                    
        } else {
            $xajax = new xajax_kbp();
            $xajax->setRequestURI($this->controller->getAjaxLink('all'));
            $xajax->setCharEncoding($this->encoding);
            $xajax->decodeUTF8InputOn();
            $xajax->js_dir = $this->js_dir;
            
            $reg->setEntry('ajax', $xajax);
        }
        
        if($debug) {
            $xajax->debugOn();
        }
        
        return $xajax;    
    }
    
    
    static function getlogout() {
        $xajax = new xajax_kbp();
        $xajax->registerCatchAllFunction(array("logout", "AppAjax", "logoutResponce"));
        return $xajax->processRequests($xajax);
    }
    
    
    static function logoutResponce($link) {
        
        $link = APP_ADMIN_PATH . 'logout.php?Msg=auth_expired';
        
        $objResponse = new xajaxResponse();
        $objResponse->addRedirect($link);
        // $objResponse->addAlert($link);
        
        return $objResponse;        
    }
}



class xajax_kbp extends xajax
{

    var $js_dir    = 'jscript/';
    var $extra_js  = '<script src="%sxajax_js/spiner.js"></script>';
    var $post_action = false;
        
    
    function &getJavascript($js_dir = '', $js_file = NULL) {
        $js_dir = ($js_dir) ? $js_dir : $this->js_dir;
        $js = parent::getJavascript($js_dir);
        $js .= ($this->extra_js) ? sprintf($this->extra_js, $js_dir) : '';
        return $js;
    }
}

?>