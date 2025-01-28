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

#[AllowDynamicProperties]

class AppView extends BaseView
{

    var $popup = false;
    var $popup_var = 'popup';
    var $form_val = array();
    var $statusable;
    var $status_active = true; // so if user no status priv all new record will be inactive
                               // false = inactive, true = active;

    var $inactive_style = 'color: #707070;';

    var $append_str = '';
    var $controller;
    var $msg = array();
    var $use_detail = false;

    var $encoding;
    var $week_start = 0;
    var $date_convert;
    var $extra_msg = [];


    function __construct() {

        $reg = &Registry::instance();
        // acontroller set in client to separete controllers
        $key = ($reg->isEntry('acontroller')) ? 'acontroller' : 'controller'; 
        $this->controller = &$reg->getEntry($key);
        $this->conf = &$reg->getEntry('conf');
        $this->priv = &$reg->getEntry('priv');
        $this->extra = &$reg->getEntry('extra');
        $this->setting = &$reg->getEntry('setting');

        $this->template_dir = $this->controller->working_dir.'template/';
        $this->setCommonMsg();
        $this->setModuleMsg();
        $this->setPopup(@$_GET[$this->popup_var]);

        $this->date_format = $this->setting['date_format'];
        $this->encoding = $this->conf['lang']['meta_charset'];
        $this->date_convert = $this->getDateConvertFrom($this->conf['lang']);
        if(isset($this->conf['lang']['week_start'])) {
            $this->week_start = $this->conf['lang']['week_start'];
            $reg->setEntry('week_start', $this->week_start);
        }
    }


