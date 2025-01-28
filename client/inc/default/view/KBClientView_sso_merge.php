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


class KBClientView_sso_merge extends KBClientView_common
{
    
    var $page_modal = true;
    
    
    function &execute(&$manager) {
        
        $this->addMsg('common_msg.ini');
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['merge_account_title_msg'];
        $this->nav_title = $this->msg['merge_account_title_msg'];
        
        $data = $this->getForm($manager);
    
        return $data;
    }


    function getForm($manager) {
                
        $tpl = new tplTemplatez($this->getTemplate('sso_merge_form.html'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors());
        }
        
        $hint = str_replace('{provider}', $this->remote_provider, $this->msg['merge_account_hint_msg']);
        $tpl->tplAssign('hint_msg', $hint);
                
        if($this->useCaptcha($manager, 'auth')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'auth', 'placeholder'));
        }

        $forgot_password = false;
        $forgot_password_link = $this->getLink('password');
        if($forgot_password) {
            $tpl->tplAssign('forgot_password_link', $forgot_password_link);
            $tpl->tplSetNeeded('/forgot_password');            
        }
        
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink('login'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function validate($values, $manager) {
        
        $required = array('password');

        $v = new Validator($values, false);
        $v->required('required_msg', $required);
        
        if($error = $this->validateCaptcha($manager, $values, 'auth')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
        }
        
        return $v->getErrors();
    }
}
?>