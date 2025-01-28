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


class PageDesignView_form extends AppView
{
    
    var $tmpl = 'page.html';
    
    
    function execute(&$obj, &$manager) {

        $this->addMsg('client_msg.ini', 'public');
        $this->addMsg('common_msg.ini', 'public_setting');
        $this->addMsg('page_design_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $tpl->tplAssign('grid_size', PageDesignModel::$grid_size);
        $tpl->tplAssign('setting_name', $this->controller->getMoreParam('key'));
        
        $vars = $this->setCommonFormVars($obj);
        
        // breadcrumbs
        $nav = array();
        $nav[2]['item'] = $this->msg['pages_msg'];
        $nav[2]['link'] = $vars['cancel_link'];
        
        $page_key = substr($this->controller->getMoreParam('key'), 12);
        $nav[3]['item'] = $this->msg['pages'][$page_key];
        
        $nav = sprintf('<b style="line-height: 25px;">%s</b>', $this->getBreadCrumbNavigation($nav));
        $tpl->tplAssign('nav', $nav);
        
        $msg = '+';
        $link = "javascript:$('#customizePopup').dialog('open');";
        $button = array($msg => $link);
        $tpl->tplAssign('add_block_btn', $this->getButtons($button));
        
        
        if(SettingModel::getQuick(2, 'view_header')) {
            $tpl->tplSetNeeded('/header');
        }
        
        if(SettingModel::getQuick(2, 'view_format') != 'default') {
            $tpl->tplSetNeededGlobal('menu');
            
            $menu_display = 'table-cell';
            
            $key = $this->controller->getMoreParam('key');
            $menu_setting_key = $key . '_menu';
            
            $menu_setting = SettingModel::getQuick(11, $menu_setting_key);
            if (!is_null($menu_setting)) { // menu setting exists for this page
                $tpl->tplSetNeededGlobal('menu_editable');
            
                if (!$menu_setting) {
                    $menu_display = 'none';
                }
            }
            
            $tpl->tplAssign('menu_display', $menu_display);
        }

        $blocks = $manager->getDesign($page_key);
        foreach ($blocks as $block) {
            
            if (!empty($block['id']) && !empty($manager->block_to_setting[$block['id']])) {
                $setting = SettingModel::getQuick(0, 'module_' . $manager->block_to_setting[$block['id']]);
                if (!$setting) {
                    if ($page_key != 'files' || $manager->block_to_setting[$block['id']] != 'file') {
                        continue;
                    }
                }
            }
            
            $settings_attr = array();
            
            if (!empty($block['id'])
                && isset(PageDesignData::$blocks[$block['id']]['editable'])
                && PageDesignData::$blocks[$block['id']]['editable'] === false) {
                    
                $block['editable'] = '';
                
            } else {
                $block['editable'] = sprintf('class="editable" title="%s"', $this->msg['editable_hint_msg']);
            }
            
            if (empty($block['id'])) { // empty cell
                $block['title'] = sprintf('<i>%s</i>', $this->msg['space_msg']);
                $block['editable'] = '';
                
            } elseif (substr($block['id'], 0, 6) == 'custom') { // custom block
                $block_id = substr($block['id'], 7);
                $custom_block = $manager->getById($block_id);
                $options = unserialize($custom_block['data_string']);
                
                if (empty($block['settings']['title'])) {
                    $block['title'] = $options['title'];
                    if (empty($block['title'])) {
                        $block['title'] = sprintf('<i>%s #%s</i>', $this->msg['custom_block_msg'], $block_id);
                    }
                    
                } else {
                    $block['title'] = $block['settings']['title'];
                }
                
                $tpl->tplSetNeeded('row/settings_popup');
                $block['setting_popup'] = $this->getLink('this', 'this', 'this', 'custom_block', array('id' => $block_id));
                
            } else {
                
                if (empty($block['settings']['title'])) {
                    $key = PageDesignData::$blocks[$block['id']]['title'];
                    $block['title'] = $this->msg[$key];
                    
                } else {
                    $block['title'] = $block['settings']['title'];
                    $settings_attr[] = sprintf('data-title="%s"', $block['title']);
                }
                
                if (!empty(PageDesignData::$blocks[$block['id']]['settings']) || $block['editable']) {
                    $tpl->tplSetNeeded('row/settings_popup');
                    $block['setting_popup'] = $this->getLink('this', 'this', 'this', 'setting');
                }
            }
            
            if (!empty($block['id'])
                && isset(PageDesignData::$blocks[$block['id']]['resizable'])
                && PageDesignData::$blocks[$block['id']]['resizable'] === false) {
                $tpl->tplParse($block, 'not_resizable');
            }
            
            if (empty($block['id']) || !isset(PageDesignData::$blocks[$block['id']]['removable']) || PageDesignData::$blocks[$block['id']]['removable'] !== false) {
                $tpl->tplSetNeeded('row/remove_icon');
            }
            
            
            // settings
            if (!empty($block['id']) && !empty(PageDesignData::$blocks[$block['id']]['settings'])) {
                foreach (PageDesignData::$blocks[$block['id']]['settings'] as $setting_key => $default_value) {
                    $settings_attr[] = sprintf('data-%s="%s"', $setting_key, $block['settings'][$setting_key]);
                    
                    if (in_array($setting_key, array('num_entries', 'num_columns'))) {
                        $block['block_num'] = sprintf('(%s)', $block['settings'][$setting_key]);
                    }
                }
            }
            
            $block['settings_attr'] = implode(' ', $settings_attr);
            
            $tpl->tplParse($block, 'row');
        }
        
        foreach (PageDesignData::$blocks as $k => $v) {
            if (isset($v['pages']) && !in_array($page_key, $v['pages'])) {
                continue;
            }
            
            $v['block_id'] = $k;
            
            $key = $v['title'];
            $v['block_name'] = $this->msg[$key];
            
            $v['block_class'] = 'block';
            $v['block_onclick'] = 'onclick="addBlock(this);"';
            if (!empty($manager->block_to_setting[$k])) {
                $setting = SettingModel::getQuick(0, 'module_' . $manager->block_to_setting[$k]);
                if (!$setting) {
                    if ($page_key != 'files' || $manager->block_to_setting[$k] != 'file') {
                        $v['block_class'] = 'block disabled off';
                        $v['block_onclick'] = '';
                    }
                }
            }
            
            $tpl->tplParse($v, 'block_row');
        }

        $tpl->tplAssign('header_title', SettingModel::getQuick(2, 'header_title'));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array(
            'key' => $this->controller->getMoreParam('key'),
            'menu' => $this->controller->getMoreParam('menu'),
            'popup' => $this->controller->getMoreParam('popup'),
        );
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        
        $xajax->registerFunction(array('saveGrid', $this, 'ajaxSaveGrid'));
        $xajax->registerFunction(array('saveMenuStatus', $this, 'ajaxSaveMenuStatus'));
        $xajax->registerFunction(array('getBlock', $this, 'ajaxGetBlock'));
        $xajax->registerFunction(array('getEmptyBlock', $this, 'ajaxGetEmptyBlock'));
        $xajax->registerFunction(array('loadCustomBlockList', $this, 'ajaxLoadCustomBlockList'));
        $xajax->registerFunction(array('updateCustomBlockTitle', $this, 'ajaxUpdateCustomBlockTitle'));
        $xajax->registerFunction(array('deleteCustomBlock', $this, 'ajaxDeleteCustomBlock'));
        
        $tpl->tplAssign('custom_block_popup', $this->getLink('this', 'this', 'this', 'custom_block'));
        
        $tpl->tplAssign($vars);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxSaveGrid($data) {
        $objResponse = new xajaxResponse();
        
        $key = $this->controller->getMoreParam('key');
        $setting_id = $this->manager->sm->getSettingIdByKey($key);
        
        $html_setting_key = $key . '_html';
        $html_setting_id = $this->manager->sm->getSettingIdByKey($html_setting_key);
        
        try {
            $grid = PageDesignModel::getHtmlGrid($data, $this->manager);
            
        } catch (Exception $e) {
            $bad_block_id = $e->getMessage();
            
            $growl_cmd = '$("#growls").empty();$.growl.error({title: "", message: "%s", fixed: true});';
            $growl_cmd = sprintf($growl_cmd, $this->msg['bad_grid_desc_msg']);
            $objResponse->script($growl_cmd);
            
            $script = '$("#%s > div:first").addClass("grid_error").fadeTo(200, 0.1).fadeTo(300, 1.0)';
            $script = sprintf($script, $bad_block_id);
            $objResponse->script($script);
            
            return $objResponse;
        }
        
        $settings = array(
            $setting_id => $data,
            $html_setting_id => $grid
        );
        $this->manager->sm->setSettings($settings);
        
        $blocks = json_decode($data);
        foreach ($blocks as $block) {
            if (!empty($block->id) && !empty(PageDesignData::$blocks[$block->id]['post_actions'])) {
                foreach (PageDesignData::$blocks[$block->id]['post_actions'] as $method_name) {
                    $this->manager->{$method_name}();
                }
            }
        }
        
        if ($this->controller->getMoreParam('popup')) {
            $script = 'var parent_window = PopupManager.getParentWindow();parent_window.location.reload();PopupManager.close();';
            $objResponse->script($script);
            
        } else {
            // $url = $this->controller->getCommonLink();
            // $more = ['key' => $key];
            // if ($this->controller->getMoreParam('menu')) {
                // $more['popup'] = 1;
            // }
            // $url = $this->controller->getLink('all', 0, 0, 0, $more);
            // $url = $this->controller->_replaceArgSeparator($url);
            
            $msg = AppMsg::getMsg('after_action_msg.ini', false, 'saved_simple');
            $growl_cmd = '$("#growls").empty();$.growl({title: "", message: "%s"});';
            $growl_cmd = sprintf($growl_cmd, $msg['body']);
            $objResponse->script($growl_cmd);
            $objResponse->script('LeaveScreenMsgCheck();');
            
            // $objResponse->script(sprintf('console.log("%s");', $url));
            // $objResponse->script(sprintf('LeaveScreenMsg.skipCheck();location.href = "%s";', $url));
        }
        
        return $objResponse;
    }
    
    
    function ajaxSaveMenuStatus($status) {
        $objResponse = new xajaxResponse();
        
        $key = $this->controller->getMoreParam('key');
        $menu_setting_key = $key . '_menu';
        $menu_setting_id = $this->manager->sm->getSettingIdByKey($menu_setting_key);
        
        $settings = array(
            $menu_setting_id => $status
        );
        $this->manager->sm->setSettings($settings);
        
        return $objResponse;
    }
    
    
    function ajaxGetBlock($id) {
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $block = array(
            'id' => $id,
            'x' => 0,
            'y' => 0,
            'width' => 0,
            'height' => 0,
            'editable' => sprintf('class="editable" title="%s"', $this->msg['editable_hint_msg']),
            'block_num' => ''
        );
        
        $tpl->tplSetNeeded('row/remove_icon');
        
        if (substr($id, 0, 6) == 'custom') { // custom block
            $block_id = substr($id, 7);
            $custom_block = $this->manager->getById($block_id);
            $options = unserialize($custom_block['data_string']);
            
            $block['title'] = $options['title'];
            if (empty($block['title'])) {
                $block['title'] = sprintf('<i>%s #%s</i>', $this->msg['custom_block_msg'], $block_id);
            }
            
            $tpl->tplSetNeeded('row/settings_popup');
            $block['setting_popup'] = $this->getLink('this', 'this', 'this', 'custom_block', array('id' => $block_id));
            
        } else {
            $key = PageDesignData::$blocks[$id]['title'];
            $block['title'] = $this->msg[$key];
            
            // settings
            @$editable = (PageDesignData::$blocks[$id]['editable']);
            $block_setttings = ($bs = PageDesignData::$blocks[$id]['settings']) ? $bs : [];
            
            $settings_attr = array();
            if ($block_setttings || $editable) {
                foreach ($block_setttings as $setting_key => $default_value) {
                    $settings_attr[] = sprintf('data-%s="%s"', $setting_key, $default_value);
                    
                    if (in_array($setting_key, array('num_entries', 'num_columns'))) {
                        $block['block_num'] = sprintf('(%s)', $default_value);
                    }
                }

                $tpl->tplSetNeeded('row/settings_popup');
                $block['setting_popup'] = $this->getLink('this', 'this', 'this', 'setting');
            }
            
            $block['settings_attr'] = implode(' ', $settings_attr);
            
            // if (!empty(PageDesignData::$blocks[$block['id']]['settings'])) {
            //     $tpl->tplSetNeeded('row/settings_popup');
            //     $block['setting_popup'] = $this->getLink('this', 'this', 'this', 'setting');
            // }
        }
        
        $tpl->tplParse($block, 'row');
        
        $width = ($id == 'search') ? PageDesignModel::$grid_size : 3;
        $auto = ($id == 'search') ? false : true;
        
        $objResponse->call('grid.addWidget', $tpl->parsed['row'], 0, 0, $width, 1, $auto);
        $objResponse->call('makeEditable', sprintf('#%s .editable', $id));
                
        return $objResponse;
    }


    function ajaxGetEmptyBlock() {
        $objResponse = new xajaxResponse();
        
        $html = '<div class="grid-stack-item">
                    <div class="grid-stack-item-content">
                        <div>
                            <span style="float: left;"><i>%s</i></span>
                        </div>
                        
                        <div class="widget_actions">
                            <img src="images/icons/close2.svg" class="remove_block_icon"
                                onclick="grid.removeWidget($(this).parents().eq(2));" />
                        </div>
                    </div>
                </div>';
                
        $html = sprintf($html, $this->msg['space_msg']);
        $objResponse->call('grid.addWidget', $html, 0, 0, PageDesignModel::$grid_size, 1, true);
        
        $objResponse->script("$('#popup_empty').removeClass('disabled');");
                
        return $objResponse;
    }


    function ajaxLoadCustomBlockList() {
        $objResponse = new xajaxResponse();
        
        $obj = new PageDesignCustomBlock;
        
        $this->manager->setSqlParams(sprintf('AND data_key = "%s"', $obj->get('data_key')));
        $rows = $this->stripVars($this->manager->getRecords(), array('data_string'));
        
        $html = '';
        
        foreach ($rows as $row) {
            $options = unserialize($row['data_string']);
            
            $vars = array(
                'id' => $row['id'],
                'update_msg' => $this->msg['update_msg'],
                'delete_msg' => $this->msg['delete_msg'],
            );
            
            $vars['title'] = $options['title'];
            if (empty($vars['title'])) {
                $vars['title'] = sprintf('<i>%s #%s</i>', $this->msg['custom_block_msg'], $row['id']);
            }
            
            $vars['link'] = $this->getLink('this', 'this', 'this', 'custom_block', array('id' => $row['id']));
            
            $html .= self::getCustomBlock($vars);
        }
            
        $objResponse->assign('custom_block_list', 'innerHTML', $html);
        $objResponse->assign('custom_block_caption', 'style.display', ($html) ? 'block' : 'none');
        $objResponse->call('disableBlocks');
        
        return $objResponse;
    }


    static function getCustomBlock($vars) {
        $custom_block_str = '<div id="popup_custom_%s" class="block">
            <div onclick="addBlock($(this).parent().get(0));"><div style="text-overflow: ellipsis;white-space:nowrap;width: 150px;overflow:hidden;">%s</div></div>
            <div data-jq-dropdown="#custom_block_menu%s" style="cursor: pointer;float: right;">
                <a href="#"><img src="images/icons/action.svg" height="14" style="border: 0px;" /></a>
            </div>
            <div id="custom_block_menu%s" class="jq-dropdown jq-dropdown-tip jq-dropdown-relative jq-dropdown-anchor-right">
                <ul class="jq-dropdown-menu">
                    <li><a href="javascript:PopupManager.create(\'%s\');">%s</a></li>
                    <li><a href="javascript:deleteCustomBlock(%s);">%s</a></li>
                </ul>
            </div></div>';
            
        return sprintf($custom_block_str,
            $vars['id'],
            $vars['title'],
            $vars['id'],
            $vars['id'],
            $vars['link'],
            $vars['update_msg'],
            $vars['id'],
            $vars['delete_msg']
        );
    }
    
    
    function ajaxUpdateCustomBlockTitle($id, $title) {
        $objResponse = new xajaxResponse();
        
        $block_id = substr($id, 7);
        $custom_block = $this->manager->getById($block_id);
        $options = unserialize($custom_block['data_string']);
        $options['title'] = $title;
        
        $obj = new PageDesignCustomBlock;
        $obj->set($custom_block);
        $obj->set('data_string', serialize($options));
        
        $this->manager->save($obj, 'update');
        
        return $objResponse;
    }
    
    
    function ajaxDeleteCustomBlock($id) {
        $objResponse = new xajaxResponse();
        
        $this->manager->delete($id);
        
        $objResponse->call('fadeCustomBlock', 'popup_custom_' . $id);
        
        return $objResponse;
    }
 
}
?>