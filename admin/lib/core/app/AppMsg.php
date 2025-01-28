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

// we combine all functions to get msg
// just for simplifing life in future
class AppMsg
{
    
    // static $app_lang = APP_LANG;
    static $app_msg_dir = APP_MSG_DIR;


    static function getAppLang() {
        return (defined('APP_LANG')) ? APP_LANG : 'en';
    }
    
    
    static function getCommonMsgFile($file_name) {
    
        $app_lang = self::getAppLang();
    
        $file = array();
        if(is_array($file_name)) {
            foreach($file_name as $k => $v) {
                $file['en'. $k]       = self::$app_msg_dir . 'en/' . $v;
                $file[$app_lang . $k] = self::$app_msg_dir . $app_lang . '/' . $v;
            }
        } else {
            $file['en']      = self::$app_msg_dir . 'en/' . $file_name;
            $file[$app_lang] = self::$app_msg_dir . $app_lang . '/' . $file_name;
        }
        
        foreach($file as $k => $v) {
            if(!file_exists($v)) { unset($file[$k]); }
        }
        
        return $file;
    }
    
    
    static function getModuleMsgFile($module, $file_name) {
        return AppMsg::getCommonMsgFile($module . '/' . $file_name);
    }
    
    
    static function getModuleMsgFileSingle($module, $file_name) {
        $app_lang = $app_lang = self::getAppLang();
        $files = AppMsg::getCommonMsgFile($module . '/' . $file_name);
        
        if(isset($files[$app_lang])) {
            return $files[$app_lang];
        } else {
            return $files['en'];
        }
    }
    
    
    static function getModuleMsgFileLangDefault($module, $file_name){
        $files = AppMsg::getCommonMsgFile($module . '/' . $file_name);
        return (isset($files['en'])) ? $files['en'] : [];
    }
    
    
    static function parseMsgs($file, $section = false, $process_sections = 1, $parse_array = false) {
        
        $msg = array();
        $msg_array = array();
        $app_lang = self::getAppLang();
                
        if(is_array($file)) {
            foreach($file as $k => $v) {
                if($parse_array) {
                    $msg_array[$k] = GetMsgHelper::parseIni($v, $section, $process_sections);
                } else {
                    $msg = GetMsgHelper::parseIni($v, $section, $process_sections) + $msg;
                }
            }

            // parse msg arrays, add en msg if missed in $app_lang
            // changed 2015-12-24 by eleontev added check for array and else block to parse 
            if(isset($msg_array['en'])) {
                
                foreach(array_keys($msg_array['en']) as $k) {
                        
                    if(is_array($msg_array['en'][$k])) {
                        if(@!is_array($msg_array[$app_lang][$k])) {
                            $msg_array[$app_lang][$k] = array();
                        }
                        
                        $msg[$k] = $msg_array[$app_lang][$k] + $msg_array['en'][$k];
                        
                    } else {
                        if(@!is_array($msg_array[$app_lang])) {
                            $msg_array[$app_lang] = array();
                        }
                        
                        $msg_array[$app_lang] = array_filter($msg_array[$app_lang]);
                        $msg = $msg_array[$app_lang] + $msg_array['en'];
                        
                        break;
                    }
                }            
            }

            
        } else { 
            $msg = GetMsgHelper::parseIni($file, $section, $process_sections);
        }

        // timestop('parseMsgs');
            
        return $msg;
    }
    
        
    static function parseMsgsMultiIni($file, $section = false) {
        $msg = array();
        
        if(is_array($file)) {
            foreach($file as $k => $v) {
                $msg2 = GetMsgHelper::parseMultiIni($v, $section);

                // skip if section and no value to correct merge langs
                if($section && empty($msg2)) {
                    continue;
                }
                                
                if(is_array($msg2)) {
                    $msg = $msg2 + $msg;
                } else {
                    $msg = $msg2;
                }
            }
        } else {
            $msg = GetMsgHelper::parseMultiIni($file, $section);
        }
        
        // echo '<pre>', print_r($file, 1), '</pre>';
        //echo "<pre>"; print_r($msg); echo "</pre>";
        return $msg;
    }
    
    
    // read common or module
    static function getMsg($file_name, $module = false, $section = false, $process_sections = 1, $parse_array = false) {
        
        $file = ($module) ? AppMsg::getModuleMsgFile($module, $file_name)
                          : AppMsg::getCommonMsgFile($file_name);
        
        return AppMsg::parseMsgs($file, $section, $process_sections, $parse_array);
    }
    
    
    // read common + module if any
    static function getMsgs($file_name, $module = false, $section = false, $process_sections = 1, $parse_array = false) {
    
        $file = AppMsg::getCommonMsgFile($file_name);
        $msg = AppMsg::parseMsgs($file, $section, $process_sections);
        
        if($module) {
            $file = AppMsg::getModuleMsgFile($module, $file_name);
            $msg = array_merge($msg, AppMsg::parseMsgs($file, $section, $process_sections, $parse_array));
        }
        
        return $msg;
    }


