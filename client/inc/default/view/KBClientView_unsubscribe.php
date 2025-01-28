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
    

class KBClientView_unsubscribe extends KBClientView_common
{
    
    var $page_modal = true;
    
    
    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['unsubscribe_msg'];
        $this->nav_title = $this->msg['unsubscribe_msg'];
        
        $data = '';
        $msg_no_form = array('unsusbscription_success', 'unsusbscription_error');
        if(!in_array($this->msg_id, $msg_no_form)) {
           $data = $this->getForm();
        }
        
        return $data;
    }
    
    
    function getForm() {
        
        $tpl = new tplTemplatez($this->template_dir . 'unsubscribe_form.html');
        
        // hint
        $msg_key = sprintf('unsubscribe_%s_msg', $this->sub_type);
        $msg_key = (isset($this->msg[$msg_key])) ? $msg_key : 'unsubscribe_all_msg';
        $hint = sprintf('%s %s?', $this->msg['unsubscribe_from_msg'], $this->msg[$msg_key]);
        $tpl->tplAssign('unsubscribe_hint', $hint);
        
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink());                                                              
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>