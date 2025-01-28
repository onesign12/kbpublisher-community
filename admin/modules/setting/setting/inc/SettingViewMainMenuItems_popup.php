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


class SettingViewMainMenuItems_popup extends SettingView_form
{

    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'public_setting');
        $this->addMsgPrepend('client_msg.ini', 'public');
        

        $tpl = new tplTemplatez($this->template_dir . 'main_menu_items.html');
        
        $tpl->tplSetNeededGlobal('built_in_item');
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $this->pd_manager = new PageDesignModel;
        
        $popup = $this->controller->getMoreParam('popup');
        $tpl->tplAssign('setting_name', $popup);
        $tpl->tplAssign('popup_title', $setting_msg[$popup]['title']);
        
        $items = $obj->get($popup);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        $pluginable = AppPlugin::getPluginsFiltered('menu_id', true);
        
        $button['+'] = 'javascript:showOptionsPopup();void(0);';
        $button['...'] = array(
            array(
                'msg' => $this->msg['reorder_msg'],
                'link' => 'javascript:xajax_getSortableList();void(0);'
            )
        );
        
        $tpl->tplAssign('buttons', $this->getButtons($button));
        
        $more = array('detail' => 1);
        $link = $this->controller->_replaceArgSeparator($this->getLink('this', 'this', 'this', false, $more));
        $tpl->tplAssign('popup_link', $link);
        
        $view_format = SettingModel::getQuick(2, 'view_format');
        
        $group_titles = array(
            'active' => $this->msg['active_items_msg'],
            'inactive' => $this->msg['inactive_items_msg']
        );
        
        foreach ($items as $k => $rows) {
            if (empty($rows)) {
                continue;
            }
            
            $i = 0;
            foreach ($rows as $item) {
                $v = $item;
                $v['line'] = $i;
                
                if (!empty($item['id'])) { // built-in item
                    
                    if(isset($pluginable[$item['id']])) {
                        if(!AppPlugin::isPlugin($pluginable[$item['id']])) {
                            continue;
                        }
                    }
                    
                    $tpl->tplSetNeeded('row/bullet');
                    
                    if (empty($v['title'])) {
                        $v['title'] = $this->msg['menu_' . $item['id'] . '_msg'];
                    }
                }
                
                if($view_format != 'fixed') {
                    $tpl->tplSetNeeded('row/dropdown');
                    
                    if(empty($item['dropdown'])) {
                        $v['dropdown_img'] = $this->getImgLink('', 'active_d_0', '');
            
                    } else {
                        $v['dropdown_img'] = $this->getImgLink('', 'active_d_1', '');
                    }
                }
                
                // actions/links
                $links = array();
                $links['update_link'] = sprintf("javascript:showOptionsPopup('%s', %d);", $k, $i);
                $links['status_link'] = sprintf("javascript:changeStatus('%s', %d);", $k, $i);
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
        
        
        $xajax->registerFunction(array('changeStatus', $this, 'ajaxChangeStatus'));
        $xajax->registerFunction(array('deleteItem', $this, 'ajaxDeleteItem'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }


    function getListActions($item, $group_id, $links) {
        $actions = array(
            'update' => array(
                'link' => $links['update_link'],
                'msg' => $this->msg['update_msg']
            )
        );
        
        if (!empty($item['id']) && $item['id'] != 'article') {
            $msg_key = ($group_id == 'active') ? 'set_inactive_msg' : 'set_active_msg';
            $actions['read'] = array(
                'msg' => $this->msg[$msg_key],
                'link' => $links['status_link']
            );
        }
        
        if (empty($item['id'])) {
            $actions['default'] = array(
                'msg' => $this->msg['delete_msg'],
                'link' => $links['delete_link']
            );
        }
        
        
        $settings[] = $this->pd_manager->getDesign('index');
        //$settings[] = $this->pd_manager->getDesign('file');
        
        $in_use = false;
        if ($group_id == 'active' && !empty($item['id'])) {
            
            $items_to_check = array_keys($this->pd_manager->block_to_setting, $item['id']);
            if ($items_to_check) {
                foreach ($settings as $v) {
                    foreach ($v as $v1) {
                        if (in_array($v1['id'], $items_to_check) &&
                            $v1['width'] != PageDesignModel::$grid_size) {
                            $in_use = true;
                        }
                    }
                }
            }
        }
        
        if ($in_use) {
            $actions['read'] = array(
                'msg' => $this->msg[$msg_key],
                'link' => 'javascript:showInUseHint();'
            );
        }
        
        return $actions;
    }


    function ajaxChangeStatus($group, $line) {
        
        $objResponse = new xajaxResponse();

        $setting_key = $this->controller->getMoreParam('popup');

        $items = $this->manager->getSettings(2, $setting_key);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        $item = $items[$group][$line];
        unset($items[$group][$line]);
        $items[$group] = array_values($items[$group]);
        
        $new_group = ($group == 'active') ? 'inactive' : 'active';
        if ($new_group == 'inactive') { // stashing its position
            $item['prev_position'] = $line;
            $items[$new_group][] = $item;
            
        } else {
            if (!empty($item['prev_position'])) { // restoring the position
                $position = $item['prev_position'];
                unset($item['prev_position']);
                
                $new_item = array($item);
                array_splice($items[$new_group], $position, 0, $new_item);
                
            } else {
                $items[$new_group][] = $item;
            }
        }
        
        $value = ($new_group == 'active') ? 1 : 0;
        
        // setting
        if (!empty($item['id'])) {
            
            $items_to_check = array_values($this->pd_manager->block_to_setting);
            $items_to_check = array_unique($items_to_check);
            
            if (in_array($item['id'], $items_to_check)) {
                $page_design = $this->pd_manager->getDesign('index');
                
                if (!$value) {
                    foreach (array_keys($page_design) as $k) {
                        $v = $page_design[$k];
                        $block_ids = array_keys($this->pd_manager->block_to_setting, $item['id']);
                        
                        if (in_array($v['id'], $block_ids)) {
                            unset($page_design[$k]);
                        }
                    }
                }
                
                $page_design = array_values($page_design);
                $page_design = json_encode($page_design);
                
                $grid = PageDesignModel::getHtmlGrid($page_design, $this->pd_manager);
                
                $setting_id = $this->manager->getSettingIdByKey('page_design_index_html');
                $this->manager->setSettings(array($setting_id => $grid));
            }
            
            if (!empty(SettingData::$main_menu[$item['id']]['setting'])) {
                $key = SettingData::$main_menu[$item['id']]['setting'];
                $setting_id = $this->manager->getSettingIdByKey($key);
                $this->manager->setSettings(array($setting_id => $value));
            }
        }
        
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        return $objResponse;
    }
    
    
    function ajaxDeleteItem($group, $line) {
        
        $objResponse = new xajaxResponse();

        $setting_key = $this->controller->getMoreParam('popup');

        $items = $this->manager->getSettings(2, $setting_key);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        unset($items[$group][$line]);
        
        $items[$group] = array_values($items[$group]);
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        return $objResponse;
    }
    
    
    function ajaxGetSortableList() {
        
        $tpl = new tplTemplatez($this->template_dir . 'main_menu_items_sortable.html');
        
        $popup = $this->controller->getMoreParam('popup');
        
        $view_format = SettingModel::getQuick(2, 'view_format');
        
        $tpl->tplAssign('first_block_caption', $this->msg['visible_items_msg']);
        
        $items = $this->obj->get($popup);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        if ($view_format != 'fixed') {
            $tpl->tplSetNeeded('/first_block');
            $tpl->tplSetNeeded('/dropdown_title');
        }
        
        $pluginable = AppPlugin::getPluginsFiltered('menu_id', true);
        
        // current items
        $i = 0;
        foreach ($items['active'] as $item) {
            $v = $this->msg;
            $v['display]'] = 'block';
            
            if (!empty($item['id'])) { // built-in item
                $v['title'] = (empty($item['title'])) ? $this->msg['menu_' . $item['id'] . '_msg'] : $item['title'];

                if(isset($pluginable[$item['id']])) {
                    if(!AppPlugin::isPlugin($pluginable[$item['id']])) {            
                        $v['display'] = 'none';
                    }
                }
            
            } else {
                $v['title'] = trim($item['title']);
            }
            
            $v['line'] = $i;
            
            if ($item['dropdown']) {
                $tpl->tplParse($v, 'dropdown_item');
                
            } else {
                $tpl->tplParse($v, 'visible_item');
            }
            
            $i ++;
        }
        
        $tpl->tplParse($this->msg);
        
        
        $objResponse = new xajaxResponse();
        
        $objResponse->script("$('.bb_popup').remove();");        
        $objResponse->addAssign('extra_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');
    
        return $objResponse;
    }

}
?>