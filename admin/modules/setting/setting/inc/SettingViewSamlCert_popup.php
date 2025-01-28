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


class SettingViewSamlCert_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'saml_setting');
        
        $tpl = new tplTemplatez($this->template_dir . 'form_saml_cert.html');
        
        $setting_key = htmlspecialchars($_GET['popup']);
        $tpl->tplAssign('setting_key', $setting_key);
        
        $popup_title = ($setting_key == 'saml_sp_private_key') ? $this->msg['saml_private_key_msg'] : $this->msg['saml_cert_msg'];
        $tpl->tplAssign('popup_title', $popup_title);
        
        $tpl->tplAssign('hint_msg', AppMsg::hintBox($setting_key, 'saml_setting'));
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $setting_id = $manager->getSettingIdByKey($_GET['popup']);
        $tpl->tplAssign('setting_id', $setting_id);
        
        
        if ($obj->get($setting_key) && $setting_key != 'saml_sp_private_key') { // cert info
            $tpl->tplSetNeeded('/current_cert');
            
            //$cert = (function_exists('openssl_x509_parse')) ? openssl_x509_parse($obj->get($setting_key)) : false;
            $cert = openssl_x509_parse($obj->get($setting_key));            

            if ($cert) {
                $str = '%s: <b>%s</b>';
                $cert_info = array();
                $cert_info[] = sprintf($str, $this->msg['common_name_msg'], $cert['subject']['CN']);
                $cert_info[] = sprintf($str, $this->msg['valid_from_msg'], $this->getFormatedDate($cert['validFrom_time_t']));
                $cert_info[] = sprintf($str, $this->msg['valid_to_msg'], $this->getFormatedDate($cert['validTo_time_t']));
                
                $tpl->tplAssign('cert_info', implode('<br />', $cert_info));
                
            } else {
                $tpl->tplAssign('cert_info', $this->msg['bad_cert_desc_msg']);
            }
        }
        
        $tpl->tplAssign('value', $obj->get($setting_key));
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');
            
            if ($setting_key == 'saml_sp_private_key') {
                $tpl->tplSetNeeded('/set_status');
                
                $status = ($obj->get($setting_key)) ? 'true' : 'false';
                $tpl->tplAssign('status', $status);
                
            } else {
                $tpl->tplSetNeeded('/set_cn');
                
                $cn = (!empty($cert['subject']['CN'])) ? $cert['subject']['CN'] : '';
                $tpl->tplAssign('cn', $cn);
            }
        }
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplParse($this->msg);
        
        return $tpl->tplPrint(1);
    }
}
?>