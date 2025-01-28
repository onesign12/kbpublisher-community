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


class UserView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    function execute(&$obj, &$manager) {    
        
        $this->addMsg('user_msg.ini');
        @$values = &$_POST['bulk'];
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
            
        $priv_range = $manager->getPrivSelectRange();    
            
        // check
        $au = KBValidateLicense::getAllowedUserRest($manager);
        if($au !== true) {
            if($au < 0) {
                $priv_range = array();
            }
            
            $key = ($au <= 0) ? 'license_exceed_users_note' : 'license_limit_users_note';
            $msg = AppMsg::licenseBox($key, array('num_users' => $au));
            $tpl->tplAssign('license_limit_user_msg', $msg);
        }
        
        
        // priv
        @$priv_id = $values['priv'];
        $select->setRange($priv_range, array('none' => $this->msg['remove_priv_msg']));
        $tpl->tplAssign('priv_select', $select->select($priv_id));
        
        // role
        $items = array('remove', 'set', 'add');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_user_role');
        $select->setRange($range);
        $tpl->tplAssign('role_action_select', $select->select());
        
        // company
        @$company_id = $values['comp'];
        $select->setRange($manager->getCompanySelectRange(), array(0 => $this->msg['remove_company_msg']));
        $tpl->tplAssign('company_select', $select->select($company_id));
        
        // status
        @$status = $values['s'];
        $extra_range = array();
        $select->setRange($manager->getListSelectRange(), $extra_range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        // subscribe
        $items = array('remove', 'set', 'add');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_subscription');
        $select->setRange($range);
        $tpl->tplAssign('subscription_action_select', $select->select());
        
        $select->setRange($manager->getSubscriptionSelectRange($this->msg));
        $tpl->tplAssign('subscription_select', $select->select($obj->getSubscription()));
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
}
?>