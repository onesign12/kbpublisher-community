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


class ListBuilderView_customize extends AppView
{
    
    var $template = 'customize_popup.html';
    var $setting_key = 'list_columns';
    var $reload_parent = 1;
    
    
    function execute(&$obj, &$manager) {   
                                     
        $this->addMsg('log_msg.ini');      
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'file');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $_module = $this->controller->getMoreParam('_module');
        $_page = $this->controller->getMoreParam('_page');
        $_view = urldecode(urldecode($this->controller->getMoreParam('_view')));
        $_d = $this->controller->getMoreParam('_d');
        
        if(strpos($_page, 'export') !== false) {
            $this->addMsg('common_msg.ini', 'export');
        }
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
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
            
        $list_columns_raw = $eview->getListColumns();     
           
        $list_columns = array();
        foreach($list_columns_raw as $k => $v) {
            if(!is_array($v)) {
                $list_columns[$v] = array();
                
            } elseif(empty($v['hidden'])) {
                $list_columns[$k] = $v;
            }
        }
        
        // remove plugins
        $pluginable = AppPlugin::getPluginsFiltered('column', true);
        foreach($pluginable as $k => $plugin) {
            if(!AppPlugin::isPlugin($plugin)) {
                unset($list_columns[$k]);
            }
        }
        
        $list = new ListBuilder($eview, $_page);
         
        $columns = $list->getColumns();
        $columns_sort = ($cs = $list->getColumnsSort()) ? $cs : array_keys($list_columns);
        
        foreach ($columns_sort as $column_key) {
            
            $v = array();
            $v['id'] = $column_key;
            $v['title'] = $list->getColumnTitle($column_key, array_merge($this->msg, $eview->msg));
            $v['checked'] = (in_array($column_key, $columns)) ? 'checked' : '';
            
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

    
    function ajaxSaveCustomizedList($data, $sort) {
        
        $objResponse = new xajaxResponse();
        
        $list_columns_key = $this->controller->getMoreParam('_page');
        $setting_columns = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, $this->setting_key);
        
        $setting_data = unserialize($setting_columns); // all lists 
        $setting_data[$list_columns_key] = $data; // updated list        
        $setting_data[$list_columns_key.'_sort'] = $sort; // updated list        
        $setting_data = serialize($setting_data);

        $setting_id = $this->manager->sm_manager->getSettingIdByKey($this->setting_key);
        $this->manager->sm_manager->setSettings(array($setting_id => $setting_data));
        
        $objResponse->call('showSpinner', 'none');
        $objResponse->call('reloadList', $this->reload_parent);
        
        return $objResponse;
    }
    
    
    function ajaxSetDefaultList() {
        
        $objResponse = new xajaxResponse();
        
        $list_columns_key = $this->controller->getMoreParam('_page');
        $setting_columns = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, $this->setting_key);
        
        $setting_data = unserialize($setting_columns);
        if(isset($setting_data[$list_columns_key])) {
            unset($setting_data[$list_columns_key]);
            unset($setting_data[$list_columns_key . '_sort']);
        };
        
        $setting_id = $this->manager->sm_manager->getSettingIdByKey($this->setting_key);
        
        // save other lists
        if($setting_data) {
            $setting_data = serialize($setting_data);
            $this->manager->sm_manager->setSettings(array($setting_id => $setting_data));   
            
        // no ohter lists, remove setting         
        } else {
            $this->manager->sm_manager->setDefaultValues($setting_id);
        }
        
        $objResponse->call('showSpinner', 'none');
        $objResponse->call('reloadList', $this->reload_parent);
        
        return $objResponse;
    }
    
    
    function array_insert($array, $values, $offset) {
        return array_slice($array, 1, $offset, true) + $values + array_slice($array, $offset, NULL, true);  
    }
}
?>