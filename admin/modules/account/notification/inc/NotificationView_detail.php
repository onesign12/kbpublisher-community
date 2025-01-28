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


class NotificationView_detail extends AppView
{
    
    var $template = 'detail.html';
    
    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('date_formatted', $this->getFormatedDate($obj->get('date_posted'), 'datetime'));
        
        $prev_btn_class = $next_btn_class = 'button buttonDisabled';
        $prev_btn_disabled = $next_btn_disabled = 'disabled';
        
        $active = ($this->controller->getMoreParam('popup') == 1) ? 1 : 0; 
        
        $prev_id = $manager->getPrevNotificationId($obj->get('id'), $active);
        if ($prev_id) {
            $prev_btn_class = 'button';
            $prev_btn_disabled = '';
            $tpl->tplAssign('prev_link', $this->getActionLink('detail', $prev_id));
        }
        
        $next_id = $manager->getNextNotificationId($obj->get('id'), $active);
        if ($next_id) {
            $next_btn_class = 'button';
            $next_btn_disabled = '';
            $tpl->tplAssign('next_link', $this->getActionLink('detail', $next_id));
        }
        
        $tpl->tplAssign('prev_btn_class', $prev_btn_class);
        $tpl->tplAssign('next_btn_class', $next_btn_class);
        $tpl->tplAssign('prev_btn_disabled', $prev_btn_disabled);
        $tpl->tplAssign('next_btn_disabled', $next_btn_disabled);
        
        $msg_num = $manager->getUserNotificationsCount();
        $msg_num = ($msg_num > NotificationView_common::$max_badge_num) ? NotificationView_common::$max_badge_num : $msg_num;
        $tpl->tplAssign('unseen_msg_num', $msg_num);
        
        // $obj->set('message', nl2br($obj->get('message')));
        $obj->set('message', $obj->get('message'));
        
        if($this->controller->getMoreParam('popup')) {
            $popup_title = $obj->get('subject');
            $tpl->tplAssign('popup_title', RequestDataUtil::stripVars($popup_title));
        }
        
        if($this->controller->getMoreParam('popup') || $this->controller->getMoreParam('frame')) {
            $tpl->tplSetNeeded('/blank');
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}
?>