    // read common or module
    static function getMsgMutliIni($file_name, $module = false, $section = false) {
        
        $file = ($module) ? AppMsg::getModuleMsgFile($module, $file_name)
                          : AppMsg::getCommonMsgFile($file_name);
        
        return AppMsg::parseMsgsMultiIni($file, $section);
    }

    
    
    static function getMenuMsgs($section = false, $process_sections = 1) {
        return AppMsg::getMsg('menu_msg.ini', false, $section, $process_sections);        
    }
    
    
    static function getErrorMsgs($module = false, $section = false, $process_sections = 1) {
        return AppMsg::getMsgs('error_msg.ini', $module, $section, $process_sections);        
    }
    
    
    static function getAfterActionMsg($keyword, $module = false) {
        return AppMsg::getMsgs('after_action_msg.ini', $module, $keyword, 1);
    }
    
    
    // MSGS BOXEX // ---------------
    
    static function _licenseBox($file, $keyword, $vars = array()) {
        $file = AppMsg::getCommonMsgFile($file);
        $msg['body'] = AppMsg::parseMsgsMultiIni($file, $keyword);
        if(!empty($msg['body'])) {
            $msg['title'] = false;
            $vars['client_area_link'] = 'https://www.kbpublisher.com/client/';
            $vars['ca_link'] = $vars['client_area_link'];
            //$vars['application'] = 'KBPublisher';
            return BoxMsg::factory('error', $msg, $vars);
        }
    }    

    static function licenseBox($keyword, $vars = array()) {
        return AppMsg::_licenseBox('license_msg.ini', $keyword, $vars);
    }    

    static function pluginBox($keyword, $vars = array()) {
        return AppMsg::_licenseBox('export_setting/plugin_msg.ini', $keyword, $vars);
    }
        
    
    // this after wrong form submission
    static function &_gerErrorMessages(&$errors, $module = false) {
        
        $msgs = array();
        if(!$errors) { 
            return $msgs; 
        }
        
        foreach($errors as $type => $error) {
            $e = array();
            
            // formated error message
            if($type === 'formatted') {
                foreach($error as $k => $v) {
                    $e[] = $v['msg'];
                }
                
                $msgs['title'] = false;
                $msgs['body'] = &$e;                
            
            // title and body parsed msg, ready to use
            } elseif($type === 'parsed') {
                foreach($error as $k => $v) {
                    $e[] = $v['msg'];
                }              
                
                $msgs['title'] = $e[0]['title'];
                $msgs['body'] =  array($e[0]['body']);
                   
            // body is custom message    
            } elseif($type === 'custom') {
                $msg = AppMsg::getErrorMsgs($module);
                foreach($error as $k => $v) {
                    $e[] = $v['msg'];
                }                

                $msgs['title'] = ''; //$msg['error_title_msg'];
                $msgs['body'] = &$e;
            
            // default by key
            } else {
                $msg = AppMsg::getErrorMsgs($module);
                foreach($error as $k => $v) {
                    $e[] = $msg[$v['msg']];
                }
                
                $e = array_unique($e);
                
                $msgs['title'] = ''; //$msg['error_title_msg'];
                $msgs['body'] = &$e;
            }        
        }
        // echo '<pre>', print_r($msgs, 1), '</pre>';
        return $msgs;
    }


