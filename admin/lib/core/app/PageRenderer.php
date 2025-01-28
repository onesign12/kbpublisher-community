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

class PageRenderer
{

    var $title_delim = ' / ';
    var $template_dir;
    var $template = 'page.html';
    var $needed = array();
    
    var $meta_title;
    var $meta_keyword;
    var $meta_charset;
    var $tpl;
    
    var $vars = array(
        'meta_title'        => 'Title',  
        'meta_keyword'      => '',  
        'meta_description'  => '',  
        'meta_charset'      => '',
        'meta_content'      => ''
        );
    
    
    function __construct($engine = 'tpl_templatez') {
        $this->tpl = &$this->setEngine($engine);
    }
    
    
    function &setEngine($engine) {
        if($engine == 'tpl_templatez') {
            $t = new PageRenderer_templatez();
            return $t;
        
        } elseif($engine == 'smarty') {
            $t = new PageRenderer_smarty();
            return $t;
        } 
    }
    
    
    function setNeeded($key) {
        $this->needed[] = $key;
    }
    
    
    function render() {
        return $this->tpl->render($this->vars, $this->template_dir, $this->template, $this->needed);    
    }
    
    
/*
    function assign(&$var, $value = false) {
        
        if($value) {
            $this->vars[$value] = &$var; 
        
        } else {
        
            if(is_array($var)) {
                foreach($var as $k => $v) {
                    $this->vars[$k] = &$var[$k];
                }
            } else {
                if($value === false) {
                    $msg = "%s Can't assign value for var - <b>%s</b>. Missing argument 2 for tplAssign();";
                    trigger_error(sprintf($msg, $this->error_pref, $val));
                } else { 
                    $this->vars[$value] = &$var; 
                }
            }            
        }
    }*/


    function assign($var, $value = false) {
        
        if($value) {
            $this->vars[$value] = $var; 
        
        } else {
        
            if(is_array($var)) {
                foreach($var as $k => $v) {
                    $this->vars[$k] = $var[$k];
                }
            } else {
                if($value === false) {
                    $msg = "%s Can't assign value for var - <b>%s</b>. Missing argument 2 for tplAssign();";
                    trigger_error(sprintf($msg, $this->error_pref, $val));
                } else { 
                    $this->vars[$value] = $var; 
                }
            }            
        }
    }
    
    
    function setTemplate($var) {
        $this->template = $var;
    }
    
    
    function display() {
        echo $this->render();
    }
}



class PageRenderer_templatez
{
    
    function render($vars, $template_dir, $template, $needed = array()) {
        
        $tpl = new tplTemplatez($template_dir . $template);
        $tpl->strip_vars = true;
        $tpl->s_var_tag = '{';
        $tpl->e_var_tag = '}';
        
        foreach($needed as $v) {
            $tpl->tplSetNeeded('/' . $v);
        }
        
        $tpl->tplParse($vars);
        return $tpl->tplPrint(1);        
    }    
}



class PageRenderer_smarty
{
    
    function render($vars, $template_dir, $template) {
        
        $tpl = new CommonSmarty();
        $tpl->template_dir = $template_dir;
        $tpl->assign($vars);
        return $tpl->fetch($template);
    }    
}

?>