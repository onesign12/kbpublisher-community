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


class AppNavigation extends Navigation
{
    
    var $db;
    var $tbl_pref_custom;
    var $tbl_pref;
    var $tbl;
    var $tables = array('priv_module', 'priv_module_lang');
    var $page;
    var $msg = array();
    var $callback_tpl;
    var $sub_equal;
    
    
    function __construct() {
        
        $reg =& Registry::instance();
        $this->db         =& $reg->getEntry('db');
        $this->tbl_pref =& $reg->getEntry('tbl_pref');
        
        $this->tbl = (object) AppModel::_setTableNames($this->tables, 
                                                       $this->tbl_pref, 
                                                       $this->tbl_pref_custom);
                                                       
        $this->setPage($_SERVER['PHP_SELF']);
    }
    
    
    function setMenuMsg($msgs, $append_msg_keys = array(), 
                            $append_string = " <span style='color: red;'>[new]</span>") {
        $this->msg = $msgs;
        foreach($append_msg_keys as $msg_key) {
            if(isset($this->msg[$msg_key])) {
                $this->msg[$msg_key] .= $append_string; 
            }
        }
    }
    
    
    function setPage($val) {
        $this->page = $val;
    }
    
    
    function getSql($module_name = false) {

        $sql = "SELECT 
            p1.use_in_sub_menu AS use_in_sub_menu_top,
            p1.menu_name AS top_menu_name, 
            p1.module_name AS top_module_name,
            p2.by_default AS by_default,
            p2.use_in_sub_menu AS use_in_sub_menu_sub, 
            p2.menu_name AS sub_menu_name,
            p2.module_name AS sub_module_name,
            p2.check_priv
        
        FROM {$this->tbl->priv_module} p1, {$this->tbl->priv_module} p2 
        
        WHERE p1.id = p2.parent_id
        AND p1.module_name = '{$module_name}'
        AND p2.active = 1
        ORDER BY p2.sort_order";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    // select from table priv_module
    function setMenu($module_name, $sub_menu_attrib = false) {
    
        $menu = false;
        
        $sql = $this->getSql($module_name);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $menu = true;
            
            if($row['sub_module_name'] == 'spacer') {
                $this->setMenuItem('spacer', $row['sub_menu_name']);
                continue;
            }            
            
            // hide not authorized module, page, ...
            if(!isset($_SESSION['priv_']['all']) && $row['check_priv']) {
                if(!isset($_SESSION['priv_'][$row['sub_module_name']])) { continue; }
            }
                        
            // miss top
            if($row['sub_module_name'] == 'all') { continue; }            
                        
            $params = array();
            
            // generate different link go to page by default
            if($row['by_default']) {
                
                //change by default menu link
                // if(isset($_SESSION['priv_'][$row['sub_module_name']]['by_default'])) {
                //     $row['by_default'] = $_SESSION['priv_'][$row['sub_module_name']]['by_default'];
                // }
                
                // 27 Feb, 2015, eleontev changes to be able not to change link
                // if access to normal by_default is allowed, example module=tool&page=tool
                if(!isset($_SESSION['priv_'][$row['by_default']])) {
                    if(isset($_SESSION['priv_'][$row['sub_module_name']]['by_default'])) {    
                        $row['by_default'] = $_SESSION['priv_'][$row['sub_module_name']]['by_default'];
                    }
                }
                
                if(strpos($row['by_default'], '/')) {
                    $i = 0;
                    $param = explode('/', $row['by_default']);
                    foreach($param as $k => $v) {
                        $params[] = $this->sub_equal[$i++]['value'] . '=' . $v;
                    }
                    
                } else {
                    $key_ = 0;
                    $params = array($this->sub_equal[$key_]['value'] .'='. $row['by_default']);
                }
            
            // generate different link to go to sub page
            } elseif($row['use_in_sub_menu_sub'] == 'YES_DEFAULT') {
                $params = array($this->sub_equal[0]['value'] .'='. $row['sub_module_name']);
            }


            $params = implode('&', array_merge($this->get_params, $params)) . '&';
            
            // add menu to sub from top
            if($row['use_in_sub_menu_top'] != 'NO' 
                && $row['use_in_sub_menu_top'] != '' 
                && !isset($use_in_sub_menu_top)) {
                
                $use_in_sub_menu_top = 1;
                $page = $this->page . '?' . $params . $this->equal_value . '=' . $row['top_module_name'];                
                
                if(isset($this->msg[$row['top_module_name']])) { 
                    $menu_name = $this->msg[$row['top_module_name']]; 
                } else { 
                    $menu_name = $row['top_menu_name']; 
                }
                
                $this->setMenuItem($menu_name, $page);
            }
            
            
            $page = $this->page . '?' . $params . $this->equal_value . '=' . $row['sub_module_name'];
            
            if(isset($this->msg[$row['sub_module_name']])) { 
                $menu_name = $this->msg[$row['sub_module_name']]; 
            } else { 
                $menu_name = $row['sub_menu_name']; 
            }
            
            $this->setMenuItem($menu_name, $page);
            $page = '';
        }
        
        return $menu;
    }
    
    
    function getMenuArr($skip_top = false) {
        
        $sql = "SELECT * FROM {$this->tbl->priv_module} 
        WHERE module_name != 'spacer' AND id != 0 AND active = 1";
        
        if($skip_top) {
            $sql .= " AND parent_id != 0";
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $menu_msg = AppMsg::getMenuMsgs(false, false);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['id']] = $row;
            $data[$row['id']]['menu_name'] = $menu_msg[$row['module_name']];
        }
        
