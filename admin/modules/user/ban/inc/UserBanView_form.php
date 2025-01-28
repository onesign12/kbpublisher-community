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




class UserBanView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        $select = new FormSelect();
        $select->setSelectWidth(250);
        
        // type
        $range = $manager->getTypeSelectRange();
        $select->setSelectName('ban_type');        
        $select->setRange($range);
        $tpl->tplAssign('ban_type_select', $select->select(@$obj->get('ban_type')));   
        
        // rule
        $range = $manager->getRuleSelectRange();
        $select->select_tag = false;    
        $select->setRange($range);
        $tpl->tplAssign('ban_rule_select', $select->select(@$obj->get('ban_rule')));
        
        
        // reason
        $range = $manager->getBanReasonSelectRange();
        $select->select_tag = false;    
        $select->setRange($range);
        $tpl->tplAssign('ban_reason_select', $select->select(@$obj->get('ban_reason')));
        

        if ($this->controller->action == 'update') {
            $tpl->tplSetNeeded('/date_end');
            
            if ($obj->get('date_end') == NULL) {
                $date_end_formatted = $this->msg['permanent_msg'];                
            } else {
                $date_end_formatted = $this->getFormatedDate($obj->get('date_end'), 'datetime');                 
            }
            
            $tpl->tplAssign('date_end_formatted', $date_end_formatted);
            
        } else {
            $tpl->tplSetNeeded('/date_end_edit');
            $this->msg['interval'] = AppMsg::getMsg('datetime_msg.ini', false, 'time_interval');
             
            $range = array('minute' => $this->msg['interval']['minute_2'],
                'hour' => $this->msg['interval']['hour_2'],
                'day' => $this->msg['interval']['day_2'],
                'week' => $this->msg['interval']['week_2'],
                'month' => $this->msg['interval']['month_2'],
                'year' => $this->msg['interval']['year_2']
                );

            $select->setSelectWidth(210);
            $select->setRange($range, array('perm' => $this->msg['permanent_msg']));
            
            $v = ($obj->get('date_end')) ? $obj->get('date_end') : 'perm';
            $tpl->tplAssign('date_end_select', $select->select($v));
        }

        if (isset($data['date_end_num'])) {
            $tpl->tplAssign('date_end_num', $data['date_end_num']);
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        $tpl->tplAssign('users_link', html_entity_decode($this->getLink('users', 'user', false, false, array('close' => 1))));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));    
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        if ($obj->get('ban_rule') == 1) {
            $manager2 = new UserModel;
            $user = $manager2->getById($obj->get('ban_value'));
            
            if ($user) {
                $tpl->tplAssign('ban_value', sprintf('%s %s', $user['last_name'], $user['first_name']));
                $tpl->tplAssign('user_id', $obj->get('ban_value'));
            }
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>