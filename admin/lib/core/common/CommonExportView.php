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

class CommonExportView
{    

    static function getExportFormBlock($view, $options = array()) {
        
        // $action  = (isset($options['action'])) ? $options['action'] : 'file';
        
        // make compatable with old format 
        if(isset($options['action']) && !is_array($options['action'])) {
            $options['action'] = ['action' => $options['action']];
        }
        
        $p = [];
        $params = ['module', 'page', 'subpage', 'action'];
        foreach($params as $v) {
            $da = ($v == 'action') ? 'file' : 'this'; // default
            $p[$v] = (isset($options['action'][$v])) ? $options['action'][$v] : $da; 
        }
        
        $types = array('xml' => 'XML', 'csv' => 'CSV', 'xls' => 'Excel');
        $default = array('xml', 'csv', 'xls');
        $types_use = (isset($options['types'])) ? $options['types'] : $default;
        
        
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'block_export_form.html');
        
        if (!empty($options['customize'])) {
            $key = $view->controller->page;
            $search = sprintf("#%s|%s#", APP_MODULE_DIR, APP_PLUGIN_DIR);
            $_module = $view->controller->module;
            $_page = urlencode($key);
            $_view = preg_replace($search, '', $view->controller->working_dir);
            $_view .= urlencode('inc/' . get_class($view));
            $more = ['_module' => $_module,'_page' => $_page, '_view' => $_view];
            $link = $view->getLink('stuff', 'list_builder', false, 'customize_export', $more);
            
            $tpl->tplAssign('customize_link', $link);
            $tpl->tplSetNeeded('/customize_list');
        }        
        
        if (!empty($options['total_row'])) {
            $tpl->tplSetNeeded('/total_row');
        }
        
        foreach ($types_use as $type) {
            // $link = $view->getActionLink($action, false, ['type' => $type]);
            // $link = $view->getLink($__module, $__page, $__subpage, $__action, ['type' => $type]);
            // $tpl->tplAssign('export_' . $type . '_link', $link);
            
            $block = (!isset($first)) ? 'block' : 'none';
            $tpl->tplAssign($type . '_display', $block);
            
            $a = [];
            $a['type'] = $type;
            $a['type_title'] = $types[$type];
            $a['type_checked'] = (!isset($first)) ? 'checked="checked"' : '';
            $tpl->tplParse($a, 'types');
            $first = true;
        }
                
        // $link = $view->getActionLink($action); // 14-04-2023 eleontev, to work from user module
        $more = $view->controller->getMoreParams();
        $link = $view->getLink($p['module'], $p['page'], $p['subpage'], $p['action'], $more);
        $tpl->tplAssign('action', $link);
        
        $tpl->tplParse($view->msg);
        return $tpl->tplPrint(1);    
    }
    
    
    // when we need in list_in
    static function getExportVars($view, $options = array()) {
  
        $args = array(
            'export_msg' => $view->msg['export_msg'],
            'export_block' => CommonExportView::getExportFormBlock($view, $options)
        );

        $func = array(
            array('tplAssign', array($args))
        );
        
        $tmpl = APP_MODULE_DIR . 'stuff/list_builder/template/list_in_export.html';
        $tmpl = (empty($options['tmpl'])) ? $tmpl : $options['tmpl'];
    
        return array(
            'tmpl' => $tmpl,
            'func' => $func
        );
    }
    
    
    static function getListExportVars($view, $options = array()) {
        $options = [
            'types'=>['xls', 'csv', 'xml'], 
            'action' => 'export',
            'customize' => true
        ];
        
        return self::getExportVars ($view, $options);
    }
    
}
?>