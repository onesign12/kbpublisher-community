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


class UserSecurityView_list extends AppView
{
    
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        // mfa
        $rule_id = $manager->extra_rules['mfa'];
        $mfa_data = $obj->getExtraValues($rule_id);
        $mfa_active = (!empty($mfa_data['mfa_active']));
        // $mfa_exists = (!empty($mfa_data['mfa_secret']));
        $mfa_setting = MfaAuthenticator::getMfaSetting();
        $priv_id = AuthPriv::getPrivId();
        
        $tpl->tplAssign('mfa_disabled_num', 0);
        if(MfaAuthenticator::isPermamentDisabled()){
            $tpl->tplAssign('mfa_disabled', 'buttonDisabled');
            $tpl->tplAssign('mfa_disabled_num', 1);
            $mfa_active = false;
        }
            
        $blocks = [];
        if($mfa_setting == 1 || ($mfa_setting == 2 && !$priv_id)) { // allowed
            $blocks = ($mfa_active) ? ['mfa_disable', 'mfa_reset'] : ['mfa_enable'];
        
        } elseif($mfa_setting == 3 || ($mfa_setting == 2 && $priv_id)) { // required
            $blocks = ($mfa_active) ? ['mfa_reset'] : ['mfa_enable'];
            if($mfa_active) {// primary for reset
                $tpl->tplAssign('mfa_disabled', 'primary');
            }
        }
        
        foreach($blocks as $block) {
            $tpl->tplSetNeeded('/' . $block);
        }
        
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateDisableMfa'));
        
        // $tpl->tplAssign('mfa_delete_action', );
        $tpl->tplAssign('atoken', Auth::getCsrfToken());
        $tpl->tplAssign('mfa_link', $this->getActionLink('mfa_enable'));
        $tpl->tplAssign('mfa_off_link', $this->getActionLink('mfa_disable'));
        $tpl->tplAssign('mfa_color', $mfa_active ? 'green' : 'red');
        
        $st_msg = ($mfa_active) ? 'enabled_msg' : 'disabled_msg';
        $tpl->tplAssign('mfa_status', $this->msg[$st_msg]);
        
        // account view, action buttons
        $password_link = $this->getActionLink('password');
        
        // password
        if(!$manager->account_updateable) {
            $tpl->tplAssign('password_disabled', 'buttonDisabled');
            $password_link = '';
        }
        
        // $tpl->tplAssign('password_hint', htmlentities(PasswordUtil::getWeakPasswordError()));
        $tpl->tplAssign('password_link', $password_link);
        
        // delete account
        $tpl->tplSetNeeded('/account_delete');
        $tpl->tplAssign('delete_link', $this->getActionLink('delete'));
               
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    } 


    function ajaxValidateDisableMfa($values, $options = array()) {
        $options['func'] = 'getValidateDisableMfa';
        $objResponse = $this->ajaxValidateForm($values, $options);

        return $objResponse;
    }
}
?>