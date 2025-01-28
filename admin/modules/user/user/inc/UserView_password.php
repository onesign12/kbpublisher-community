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


class UserView_password extends AppView
{
    
    var $tmpl = 'form_password.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        if ($manager->use_old_pass) {
            $this->msg['password_msg'] = $this->msg['password_new_msg'];
            $tpl->tplSetNeeded('/old_password'); 
        }

        $vars = $this->setCommonFormVars($obj);
        
        if (!$this->account_view) {
            
            $vars['cancel_link'] = $this->getActionLink('detail', $obj->get('id'));
            
            @$val = ($_POST) ? $_POST['notify'] : 1;
            $tpl->tplAssign('notify_ch', $this->getChecked($val));
            $tpl->tplAssign('menu_block', UserView_common::getEntryMenu($obj, $manager, $this));
            $tpl->tplSetNeededGlobal('not_account');
            $tpl->tplSetNeeded('/cancel_btn');
        
        } else {
            
            if (AuthPriv::getPassExpired()) {
                $reg =& Registry::instance();
                $setting  = &$reg->getEntry('setting');
                $tpl->tplAssign('hint', $this->getChangePasswordHint($this->msg, $setting));
            } else {
                $tpl->tplSetNeeded('/cancel_btn');
            }
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        if($this->account_view) {
            $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        }
        
        $fview = new UserView_form;
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormPassword'));
        $xajax->registerFunction(array('generatePassword', $fview, 'ajaxGeneratePassword'));
        
        $tpl->tplAssign('generate_pass_block', $fview->getGeneratePasswordBlock());
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('action_title', $this->msg['update_password_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    static function getChangePasswordHint($msg, $setting) {
                
        $days = $setting['password_rotation_freq'];
        $policy = $setting['password_rotation_policy'];
    
        $msg_key = ($policy == 1) ? 'rotate_pass_advice_msg' : 'rotate_pass_required_msg'; 
        $password_rotation_note = AppMsg::replaceParse($msg[$msg_key], array('num' => $days));
        return BoxMsg::factory('hint', array('body' => $password_rotation_note));
    }
    
    
    function ajaxValidateFormPassword($values, $options = array()) {
        $options['func'] = 'getValidatePassword';
        return $this->ajaxValidateForm($values, $options);
    }
    
}
?>