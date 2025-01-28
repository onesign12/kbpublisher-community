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


class ListBuilderView_customize_export extends ListBuilderView_customize
{
    
    var $setting_key = 'export_columns';
    var $template = 'customize_popup.html';
    var $reload_parent = 0;
    
    
    function execute(&$obj, &$manager) {   
                                             
        $_module = $this->controller->getMoreParam('_module');
        $_page = $this->controller->getMoreParam('_page');
        $_view = urldecode(urldecode($this->controller->getMoreParam('_view')));
        $_d = $this->controller->getMoreParam('_d');
        $column_key = $_page;
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplSetNeeded('/check_all');
        
        
        $file = APP_MODULE_DIR .  $_view . '.php';
        if($_d == 'plugin') {
            $file = APP_PLUGIN_DIR .  $_view . '.php';
        }
        
        if(file_exists($file)) {
            require_once $file;
            $class = explode('/', $_view);
            $class = $class[count($class)-1];
            
            if(method_exists($class, 'execute')) {
                $eview = new $class;
                $eview->setModuleMsg($_module);
            }
        } 
        
        if(!isset($eview)) {
            die('Error!');
        }
        
        $columns = $this->getColumns($column_key, $eview);
        
        foreach ($columns['sort'] as $column_key) {
            $v = array();
            $v['id'] = $column_key;
            $v['title'] = $this->getFieldTitleKey($column_key, $eview);
            $v['checked'] = (in_array($column_key, $columns['disp'])) ? 'checked' : '';
            
            $tpl->tplParse($v, 'row'); 
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array('_page' => $_page, '_view' => $_view, '_d' => $_d);
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
            
        $xajax->registerFunction(array('saveCustomizedList', $this, 'ajaxSaveCustomizedList'));
        $xajax->registerFunction(array('setDefaultList', $this, 'ajaxSetDefaultList'));
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFieldTitleKey($column_key, $view) {
        
        static $list_columns_raw = null; 
        if($list_columns_raw === null) {
            $list_columns_raw = $view->getListColumns();
            $list_columns_raw = array_merge_recursive(ListBuilderData::$options, $list_columns_raw);
        }
        
        $title_key = $column_key . '_msg';
        if(isset($list_columns_raw[$column_key]['title'])) {
            $title_key = $list_columns_raw[$column_key]['title'];
        }
        
        return $view->msg[$title_key];
    }
    
    
    function getColumns($column_key, $view) {
        $default_columns = $view->columns_export;
        $setting_columns = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, 'export_columns');
        $setting_columns = unserialize($setting_columns);
        $columns = $default_columns;
        $columns_sort = $default_columns;
        if(isset($setting_columns[$column_key])) {
            $columns = array_intersect($setting_columns[$column_key], $default_columns);
            $columns_sort = array_intersect($setting_columns[$column_key.'_sort'], $default_columns);
        };
        
        return ['disp' => $columns, 'sort' => $columns_sort];
    }
    
}
?>