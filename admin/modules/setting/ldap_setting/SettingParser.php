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

namespace LdapSetting;

use SettingParserCommon;
use BaseModel;
use tplTemplatez;


class SettingParser extends SettingParserCommon
{
    
    function parseIn($key, $value, &$values = array()) {
            
        if($key == 'ldap_connect_password') {
            $value = \EncryptedPassword::encode($value);
        }
        
        return $value;
    }
    
    
    function parseOut($key, $value) {
        
        if($key == 'ldap_connect_password') {
            $value = \EncryptedPassword::decode($value);           
        }
        
        return $value;
    }
    
    
    function parseExtraOut($key, $value, $manager, $view, $id) {
        $extra['text'] = '';
        
        // group mapping
        $group_mapping_keys = array(
            'remote_auth_map_group_to_priv' => array(
                'custom_setting' => 'remote_auth_map_priv_id'
            ),
            'remote_auth_map_group_to_role' => array(
                'custom_setting' => 'remote_auth_map_role_id'
            )
        );
                                         
        if (isset($group_mapping_keys[$key])) {
            $rules_count = 0;
            if ($value) {
                $rules_count += count(explode("\n", trim($value)));
            }
            
            $custom_setting_key = @$group_mapping_keys[$key]['custom_setting'];
            if ($custom_setting_key) {
                $custom_setting = $manager->getSettings('160', $custom_setting_key);
                if ($custom_setting) {
                    $rules_count += count(explode("\n", trim($custom_setting)));
                }
            }
            
            $extra['text'] = sprintf('%s: <span id="%s_count">%s</span>', $view->msg['rules_added_msg'], $id, $rules_count);                        
        }
        
        return $extra;
    }
    
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        if($key == 'remote_auth' && $this->isAuthRemoteDisabled()) {
            $ret = ' disabled';
        }
        
        return $ret;
    }
    
    
    function parseSubmit($template_dir, $msg, $options = array()) {
        
        $tpl = new tplTemplatez($template_dir . 'form_submit_auth.html');
        $tpl->tplSetNeededGlobal('ldap');
        
        $tpl->tplAssign('type', 'ldap');
        $tpl->tplAssign('params', 'false');
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }


    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(6 => '3.1', 7 => '3.2');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    // when display
    function skipSettingDisplay($key, $value = false) {
        $ret = false;
        
        if($key == 'remote_auth_map_priv_id' || $key == 'remote_auth_map_role_id') {
            $ret = true;
        }
                
        return $ret;
    }
    
    
    // parse description
    function parseDescription($key, $value) {
        if($key == 'remote_auth' && BaseModel::isCloud()) {
            $value = '';
        }
    
        return $value;
    }
}
?>