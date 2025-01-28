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
    

class KBClientView_confirm_sent extends KBClientView_common
{
    
    var $page_modal = true;
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['confirm_registration_msg'];
        $this->nav_title = $this->msg['confirm_registration_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;
    }
    
    
    function getForm($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'confirm_sent.html');
        
        $tpl->tplAssign('error_msg', $this->getErrors()); 
        
        $msg = AppMsg::getMsgs('after_action_msg.ini', 'public', 'confirmation_sent');
        $tpl->tplAssign('hint_title', $msg['title']);
        $tpl->tplAssign('hint_desc', $msg['body']);
        
        if(!empty($_SESSION['kb_reg_user_'])) {
            $tpl->tplSetNeeded('/resend');
        }
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink());
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>