    // return body of the massage (array)
    static function &errorMessage(&$errors, $module = false) {
        $msgs = AppMsg::_gerErrorMessages($errors, $module);
        return $msgs;
    }    

    
    static function &errorMessageString(&$errors, $module = false) {
        $msgs = AppMsg::_gerErrorMessages($errors, $module);
        if($msgs) {
            $str = '%s - %s';
            $msg = sprintf($str, $msgs['title'], implode(', ', $msgs['body'])); 
            return $msg;
        }
        
        return $msgs;
    }


    // this after wrong form submission
    static function errorBox(&$errors, $module = false) {
        $msgs = AppMsg::_gerErrorMessages($errors, $module);
        if(!$msgs) {
            return;
        }        
        
        // formatted
        if($msgs['title'] === false) {
            return implode('<br />', $msgs['body']);
        
        // body is custom message and/or default (by key)
        } else {
            // $msgs['body'] = '<b>&raquo;</b> ' . implode('<br /><b>&raquo;</b> ', $msgs['body']);
            $first_sign = (count($msgs['body']) > 1) ? '- ' : '';
            $msgs['body'] = $first_sign . implode('<br />- ', $msgs['body']);
            $options = array('div_id' => 'errorBoxMessageDiv');
            return BoxMsg::factory('error', $msgs, array(), $options);
        }
    }    
    
    
    // mostly it will generate hint in admin area if specified
    static function hintBox($keyword, $module = false, $img = false) {
        $msg = array();
        $file = ($module) ? AppMsg::getModuleMsgFile($module, 'hint_msg.ini')
                          : AppMsg::getCommonMsgFile('hint_msg.ini');

        $msg['body'] = AppMsg::parseMsgsMultiIni($file, $keyword);
        if(!empty($msg['body'])) {
            return BoxMsg::factory('hint', $msg);
        }
    }
    

    // mostly it will generate hint in admin area if specified
    static function hintBoxCommon($keyword, $vars = array()) {
        $msg = array();
        $file = AppMsg::getCommonMsgFile('common_hint.ini');

        $msg['body'] = AppMsg::parseMsgsMultiIni($file, $keyword);
        if(!empty($msg['body'])) {
            return BoxMsg::factory('hint', $msg, $vars);
        }
    }
    

    static function serviceBox($keyword, $multi_ini = true) {
        $msg = array();
        $file = AppMsg::getCommonMsgFile('service_msg.ini');    

        $msg['body'] = AppMsg::parseMsgsMultiIni($file, $keyword);
        if(!empty($msg['body'])) {
            return BoxMsg::factory('hint', $msg);
        }
    }
    
    
    static function errorServiceBox($keyword, $factory = 'error', $module = false) {
        $msg = AppMsg::getMsgs('error_msg.ini', $module, $keyword, 1);
        if($msg) {
            return BoxMsg::factory($factory, $msg);
        }
    }
    
    
    static function afterActionBox($keyword, $factory = 'error', $module = false, $vars = array()) {
        $msg = AppMsg::getMsgs('after_action_msg.ini', $module, $keyword, 1);
        if($msg) {
            // $options = array('close_btn' => true);
            $options = array();
            return BoxMsg::factory($factory, $msg, $vars, $options);
        }
    }

    
    static function afterActionMsg($keyword, $module = false) {
        return  AppMsg::getMsgs('after_action_msg.ini', $module, $keyword, 1);
    }
    
    
    static function replaceParse($str, $vars) {
        return Replacer::doParse($str, $vars);
    }
    
    
    // add identical messages, call it where required
    static function addIdeticalMsgs ($msg) {
        if(isset($msg['entry_history_max'])) {
            $msg['file_history_max'] = $msg['entry_history_max'];
        }
    
        return $msg;
    }
}
?>