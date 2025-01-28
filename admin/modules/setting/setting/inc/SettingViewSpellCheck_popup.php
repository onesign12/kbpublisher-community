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




class SettingViewSpellCheck_popup extends SettingView_form
{
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'public_setting');
        
        
        $tpl = new tplTemplatez($this->template_dir . 'form_spell_check.html');
        
        $source = $_GET['source']; // validated in PageController
        $method = 'getForm' . ucwords($source);
        if (method_exists($this, $method)) {
            $this->$method($obj, $tpl);
        }
        
        $checked = '';
        if ($obj->get('search_spell_suggest') == $source) {
            $checked = 'checked';
        }
        $tpl->tplAssign('checked', $checked);
        
        $tpl->tplAssign('popup_title', $this->msg[$source . '_title_msg']);  
        $tpl->tplAssign('custom_words', $obj->get('search_spell_custom'));
        
        $vars = $this->setCommonFormVars($obj);
        $tpl->tplAssign($vars);
        $tpl->tplParse($this->msg);
        
        return $tpl->tplPrint(1);
    }
    
    
    function getFormPspell($obj, &$tpl) {
        
        $show_form = true;
        $ret = PublicSetting\SettingValidator::validatePspell($obj->get());
            
        if(is_array($ret)) {
            $msg = 'Pspell - ' . $ret['code_message'];
            $msg_vars = array('body' => $msg);
            $tpl->tplAssign('error_msg', BoxMsg::factory('error', $msg_vars));
            $show_form = ($ret['code']) ? true : false;
        }
        
        if ($show_form) {
            $tpl->tplSetNeededGlobal('form');
            $tpl->tplSetNeeded('/pspell');
            
            $select = new FormSelect();
            $select->setSelectWidth(250);
            
            $select->setSelectName('dictionary');
            
            exec('aspell dump dicts', $list);
            
            $range = array();
            foreach ($list as $v) {
                $range[$v] = $v;
            }
            
            $select->setRange($range);
            $tpl->tplAssign('dictionary_select', $select->select($obj->get('search_spell_pspell_dic')));
        }
    }
    
    
    function getFormBing($obj, &$tpl) {
        $tpl->tplSetNeededGlobal('form');
        $tpl->tplSetNeeded('/bing');
        $tpl->tplAssign($obj->get());
    }
    
    
    function getFormEnchant($obj, &$tpl) {
        
        $show_form = true;
        $ret = PublicSetting\SettingValidator::validateEnchant($obj->get());
        
        if(is_array($ret)) {
            $msg = 'Enchant - ' . $ret['code_message'];
            $msg_vars = array('body' => $msg);
            
            $box_type = ($ret['code']) ? 'hint' : 'error';
            $tpl->tplAssign('error_msg', BoxMsg::factory($box_type, $msg_vars));
            
            $show_form = ($ret['code']) ? true : false;
        }
        
        if ($show_form) {
            $tpl->tplSetNeededGlobal('form');
            $tpl->tplSetNeeded('/enchant');
            
            $r = enchant_broker_init();
            $providers = ($p = enchant_broker_describe($r)) ? $p : array();
            $dictionaries = ($d = enchant_broker_list_dicts($r)) ? $d : array();
            
            $selected_provider = $obj->get('search_spell_enchant_provider');
            $selected_dictionary = $obj->get('search_spell_enchant_dic');

            $select = new FormSelect();
            $select->setSelectWidth(250);
            $select->select_tag = false;
            
            $dictionary_range = array();
            foreach ($dictionaries as $dictionary) {
                $dictionary_range[$dictionary['provider_name']][] = $dictionary['lang_tag'];
            }
            
            // testing
            //$dictionary_range['myspell'] = array('ru');
            
            if (!empty($dictionary_range['myspell'])) {
                $reg = &Registry::instance();
                $conf = &$reg->getEntry('conf');
                $lang = $conf['lang']['meta_content'];
                
                if (!in_array($lang, $dictionary_range['myspell'])) {
                    $lang_dictionary = enchant_broker_dict_exists($r, $lang);
                    if ($lang_dictionary) {
                        $dictionary_range['myspell'][] = $lang;
                    }
                }
            }
            
            $range = array();
            foreach ($providers as $provider) {
                $key = $provider['name'];
                if (!empty($dictionary_range[$key])) {
                    $range[$key] = $key;
                }
            }
            
            $select->setSelectName('provider');
            
            $select->setRange($range);
            $tpl->tplAssign('provider_select', $select->select($selected_provider));
            
            
            $json = array();
            foreach($dictionary_range as $provider => $dictionaries) {                
                sort($dictionaries);
                
                $json_body = array();
                foreach($dictionaries as $v) {
                    $val = $v;
                    $s = (($v == $selected_dictionary) && ($provider == $selected_provider)) ? 'true' : 'false';
                    
                    $json_body[] = sprintf('{"val": "%s", "s": %s}', $val, $s);
                }
                
                $json[] = sprintf("\"%s\": [\n%s\n]", $provider, implode(",\n", $json_body));
            }
            
            $json = implode(",\n", $json);
            $tpl->tplAssign('myOptionsJson', $json);
            
        }
        
        
        $tpl->tplAssign($obj->get());
    }
}
?>