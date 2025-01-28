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
 
 
class SettingParserCommon
{
    
    var $replacements = array();
    var $model;
    var $manager;
    
    
    // function __construct($model) {
    function __construct() {
        
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $pattern[] = '#\\\\#';
        $pattern[] = '#/{2,}#';
            
        $document_root = preg_replace($pattern, '/', $document_root);
        $document_root = preg_replace('#/$#', '', $document_root);
        $this->setReplacement('document_root', $document_root);
        
        $document_root_parent = preg_replace('#/\w+$#', '', $document_root);
        $this->setReplacement('document_root_parent', $document_root_parent);
        
        $cache_dir = preg_replace($pattern, '/', APP_CACHE_DIR);
        $cache_dir = preg_replace('#/$#', '', $cache_dir);
        $this->setReplacement('cache_dir', $cache_dir);
        
        $noreply_email = 'noreply@'.preg_replace(array('#^www\.#', '#:\d+#'), '', strval(@$_SERVER['HTTP_HOST']));
        $this->setReplacement('noreply_email', $noreply_email);
    }
    
    function setReplacement($key, $value) {
        $key = '[' . $key . ']';
        $this->replacements[$key] = $value;
    }
    
    function parseReplacements($value) {
        return str_replace(array_keys($this->replacements), $this->replacements, $value);
    }
    
    function parseReplacementsArray($values) {
        foreach($values as $k => $v) {
            $values[$k] = $this->parseReplacements($v);
        }
        
        return $values;
    }
    
    // used in getSettings for some special keys 
    function parseSettingOut($key, $value) {
        if($key == 'sphinx_lang') { // fselect with search option
            $value = explode(',', $value);
        }
    
        return $value;
    }
    
    function getSettingMsg($module_name) {
        $msg = AppMsg::getMsg('setting_msg.ini', $module_name, 0, 1, 1);
        return AppMsg::addIdeticalMsgs($msg);
    }
    
    // when inserted, not for default 
    function parseIn($key, $value, &$values = array()) {
        return $value;
    }
    
    function parseInArray($values) {
        foreach($values as $key => $value) {
            $values[$key] = $this->parseIn($key, $value, $values);
        }
        
        return $values;
    }
    
    // when out
    function parseOut($key, $value) {
        return $value;
    }
    
    function parseExtraOut($key, $value, $manager, $view, $id) {
        $extra['text'] = '';
        return $extra;
    }
    
    // when display we want to skip some values
    function skipSettingDisplay($key, $value = false) {
        return false;
    }
    
    // options parse
    function parseSelectOptions($key, $values) {
        return $values;
    }

    // input options
    function parseInputOptions($key, $value) {
        return false;
    }

    // parse description
    function parseDescription($key, $value) {
        return $value;
    }
    
    // parse title
    function parseTitle($key, $value) {
        return $value;
    }
    
    // to replace msg if needed, to keep old setting_key in code
    function parseMsgKey($key) {
        return $key;
    }
    
    // here we can rearange resort group_id
    function parseGroupId($group_id) {
        return $group_id;
    }
    
    // any special rule to parse form field
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {
        return false;
    }
    
    //parse submit section of form
    function parseSubmit($template_dir, $msg, $options = array()) {
        
        $tmpl = 'form_submit_default.html';
        if(isset($options['submit_tmpl'])) {
            $tmpl = $options['submit_tmpl'];
        }
        
        $tpl = new tplTemplatez($template_dir . $tmpl);
        
        $default_btn = (isset($options['default_btn'])) ? $options['default_btn'] : true;        
        if(empty($_GET['sid']) && $default_btn) {
            $tpl->tplSetNeeded('/default');
        }
        
        if(!empty($_GET['popup'])) {
            $tpl->tplSetNeeded('/cancel');
        }
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    function getCustomFormHeader($obj) {
        return '';
    }
    
    function parseDirectoryValue($value) {
        // $value = $value . '/';
        // $pattern = array('#\\\\#', '#/{2,}#');
        // $value = preg_replace($pattern, '/', $value);
        
        $value = preg_replace("#[/\\\]+$#", '', trim($value)); // remove trailing slash
        $value = str_replace('\\', '/', $value) . '/';
                        
        return $value;
    }
    
    
    // CLOUD // -------------------
    
    // when display
    function skipSettingDisplayCommon($key, $value = false) {
        $ret = false;

        $hide_settings = AppPlugin::getPluginHideSettings();
        if(BaseModel::isCloud()) {
            $hide_settings += BaseModel::getCloudHideSettings();
        }
        
        if(in_array($key, $hide_settings)) {
            $ret = true;
        }
        
        return $ret;
    }


    // remove some options from values not to replace values
    // so not allowed values could not be submited in POST
    function parseInArrayCloud($values) {
        
        if(BaseModel::isCloud()) {
            $keys = BaseModel::getCloudHideSettings();
            $keys = array_merge($keys, BaseModel::getCloudPluginKeys());
            
            foreach($keys as $key) {
                if(isset($values[$key])) {
                    unset($values[$key]);
                }
            }
        }
        
        return $values;
    }

    
    function isAuthRemoteDisabled() {
        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');
        return ($conf['auth_remote']) ? false : true;
    }
    
    
    function getDefaultCategorySelectRange($values, $type, $user_setting = false) {
        
        if($type == 'article') {        
            $emanager = new KBEntryModel();
        } else {
            $emanager = new FileEntryModel();
        }
        
        $options = array();
        $options['none'] = $values['option_1'];
        
        $cats = ($user_setting) ? $emanager->getCategoryRecordsUser() : $emanager->getCategoryRecords();
        
        foreach ($cats as $id => $v) {
            if ($v['parent_id'] == 0) {
                $options[$id] = $v['name'];
            }   
        }
        
        return $options;
    }
}
?>