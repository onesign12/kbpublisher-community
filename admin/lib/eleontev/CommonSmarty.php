<?php
require_once 'Smarty/Smarty.class.php';

class CommonSmarty extends Smarty
{

    function __construct() {
        $this->setSmartyDirs();
        $this->assign('required_class', 'required');
    }
    
    function setSmartyDirs() {
        $this->template_dir = APP_TMPL_DIR;
        $this->compile_dir = APP_CACHE_DIR . 'smarty/templates_c';
        $this->config_dir = APP_CACHE_DIR . 'smarty/configs';
        $this->cache_dir = APP_CACHE_DIR . 'smarty/cache';
        //$this->plugins_dir = APP_ROOT_DIR . 'Smarty/internals';
    }
    
    function setTemplateDir() {
        
    }
}
?>