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


class SettingViewMainMenuItems_detail_popup extends SettingViewMainMenuItems_popup
{
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('error_msg.ini');
        $this->addMsg('client_msg.ini', 'public');
        $this->addMsg('common_msg.ini', 'public_setting');


        $tpl = new tplTemplatez($this->template_dir . 'form_main_menu_item.html');
        
        $popup = $this->controller->getMoreParam('popup');
        $view_format = SettingModel::getQuick(2, 'view_format');
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more_ajax = array('popup' => $popup, 'detail' => 1);
        
        $group_key = $this->controller->getMoreParam('group');
        if ($group_key) {
            $popup_title = $this->msg['detail_msg'];
            
            $tpl->tplAssign('save_method', 'xajax_updateItem');
            
            $line_num = $this->controller->getMoreParam('line');
            
            $items = $obj->get($popup);
            $items = unserialize($items);
            $items = SettingData::getMainMenu($items);
            
            $item = $items[$group_key][$line_num];
            
            $dropdown = true;
            if ($view_format == 'fixed') {
                $dropdown = false;
            }
            
            if (!empty($item['id'])) {
                $tpl->tplSetNeededGlobal('built_in');
                
                if ($item['id'] == 'article') {
                    $dropdown = false;
                }
                
                if (empty($item['title'])) {
                    $item['title'] = $this->msg['menu_' . $item['id'] . '_msg'];
                }
                
            } else {
                $tpl->tplSetNeededGlobal('extra');
            }
            
            if ($dropdown) {
                $tpl->tplSetNeeded('/dropdown');
                $item['dropdown_attr'] = ($item['dropdown']) ? 'checked' : '';
            }
            
            $item['target_attr'] = (!empty($item['target'])) ? 'checked' : '';
            $item['logged_attr'] = (!empty($item['logged'])) ? 'checked' : '';
            
            $item = RequestDataUtil::stripVars($item, array(), true);
            $tpl->tplAssign($item);
            
            $more_ajax['group'] = $group_key;
            $more_ajax['line'] = $line_num;
            
        } else {
            $popup_title = $this->msg['add_extra_item_msg'];
            
            if ($view_format != 'fixed') {
                $tpl->tplSetNeeded('/dropdown');
            }
            
            $tpl->tplAssign('save_method', 'xajax_addItem');
            $tpl->tplSetNeededGlobal('extra');
        }
        
        $tpl->tplAssign('popup_title', $popup_title);
        
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));
        
        $xajax->registerFunction(array('addItem', $this, 'ajaxAddItem'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        
        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }
    

    function ajaxAddItem($data) {
        
        $objResponse = new xajaxResponse();
        
        $setting_key = $this->controller->getMoreParam('popup');
        
        $items = $this->manager->getSettings(2, $setting_key);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        $items['active'][] = array(
            'title' => $data['title'],
            'link' => $data['link'],
            'options' => $data['options'],
            'dropdown' => (@$data['more']),
            'target' => (@$data['target']),
            'logged' => (@$data['logged'])
        );
        
        $items['active'] = array_values($items['active']);
        
        usort($items['active'], function($a, $b) {
            return $a['dropdown'] - $b['dropdown'];
        });
        
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->call('refreshItems');
        return $objResponse;
    }
    
    
    function ajaxUpdateItem($data) {
        
        $objResponse = new xajaxResponse();
        
        $setting_key = $this->controller->getMoreParam('popup');
        $group_key = $this->controller->getMoreParam('group');
        $line_num = $this->controller->getMoreParam('line');
        
        $items = $this->manager->getSettings(2, $setting_key);
        $items = unserialize($items);
        $items = SettingData::getMainMenu($items);
        
        $item = $items[$group_key][$line_num];
        
        $items[$group_key][$line_num] = array(
            'title' => $data['title'],
            'dropdown' => (@$data['more']),
            'target' => (@$data['target']),
            'logged' => (@$data['logged'])
        );
        
        if (empty($item['id'])) {
            $items[$group_key][$line_num]['link'] = $data['link'];
            $items[$group_key][$line_num]['options'] = $data['options'];
            
        } else {
            $items[$group_key][$line_num]['id'] = $item['id'];
            
            // unset title if the same as in msg
            if(isset($this->msg['menu_' . $item['id'] . '_msg'])) {
                if($data['title'] == $this->msg['menu_' . $item['id'] . '_msg']) {
                    unset($items[$group_key][$line_num]['title']);
                }
            }
        }
        
        $items['active'] = array_values($items['active']);
        
        usort($items['active'], function($a, $b) {
            return $a['dropdown'] - $b['dropdown'];
        });
        
        $items = serialize($items);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        $objResponse->call('refreshItems');
        return $objResponse;
    }

}
?>