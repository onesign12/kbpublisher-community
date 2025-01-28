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


class KBClientView_sso_incomplete extends KBClientView_common
{
    
    var $page_modal = true;
    
    
    function &execute(&$manager) {
        
        $this->addMsg('common_msg.ini');
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['fill_account_title_msg'];
        $this->nav_title = $this->msg['fill_account_title_msg'];
        
        $data = $this->getForm($manager);
    
        return $data;
    }


    function getForm($manager) {
                
        $tpl = new tplTemplatez($this->getTemplate('sso_incomplete_form.html'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $hint = str_replace('{provider}', $this->remote_provider, $this->msg['fill_account_hint_msg']);
        $tpl->tplAssign('hint_msg', $hint);
        
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


    function validate($values, $manager_2) {
         
        $required = array('email');

        $v = new Validator($values, false);
        $v->required('required_msg', $required);
        $v->regex('email_msg', 'email', 'email');
        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        if ($manager_2->isEmailExists($values['email'])) {
            $v->setError('email_exists_msg', 'email');
        }
        
        return $v->getErrors();
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $manager_2 = new UserModel;
        
        $ret['options'] = array($values, $manager_2);
        return $ret;
    }
}
?>