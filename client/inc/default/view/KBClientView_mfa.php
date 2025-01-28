<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+
    

class KBClientView_mfa extends KBClientView_common
{
    
    var $page_modal = true;


    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['mfa_msg'];
        $this->nav_title = $this->msg['mfa_msg'];
        
        if($this->action == 'setup') {
            $data = $this->getSetupForm($manager);
        } else {
            $data = $this->getConfirmForm($manager);            
        }
        
        return $data;
    }


    function getConfirmForm($manager) {
    
        $tpl = new tplTemplatez($this->template_dir . 'mfa_confirm_form.html');
    
        if($this->getErrors()) {
            $tpl->tplAssign('error_msg', $this->getErrors());
        } else {
            $key = sprintf('mfa_note_%s_msg', $this->mfa_type);
            $msg['body'] = AppMsg::getMsgs('user_msg.ini')[$key];
            $tpl->tplAssign('note', BoxMsg::factory('hint', $msg));
        }
                
        if(!$this->controller->admin_login) {
            $tpl->tplAssign('action_link', $this->getLink('all'));
        }
    
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getSetupForm($manager) {
    
        $tpl = new tplTemplatez($this->template_dir . 'mfa_setup_form.html');
    
        if($this->getErrors()) {
            $tpl->tplAssign('error_msg', $this->getErrors());
        } else {
            $key = 'mfa_required_msg';
            $msg['body'] = AppMsg::getMsgs('user_msg.ini')[$key];
            $tpl->tplAssign('note', BoxMsg::factory('hint', $msg));
        }
        
        $sess = MfaAuthenticator::getSession();
        $user = $manager->getUserInfo(intval($sess['user_id']));
        
        $mfa = MfaAuthenticator::factory('app');
        $mfa_data = $mfa->getSetupVars($user['email']);

        $tpl->tplAssign('qr_secret_src', $mfa_data['qrcode']);
        $tpl->tplAssign('qr_secret_letter', chunk_split($mfa_data['secret'], 4, ' '));
        $tpl->tplAssign('secret', $mfa_data['secret']);
        $tpl->tplAssign('type', $mfa_data['mfa_type_str']);
        
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msg['body'] = AppMsg::parseMsgsMultiIni($file, 'mfa_scratch_code');
        $tpl->tplAssign('scratch_desc', BoxMsg::factory('info', $msg));
        $tpl->tplAssign('scratch_code', MfaAuthenticator::generateScratchCode()['code']);
        
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        if(!$this->controller->admin_login) {
            $tpl->tplAssign('action_link', $this->getLink('all'));
        }
        
        $tpl->tplAssign('cancel_link', $this->getLink());
    
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values) { // for ajax
        $errors = $this->validateInput($values);
        if(!$errors) {
            $errors = $this->validateMfa($values);            
        }
    
        return $errors;
    }
    
    
    function validateInput($values) {

        $v = new Validator($values, false);
        
        $v->csrfCookie();
        $v->required('required_msg', array('code'));

        return $v->getErrors();
    }


    function validateMfa($values) {
        
        $v = new Validator($values, false);
        
        $mfa = MfaAuthenticator::factory('app');
        $result = $mfa->validate(trim($values['code']), $values['secret']);
        if($result !== true) {
            $v->setError('confirm_text_msg', 'code');
        }
        
        return $v->getErrors();
    }

    
    function validateMfaScratch($values) {
        
        $v = new Validator($values, false);
        
        $result = HashPassword::validate(trim($values['code']), $values['scratch']);
        if(!$result) {
            $v->setError('confirm_text_msg', 'code');
        }
        
        return $v->getErrors();
    }

}
?>