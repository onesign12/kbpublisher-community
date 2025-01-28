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

namespace EmailSetting;

use SettingParserCommon;
use BaseModel;
use tplTemplatez;


class SettingParser extends SettingParserCommon
{

    function parseIn($key, $value, &$values = array()) {
            
        if($key == 'admin_email' && empty($value)) {
            $value = $this->manager->getSettings('134', 'from_email');
            
        } elseif($key == 'smtp_pass') {
            $value = \EncryptedPassword::encode($value);            
        }
        
        return $value;
    }
    
    
    function parseOut($key, $value) {
        
        if($key == 'smtp_pass') {
            $value = \EncryptedPassword::decode($value);
        }
        
        return $value;
    }
    
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        if($key == 'mailer') {
        
            $options = array();
            $options['mail'] = $values['option_1'];
        
            // remove Sendmail in cloud
            if(!BaseModel::isCloud()) {
                $options['smtp'] = $values['option_2'];
                $options['sendmail'] = $values['option_3'];
            }
            
            $values = $options;
        }
    
        return $values;
    }
    
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        // readonly in cloud
        if($key == 'noreply_email' && BaseModel::isCloud()) {
            $ret = ' readonly';
        }
        
        return $ret;
    }
    
    
    function parseSubmit($template_dir, $msg, $options = array()) {
        $tpl = new tplTemplatez($template_dir . 'form_submit_email.html');
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    // any special rule to parse form field
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {
        $setting_keys = array('mailer');
        if(in_array($setting_key, $setting_keys)) {
            if($val == 'check') {
                return true;
            }
            
            $js = '<script>
                $(document).ready(function() {
                    toggleSMTPSettings($(\'#mailer\').val());
                });
                
                function toggleSMTPSettings(value) {
                    var hash = window.location.hash.substr(1);
                    var search_for_hidden_setting = (hash.indexOf(\'anchor_smtp\') == 0) || (hash.indexOf(\'anchor_sendmail\') == 0);
                    
                    if (value == \'smtp\') {
                        $(\'div[id^=template_smtp_]\').show();
                        $(\'div[id^=template_smtp_]\').removeClass(\'auto_hidden\');
                                            
                    } else if (!search_for_hidden_setting) {
                        $(\'div[id^=template_smtp_]\').hide();
                        $(\'div[id^=template_smtp_]\').addClass(\'auto_hidden\');
                    }
                    
                    if(value == \'sendmail\') {
                        $(\'div[id^=template_sendmail_path]\').show();
                        $(\'div[id^=template_sendmail_path]\').removeClass(\'auto_hidden\');
                        
                    } else if (!search_for_hidden_setting) {
                        $(\'div[id^=template_sendmail_path]\').hide();
                        $(\'div[id^=template_sendmail_path]\').addClass(\'auto_hidden\');    
                    }
                }
            </script>';
            
            return $field . $js;
        }

        return false;
    }
}
?>