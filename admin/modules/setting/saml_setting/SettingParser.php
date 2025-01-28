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

namespace SamlSetting;

use SettingParserCommon;
use tplTemplatez;
use AppMsg;


class SettingParser extends SettingParserCommon
{   
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        if($key == 'saml_auth' && $this->isAuthRemoteDisabled()) {
            $ret = ' disabled';
        }
        
        return $ret;
    }
	
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(5=>'2.1', 3=>'4.1');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    function parseSubmit($template_dir, $msg, $options = array()) {
        
        $tpl = new tplTemplatez($template_dir . 'form_submit_auth.html');
        
        $tpl->tplAssign('type', 'saml');
        $tpl->tplAssign('params', 'true, true');
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    function parseExtraOut($key, $value, $manager, $view, $id) {
        $extra['text'] = '';
        
        // group mapping
        $group_mapping_keys = array(
            'saml_map_group_to_priv',
            'saml_map_group_to_role'
        );
                                         
        if (in_array($key, $group_mapping_keys)) {
            $rules_count = 0;
            if ($value) {
                $rules_count += count(explode("\n", trim($value)));
            }
            
            $extra['text'] = sprintf('%s: <span id="%s_count">%s</span>', $view->msg['rules_added_msg'], $id, $rules_count);                        
        }
        
        
        // saml certificates
        $cert_keys = array(
            'saml_idp_certificate',
            'saml_sp_certificate'
        );
        
        if (in_array($key, $cert_keys)) {
            if ($value) {
                $cert = openssl_x509_parse($value);
                if ($cert) {
                    $extra['text'] = 'CN=' . $cert['subject']['CN'];
                }
                
            } else {
                //$extra['text'] = '--';
            }
        }
        
        return $extra;
    }
     
    
    function parseDescription($key, $value) {
        if($key == 'saml_auth_context') {
            $text = '<br/>urn:oasis:names:tc:SAML:2.0:ac:classes:Password <br/>urn:oasis:names:tc:SAML:2.0:ac:classes:X509<br/>';
            $value = str_replace('{ac_example_value}', $text, $value);

        } elseif($key == 'saml_mfa') {
            $msg = AppMsg::getMsgs('setting_msg.ini', 'admin_setting', 'mfa_policy');
            $value = $msg['descr'];
        }
        
        return $value;
    }
    
}
?>