        return $data;
    }
    
    
    function getDefaultPageByModule($module) {
        $sql = "SELECT by_default FROM {$this->tbl->priv_module} 
        WHERE module_name = '%s' AND parent_id = 0";
        $sql = sprintf($sql, addslashes($module));
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return explode('/', $result->Fields('by_default'));
    }
    
    
    function getSearchableSetingsMenu() {
        
        $this->sm = new SettingModel;
        $settings = $this->sm->getSearchableSettings();
        // die(var_dump($settings));
        
        $menu_arr = $this->getMenuArr();
        // die(var_dump($menu_arr));z
        
        $hide_modules = AppPlugin::getPluginsFilteredOff('setting_id');
        $menu_ar2 = array_flip(array_column($menu_arr, 'module_name', 'id'));  
        foreach($hide_modules as $plugin => $v) {
            
            // remove settings from in whole tab
            foreach($v['setting_id'] as $setting_id) {
                unset($settings[$setting_id]);
            }
            
            // remove tab name from search
            if(isset($v['pages'])) {
                foreach($v['pages'] as $page) {
                    unset($menu_arr[$menu_ar2[$page]]);
                }
            }
        }
    
        
        $tree = new TreeHelper();
        foreach(array_keys($menu_arr)  as $k) {
            $tree->setTreeItem($menu_arr[$k]['id'], $menu_arr[$k]['parent_id'], $menu_arr[$k]);
        }
        
        $priv_module_ids = $this->sm->getValuesArray($menu_arr, 'id');
        $priv_module_names = $this->sm->getValuesArray($menu_arr, 'module_name');
        $priv_modules = array_combine($priv_module_ids, $priv_module_names);
        
        
        $menu_arr_setting = $tree->getChildsById(3); // setting pages/tabs
        
        // 19-08-2022 eleontev
        // remove not allowed settings from search
        if(!isset($_SESSION['priv_']['all'])) {
        
            $top_ids = $parent_ids = [];
            foreach($menu_arr_setting as $k => $v) {
                if(isset($_SESSION['priv_'][$v['module_name']])) { 
                    $top_ids[] = $v['id'];
                }
            }
            
            foreach($menu_arr_setting as $k => $v) {
                foreach($top_ids as $top_id) {
                    if($v['parent_id'] == $top_id) { 
                        $parent_ids[] = $v['id'];
                    }
                }
            }
        
            $keep_ids = array_merge($top_ids, $parent_ids);
            foreach($menu_arr_setting as $k => $v) {
                if(!in_array($v['id'], $keep_ids)) {
                    unset($menu_arr_setting[$k]);
                }
            }
        }
        // <- remove 
        
        // echo '<pre>' . print_r($keep_ids, 1) . '</pre>';
        // echo '<pre>' . print_r($top_ids, 1) . '</pre>';
        // echo '<pre>' . print_r($parent_ids, 1) . '</pre>';
        // echo '<pre>' . print_r($menu_arr_setting, 1) . '</pre>';exit;
        // die(var_dump($menu_arr_setting));
        
        $hide_settings = AppPlugin::getPluginHideSettings();    
        if(BaseModel::isCloud()) {
            $hide_settings += BaseModel::getCloudHideSettings();
        }
        
        $tmenu = array();
        foreach($menu_arr_setting as $k => $v) {
            $tmenu[$v['parent_id']][$v['id']] = $v;
        }
        
        foreach($tmenu as $k => $v) {            
            uasort($v, function($a, $b) {
                return $a['sort_order'] - $b['sort_order'];
            });
            
            $tmenu[$k] = $v;
        }
        
        $menu = array();
        foreach(array_keys($tmenu) as $parent_id) {
            
            $has_parent = isset($menu[$parent_id]);
        
            foreach($tmenu[$parent_id] as $id => $v) {
                $id = $v['id'];
                
                if($has_parent) {
                    $url = array(
                        'page=' . $menu[$parent_id]['menu_key'],
                        'sub_page=' . $v['module_name']
                    );
                    
                    $menu[$id] = $this->parseMenuItem($v, $url, array('sub_page'));
                    if($menu[$id]) {
                        $m = $this->parseMenuItemText_setting($v, $settings, $priv_modules);
                        if(isset($m['menu_text']) && $hide_settings) {
                            $m['menu_text'] = array_diff_key($m['menu_text'], array_flip($hide_settings));
                            
                            // foreach($hide_settings as $v) {
                            //     $group_ids[] = $m['menu_text'][$v]['group_id'];
                            // }
                            // echo '<pre>' . print_r($group_ids, 1) . '</pre>';
                            
                        }
                        
                        $menu[$id] += $m;
                        // $menu[$id] += $this->parseMenuItemText_setting($v, $settings, $priv_modules);
                    }
                    
                    $menu[$id]['parent_id'] = $menu[$parent_id]['menu_item'];
                    $menu[$id]['parent'] = $menu[$parent_id];
                    
                } else {
                    
                    $url = array(
                        'page=' . $v['module_name']
                    );
                    
                    $menu[$id] = $this->parseMenuItem($v, $url, array('sub_page'));
                    if($menu[$id]) {
                        $m = $this->parseMenuItemText_setting($v, $settings, $priv_modules);
                        if(isset($m['menu_text']) && $hide_settings) {
                            $m['menu_text'] = array_diff_key($m['menu_text'], array_flip($hide_settings));
                        }
                        
                        $menu[$id] += $m;
                        // $menu[$id] += $this->parseMenuItemText_setting($v, $settings, $priv_modules);
                    }
                }
                // echo '<pre>' . print_r($menu[$id], 1) . '</pre>';
            }
            
            // $a[] = array_column($menu[$id]['menu_text'], 'group_id');
            // echo '<pre>' . print_r($a, 1) . '</pre>';
            
            if ($has_parent) {
                unset($menu[$parent_id]);
            }
        }
        
        // echo '<pre>' . print_r($menu, 1) . '</pre>';
        // exit;
        
        // die(var_dump($menu));
        return $menu;
    }
    
    
    function getTreeMenu() {
        
        $menu_arr = $this->getMenuArr();
        // die(var_dump($menu_arr));
        
        $tree = new TreeHelper();
        foreach(array_keys($menu_arr)  as $k) {
            $tree->setTreeItem($menu_arr[$k]['id'], $menu_arr[$k]['parent_id'], $menu_arr[$k]);
        }
        
        $tmenu = $tree->tree_ar;
        $menu = array();
        
        foreach(array_keys($tmenu) as $parent_id) {
            
            $has_parent = isset($menu[$parent_id]);
        
            foreach($tmenu[$parent_id] as $id => $v) {
                $id = $v['id'];
                
                if($has_parent) {
                    $url = array(
                        'page=' . $menu[$parent_id]['menu_key'],
                        'sub_page=' . $v['module_name']
                    );
                    
                    if($ret = $this->parseMenuItem($v, $url, array('sub_page'))) {
                        $menu[$parent_id]['sub'][$id] = $ret;
                    }
                    
                } else {
                    
                    $url = array(
                        'page=' . $v['module_name']
                    );

                    if($ret = $this->parseMenuItem($v, $url, array('sub_page'))) {
                        $menu[$id] = $ret;
                    }
                    
                }
            }
            
            foreach(array_keys($tmenu) as $parent_id) {
                if(isset($menu[$parent_id])) {

                }
            }
        }
        
        // echo '<pre>', print_r($menu,1), '<pre>'; die();
        return $menu;
    }
    
    
    function parseMenuItem($row, $url, $sub_param) {
        
        if($row['module_name'] == 'spacer') {
            return;
        }            
        
        // hide not authorized module, page, ...
        if(!isset($_SESSION['priv_']['all']) && $row['check_priv']) {
            if(!isset($_SESSION['priv_'][$row['module_name']])) { 
                return; 
            }
        }
        
        $params = array();
        
        // generate different link go to page by default
        if($row['by_default']) {
            if(!isset($_SESSION['priv_'][$row['by_default']])) {
                if(isset($_SESSION['priv_'][$row['module_name']]['by_default'])) {
                    $row['by_default'] = $_SESSION['priv_'][$row['module_name']]['by_default'];
                }
            }

            $i = 0;
            foreach(explode('/', $row['by_default']) as $k => $v) {
                $url[] = $sub_param[$i++] . '=' . $v;
            }
        }

        $params = implode('&', array_merge($this->get_params, $url));        
        $page = $this->page . '?' . $params;
        
        $menu_name = $row['menu_name']; 
        if(isset($this->msg[$row['module_name']])) { 
            $menu_name = $this->msg[$row['module_name']]; 
        }
        
        $ar['menu_item'] = $menu_name;
        $ar['menu_link'] = $page;
        $ar['menu_key'] = $row['module_name'];
        
        return $ar;
    }

    
    function parseMenuItemText_setting($row, $settings, $priv_modules) {
        
        $ar = array();
        
        // for settings here may need move out if required 
        $setting_ar = SettingModel::$key_to_module;
        $module_name = (isset($setting_ar[$row['module_name']])) ? $setting_ar[$row['module_name']] : $row['module_name'];
        
        // lang file
        $file = AppMsg::getModuleMsgFile($module_name, 'setting_msg.ini');
        $msg = AppMsg::parseMsgs($file);
        $msg = AppMsg::addIdeticalMsgs($msg);
        
        // add en tags to other lang;
        if(AppMsg::getAppLang() != 'en') {
            $file_en = AppMsg::getModuleMsgFileLangDefault($module_name, 'setting_msg.ini');
            $msg_en = AppMsg::parseMsgs($file_en);
            $msg_en = array_filter($msg_en, function ($var) {
                return (isset($var['tags']));
            });
            
            foreach(array_keys($msg_en) as $k) {
                if(isset($msg[$k])) {
                    $msg[$k]['tags'] .= ' ' . $msg_en[$k]['tags'];  
                }
            }
        }
        
        // settings
        $setting_module_id = array_search($module_name, $this->sm->module_names);
        if (!$setting_module_id) {
            $setting_module_id = array_search($module_name, $priv_modules);
        }
        
        if (!empty($settings[$setting_module_id])) {
            foreach ($settings[$setting_module_id] as $group_id => $setting) {
                $group_title = (!empty($msg['group_title'][$group_id])) ? $msg['group_title'][$group_id] : '';
                $ar['groups'][$group_id] = $group_title;
                
                foreach ($setting as $setting_id => $setting_key) {
                    $anchor = 'anchor_' . $setting_key;
                    
                    $hidden_keywords = '';
                    $boost_keywords = '';
                    
                    if ($setting_key == 'search_spell_suggest') {
                        $sources = SpellSuggest::getSources();
                        $hidden_keywords = implode(' ', $sources);
                    }
                    
                    if ($setting_key == 'page_to_load') {
                        $popup_settings = $settings[10][1];
                        foreach ($popup_settings as $popup_setting_key) {
                            $ar['menu_text'][$popup_setting_key] = array(
                                'title' => $msg[$popup_setting_key]['title'],
                                'anchor' => 'anchor_page_to_load',
                                'group_title' => $group_title,
                                'hidden_keywords' => $hidden_keywords
                            );
                        }
                    }
                    
                    $title = @$msg[$setting_key]['title'];
                    if (!empty($msg[$setting_key]['tags'])) {
                        $hidden_keywords .= $msg[$setting_key]['tags'];
                    }
                    
                    if (!empty($msg[$setting_key]['boost'])) {
                        $boost_keywords .= $msg[$setting_key]['boost'];
                    }
                    
                    if ($title || $hidden_keywords) {
                        $ar['menu_text'][$setting_key] = array(
                            'title' => $title,
                            'anchor' => $anchor,
                            'group_id' => $group_id,
                            'group_title' => $group_title,
                            'hidden_keywords' => $hidden_keywords,
                            'boost_keywords' => $boost_keywords
                        );
                    }
                }
            }
        }
        
        return $ar;
    }


    static function parseEPage($page) {

        $ret = false;
        @$content = file_get_contents($page);
        if(!$content) {
            $b = array('e', 'o');
            $s = $b[0] . 'r' . 'r' . $b[1] . 'r';
            $s .= ' o'.'p'.$b[0].'n: '. basename($page);
            echo $s;
            die();
        }

        preg_match('#\/\*(\w{48})\*\/#', $content, $match);
        $code = (!empty($match[1])) ? $match[1] : false;
        
        $f = 'b'.'a'.'s'.'e'.'6'.'4'.'_'.'d'.'ec'.'od'.'e';
        preg_match('#eval\('.$f.'\("\s(.*?)\s"\)\);#s', $content, $match);
        $data = (!empty($match[1])) ? $match[1] : false;

        if($code && $data) {

             // not mixed code, the same as encoded on site
            $dc = str_replace(array("\n", "\r"), '', trim($data));
            $code2 = str_repeat(md5($dc), 2);

            // not mixed code, reverted
            $code3 = str_split($code, 16);
            $code3 = $code3[2] . $code3[0] . $code3[1] . $code3[0];

            if($code2 === $code3) {
                $ret = true;
            }
        }

        return $ret;
    }


    static function parseEMenu() {
        $p = array(
            APP_LIB_DIR . 'core/base/BaseApp.php',
            APP_MODULE_DIR . 'setting/license_setting/inc/LicenseSettingModel.php',
            APP_ADMIN_DIR . 'index.php',
            APP_MODULE_DIR . 'user/user/inc/UserModel.php',
            APP_CLIENT_DIR . 'plugin/export/export/inc/KBExportHtmldoc_pdf.php'
        );
        
        $k = array_rand($p);
        // echo '<pre>', print_r($p[$k], 1), '</pre>';
        
        if(!AppNavigation::parseEPage($p[$k])) {
            $b = array('e', 'o');
            $s = $b[0] . 'r' . 'r' . $b[1] . 'r';
            $s .= ' d'.$b[0].'c'. $b[1] .'d'.'i'.'n'.'g';
            echo $s;
            die();
        }
    }


    function generate($menu_name = 'default') {
        
        if(!isset($this->menu_array[$menu_name])) { 
            return $this->_generateEmpty();
        } else {
            return $this->_generateMenu($menu_name);
        }
    }
    
    
    function _generateMenu($menu_name) {
        
        // why? 
        // ksort($this->menu_array[$menu_name]);
        
        $tpl = new tplTemplatez($this->template);
        
        // may need to set some extra blocks 
        if($this->callback_tpl) {
            call_user_func_array(array($tpl, $this->callback_tpl[0]), $this->callback_tpl[1]);
        }
        
        // only if template with row/active and row/nonactive
        if($this->template_type == 'NORMAL') {
            $page_val = $this->_getCheckValue($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            
            if(isset($this->highlight_menu_item[$menu_name][$page_val])) {
                $page_val = $this->highlight_menu_item[$menu_name][$page_val];
            }
            
            $menu_key = $this->_getCurrentMenuItem($page_val, $menu_name);
            
            foreach($this->menu_array[$menu_name] as $k => $v){
                $v['menu_key'] = $this->auxilary[$menu_name][$k];
                
                if($v['menu_item'] == 'spacer') {
                    $tpl->tplSetNeeded('row/spacer'); 
                
                } elseif($k == $menu_key){ 
                    $tpl->tplSetNeeded('row/active'); 
                
                } else { 
                    $tpl->tplSetNeeded('row/nonactive'); 
                }
                
                $tpl->tplParse(array_merge($this->options,$v),'row');
            }
            
        } else {
            foreach($this->menu_array[$menu_name] as $k => $v){
                $tpl->tplParse($v,'row');
            }
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function generateSearchSidebar($menu_arr, $options) {
        
        $tpl = new tplTemplatez($this->template);
    
        $js_hash = array();
        $str = '{key: "%s", title: "%s", hidden_keywords: "%s", boost_keywords: "%s", page: "%s", sub_page: "%s", group_id: "%s", group_title: "%s", url: "%s"}';
        
        $parents = array();
        foreach(array_keys($menu_arr) as $k) {
            
            $v = $menu_arr[$k];
            $sub_page = (!empty($v['parent_id'])) ? $v['parent_id'] : '';
            
            if (!empty($v['parent'])) {
                $parents[$v['parent']['menu_key']] = $v['parent'];
            }
            
            $js_hash[] = sprintf(
                $str,
                '',
                addslashes($v['menu_item']),
                '',
                '',
                addslashes($sub_page),
                '',
                $v['menu_key'],
                '',
                addslashes($v['menu_link'])
            );
            
            // settings
            $group_item_count = [];
            if (!empty($v['menu_text'])) {
                foreach ($v['menu_text'] as $setting_key => $setting) {
                    $group_id = $v['menu_key'];
                    if (!empty($setting['group_id'])) {
                        $group_id .= $setting['group_id'];
                        @$group_item_count[$setting['group_id']] = 1;
                    }
                    
                    $js_hash[] = sprintf(
                        $str,
                        $setting['anchor'],
                        addslashes($setting['title']),
                        addslashes($setting['hidden_keywords']),
                        @addslashes($setting['boost_keywords']),
                        addslashes($v['menu_item']),
                        addslashes($sub_page),
                        $group_id,
                        addslashes($setting['group_title']),
                        addslashes($v['menu_link'])
                    );
                }
            }
            
            // groups
            if (!empty($v['groups'])) {
                foreach ($v['groups'] as $group_id => $group_title) {
                    
                    // no index group if no items inside
                    if(!isset($group_item_count[$group_id])) {
                        continue;
                    }
                    
                    $js_hash[] = sprintf(
                        $str,
                        'group_anchor_' . $group_id,
                        addslashes($group_title),
                        '',
                        '',
                        addslashes($v['menu_item']),
                        addslashes($sub_page),
                        $v['menu_key'] . $group_id,
                        '',
                        addslashes($v['menu_link'])
                    );
                }
            }
        }
        
        foreach ($parents as $parent) {
            $js_hash[] = sprintf($str, '', addslashes($parent['menu_item']), '', '', '', '', $parent['menu_key'], '', addslashes($parent['menu_link']));
        }
        
        $js_hash = implode(",\n", $js_hash);         
        $tpl->tplAssign('list', $js_hash);
        
        if (isset($_REQUEST['filter']['qs'])) {
            $tpl->tplAssign('filter', $_REQUEST['filter']['qs']);
        }

        $msg = AppMsg::getMsgs('common_msg.ini');
        $tpl->tplAssign($msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    // just top line
    function _generateEmpty() {
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'sub_menu_empty.html');        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getMenu() {
        
    }
    
    
    function getTopMenu() {
        
    }
    
    
    function getShortcutMenu($controller, $priv, $msg) {

        $links = array();
        $links[] = array('knowledgebase', 'kb_entry', 'entry');
        $links[] = array('knowledgebase', 'kb_draft', 'entry_draft');
        $links[] = array('knowledgebase', 'kb_category', 'category');
        $links[] = array('knowledgebase', 'kb_glossary', 'glossary');
        $links[] = array('file', 'file_entry', 'file');
        $links[] = array('file', 'file_draft', 'file_draft');
        $links[] = array('file', 'file_category', 'file_category');
        $links[] = array('news', 'news_entry', 'news');
        $links[] = array('users', 'user', 'user');

        $drafts = array('kb_entry', 'file_entry');

        $range = array();
        $action_str = '<li><a href="%s">%s</a></li>';
        
        foreach($links as $v) {
            if($priv->isPriv('insert', $v[1])) {
                $link = $controller->getLink($v[0], $v[1], false, 'insert');
                $range[$v[1]] = sprintf($action_str, $link, $msg[$v[2]]);            
            
                // only drafts allowed
                if(in_array($v[1], $drafts)) {
                    if($priv->isPrivOptional('insert', 'draft', $v[1])) {
                        unset($range[$v[1]]);
                    }
                }
            }
        }
        
        return implode('<li class="jq-dropdown-divider"></li>', $range);
    }
    
    
    function getSidebarVars($controller, $sidebar2) {

        // unset($_COOKIE['kb_admin_sidebar_status_']);

        $sidebar_status = false;
        $sidebar_width = 220;
        $sidebar2_width = 220;
        $sidebar_class = '';

        $menu_text_display = 'inline';
        $content_left = (!empty($sidebar2)) ? $sidebar_width + $sidebar2_width : $sidebar_width;
        $sidebar2_class = (!empty($sidebar2)) ? 'shown' : 'empty';

        if(!$controller->getMoreParam('popup')) {

            $sidebar_status = (!empty($_COOKIE['kb_admin_sidebar_status_']));
    
            if ($sidebar_status) { // open
                $sidebar_class = 'shown';
        
            } else {
                $sidebar_width = 60;
                $sidebar_class = 'hidden';
            }
    
            $content_left = $sidebar_width;

            if(isset($_COOKIE['kb_admin_sidebar2_status_'])) {
                $sidebar2_statuses = json_decode($_COOKIE['kb_admin_sidebar2_status_']);
        
                if (!empty($sidebar2) && isset($sidebar2_statuses->{$controller->module})) {
                    $sidebar2_status = $sidebar2_statuses->{$controller->module};
                    $sidebar2_class = ($sidebar2_status) ? 'shown' : 'hidden';
                    $content_left += ($sidebar2_status) ? $sidebar2_width : 30;
                }
        
            } else { // open
                if (!empty($sidebar2)) {
                    // $content_left += $sidebar_width;
                    $content_left = $sidebar_width + $sidebar2_width;
                }
            }
    
            if (!$sidebar_status) {
                $menu_text_display = 'none';
            }
        }

        $vars = array(
            'sidebar_status' => $sidebar_status,
            'sidebar_class' => $sidebar_class,
            'sidebar_width' => $sidebar_width, 
            'sidebar2_left' => $sidebar_width, 
            'content_left' => $content_left,
            'sidebar2_class' => $sidebar2_class,
            'menu_text_display' => $menu_text_display
        );
    
        return $vars;
    }


    
    // ajax sidebar submenu, NOT IMPLEMENTED
    
    function getSidebarSubMenu($module_name) {

        $menu_name = 'sub_menu_dropdown';
        $this->setMenuMsg(AppMsg::getMenuMsgs($module_name));
        $this->setMenuName($menu_name);
        $this->setMenu($module_name);
        // $items = $this->menu[]

        $menu_items = array();

        $menu_str = '<div id="dropdown_submenu" class="jq-dropdown jq-dropdown-tip jq-dropdown-relative"><ul class="jq-dropdown-menu">%s</ul></div>';
        $menu_item_str = '<li><a href="%s" %s>%s</a></li>';
        // $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
        $divider_str = '<li class="jq-dropdown-divider"></li>';
        // $img_path = 'images/icons/%s.gif';        

        foreach($this->menu_array[$menu_name] as $k => $v){
        // foreach ($items as $k => $v) {

            $link = $v['menu_link'];
            $title = $v['menu_item'];
            $link_attributes = '';

            $menu_items[] = sprintf($menu_item_str, $link, $link_attributes, $title);

            // delim
            // $last_key = count($items)-1;
            // if($v == 'delim' && $items && $items[$last_key] != $divider_str) {
            //     $action_items[] = $divider_str;
            // }
        }

        // remove first and last delims
       //  if (isset($action_items[$last_key])) {
       //     if($action_items[$last_key] == $divider_str) {
       //         unset($action_items[$last_key]);
       //     }
       // }

        $menu = '';
        if (!empty($menu_items)) {
            $menu = sprintf($menu_str, implode('', $menu_items));
        }

        return $menu;
    }
    
    
    function ajaxGetSidebarSubMenu($module_name) {
        $objResponse = new xajaxResponse();

        // $objResponse->addAlert($module_name);
        $menu = $this->getSidebarSubMenu($module_name);
        // $objResponse->addAssign("dropdown_submenu_div", "innerHTML", '');
        // $objResponse->addAssign("dropdown_submenu_div", "innerHTML", $menu);

        // $objResponse->addScript(sprintf("$('#dropdown_submenu_div').html('%s');", $menu));
        // $objResponse->addScript("$('#dropdown_submenu').jqDropdown('show');");
        $objResponse->call('js_showSidebarSubMenu', $menu);

        return $objResponse;
    }

}

?>