    function getLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        return $this->controller->getLink($module, $page, $sub_page, $action, $more);
    }

    // set common msg
    function setCommonMsg() {

        $this->addMsg('share_msg.ini');
        $this->addMsg('common_msg.ini');

        // escape for js
        $this->escapeMsg(array('sure_common_msg', 'no_checked_msg'));
    
        foreach($this->extra_msg as $v) {
            $file = $v;
            $module = false;
            if(is_array($v)) { list($file, $module) = $v; }
            $this->addMsg($file, $module);
        }
    }

    // set concrete module msg
    function setModuleMsg($module = false) {
        $module = (!$module) ? @$this->controller->module : $module;
        $file = AppMsg::getModuleMsgFile($module, 'common_msg.ini');
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file));
    }
    

    // when we need to add additonal lang file
    function addMsg($file_name, $module = false) {
        $this->msg = array_merge($this->msg, AppMsg::getMsg($file_name, $module));
    }


    // when we need to add additonal lang file
    function addMsgPrepend($file_name, $module = false) {
        $this->msg = array_merge(AppMsg::getMsg($file_name, $module), $this->msg);
    }


    // we call it view where we call module from diff place
    function addMsgOnOtherModule($file_name, $module, $prepend = false) {
        if($this->controller->module != $module) {
            if($prepend) {
                $this->addMsgPrepend($file_name, $module);
            } else {
                $this->addMsg($file_name, $module);
            }            
        }
    }


    // when we need to add additonal lang file
    function addMsgData($msg) {
        $this->msg = array_merge($this->msg, $msg);
    }


    // in table header
    static function shortenTitle($title, $num_signs, $title2 = false) {
        $short_title = ($title2) ? $title2 : $title;
        $short_title = _substr($short_title, 0, $num_signs);
        return sprintf('<span title="%s">%s</span>', $title, $short_title);
    }


    // setting pop_up, if window popup or not to display
    // appropriate template for some lists
    function setPopup($val = false) {
        $this->popup = $val;
    }


    // create buttons, add new, ...
    function getButton($link, $msg, $new_window = false) {

        if($new_window) {
            $link_str = "OpenWin('%s', '123123123', '730', '550', 'yes', false, 1);";
            $link = sprintf($link_str, $link);
        } else {

			if(strpos($link, 'javascript:') !== false) {
				$link_str = str_replace('javascript:', '', $link);
				$link = sprintf("%s;", $link_str);
			} else {
				$link = sprintf("location.href='%s';", $link);
			}
        }

        $title = ($msg == '+') ? $this->msg['add_new_msg'] : $msg;
        $class = ($msg == '+') ? 'button2_add_new_plus' : 'button2_add_new';
        $html = '<input type="button" value="%s" class="button2 %s" title="%s"
                    style="margin: 0px;" onClick="%s">';
        $html = sprintf($html, $msg, $class, $title, $link);

        return $html;
    }


    function getImgButton($options) {
        $js_str = str_replace('javascript:', '', $options['link']);
        $html = '<button class="button2 button2_more" title="%s" onClick="%s">
                    <img src="%s" />
                 </button>';
        $html = sprintf($html, $options['msg'], $js_str, $options['icon']);

        return $html;
    }
    

    function getButtons($button_msg) {

        $links = &$this->getHeaderLinks();
		$td_str = '<td style="padding-left: 3px;">%s</td>';
        $buttons = array();
        $buttons[] = '<table class="sTable"><tr>';
        foreach($button_msg as $msg => $link) {

            $button_msg = (!$msg || $msg == 'insert') ? '+' : $msg;

            if($link == 'insert') {
                if($this->priv->isPriv('insert')) {
                    $buttons[] = sprintf($td_str, $this->getButton($links['add_link'], $button_msg));
                }

            // dropdown menu, $link array with items in menu
            } elseif(is_array($link)) {
                
                if(!empty($link['icon'])) {
                    $_td_str = '<td %s>%s</td>';
                    $buttons[] = sprintf($_td_str, $link['td_attr'], $this->getImgButton($link));
                    
                } else {
                    $buttons[] = sprintf($td_str, $this->getButtonMenu($link, $button_msg));
                }

            } else {
                $buttons[] = sprintf($td_str, $this->getButton($link, $button_msg));
            }
        }

        $buttons[] = '</tr></table>';

        return implode('', $buttons);
    }


    // create buttons, add new, ...
    function getButtonMenu($items, $msg) {
        // echo '<pre>' . print_r($items, 1) . '</pre>';exit;
        $html = array();

        $title = '';
        $btn = '<input type="button" value="%s" title="%s"
                    class="button2 button2_more"
                    onClick="" data-jq-dropdown="#button_menu" style="position: relative;">';
        $html[] = sprintf($btn, $msg, $title);


        $html[] = '<div id="button_menu" class="jq-dropdown jq-dropdown-tip jq-dropdown-anchor-right jq-dropdown-relative">';
        $html[] = '<ul class="jq-dropdown-menu">';
        
        $action_item_str = '<li class="%s" %s><a href="%s">%s</a></li>';
        $disabled_item_str = '<li class="%s" style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="jq-dropdown-divider %s"></li>';
        $last_key = count($items)-1;

        foreach ($items as $item) {
            
            $li_class = '';
            if(!empty($item['li_attr'])) {
                $li_class = $item['li_attr'];
            }
            
            if(isset($item['delim'])) {
                $html[] = sprintf($divider_str, $li_class);
            
            } elseif($item['link'] === false || !empty($item['disabled'])) {
                $html[] = sprintf($disabled_item_str, $li_class, $item['msg']);
                
            } else {
                $a_attr = '';
                
                if(strpos($item['link'], 'javascript:') !== false) {
                    $js_str = str_replace('javascript:', '', $item['link']);
                    $item['link'] = '#/';
                    $a_attr = sprintf("onClick=\"%s;\"", $js_str);
                }
                
                if(!empty($item['confirm_msg'])) {
                    $confirm_msg = str_replace(array('\n', '\r'), '', $item['confirm_msg']);
                    $confirm_msg = addslashes($confirm_msg);
                    $a_attr  = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $item['link']);
                }
                
                $html[] = sprintf($action_item_str, $li_class, $a_attr, $item['link'], $item['msg']);
            }
        }

        $html[] = '</ul></div>';

        return implode('', $html);
    }


	static function getSplitButton($items, $options = array()) {        

        $id_suffix = (!empty($options['id'])) ? '_' . $options['id'] : '';
        $button_type = (!empty($options['button'])) ? 'button' : 'submit';
        $button_class = ($button_type == 'submit') ? 'primary' : 'secondary';
        $button_class = (!empty($options['class'])) ? $options['class'] : $button_class;
        $button_attr = (!empty($options['attribute'])) ? $options['attribute'] : '';

        $current_key = key($items);
		$current_item = current($items);
        
		$selected_title = $current_item['text'];
		$selected_action = (!empty($current_item['action'])) ? $current_item['action'] . ';return false;' : '';
        // $selected_action = addslashes($selected_action);
		$selected_name = (!empty($current_item['name'])) ? $current_item['name'] : 'submit';
		
		if (count($items) == 1) { // single button

			$html = '<input type="%s" name="%s" id="submit_button%s" value="%s" class="button %s" onClick="%s" %s/>';
			$html = sprintf($html, $button_type, $selected_name, $id_suffix, $selected_title, $button_class, $selected_action, $button_attr);
			
		} else {
		    $template = 'split_button.html';
            $tpl = new tplTemplatez(APP_TMPL_DIR . $template);
            
            $tpl->tplAssign('current_key', $current_key);
            $tpl->tplAssign('selected_action', $selected_action);
            $tpl->tplAssign('selected_title', $selected_title);
            $tpl->tplAssign('selected_name', $selected_name);
            
            $tpl->tplAssign('button_type', $button_type);
            $tpl->tplAssign('button_class', $button_class);
            $tpl->tplAssign('button_attr', $button_attr);
            $tpl->tplAssign('id', $id_suffix);
            
            foreach($items as $k => $v) {
                $v['key'] = $k;
                $v['name'] = (!empty($v['name'])) ? $v['name'] : 'submit';
                $v['action'] = (!empty($v['action'])) ? $v['action'] . ';return false;' : '';
                $tpl->tplParse($v, 'row');
            }
            
            $tpl->tplParse();
            $html = $tpl->tplPrint(1);
		}
        
        return $html;
	}

    // $format = date|datetime|datetimesec|or real format
    function getFormatedDate($timestamp, $format = false) {
        $df = $this->date_format;
        
        if($format === false || $format === 'date') {
            $format = $df;
            
        } elseif($format === 'datetime') {
            $format = $df . ' ' . $this->conf['lang']['time_format'];
        
        } elseif($format === 'datetimesec') {
            $format = $df . ' ' . $this->conf['lang']['sec_format'];
        
        } elseif($format === 'time') {
            $format = $this->conf['lang']['time_format'];
        
        } elseif($format === 'full') {
            $format = [
                'date' => IntlDateFormatter::LONG,
                'time' => IntlDateFormatter::MEDIUM
            ];
        }

        return $this->_getFormatedDate($timestamp, $format);
    }


    // generate link for update, delete, etc.
    function getImgLink($path, $img, $msg, $confirm_msg = false) {

        $confirm = ($confirm_msg) ? sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $path) : '';
        
        $str = '<img src="%simages/icons/%s.svg" alt="%s" />';
        $img_tag = sprintf($str, $this->conf['admin_path'], $img, $msg);
        $link = ($path) ? sprintf('<a href="#" class="_tooltip" title="%s" %s>%s</a>', $msg, $confirm, $img_tag)
                        : $img_tag;
        return $link;
    }
    

    // $more_params - array(key=value, key=value);
    function getActionLink($action_val, $record_id = false, $more_params = array()) {
        return $this->controller->getActionLink($action_val, $record_id, $more_params);
    }


    function &getHeaderLinks() {
        $arg = $this->controller->arg_separator;
        $row['common_link'] = $this->controller->getCommonLink();
        $common_link = $row['common_link'];
        $action_key = $this->controller->getRequestKey('action');

        $row['add_link'] = sprintf('%s%s%s=%s', $row['common_link'], $arg, $action_key,
                                                $this->controller->getActionValue('insert'));

        return $row;
    }


    function getViewListVarsRow($active = NULL) {
        static $i = 0;

        $row = array();
        $row['class'] = ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors
        $row['style'] = ($active !== null && !$active) ? $this->inactive_style : ''; // style for not active

        return $row;
    }


    function _getViewListVarsUpdateData($record_id, $active, $own_record, $row) {

        $row = array();
        $row['update_link'] = $this->controller->getCurrentLink();
        $row['update_img'] = false;

        if($this->isEntryUpdateable($record_id, $active, $own_record)) {
            $row['update_link'] = $this->getActionLink('update', $record_id);
            $row['update_img'] = $this->getImgLink($row['update_link'], 'update', $this->msg['update_msg']);

        } elseif($this->use_detail) {
            $row['update_link'] = $this->getActionLink('detail', $record_id);
            $row['update_img'] = $this->getImgLink($row['detail_link'], 'load', $this->msg['detail_msg']);
        }

        return $row;
    }


    function isEntryUpdateable($record_id, $active, $own_record) {

        $ret = false;

        if($this->priv->isPriv('update')) {

            // with status
            if($this->priv->isPrivStatusAction('update', false, $active)) {
                $ret = true;
            }

            // self update
            if($ret) {
                if($this->priv->isSelfPriv('update') && !$own_record) {
                    $ret = false;
                }
            }
        }

        return $ret;
    }


    function _getViewListVarsStatusData($record_id, $active, $own_record) {

        $row = array();
        $link = false;
        
        if($this->priv->isPriv('status')) {
            
            if($this->priv->isPrivStatusAction('status', false, $active)) {
                $link = true;
                $msg = $this->msg['status_msg'];
                $img = 'status';
                $confirm_msg = $this->msg['sure_status_msg'];
            }

            // self status
            if($this->priv->isSelfPriv('status') && !$own_record) {
                $link = false;
            }
        }

        $act = $active;
        if(!$link) {
            $act = ($act == 0) ? 'not' : 'not_checked';
        }

        $row['active_link'] = '';
        if($act === 'not') {
            $row['active_img'] = $this->getImgLink('', 'active_d_0', '');

        } elseif($act === 'not_checked') {
            $row['active_img'] = $this->getImgLink('', 'active_d_1', '');

        } else {
            $active_var = ($act == 0) ? '1' : '0';
            $active_img = ($act == 0) ? 'active_0' : 'active_1';
            $active_msg = ($act == 0) ? 'set_active_msg' : 'set_inactive_msg';
            $row['active_link'] = $this->getActionLink('status', $record_id, array('status' => $active_var));
            $row['active_img'] = $this->getImgLink($row['active_link'], $active_img,
                                                   $this->msg[$active_msg],
                                                   $this->msg['sure_status_msg']);
        }

        return $row;
    }


    // function _getViewListVarsStatusData($record_id, $active, $own_record) {
    // 
    //     $row = array();
    // 
    //     $act = $active;
    //     if(!$this->priv->isPriv('status')) {
    //     // if(!$this->priv->isPriv('status') || !$this->priv->isPrivStatusAction('status', false, $active)) {
    //         $act = ($act == 0) ? 'not' : 'not_checked';
    //     }
    // 
    //     $row['active_link'] = '';
    //     if($act === 'not') {
    //         $row['active_img'] = $this->getImgLink('', 'active_d_0', '');
    // 
    //     } elseif($act === 'not_checked') {
    //         $row['active_img'] = $this->getImgLink('', 'active_d_1', '');
    // 
    //     } else {
    //         $active_var = ($act == 0) ? '1' : '0';
    //         $active_img = ($act == 0) ? 'active_0' : 'active_1';
    //         $active_msg = ($act == 0) ? 'set_active_msg' : 'set_inactive_msg';
    //         // $active_msg = 'set_status_msg'
    //         $row['active_link'] = $this->getActionLink('status', $record_id, array('status' => $active_var));
    //         $row['active_img'] = $this->getImgLink($row['active_link'], $active_img,
    //                                                $this->msg[$active_msg],
    //                                                $this->msg['sure_status_msg']);
    //     }
    // 
    //     return $row;
    // }
    
     
    // used it to set links such as delete, update
    function getViewListVars($record_id = false, $active = NULL, $own_record = true) {

        $row = $this->getViewListVarsRow($active);

        // active link
        $status = $this->_getViewListVarsStatusData($record_id, $active, $own_record);
        $row['active_link'] = $status['active_link'];
        $row['active_img'] = $status['active_img'];

        // detail
        $row['detail_link'] = $this->getActionLink('detail', $record_id);
        $row['detail_img'] = $this->getImgLink($row['detail_link'], 'load', $this->msg['detail_msg']);

        // bulk
        $row['bulk_ids_ch_option'] = '';

        // update
        $update = $this->_getViewListVarsUpdateData($record_id, $active, $own_record, $row);
        $row['update_link'] = $update['update_link'];
        $row['update_img'] = $update['update_img'];

        // delete
        $row['delete_link'] = false;
        $row['delete_img']    = false;
        if($this->priv->isPriv('delete')) {
            if($this->priv->isPrivStatusAction('delete', false, $active)) {
                $row['delete_link'] = $this->getActionLink('delete', $record_id);
                $row['delete_img']    = $this->getImgLink($row['delete_link'], 'delete',
                                                        $this->msg['delete_msg'],
                                                        $this->msg['sure_delete_msg']);
            }

            // self delete
            if($this->priv->isSelfPriv('delete') && !$own_record) {
                $row['delete_link'] = false;
                $row['delete_img'] = false;
            }
        }

        return $row;
    }


    function getViewListVarsJs($record_id = false, $active = NULL, $own_record = true, $actions = array('status', 'update', 'delete')) {

        $row = $this->getViewListVarsRow($active);

        // active link
        $status = $this->_getViewListVarsStatusData($record_id, $active, $own_record);
        $row['active_link'] = $status['active_link'];
        $row['active_img'] = $status['active_img'];

        // bulk
        $row['bulk_ids_ch_option'] = '';

        // we need default update link as it used in dblclick on tr
        // $row['update_link'] = false; //$this->controller->getCurrentLink();
        $row['update_link'] = $this->controller->getCurrentLink(); // 2017-01-19 eleontev

        // actions
        $prev_action = false;
        $action_order = $this->getActionsOrder();
        $action_items = array();
        $action_item_str = '<li%s><a href="%s" %s %s>%s</a></li>';
        // $nolink_item_str = '<li%s>%s</li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="jq-dropdown-divider"></li>';
        $img_path = 'images/icons/%s.gif';
        // $img_path = sprintf('%simages/icons/%%s.gif', $this->conf['admin_path']);
        
        // $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        // $actions = array_diff_key($actions, $pluginable);
        
        $submenu = '';

        foreach ($action_order as $k => $action) {

            $link = false;
            $img = false;
            $msg = false;
            $confirm_msg = false;
            $link_attributes = '';
            $li_attributes = '';

            $action_values = $action;
            if($action != 'delim') {
                if(in_array($action, $actions, true)) {
                    $action_values = $action;

                // use key for action $actions['update'] = ...
                } elseif(isset($actions[$action])) {

                    // remove emty actions in case if we use it like
                    // $actions['update'] = false;
                    if(empty($actions[$action])) {
                        continue;
                    }

                    if(is_array($actions[$action])) {
                        $action_values = $actions[$action];
                    }

                } else {
                    continue;
                }
            }

            switch ($action) {
                case 'detail':
                    $link = $this->getActionLink('detail', $record_id);
                    $msg = $this->msg['detail_msg'];
                    $img = '';
                    $row['detail_link'] = $link;
                    $row['update_link'] = $link; // by default for dbl click on tr

                    break;

                case 'status':
                    if($row['active_link']) {
                        $link = $row['active_link'];
                        // $msg = $this->msg['set_status_msg'];

                        if($active === NULL) {
                            $msg = $this->msg['set_status_msg'];
                            $img = 'load';
                            $confirm_msg = $this->msg['sure_status_msg'];
                        
                        // with submenu
                        } elseif (!empty($action_values[0]) && is_array($action_values[0])) {
                            
                            if(isset($action_values[0]['value'])) {
                                $key = array_search($active, array_column($action_values, 'value'));
                                unset($action_values[$key]);
                            }
                            
                            // if(count($action_values) > 1) {
                            if(1) {
                            
                                $msg = sprintf('<div style="float: left;">%s</div>', $this->msg['set_status2_msg']);
                                $arrow = '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24"><path fill="#666666" d="M21 12l-18 12v-24z" /></svg><div style="clear: both;"></div>';
                                $msg .= $arrow;
                                
                                $li_attributes = sprintf(' onclick="return false;" onmouseover="showSubMenu(%d, this)"', $record_id);
                                $link_attributes = 'style="text-align: right;"';
                                
                                $confirm_msg = str_replace(array('\n', '\r'), '', $this->msg['sure_status_msg']);
                                $confirm_msg = addslashes($confirm_msg);
                                
                                foreach ($action_values as $v) {
                                    
                                    $li_attr = (isset($v['li_attributes'])) ? $v['li_attributes'] : '';
                                    $link_attr = (isset($v['link_attributes'])) ? $v['link_attributes'] : '';
                                    $link = (isset($v['link'])) ? $v['link'] : $this->getActionLink('status', $record_id, ['status'=>$v['value']]);
                                    $confirm = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $link);
                                    
                                    $submenu_items[] = sprintf(
                                        $action_item_str,
                                        $li_attr,
                                        $link,
                                        $link_attr,
                                        $confirm,
                                        $v['msg']
                                    );
                                }
                                
                                $submenu_container = '<div id="actions_submenu%s" class="jq-dropdown jq-dropdown-relative">
                                    <ul class="jq-dropdown-menu">%s</ul>
                                </div>';
                                
                                $submenu = sprintf($submenu_container, $record_id, implode('', $submenu_items));
                            
                            // one item in status array, print as normal menu item
                            } else {
                                $key = key($action_values);
                                $link = $this->getActionLink('status', $record_id, array('status' => $action_values[$key]['value']));
                                $msg = sprintf('%s %s', $this->msg['set_msg'], $action_values[$key]['msg']);
                                $img = 'load';
                                $confirm_msg = $this->msg['sure_status_msg'];
                            }
                            
                        } else {
                            $msg_key = ($active) ? 'set_inactive_msg' : 'set_active_msg';
                            $msg = $this->msg[$msg_key];
                            $img = 'load';
                            $confirm_msg = $this->msg['sure_status_msg'];
                        }
                    }

                    break;

                case 'clone':
                    if($this->priv->isPriv('insert')) {
                        $more = array('show_msg'=>'note_clone-hint');
                        $link = $this->getActionLink('clone', $record_id, $more);
                        $msg = $this->msg['duplicate_msg'];
                        $img = 'clone';
                    }

                    $row['clone_link'] = $link;

                    break;

                case 'update':
                    if($this->isEntryUpdateable($record_id, $active, $own_record)) {
                        $link = $this->getActionLink('update', $record_id);
                        $msg = $this->msg['update_msg'];
                        $img = 'update';

                    } elseif($this->use_detail) {
                        $link = $this->getActionLink('detail', $record_id);
                        $msg = $this->msg['detail_msg'];
                        $img = 'load';
                    }

                    // we need update link as it is used in dblclick
                    if($link) {
                        $row['update_link'] = $link;
                    }

                    break;

                case 'delete':
                case 'trash':
                    if($this->priv->isPriv('delete')) {
                        if($this->priv->isPrivStatusAction('delete', false, $active)) {
                            $link = $this->getActionLink('delete', $record_id);
                            $msg = $this->msg['delete_msg'];
                            $img = 'delete';
                            $confirm_msg = $this->msg['sure_delete_msg'];
                        }

                        // self delete
                        if($this->priv->isSelfPriv('delete') && !$own_record) {
                            $link = false;
                        }

                        // trash
                        if($action == 'trash') {
                            $msg = $this->msg['trash_msg'];
                            $confirm_msg = $this->msg['sure_common_msg'];
                        }
                    }

                    $row['delete_link'] = $link;

                    break;
            }
            

            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('clone', 'status', 'update', 'trash', 'delete'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];

                        // reasign update_link to have it valid on dbl click
                        if($action == 'update') {
                            $row['update_link'] = $link;
                        }
                    }
                    
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['img'])) {
                    $img = $action_values['img'];
                }

                if (!empty($action_values['msg'])) {
                    $msg = $action_values['msg'];
                }

                // if (!empty($action_values['confirm_msg'])) {
                if (isset($action_values['confirm_msg'])) {
                    $confirm_msg = $action_values['confirm_msg'];
                }

                if (!empty($action_values['link_attributes'])) {
                    $link_attributes = $action_values['link_attributes'];
                }
                
                if (!empty($action_values['li_attributes'])) {
                    $li_attributes = ' ' . $action_values['li_attributes'];
                }
            }


            if ($link) {
                $confirm = '';
                if($confirm_msg) {
                    $confirm_msg = str_replace(array('\n', '\r'), '', $confirm_msg);
                    $confirm_msg = addslashes($confirm_msg);
                    $confirm = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $link);
                }

                if (!empty($action_values['disabled']) && $action_values['disabled'] === true) {
                    $action_items[] = sprintf($disabled_item_str, $msg);

                } else {
                    $action_items[] = sprintf($action_item_str, $li_attributes, $link, $link_attributes, $confirm, $msg);
                }
            }

            // delim
            $last_key = count($action_items)-1;
            if($action == 'delim' && $action_items && $action_items[$last_key] != $divider_str) {
                $action_items[] = $divider_str;
            }
        }

        $options_img = '
        <div data-jq-dropdown="#actions%s" id="trigger_actions%s" style="cursor: pointer;position: relative;">
            <a href="#">
                <img src="{base_href}admin/images/icons/action.svg" width="14" height="14" alt="action" style="border: 0px; padding: 0px 2px;" />
            </a>
        </div>
        <div id="actions%s" class="jq-dropdown jq-dropdown-anchor-right jq-dropdown-tip jq-dropdown-relative">
            <ul class="jq-dropdown-menu">%s</ul>%s
        </div>';


        // remove last delims
        if (isset($action_items[$last_key])) {
           if($action_items[$last_key] == $divider_str) {
               unset($action_items[$last_key]);
           }
        }

        $row['options_img'] = false;
        if (!empty($action_items)) {
            $row['options_img'] = sprintf($options_img, $record_id, $record_id, $record_id, implode('', $action_items), $submenu);
        }

        return $row;
    }


    // in logs
    function getVliewListLogVars($link, $output) {
        $str = '<a href="%s" class="_tooltip" title="%s">
            <img src="images/icons/info.svg" alt="view" style="margin: 0 10px;" /></a>'; 
        
        $row = array();
        $row['options_img'] = sprintf($str, $link, $output);
        
        return $row;
    }


    function getActionsOrder() {
        $order = array(
            'approve',                          'delim',
            'preview', 'public',                'delim',
            'fopen', 'file',                    'delim', // for files
            'detail', 'history', 'activity',    'delim',
            'insert', 'sort',                   'delim', // for category
            'load', 'login',                    'delim',
            'read', 'unread',                   'delim',
            'clone', 'clone_tree', 'restore',   'delim',
            'status', 'draft', 'move_to_draft', 'update', 'delim',
            'default', 'list',                  'delim',
            'custom1', 'custom2',               'delim',
            'trash', 'delete', 'delete_category'
            );

        return $order;
    }


    // this is for form
    function setCommonFormVars(&$obj, $add_msg = false, $update_msg = false) {

        $add_msg = ($add_msg !== false) ? $add_msg : $this->msg['add_new_msg'];
        $update_msg = ($update_msg !== false) ? $update_msg : $this->msg['update_msg'];

        $row['action_title'] = ($this->controller->getAction() == 'insert') ? $add_msg : $update_msg;
        $row['action_link'] = $this->controller->getCurrentLink();
        $row['cancel_link'] = $this->controller->getCommonLink();

        $row['full_cancel_link'] = sprintf("location.href='%s'", $this->controller->getCommonLink());
        if($this->popup) {
            $row['full_cancel_link'] = 'PopupManager.close()';
        }
        
        $row['required_class'] = 'required';

        // hidden fields in form to remember a state
        $arr = array();
        foreach($obj->hidden as $v) {
            $arr[$v] =& $obj->properties[$v];
            if(in_array($v, $obj->reset_on_update)) {
                $arr[$v] = 0;
            }
        }
        
        $arr['atoken'] = Auth::getCsrfToken();

        $row['hidden_fields'] = http_build_hidden($arr);

        // hints        
        $tooltips = self::getHelpTooltip();
        foreach(array_keys($tooltips) as $k) {
            $row[$k] = $tooltips[$k];
        }

        // ck drag and drop upload
        $row['ck_drop_upload_url'] = $this->controller->getAjaxLinkToFile('ck_upload');

        return $row;
    }


    static function getHelpTooltip($key = false) {
        $file = AppMsg::getCommonMsgFile('tooltip_msg.ini');
        $hint_msg = AppMsg::parseMsgsMultiIni($file, $key);

        $arr = array();
        if($hint_msg) {
            $hint_js = '<img src="%sclient/images/icons/help.svg" alt="help" 
                            style="cursor: help;width: 16px;height: 16px;" class="_tooltip_click" title="%s" />';

            if($key) {
                $hint_msg_ = trim(RequestDataUtil::stripVars($hint_msg, array(), true));
                $arr[$key] = sprintf($hint_js, APP_CLIENT_PATH, $hint_msg_, array(), 'display');
                
            } else {
                foreach(array_keys($hint_msg) as $k) {
                    $hint_msg_ = trim(RequestDataUtil::stripVars($hint_msg[$k], array(), true));
                    $arr[$k] = sprintf($hint_js, APP_CLIENT_PATH, $hint_msg_, array(), 'display');
                }
            }    
        }
        
        return $arr;
    }


    function setRefererFormVars($referer, $client_link = array()) {
        $row = array();
        if(!empty($referer)) {
            $row['cancel_link'] = $this->getRefererLink($referer, $client_link);
            $row['full_cancel_link'] = sprintf("location.href='%s'", $row['cancel_link']);
            if($this->popup) {
              $row['full_cancel_link'] = 'PopupManager.close()';
            }
        }

        return $row;
    }


    function getRefererLink($referer, $client_link = array()) {
        return $this->controller->getRefererLink($referer, $client_link);
    }

    
    function parseFilterVars($values) {
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        return $values;
    }


    // this is for filter form
    function setCommonFormVarsFilter() {
        $params = $this->controller->full_page_params;
        // $params = $this->controller->getFullPageParams();

        unset($params['filter']);
        unset($params['bp']); // new filter should start from new page

        $class_name = get_class($this);
        if(strpos($class_name, '_list') !== false) {
            $search = $this->getSpecialSearch();
            $row['search_help'] = SpecialSearch::getSpecialSearchHelpMsg($search);
        }

        $row['hidden_fields'] = http_build_hidden($params, true);
        $row['action_link'] = $this->controller->getCurrentLink();
        return $row;
    }


    // to set field active in form depends on user priv
    function setStatusFormVars($active, $use_priv = true, $disabled = false) {

        $action_key = $this->controller->getRequestVar('action');

        if($action_key == 'insert') {
            $is_statusable = ($use_priv && $this->priv) ? $this->priv->isPriv('status') : true;
            $active = ($is_statusable) ? $active : 0;
        } else {
            // checking for self status
            $is_statusable = ($use_priv && $this->priv) ? $this->priv->checkPrivAction('status') : true;
        }

        $is_statusable = ($disabled) ? false : $is_statusable;

        // echo '<pre>', print_r($this->priv, 1), '</pre>';
        // echo '<pre>', print_r($is_statusable, 1), '</pre>';

        $str = '';
        $data = array();
        if(!$is_statusable || !$active) {
            $str = '<input type="hidden" name="active" value="%s">';
            $str = sprintf($str, $active);
        } elseif($active) {
            $str = '<input type="hidden" name="active" value="%s">';
            $str = sprintf($str, 0);
        }

        $disabled = ($is_statusable) ? '' : 'disabled';
        $checked = ($active) ? 'checked' : '';

        $checkbox = '<input type="checkbox" name="active" id="active" value="%s" %s %s>
        <label for="active">%s</label>';
        $str .= sprintf($checkbox, 1, $disabled, $checked, $this->msg['yes_msg']);
        $data['status_checkbox'] = $str;

        return $data;
    }


    // return statuses range depends on user priv
    // we use it where select for statuses - users, articles etc
    function getStatusFormRange($range, $current_status) {

        $rest_range = $this->priv->getPrivStatusSet($range, $this->controller->getAction());
        if(!$rest_range) {
            if(isset($range[$current_status])) {
                $rest_range[$current_status] = $range[$current_status];
            }
        }

        return $rest_range;
    }


    function &pageByPage($limit, $sql, $options = array()) {

       $per_page = (!empty($options['per_page'])) ? $options['per_page'] : true;
       $limit_range = (!empty($options['limit_range'])) ? $options['limit_range'] : array(10,20,50);
       $class = (!empty($options['class'])) ? $options['class'] : 'form';
       $get_name = (!empty($options['get_name'])) ? $options['get_name'] : false;


        $msg = array(
            $this->msg['page_msg'],
            $this->msg['record_msg'],
            $this->msg['record_from_msg']
        );

        $bp_options = array('get_name' => $get_name);
        $bp = PageByPage::factory($class, $limit, $_GET, $bp_options);

        if ($get_name) { // new URL param
            $bp->get_name = $get_name;
            unset($bp->query[$get_name]);
            $bp->setGetVars();
        }

        $bp->setMsg($msg);
        $bp->setPerPageMsg($this->msg['per_page_msg']);
        $bp->per_page_range = $limit_range;

        $reg = &Registry::instance();
        $db = &$reg->getEntry('db');

        $bp->countAll($sql, $db);
        $bp->nav = $bp->navigate($per_page);

        return $bp;
    }


    // generate page by page navigation, some buttons, ...
    function &commonHeaderList($nav = '', $left_side = '', $button_msg = true, $options = array()) {

        $links =& $this->getHeaderLinks();
        
        $template = (!empty($options['tmpl'])) ? $options['tmpl'] : 'common_list_header.html';
        $tpl = new tplTemplatez(APP_TMPL_DIR . $template);
        
        $bulk_action = (!empty($options['bulk_action'])) ? $options['bulk_action'] : $this->getActionLink('bulk');
        $tpl->tplAssign('bulk_form_action', $bulk_action);
        
        // we may need not to parse bulk form block, mostly no used, always parsed
        $bulk_form = (isset($options['bulk_form']) && empty($options['bulk_form'])) ? false : true;
        if($bulk_form) { $tpl->tplSetNeeded('/bulk_form'); }
                
        
        $extra_btn_options = array();
        if(is_array($button_msg) || $button_msg !== false) {
            $reg =& Registry::instance();
            
            // item to customize list in [...] button
            if($reg->isEntry('customize_list_link')) {
                $link = $reg->getEntry('customize_list_link');
                $extra_btn_options['customize'] = array(
                    'msg' => $this->msg['customize_list_msg'],
                    'link' => "javascript:PopupManager.create('{$link}', 'r', 'r', 1, 500);void(0);"
                );
            }
            
            // bulk actionss
            if($bulk_form && $reg->isEntry('bulk_actions')) {
                $msg_ = sprintf('<b>%s</b>', $this->msg['with_checked_msg']);
                if($extra_btn_options) {// add delim if we have someting before
                    $extra_btn_options[] = ['delim' => 1, 'li_attr' => 'bulk_actions'];
                }
                $extra_btn_options[] = ['msg' => $msg_, 'link' => false, 'li_attr' => 'bulk_actions'];
                
                $actions = $reg->getEntry('bulk_actions');
                $action_str = "javascript:performAction('%s', '%s', 'jqueryui'); $('#bulk_action').val('%s');";
                foreach($actions as $k => $v) {
                    $extra_btn_options[] = [
                        'msg' => '&nbsp;&nbsp;' . $v,
                        'link' => sprintf($action_str, $k, $this->msg['sure_common_msg'], $k),
                        'li_attr' => 'bulk_actions'
                    ];
                }
            }
        }
            
        if(is_array($button_msg)) {
            if(!$this->priv->isPriv('insert')) {
                $key = array_search('insert', $button_msg);
                unset($button_msg[$key]);
            }
            
            if($extra_btn_options) {
                if(isset($button_msg['...'])) {
                    $button_msg['...'] = array_merge($button_msg['...'], $extra_btn_options);
                } else {
                    $button_msg['...'] = $extra_btn_options;
                }
            }

            $tpl->tplAssign('add_link', $this->getButtons($button_msg));


        } elseif($button_msg !== false) {
            $button = array();
            
            if($this->priv->isPriv('insert')) {
                $button_msg = ($button_msg === true) ? '+' : $button_msg;
                $button[$button_msg] = $links['add_link'];
            }

            if($extra_btn_options) {
                $button['...'] = $extra_btn_options;
            }
            
            $tpl->tplAssign('add_link', $this->getButtons($button));
            

        } else {
            $tpl->tplAssign('add_link', '');
        }

        if($nav) {
            $tpl->tplSetNeeded('/by_page');
            $tpl->tplAssign('by_page_tpl', $nav);
        }

        if($left_side) {
            $tpl->tplAssign('left_side', $left_side);
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function &titleHeaderList($nav = '', $title = '', $button_msg = true, $options = array()) {
        $tmpl = 'title_list_header.html';
        $options += array('tmpl' => $tmpl);
        $ret = $this->commonHeaderList($nav, $title, $button_msg, $options);
        return $ret;
    }


    function getBulkForm() {
        return;
    }


    function getBreadCrumbNavigation($data) {
        $nav = array();
        foreach($data as $k => $v) {
            if(!empty($v['link'])) {
                $nav[] = sprintf('<a href="%s">%s</a>', $v['link'], $v['item']);
            } else {
                $nav[] = sprintf('%s', (isset($v['item'])) ? $v['item'] : $v);
            }
        }

        return implode(' -> ', $nav);
    }


    // errors from validate class
    function getErrorJs($errors, $script_block = true) {

        if(!$errors) { return; }

        $js = array();
        $ids = array();

        foreach(array_keys($errors) as $type) {
            foreach(array_keys($errors[$type]) as $k) {
                $f = $errors[$type][$k]['field'];
                if($f) {
                    if(is_array($f)) {
                        foreach($f as $fv) {
                            $ids[] = $fv;
                        }
                    } else {
                        $ids[] = $f;
                    }
                }
            }
        }

        if($ids) {
            $ids = "'" . implode("', '", $ids) . "'";
            $js[] = ($script_block) ? '<script>' : '';
            $js[] = "function kbpParseFormErrors() {";
            $js[] = sprintf("var errorFields = new Array(%s);", $ids);
            $js[] = "
            var f = document.getElementById(errorFields[0]);
            if(f) {
                f.focus();
                f.select();
            }

            for (var i = errorFields.length - 1; i >= 0; i--){
                f = document.getElementById(errorFields[i]);
                if(f) {
                    f.style.border = 'solid 1px red';
                    f.style.backgroundColor = '#F9E5E5';
                }
            };
            ";

            $js[] = '}';
            $js[] = 'kbpParseFormErrors();';
            $js[] = ($script_block) ? '</script>' : '';
        }

        return implode("\n", $js);
    }


    // will keep some module specific items
    function getSpecialSearch() {
        return array();
    }


    // if some special search used,
    function isSpecialSearch($str) {
        
        $module_search = $this->getSpecialSearch();
        $custom_search = SpecialSearch::parseCustomSearch($module_search);
        $rules = SpecialSearch::getSpecialSearch();
        
        // echo '<pre>', print_r($module_search,1), '<pre>';
        // echo '<pre>', print_r($custom_search,1), '<pre>';
        // echo '<pre>', print_r($rules,1), '<pre>';
        // exit;
        
        $search = array_intersect_key($rules['search'], array_flip($module_search));
        $search += $custom_search['search'];        
        
        $filter = array_intersect_key($rules['filter'], array_flip($module_search));
        if(!empty($custom_search['filter'])) {
            $filter += $custom_search['filter'];
        }

        // echo '<pre>', print_r($search,1), '<pre>';
        // echo '<pre>', print_r($filter,1), '<pre>';
        // exit;

        return SpecialSearch::parseSpecialSearchStr($str, $search, $filter);
    }


    // will keep some module specific items
    function getSpecialSearchSql($manager, $ret, $string) {
        return array();
    }


    // function getSpecialSearchSql($manager, $ret, $string, $id_field = 'e.id') {
    function parseSpecialSearchSql($manager, $ret, $string, $id_field = 'e.id') {
        $arr = array(); 
        if($ret['rule'] == 'id') {
            $arr['where'] = sprintf("AND {$id_field} IN(%s)", $ret['val']);
        
        } else {
            $arr = $this->getSpecialSearchSql($manager, $ret, $string);
        }
        
        $mysql = array();
        foreach($arr as $k => $v) {
            $mysql[$k][] = $v;
        }
        
        return $mysql;
    }
    

    // it works in all list views for filtering, excluding reports
    function getPeriodSql($period, $values, $field, $week_start) {

        $data = TimeUtil::getPeriodData($period, $values, $week_start);

        $this->start_day = $data['start_day'];
        $this->end_day = $data['end_day'];

        // $str = "AND $field BETWEEN '%s' AND '%s'"; // November 3, 2020 eleontev
        $str = "AND $field BETWEEN '%s 00:00:00' AND '%s 23:59:59'";
        $sql = sprintf($str, $data['start_day'], $data['end_day']);

        if($period == 'previous_24_hour') {
            
            $str = "AND $field BETWEEN '%s' AND '%s'";
            $sql = sprintf($str, $data['start_day'], $data['end_day']);
            
        } elseif($period == 'custom_period') {

            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));

            $str = "AND $field BETWEEN '%s 00:00:00' AND '%s 23:59:59'";

            if (!$date_from && $date_to) {
                $this->end_day = date('Y-m-d', $date_to);
                $sql = sprintf("AND $field < '%s 23:59:59'", $this->end_day);
            }

            if ($date_from && !$date_to) {
                $this->start_day = date('Y-m-d', $date_from);
                $sql = sprintf("AND $field > '%s 00:00:00'", $this->start_day);
            }

            if ($date_from && $date_to) { // both dates are valid
                $this->start_day = date('Y-m-d', $date_from);
                $this->end_day = date('Y-m-d', $date_to);
                $sql = sprintf($str, $this->start_day, $this->end_day);
            }

            if (!$date_from && !$date_to) { // both dates are missing
                $this->start_day = date('Y-m-d', time());
                $this->end_day = date('Y-m-d', time());
                $sql = sprintf($str, $this->start_day, $this->end_day);
            }
        }

        // echo '<pre>', print_r($sql,1), '<pre>';
        return $sql;
    }


    // ajax
    function &getAjax(&$obj = false, &$manager = false, $view = false) {

        if($obj) { $this->obj = &$obj; }
        if($manager) { $this->manager = &$manager; }

        $ajax = &AppAjax::factory($view);
        return $ajax;
    }


    function getEditor($value, $cfile, $fname = 'body', $cconfig = array()) {

        // $options = [
        //     'cfile' => $cfile,
        //     'fname' => $fname,
        //     'cconfig' => $cconfig
        // ];

        // return AppEditor::getEditor('ckeditor5', $value, $options);
        // return AppEditor::getEditor('ckeditor5_md', $value, $options);
        
        require_once APP_ADMIN_DIR . 'tools/ckeditor_custom/ckeditor.php';
        
        $config_file = array(
          'news' => 'ckconfig_news.js',
          'article' => 'ckconfig_article.js',
          'glossary' => 'ckconfig_glossary.js',
          'custom_field' => 'ckconfig_custom_field.js',
          'export' => 'ckconfig_export.js'
        );
        
        $CKEditor = new CKEditor();
        $CKEditor->returnOutput = true;
        $CKEditor->basePath = APP_ADMIN_PATH . 'tools/ckeditor/';
        
        $config = array();
        $config['customConfig'] = APP_ADMIN_PATH . 'tools/ckeditor_custom/' . $config_file[$cfile];
        
        foreach($cconfig as $k => $v) {
            $config[$k] = $v;
        }
        
        $events = array();
        // $events['instanceReady'] = 'function (ev) {
        //     alert("Loaded: " + ev.editor.name);
        // }';
        
        return $CKEditor->editor($fname, $value, $config, $events);
    }


    function getViewEntryTabs($entry, $tabs, $own_record = true, $options2 = array()) {

        $tabs_generate = array();

        if ($entry) {
            $record_id = $entry['id'];
            $active = $entry['active'];
        }

        foreach ($tabs as $k => $tab) {

            $title = false;
            $link = false;
            $highlight = false;
            $options = array();

            $action = (is_array($tab)) ? $k : $tab;

            $action_values = $tab;
            if(in_array($action, $tabs, true)) {
                $action_values = $action;

            // use key for action $tabs['update'] = ...
            } elseif(isset($tabs[$action])) {

                // remove emty actions in case if we use it like
                // $tabs['update'] = false;
                if(empty($tabs[$action])) {
                    continue;
                }

                if(is_array($tabs[$action])) {
                    $action_values = $tabs[$action];
                }

            } else {
                continue;
            }


            switch ($action) {
            case 'detail':
                $link = $this->getActionLink('detail', $record_id);
                $title = $this->msg['detail_msg'];

                break;

            case 'log':
                $link = $this->getActionLink('log', $record_id);
                $title = $this->msg['log_msg'];

                break;

            case 'update':
                if($this->priv->isPriv('update')) {
                    if($this->priv->isPrivStatusAction('update', false, $active)) {
                        $link = $this->getActionLink('update', $record_id);
                        $title = $this->msg['update_msg'];
                    }

                    // self update
                    if($this->priv->isSelfPriv('update') && !$own_record) {
                        $link = false;
                    }

                    // as draft only allowed
                    if($this->priv->isPrivOptional('update', 'draft')) {
                        $link = false;
                    }

                }

                break;
            }


            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('update'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];
                    }
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['title'])) {
                    $title = $action_values['title'];
                }

                if (!empty($action_values['highlight'])) {
                    $highlight = $action_values['highlight'];
                }

                if (!empty($action_values['options'])) {
                    $options = $action_values['options'];
                }
            }

            if($link) {
                $tabs_generate[$action] = array(
                    'link' => AppController::_replaceArgSeparator($link),
                    'title' => $title,
                    'highlight' => $highlight,
                    'options' => $options
                    );
            }
        }
        
        // $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        // $tabs_generate = array_diff_key($tabs_generate, $pluginable);

        $str = '';
        if($tabs_generate) {

            // more button with menu
            $more_menu = '';
            if(!empty($options2['more'])) {
                $more_menu = $this->getViewEntryTabsMoreMenu($entry, $options2['more'], $own_record);
                if($more_menu) {
                    $tabs_generate['menu'] = array(
                        'title'  => ucwords($this->msg['more_msg']) . '&nbsp;<img src="images/icons/dropdown_arrow.svg" style="vertical-align: top; margin-top: 5px;"/>',
                        'link' => 'javascript: return false;',
                        'options' => array('a_extra' => 'data-jq-dropdown="#more_menu"')
                    );
                }
            }

            // back link
            if ($entry) {
                $tabs_generate += $this->getViewEntryTabsBack($record_id, $options2);
            }

            $nav = new AppNavigation;
            $nav->setPage(APP_ADMIN_PATH . 'index.php');
            
            $equal_attrib = (!empty($options2['equal_attrib'])) ? $options2['equal_attrib'] : 'action';
            $nav->setEqualAttrib('GET', $equal_attrib);
            // $nav->setTemplate(APP_TMPL_DIR . 'sub_menu_html.html');
            // $nav->setTemplate(APP_TMPL_DIR . 'sub_menu_simple.html');
            $nav->setTemplate(APP_TMPL_DIR . 'btn_menu.html');

            foreach ($tabs_generate as $k => $v) {
                $opt = (!empty($v['options'])) ? $v['options'] : array();
                $nav->setMenuItem($v['title'], $v['link'], $opt);

                // add some more when highlight
                if(!empty($v['highlight'])) {
                    foreach($v['highlight'] as $v2) {
                        $nav->setHighlightMenuItem($v2, $k);
                    }
                }
            }

            if(!empty($entry['title'])) {
                $str_ = '<div class="entryTabTitle">[%d], %s</div>';
                $str = sprintf($str_, $record_id, $entry['title']);
            }

            if(!empty($options2['right'])) {
                $nav->callback_tpl = array(
                    'tplAssign', array('right_content', $options2['right'])
                );
            }

            $str .= $nav->generate() . "<br/><br/>";
            $str .= $more_menu;

            return $str;
        }

        return $str;
    }


    function getViewEntryTabsMoreMenu($entry, $actions, $own_record = true) {

        $record_id = $entry['id'];
        $active = $entry['active'];

        // actions
        $prev_action = false;
        $action_order = self::getActionsOrder(); // self here not to reasign in case if inline view like in article -> history 
        $action_items = array();

        $menu_str = '<div id="more_menu" class="jq-dropdown jq-dropdown-tip jq-dropdown-relative"><ul class="jq-dropdown-menu">%s</ul></div>';
        $action_item_str = '<li><a href="%s" %s %s>%s</a></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="jq-dropdown-divider"></li>';
        $img_path = 'images/icons/%s.gif';

        
        // $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        // $actions = array_diff_key($actions, $pluginable);

        foreach ($action_order as $k => $action) {

            $link = false;
            $img = false;
            $msg = false;
            $title = false;
            $confirm_msg = false;
            $link_attributes = '';

            $action_values = $action;
            if($action != 'delim') {
                if(in_array($action, $actions, true)) {
                    $action_values = $action;

                // use key for action $actions['update'] = ...
                } elseif(isset($actions[$action])) {

                    // remove emty actions in case if we use it like
                    if(empty($actions[$action])) {
                        continue;
                    }

                    if(is_array($actions[$action])) {
                        $action_values = $actions[$action];
                    }

                } else {
                    continue;
                }
            }

            switch ($action) {
            case 'delete':
                if($this->priv->isPriv('delete')) {
                    if($this->priv->isPrivStatusAction('delete', false, $active)) {
                        $link = $this->getActionLink('delete', $record_id);
                        $title = $this->msg['delete_msg'];
                        $confirm_msg = $this->msg['sure_common_msg'];
                    }

                    // self delete
                    if($this->priv->isSelfPriv('delete') && !$own_record) {
                        $link = false;
                    }
                }

                break;

            case 'clone':
                if($this->priv->isPriv('insert')) {
                    $more = array('show_msg'=>'note_clone-hint');
                    $link = $this->getActionLink('clone', $record_id, $more);
                    $title = $this->msg['duplicate_msg'];
                    $img = 'clone';
                }

                break;
            }

            // custom actions, overriding
            if (is_array($action_values)) {

                // not to add link if not allowed
                if(in_array($action, array('clone', 'trash', 'delete'))) {
                    if($link && !empty($action_values['link'])) {
                        $link = $action_values['link'];
                    }
                    
                } elseif(!empty($action_values['link'])) {
                    $link = $action_values['link'];
                }

                if (!empty($action_values['title'])) {
                    $title = $action_values['title'];
                }

                // if (!empty($action_values['confirm_msg'])) {
                if (isset($action_values['confirm_msg'])) {
                    $confirm_msg = $action_values['confirm_msg'];
                }
            }


            if ($link) {
                $confirm = '';
                if($confirm_msg) {
                    $confirm_msg = str_replace(array('\n', '\r'), '', $confirm_msg);
                    $confirm_msg = addslashes($confirm_msg);
                    $confirm = sprintf("onClick=\"confirm2('%s', '%s');return false;\"", $confirm_msg, $link);
                }
                
                if (!empty($action_values['options'])) {
                    $confirm = ' ' . $action_values['options'];
                }
                
                if (!empty($action_values['disabled']) && $action_values['disabled'] === true) {
                    $action_items[] = sprintf($disabled_item_str, $msg);

                } else {
                    $action_items[] = sprintf($action_item_str, $link, $link_attributes, $confirm, $title);
                }
            }

            // delim
            $last_key = count($action_items)-1;
            if($action == 'delim' && $action_items && $action_items[$last_key] != $divider_str) {
                $action_items[] = $divider_str;
            }
        }

        // remove first and last delims
        if (isset($action_items[$last_key])) {
           if($action_items[$last_key] == $divider_str) {
               unset($action_items[$last_key]);
           }
       }

        $menu = '';
        if (!empty($action_items)) {
            $menu = sprintf($menu_str, implode('', $action_items));
        }

        return $menu;
    }


    function getViewEntryTabsBack($record_id, $options = array()) {

        if(!empty($options['back_link'])) {
            $link = $options['back_link'];

        } else {
            $link = $this->controller->getCommonLink();
            if($referer = @$_GET['referer']) {
                $link = $this->getRefererLink($referer, array('index'));
            }
        }

        $tabs = array();
        $tabs['spacer'] = array('title'  => 'spacer', 'link' => 1);
        $tabs['back'] = array(
            'title'  => '&#x2190; ' . $this->msg['back_msg'],
            'link' => $link,
            'options' => $options
        );

        return $tabs;
    }


    function parseErrorMsgToGrowl($msg) {
        $error_msg = preg_replace("/\r\n|\r|\n/", '<br />', trim($msg));

        $error_msg_arr = explode(' ', $error_msg);
        foreach($error_msg_arr as $k2 => $v2) {
            if (_strlen($error_msg_arr[$k2]) > 33) {
                $error_msg_arr[$k2] = wordwrap($error_msg_arr[$k2], 33, ' ', true);
            }
        }

        $error_msg = implode(' ', $error_msg_arr);
        $error_msg = RequestDataUtil::safeHtmlentitiesGrowl($error_msg);
        
        return $error_msg;
    }


    function ajaxValidateForm($values, $options = array()) {
        
        $objResponse = new xajaxResponse();
        if (!empty($values['_files'])) {
            $_FILES = $values['_files'];
        }

        $objResponse->script('$("select, input, textarea, password").removeClass("validationError");');
        $objResponse->script('$("#growls").empty();');

        $func = (empty($options['func'])) ? 'getValidate' : $options['func'];
        $ov = $this->obj->$func($values);

        if($key = array_search('action', $ov['options'])) {
            $ov['options'][$key] = $this->controller->action;
        }

        if($key = array_search('manager', $ov['options'])) {
            $ov['options'][$key] = $this->manager;
        }

        $is_error = call_user_func_array($ov['func'], $ov['options']);
        // $is_error = false; // to test/debug normal submit

        // echo '<pre>', print_r($options,1), '</pre>'; exit;

        if ($is_error) {
            $error_fields = array();
            foreach($this->obj->errors as $type => $num) {
                foreach($num as $v) {
                    $module = ($this->controller->module == 'setting') ? $this->controller->page : $this->controller->module;
                    $msg = AppMsg::getErrorMsgs($module);

                    $error_msg = ($type == 'custom') ? $v['msg'] : $msg[$v['msg']];
                    $error_msg = $this->parseErrorMsgToGrowl($error_msg);

                    // size: "large",
                    $str = '$("div.growl").remove(); $.growl.error({title: "", message: "%s", fixed: true});';
                    $objResponse->script(sprintf($str, $error_msg));

                    if (is_array($v['field'])) {
                        foreach ($v['field'] as $v1) {
                            $error_fields[$v1] = $v['rule'];
                        }

                    } else {
                        $error_fields[$v['field']] = $v['rule'];
                    }
                }
            }
            
            $objResponse->call('ErrorHighlighter.highlight', $error_fields);
            $objResponse->script('$("#loadingMessagePage").hide();');
            $objResponse->script('$(".loading_spinner").hide();');

            // saml/ldap
            if (!empty($options['callback'])) {
                if ($options['callback'] != 'skip') {
                    $script = $options['callback'] . 'ValidateErrorCallback();';
                    $objResponse->script($script);
                }
            }

        } else {
            
            $button_name = (empty($options['button_name'])) ? 'submit' : $options['button_name'];
            // echo '<pre>', print_r($button_name,1), '</pre>';
            
            if (!empty($options['callback'])) {
                if ($options['callback'] != 'skip') {
                    $objResponse->call($options['callback'], $button_name);
                }

                //$objResponse->script('$("#loadingMessagePage").hide();');

            } else { // default behaviour
                $objResponse->call('showSpinner', $button_name);

                $script = sprintf('$("input[name=%s], button[name=%s]").attr("onClick", "").click();', $button_name, $button_name);
                $objResponse->script($script);
            }

        }

        return $objResponse;
    }


    // parse filter data, use mysql or sphinx
    function parseFilterSql($manager, $q, $mysql_sql, $sphinx_sql, $options = array()) {

        $arr = array();
        $arr_keys = array('where', 'select', 'join', 'from', 'match', 'group');

        if(!empty($sphinx_sql['match']) && AppSphinxModel::isSphinxOnSearch($q)) {
            foreach($arr_keys as $v) {
                $arr[$v] = '';
                if(isset($sphinx_sql[$v])) {
                    $arr[$v] = implode(" \n", $sphinx_sql[$v]);
                }
            }

            $bp = PageByPage::factory('form', $manager->limit, $_GET);
            $sort = $this->getSort();

            $smanager = new AppSphinxModel;
            $smanager->setIndexParams($options['index']);

            if(!empty($options['own'])) {
                $reg =& Registry::instance();
                $priv = $reg->getEntry('priv');

                $smanager->setOwnParams($manager, $priv);
            }

            if(!empty($options['entry_private'])) {
                $smanager->setEntryRolesParams($manager, 'write');
            }

            if(!empty($options['cat_private'])) {
                $smanager->setCategoryRolesParams($manager, $options['cat_private']);
            }

            $smanager->setSqlParams($arr['where']);
            $smanager->setSqlParamsSelect($arr['select']);
            $smanager->setSqlParamsMatch($arr['match']);
            $smanager->setSqlParamsOrder($sort->getSql());

            $group = (!empty($arr['group'])) ? $arr['group'] : null;
            
            $arr = array_map(function($value) { return NULL; }, $arr);
            $ids = $smanager->getRecordsIds($bp->limit, $bp->offset);
            // echo '<pre>', print_r($ids, 1), '</pre>';

            $id_field = (!empty($options['id_field'])) ? $options['id_field'] : 'e.id';

            if(!empty($ids)) {
                $arr['where'] = sprintf('AND %s IN(%s)', $id_field, implode(',', $ids));
                $arr['sort'] = sprintf('ORDER BY FIELD(%s, %s)', $id_field, implode(',', $ids));

            } else {
                $arr['where'] = 'AND 0';
                $arr['sort'] = 'ORDER BY id';
            }

            if (!empty($group)) { // for mysql
                $arr['group'] = $group;
            }

            $arr['count'] = $smanager->getCountRecords();
            $arr['offset'] = 0;

        } else {

            foreach($arr_keys as $v) {
                $arr[$v] = '';
                if(isset($mysql_sql[$v])) {
                    $arr[$v] = implode(" \n", $mysql_sql[$v]);
                }
            }
        }

        return $arr;
    }
    
    
    function parseFilterMySql($mysql_sql) {
        $arr_keys = array('where', 'select', 'join', 'from', 'match', 'group');
        foreach($arr_keys as $v) {
            $arr[$v] = '';
            if(isset($mysql_sql[$v])) {
                $arr[$v] = implode(" \n", $mysql_sql[$v]);
            }
        }
        
        return $arr;
    }
    
    
    // determine if list view returned no records 
    static function isNoRecords($content) {
        $ret = false; // no empty content
    
        preg_match('#id="filter_div"#', $content, $matches);
        
        if(!empty($matches[0])) {
            
            // preg_match('#<tr id="row_[_\d\w]+"#', $content, $matches2);
            preg_match('#<tr +id="row_[\w]+"#', $content, $matches2);
            $ret = (empty($matches2[0])) ? true : false;
            // echo '<pre>', print_r($matches2,1), '<pre>';
            
            // <div id="template_85"  automations, feedbacks, etc
            if($ret) {
                preg_match('#<div id="template_[\d]+"#', $content, $matches3);
                $ret = (empty($matches3[0])) ? true : false;
            }
        }
        
        return $ret;
    }
    
    
    static function getNoRecordsLinks($controller, $priv) {
        
        $add_link = array();
        $default_link = array();
        
        if($priv->isPriv('insert')) {
            
            $app_view = new AppView;
            
            if(!in_array($controller->module, array('feedback', 'report', 'log', 'trash'))
            && !in_array($controller->page, array('kb_comment', 'kb_rate'))) 
            {
                $add_link = array(
                    'link' => $app_view->getActionLink('insert'), 
                    'msg' => $app_view->msg['add_new_msg']
                );
            }
        
            if(in_array($controller->page, array('automation'))
            || in_array($controller->page, array('workflow'))) 
            {
                $default_link = array(
                    'link' => $app_view->getActionLink('default'), 
                    'msg' => $app_view->msg['defaults_msg'],
                    'msg2' => $app_view->msg['or_msg']
                );
            }
        }
        
        return array('add' => $add_link, 'default' => $default_link);
    }
    
    
    static function getNoRecordsBox($add_link = false, $default_link = false, $key = 'records') {
        
        if($add_link) {
            $str = '<a href="%s">%s</a>';
            $str = sprintf($str, $add_link['link'], $add_link['msg']);
            
            if($default_link) {
                $str2 = ' %s <a href="%s">%s</a>';
                $str .= sprintf($str2, $default_link['msg2'], $default_link['link'], $default_link['msg']);
            }
            
            $hint_key = sprintf('note_no_%s_add', $key);
            $ret = AppMsg::hintBoxCommon($hint_key, array('link' => $str));
            
        } else {
            $ret = AppMsg::hintBoxCommon('note_no_' . $key);
        }
        
        return $ret;
    }
    
    
    static function parseGetNoRecordsFilterBox($content) {
        
        // strip not populated msg in admin/modules/stuff/list_builder/template/list.html
        // or replace will added <p> tag
        $content = preg_replace("#^\{msg\}\s+#u", '', $content); 
        
        $msg = AppMsg::hintBoxCommon('note_no_records_search');
        
        $ret = ParseHtml::replaceHtmlElementById($content, 'listTable', $msg);
        
        // get back {word} 
        // $content = preg_replace("#%7B(\w+)%7D#", '{$1}', $content);
        $content = str_replace(['%7B','%7D'], ['{','}'], $content);
    
        return $content;
    }
    
    
    // LISTS // ----------------
    
    // default functions for customize list view
    function getListColumns() {
        return array();
    }
    
    
    // move to app view
    // returns - $field, $field_title, $field_by_user
    function getUserToList($user, $field, $user_id = false) {
        $v = array();
        
        if(!empty($user)) {
            $v[$field] = PersonHelper::getShortName($user);
            
            $uname = PersonHelper::getFullName($user);
            $f = sprintf('%s_title', $field);
            $title = $uname;
            
            $user_id = (!empty($user['id'])) ? $user['id'] : $user_id;
            if($user_id) {
                
                $filter_field = sprintf('%s_link', $field);
                $filter_link = sprintf('%s_id:%d', $field, $user_id);
                $v[$filter_field] = $this->getLink('this','this','this','this', array('filter[q]'=>$filter_link));
                
                if ($this->priv->isPriv('select', 'user') && $user_id != AuthPriv::getUserId()) {
                    $ulink = $this->getLink('users', 'user', '', 'detail', array('id'=>$user['id']));
                    $ustr = '<a href="%s">%s</a>';
                    $title = sprintf($ustr, $ulink, $uname);
                }
            }
            
            $v[$f] = $this->stripVars($title);
        }
        
        // echo '<pre>', print_r($v,1), '<pre>';
        return $v;
    }
    
    
    function getDateToList($date, $field, $format = 'date', $user = false) {
        
        $ret = array();
        
        $f = sprintf('%s_formatted', $field);
        $ret[$f] = $this->getFormatedDate($date, $format);
        
        $f = sprintf('%s_interval', $field);
        $ret[$f] = $this->getTimeInterval($date);
        
        $f = sprintf('%s_full', $field);
        $datetime = $this->getFormatedDate($date, 'datetime');
        // we may need to like this, added option full for getFormatedDate
        // the same could be for datetimesec
        // this function applied in lists 
        // $datetime = $this->getFormatedDate($date, 'full');
        $ret[$f] = $datetime;
        
        $f = sprintf('%s_full_sec', $field);
        $datetime = $this->getFormatedDate($date, 'datetimesec');
        $ret[$f] = $datetime;
        
        if(!empty($user)) {
            $u = $this->getUserToList($user, 'user');
            $f = sprintf('%s_full', $field);
            $ret[$f] = sprintf('%s (%s)', $datetime, $u['user_title']);
        }
        
        return $ret;
    }
    
    
    function getTitleToList($title, $num_signs = 100, $pref = 'title') {
        $v = array();
        
        $title_entry = sprintf('%s_entry', $pref);
        $v[$title_entry] = $this->getSubstringStrip($title, $num_signs);
        
        $title_title = sprintf('%s_title', $pref);
        $v[$title_title] = ($v[$title_entry] == $title) ? '' : $title;
        
        return $v;
    }
    
    
    // used in account, could be called in public and in admin
    static function isAdminView() {
        // return (!isset($_GET['View']) || strpos(getcwd(), '/admin') !== false);
        return (!isset($_GET['View']));
    }
    
}

?>