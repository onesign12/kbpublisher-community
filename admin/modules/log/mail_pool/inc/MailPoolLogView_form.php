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

class MailPoolLogView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('log_msg.ini'); 
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        // letter type
        $letter_type = $manager->getLetterTypeSelectRange($this->msg); 
        $tpl->tplAssign('letter_type_str',  $letter_type[$obj->get('letter_type')]);
        
        $tpl->tplAssign('date_created_formatted',  $this->getFormatedDate($obj->get('date_created'), 'datetime'));
        $tpl->tplAssign('date_created_interval', $this->getTimeInterval($obj->get('date_created')));
            
        $str = '<b>%s</b> (%s)';
        $date_sent = '---';
         if($obj->get('date_sent')) {
            $date_sent = sprintf($str, $this->getTimeInterval($obj->get('date_sent')), 
                                       $this->getFormatedDate($obj->get('date_sent'), 'datetime'));
        }
        
        $tpl->tplAssign('date_sent_formatted', $date_sent);
        $tpl->tplAssign('is_sent', ($obj->get('status') == 1) ? $this->msg['yes_msg'] : $this->msg['no_msg']);
        $num_tries = ($obj->get('failed') == 0 && $obj->get('status') == 1) ? 1 : $obj->get('failed');
        $tpl->tplAssign('num_tries',  $num_tries);

        
        // more than one try and not sent, status 0
        if($obj->get('failed') > 0 && $obj->get('status') == 0) {
            $more = array('status'=>2);
            $link = $this->getActionLink('status', $obj->get('id'), $more);
            $tpl->tplAssign('status_link', $link);
            $tpl->tplSetNeeded('/not_sent');
        }
        
        if($obj->get('failed_message')  && $obj->get('status') != 1) {
            $tpl->tplSetNeeded('/failed_message');
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));    
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>