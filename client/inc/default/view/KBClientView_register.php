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


class KBClientView_register extends KBClientView_common
{
    
    var $update = false;
    var $page_modal = true;
    

    function &execute(&$manager) {
        
        $this->meta_title = $this->msg['create_account_msg'];
        $this->nav_title = $this->msg['create_account_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;        
    }
    

    function getForm($manager) {

        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->getTemplate('register_form.html'));
        
        if($this->update) {
            @$val = ($_POST) ? $_POST['remember'] : AuthPriv::getCookie();
            $val = ($val) ? 'checked' : '';
            $tpl->tplAssign('remember_option', $val);
        } else {
            $tpl->tplSetNeededGlobal('register');
        }
        
        $tpl->tplAssign('error_msg', $this->getErrors());
        
        $tpl->tplAssign('id', $manager->user_id);
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink('login'));        
        
        if($this->useCaptcha($manager, 'register')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'register', 'line'));
        }
        
        if(!$manager->getSetting('username_force_email')) {
            $tpl->tplSetNeeded('/username');
        }
        
        // subscription
        if($subs = $manager->getSetting('allow_subscribe_news')) {    
            // if subs for user with priv and assign priv on registering
            //if($subs == 3 && !$manager->getSetting('register_user_priv')) {
            //    $subs = false;
            //}
        }
        
        if($subs) {
            $subscribe = ($_POST) ? @$_POST['subsc_news'] : 1;
            $tpl->tplAssign('ch_subsc_news', $this->getChecked($subscribe));
            $tpl->tplSetNeeded('/subscription');            
        }
        
        // agree terms
        if($agree_items = $this->getAgreeTermsSettings($manager)) {
            foreach($agree_items as $k => $v) {
                
                $agree = ($_POST) ? @$_POST['agree_terms_' . $k] : 0;
                $v['agree_terms_ch'] = $this->getChecked($agree);
                $v['num'] = $k;
                    
                $tpl->tplParse($v, 'agree_terms_row');
            }
        }
        
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
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getAgreeTermsSettings($manager) {
        
        $agree_items = array();
        
        if($agree_items = $manager->getSetting('register_agree_terms')) {
            $agree_items = explode('||', $agree_items);
            
            $a = array();
            $i = 1;
            foreach($agree_items as $item) {
                $item = explode('|', $item);
                $a[$i]['text'] = $item[0];
                $a[$i]['error'] = $item[1];
                ++$i;
            }
            
            $agree_items = $a;
        }
        
        return $agree_items;
    }
    
    
    function validate($values, $manager, $manager_2) {
        
        $obj = new User;
        $obj->validate($values, $manager_2, array(), false); // false for crsf to use cookies
        if($obj->errors) {
            return $obj->errors;
        }
        
        $v = new Validator($values, false);
        
        if(!empty($values['agree_terms_validate'])) {
            if($agree_items = $this->getAgreeTermsSettings($manager)) {
                foreach($agree_items as $key => $val) {
                    $fid = 'agree_terms_' . $key;                    
                    if(empty($values[$fid])) {
                        $fid_label = 'agree_terms_label_' . $key;
                        $v->setError($val['error'], $fid_label, $fid_label, 'custom');
                    }
                }
            }
        }
        
        if($error = $this->validateCaptcha($manager, $values, 'register')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
        }
        
        return $v->getErrors();
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $manager_2 = new UserModel;
        $manager_2->use_priv = true;
        $manager_2->use_role = true;
        $manager_2->use_old_pass = false;
        
        $ret['options'] = array($values, 'manager', $manager_2);
        return $ret;
    }
}
?>