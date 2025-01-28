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


class UserSecurityView_delete extends AppView
{
    
    var $tmpl = 'form_delete_account.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager, $request_only = false) {
        
        $this->addMsg('user_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        if(!empty($_GET['saved']) && !$obj->errors) {
            $block = ($_GET['saved'] == 1) ? '/close_requested' : '/close_deleted';
            $tpl->tplSetNeeded($block);
        }

        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msg['body'] = AppMsg::parseMsgsMultiIni($file, 'note_remove_user_account');
        $tpl->tplAssign('note', BoxMsg::factory('hint', $msg));
        
        $msg['body'] = AppMsg::parseMsgsMultiIni($file, 'account_deleted');
        $tpl->tplAssign('success_note', BoxMsg::factory('success', $msg));
        
        if($request_only) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msg['body'] = AppMsg::parseMsgsMultiIni($file, 'note_request_remove_user_account');
            $tpl->tplAssign('note2', BoxMsg::factory('hint', $msg));
            $tpl->tplSetNeeded('/request');
        
        } else {
            $tpl->tplSetNeeded('/delete');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormDelete'));
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('action_title', $this->msg['delete_account_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormDelete($values, $options = array()) {
        $options['func'] = 'getValidateDelete';
        return $this->ajaxValidateForm($values, $options);
    }
}
?>