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


class UserBanView_detail extends AppView
{
    
    var $tmpl = 'form_detail.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
                
        if ($obj->get('date_end') == NULL) {
            $date_end_formatted = $this->msg['permanent_msg'];                
        } else {
            $date_end_formatted = $this->getFormatedDate($obj->get('date_end'), 'datetime');                 
        }
        
        $tpl->tplAssign('date_end_formatted', $date_end_formatted);
        
        $start_date = $this->getFormatedDate($obj->get('date_start'), 'datetime');
        $tpl->tplAssign('date_start_formatted', $start_date);
        
        $tpl->tplAssign($obj->get());
        
        $range = $manager->getTypeSelectRange();
        $tpl->tplAssign('ban_type', $range[$obj->get('ban_type')]);
        
        $range = $manager->getRuleSelectRange();
        $tpl->tplAssign('ban_rule', $range[$obj->get('ban_rule')]);
        
        $range = $manager->getBanReasonSelectRange();
        $tpl->tplAssign('ban_reason', $range[$obj->get('ban_reason')]);
        
        if ($obj->get('active')) {
            $tpl->tplSetNeeded('/deactivate_button');
            
            $more = array('status' => 0);
            $tpl->tplAssign('deactivate_link', $this->getActionLink('status', $obj->get('id'), $more));
            
        } else {
            $tpl->tplAssign('duplicate_link', $range[$obj->get('ban_rule')]);
            
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['body'] = $msgs['note_ban_rule_deactivated'];
            $tpl->tplAssign('msg', BoxMsg::factory('hint', $msg));
        
            $tpl->tplSetNeeded('/duplicate_button');
            
            $more = array('show_msg' => 'note_clone-hint');
            $tpl->tplAssign('duplicate_link', $this->getActionLink('clone', $obj->get('id'), $more));
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->setCommonFormVars($obj));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
}
?>