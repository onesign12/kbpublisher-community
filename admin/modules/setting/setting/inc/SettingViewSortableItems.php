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

class SettingViewSortableItems extends SettingView_form
{

    var $module_id;


    function execute(&$obj, &$manager, $options = array()) {
        
        $this->addMsg('common_msg.ini', 'public_setting');
        $this->addMsgPrepend('client_msg.ini', 'public');
        $this->module_id = $options['module_id'];


        $tpl = new tplTemplatez($this->template_dir . 'sortable_items_list.html');
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $popup = $this->controller->getMoreParam('popup');
        $tpl->tplAssign('setting_name', $popup);
        $tpl->tplAssign('popup_title', $setting_msg[$popup]['title']);
        
        $items = $obj->get($popup);
        $items = unserialize($items);
        
        if(!empty($options['add_button'])) {
            $button['+'] = 'javascript:showOptionsPopup();void(0)';
            $tpl->tplAssign('buttons', $this->getButtons($button));
        
            $more = array('detail' => 1);
            $link = $this->controller->_replaceArgSeparator($this->getLink('this', 'this', 'this', false, $more));
            $tpl->tplAssign('popup_link', $link);
        }
        
        
        $view_format = SettingModel::getQuick(2, 'view_format');
        
        $group_titles = array(
            'active' => $this->msg['active_items_msg'],
            'inactive' => $this->msg['inactive_items_msg']
        );
        
        foreach ($items as $k => $rows) {
            if (empty($rows)) {
                // continue;
            }
            
            $i = 0;
            foreach ($rows as $item) {
                $v = array();
                $v['line'] = $i;
                
                if (!is_array($item)) { // built-in item
                    $v['title'] = $options['titles'][$item];
                    
                } else {
                    $v += $item;
                }
                
                // actions/links
                $links = array();
                $links['update_link'] = sprintf("javascript:showOptionsPopup('%s', %d);", $k, $i);
                $links['delete_link'] = sprintf("javascript:deleteItem('%s', %d);", $k, $i);
                $actions = $this->getListActions($item, $k, $links);
                
                $vars = $this->getViewListVarsJs($k . $i, true, true, $actions);
                $v += $vars;
                
                $tpl->tplParse($v, 'group/row');
                
                $i ++;
            }
            
            $tpl->tplSetNested('group/row');
            
            $v = array();
            $v['group_id'] = $k;
            $v['group_title'] = $group_titles[$k];
            $tpl->tplParse(array_merge($v, $this->msg), 'group');
        }

        $msg = AppMsg::getErrorMsgs();
        $tpl->tplAssign('required_msg', $msg['required_msg']);
        
        $more = array(
            'popup' => $popup,
            'default' => 1
        );
        $tpl->tplAssign('default_link', 
            $this->controller->_replaceArgSeparator($this->getLink('this', 'this', 'this', false, $more)));
            
        // blocks in use
        $more = array('key' => 'page_design_index', 'menu' => 1);
        $link = $this->getLink('setting', 'page_design', false, 'update', $more);
        $link = $this->controller->_replaceArgSeparator($link);
        $tpl->tplAssign('page_design_link', $link);

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('popup' => $popup);
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));
        
        
        $xajax->registerFunction(array('deleteItem', $this, 'ajaxDeleteItem'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        $xajax->registerFunction(array('saveSortOrder', $this, 'ajaxSaveSortOrder'));

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }


    function getListActions($item, $group_id, $links) {
        $actions = array();
        
        if (!empty($item['id'])) { // custom
            $actions['update'] = array(
                'link' => $links['update_link'],
                'msg' => $this->msg['update_msg']
            );
            
            $actions['delete'] = array(
                'link' => $links['delete_link']
            );
        }
        
        return $actions;
    }
    
    
    function ajaxDeleteItem($group, $line) {
        
        $objResponse = new xajaxResponse();

        $setting_key = $this->controller->getMoreParam('popup');

        $items = $this->manager->getSettings($this->module_id, $setting_key);
        $items = unserialize($items);
        
        unset($items[$group][$line]);
        
        $items[$group] = array_values($items[$group]);
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        return $objResponse;
    }
    
    
    function ajaxSaveSortOrder($old_line, $old_group, $new_line, $new_group) {
        
        $objResponse = new xajaxResponse();

        $setting_key = $this->controller->getMoreParam('popup');

        $items = $this->manager->getSettings($this->module_id, $setting_key);
        $items = unserialize($items);
        
        $item = $items[$old_group][$old_line];
        unset($items[$old_group][$old_line]);
        $items[$old_group] = array_values($items[$old_group]);
        
        //$items[$new_group][] = $item;
        array_splice($items[$new_group], $new_line, 0, array($item));
        
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        return $objResponse;
    }

}
?>