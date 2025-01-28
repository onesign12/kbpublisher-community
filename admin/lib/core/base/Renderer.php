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

class Renderer
{
    
    
    function __construct(&$builder) {
        $this->builder =& $builder;
    }
    
    
    function render() {
    
        $tpl = new tplTemplatez($this->builder->template);
        
        $data['menu'] =& $this->builder->getMenu();
        $data['content'] =& $this->builder->getContent('32121321');
        $data['debug_box'] =& $this->builder->getDebugBox(1,1);        
        
        $tpl->tplParse($data);
        return $tpl->tplPrint(1);
    }
    
    
    function display() {
        echo $this->render();
    }
}


// just empty functions
class PageBuilder extends AppController
{

    function getMenu()     { return; }
    function getContent()  { return; }
    function getDebugBox() { return; }

}



class PageBuilderCommon extends PageBuilder
{
    
    var $template = 'common_template.html ';
    
    
    function &getMenu() {
        $nav = new Navigation;
        $nav->setEqualAttrib('GET', $this->query['module'] );
        $nav->setSubEqualAttrib('GET', $this->query['page']); 
        $nav->setTemplate(APP_TMPL_DIR . 'sub_menu.html');
        $nav->setTemplateCustomRule(true);
        $nav->getMenu($this->module);
        
        return $nav->generate();
    }
    
    
    function &getContent($view)  {
        $page = $this->working_dir . 'action.php';
        require_once ($page);
    }
    
    
    function &getDebugBox($debug_info, $debug_speed) { 
        
        if($debug_info) {
            $html = "<pre><b>GET:</b><br>"; print_r($_GET, 1); "</pre>";
            $html .= "<pre><b>SESSION:</b><br>"; print_r($_SESSION, 1); "</pre>";
        }
        
        if($debug_speed) {
            //timeprint("%min %max graf");
        }
    }
}
?>