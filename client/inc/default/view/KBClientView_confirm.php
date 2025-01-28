<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+
    

class KBClientView_confirm extends KBClientView_common
{
    
    var $page_modal = true;


    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['confirm_registration_msg'];
        $this->nav_title = $this->msg['confirm_registration_msg'];
        
        $data = '';
        //if($this->msg_id == 'confirmation_sent' || $this->msg_id == 'registration_not_confirmed') {
        //    $data = &$this->getConfirmForm();
        //}
        
        return $data;
    }


    // function getConfirmForm() {
    //
    //     $tpl = new tplTemplatez($this->template_dir . 'confirm_form.html');
    //
    //     if($this->getErrors()) {
    //         $tpl->tplAssign('error_msg', $this->getErrors());
    //     }
    //
    //     $tpl->tplAssign('action_link', $this->getLink('all'));
    //     $tpl->tplAssign('cancel_link', $this->getLink());
    //
    
    //      $tpl->tplAssign($this->msg);
    //      $tpl->tplAssign($this->getFormData());
    
    //     $tpl->tplParse(array_merge();
    //     return $tpl->tplPrint(1);
    // }   
}
?>