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


        
class KBClientView_password_reset extends KBClientView_common
{
    
    var $page_modal = true;
    var $code_confirmed = false;
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['reset_password_msg'];
        $this->nav_title = $this->msg['reset_password_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;
    }
    
    
    function getForm($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('password_reset_form.html'));
        
        // generate
        $view = new UserView_form;
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/';
        $tpl->tplAssign('generate_pass_block', $view->getGeneratePasswordBlock());
        
        $ajax = &$this->getAjax('entry');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('generatePassword', $view, 'ajaxGeneratePassword'));
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $tpl->tplAssign('password_hint', $this->msg['enter_password_msg']);
        
        if($this->useCaptcha($manager, 'password')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'password', 'line'));
        }
        
        if(!isset($_POST['reset_code'])) {
            $code = $this->stripVars($_GET['rc'], array(), 'asdasdasda');
            $tpl->tplAssign('reset_code', $code);
        }        
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink('login'));                                                              
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        $required = array('password', 'password_2');
                
        $v = new Validator($values, false);

        $v->csrfCookie();
        $v->required('required_msg', $required);
        
        if(PasswordUtil::isWeakPassword($values['password'])) {
            $emsg = PasswordUtil::getWeakPasswordError();
            $v->setError($emsg, 'password', 'password', 'custom');
        }
        
        if(PasswordUtil::isNotAllowedCharacters($values['password'])) { // disabled
            $emsg = PasswordUtil::getNotAllowedCharactersPasswordError();
            $v->setError($emsg, 'password', 'password', 'custom');
        }

        $v->compare('pass_diff_msg', 'password', 'password_2');
        
        if($error = $this->validateCaptcha($manager, $values, 'password')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
        }
        
        return $v->getErrors();        
    }    
}
?>