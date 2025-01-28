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

class UserView_invite extends UserView_form
{
    
    var $tmpl = 'form_invite.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        // for ($i=0; $i < 5; $i++) {
        //     $a = ['num' => $i, 'email_msg' => $this->msg['email_msg']];
        //     $tpl->tplParse($a, 'email');
        // }

        $select = new FormSelect();
        $select->setSelectWidth(250);        
                
        // role
        if($manager->use_role) {
            $tpl->tplSetNeeded('/role_box');
            $link = $this->controller->getFullLink('users', 'user', '', 'role'); // 18-07-2022 eleontev
            $tpl->tplAssign('role_block_tmpl', $this->getRoleBlock($obj, $manager, $link));        
        }
        
        // priv
        // $this->getPrivBlock($tpl, $obj, $manager);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormInvite'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign('action_title', $this->msg['invite_msg']);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
    
    function ajaxValidateFormInvite($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        return $objResponse;
    }
    
}
?>