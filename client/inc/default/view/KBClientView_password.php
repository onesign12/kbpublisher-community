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


class KBClientView_password extends KBClientView_common
{
    
    var $page_modal = true;
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['reset_password_msg'];
        $this->nav_title = $this->msg['reset_password_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;
    }
    
    
    function getForm($manager) {
                
        $tpl = new tplTemplatez($this->getTemplate('password_form.html'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $tpl->tplAssign('password_hint', $this->msg['restore_password_msg']);
                
        if($this->useCaptcha($manager, 'password')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'password', 'placeholder'));
        }
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('action_link', $this->getLink('password'));
        $tpl->tplAssign('cancel_link', $this->getLink('login'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        
        
        $required = array('email');
        
        $v = new Validator($values, false);
        
        $v->csrfCookie();
        $v->required('required_msg', $required);
        $v->regex('email_msg', 'email', 'email');
        
        if($error = $this->validateCaptcha($manager, $values, 'password')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
        }

        return $v->getErrors();
    }
    
}
?>