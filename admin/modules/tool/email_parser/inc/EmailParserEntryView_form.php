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


class EmailParserEntryView_form extends TriggerEntryView_form
{
    
    var $tmpl = 'form.html';
        
    
    function _parseEmailBox($tpl, $obj, $manager) {
        $tpl->tplSetNeededGlobal('email_automation');
        
        // key
        if(!empty($_POST['id_key']))  {
            $id_key = $_POST['id_key'];
            
        } else {
            $id_key = array(AuthPriv::getUserId(), 1, $obj->get('id'), $this->controller->action); 
            if ($this->controller->action != 'update') {
                $id_key[] = time();
            }
        
            $id_key = md5(serialize($id_key));
        }
        
        $tpl->tplAssign('id_key', $id_key);
        
        
        $options = $obj->get('options');
        if (!empty($options['mailbox_id'])) {
            $mailbox = $manager->getMailbox($options['mailbox_id']);
            
            $mailbox_options = unserialize($mailbox['data_string']);
            $mailbox_title = (!empty($mailbox_options['title'])) ? $mailbox_options['title'] : $mailbox_options['host'];
            
            $tpl->tplAssign('mailbox_id', $options['mailbox_id']);
            $tpl->tplAssign('mailbox_title', $mailbox_title);
            
            $action = false;
             
        } else {
            $action = 'insert';
        }
        
        // mailbox
        $link = $this->controller->getFullLink('tool', 'automation', 'email_box', $action);
        $tpl->tplAssign('popup_link', $this->controller->_replaceArgSeparator($link));
    }
    
}
?>