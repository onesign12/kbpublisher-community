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


class KBClientView_password_sent extends KBClientView_common
{
    
    var $page_modal = true;
    var $hide_user_not_found; // assigned from KBClientAction_password
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['reset_password_msg'];
        $this->nav_title = $this->msg['reset_password_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;
    }
    
    
    function getForm($manager) {
                
        $tpl = new tplTemplatez($this->getTemplate('password_reset_sent.html'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $key = (!$this->hide_user_not_found) ? 'password_reset_sent' : 'password_reset_sent2';
        $msg = AppMsg::getMsgs('after_action_msg.ini', 'public', $key);
        // $tpl->tplAssign('hint_title', $msg['title']);
        $tpl->tplAssign('hint_desc', $msg['body']);
        
        if(!empty($_SESSION['resent_code_'])) {
            $tpl->tplSetNeeded('/resend');
        }
        
        $tpl->tplAssign('action_link', $this->getLink('password', false, false, 'sent'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>