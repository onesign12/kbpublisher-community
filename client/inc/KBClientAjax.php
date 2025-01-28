<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class KBClientAjax extends Ajax
{

    var $vars = array();


    static function &factory($view) {
        
        $reg = &Registry::instance();
        $controller = &$reg->getEntry('controller');
        
        $class = 'KBClientAjax_' . $view;
        $file = 'KBClientAjax_' . $view . '.php';
        require_once $controller->kb_dir . 'client/inc/ajax/' . $file;

        $ajax = new $class;
        $ajax->setVars($controller);
        return $ajax;
    }
    

    function setVars(&$controller) {
        
        $this->controller = &$controller;
        
        $this->encoding = $controller->encoding;
        $this->entry_id = $controller->entry_id;
        $this->category_id = $controller->category_id;
        $this->js_dir = $controller->client_path . 'jscript/';
    }


    function setVar($key, $value) {
        $this->vars[$key] = $value;
    }

    
    function &getAjax(&$manager, $debug = false) {
        
        $this->manager = &$manager;
        
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
        
        // $reg->setEntry('ajax', $xajax);
        
        return $xajax;
    }
    
    
    function getlogout() {
        $xajax = new xajax_kbp();
        $xajax->registerCatchAllFunction(array("logout", "KBClientAjax", "logoutResponce"));
        return $xajax->processRequests($xajax);
    }
    
    
    static function logoutResponce($link) {
        
        $link = APP_CLIENT_PATH . 'index.php?View=login';
        
        $objResponse = new xajaxResponse();
        $objResponse->addRedirect($link);
        // $objResponse->addAlert($link);
        
        return $objResponse;        
    }
}

?>