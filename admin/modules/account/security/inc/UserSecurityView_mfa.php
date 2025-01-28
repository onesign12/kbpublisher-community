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


class UserSecurityView_mfa extends AppView
{
    
    var $tmpl = 'form_mfa.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');
        }
        
        $mfa = MfaAuthenticator::factory('app');
        $mfa_data = $mfa->getSetupVars($obj->get('email'));
        
        $tpl->tplAssign('qr_secret_src', $mfa_data['qrcode']);
        $tpl->tplAssign('qr_secret_letter', chunk_split($mfa_data['secret'], 4, ' '));
        $tpl->tplAssign('secret', $mfa_data['secret']);
        $tpl->tplAssign('type', $mfa_data['mfa_type_str']);

        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msg['body'] = AppMsg::parseMsgsMultiIni($file, 'mfa_scratch_code');
        $tpl->tplAssign('scratch_desc', BoxMsg::factory('info', $msg));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormMfa'));
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('action_title', $this->msg['mfa_enable_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormMfa($values, $options = array()) {
        $options['func'] = 'getValidateMfa';
        return $this->ajaxValidateForm($values, $options);
    }
}
?>