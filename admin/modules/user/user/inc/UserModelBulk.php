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


class UserModelBulk extends BulkModel
{

    var $msg_key = 'bulk_user';

    var $actions = array(
        'role', 'priv', 'comp', 
        'subscription', 'status', 'trash'
        );
    
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
        
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }
        
    
    // BULK // ----------------------------    
    
    function removeRole($values, $ids) {
        $ids_string = $this->model->idToString($ids);
        $this->model->resetApiSession($ids_string);
        $this->model->deleteRole($this->model->idToString($ids));
    }
    
    
    function setRole($values, $ids) {
        $ids_string = $this->model->idToString($ids);
        $this->model->resetApiSession($ids_string);    
        $this->model->deleteRole($ids_string);
        if($values) {
            $this->model->addRole($values, $ids);
        }
    }
    
    
    function addRole($values, $ids) {
        if($values) {
            $ids_string = $this->model->idToString($ids);
            $this->model->resetApiSession($ids_string);
            $this->model->addRole($values, $ids);
        }
    }
    
    
    function setPriv($values, $ids) {
        $ids_string = $this->model->idToString($ids);
        $this->model->resetApiSession($ids_string);
        $this->model->deletePriv($ids_string);
        
        $sphinx_attr = 0;
        if($values != 'none') {
            $au = KBValidateLicense::getAllowedUserRest($this->model);
            $this->model->addPriv($values, $ids, $this->model->user_id, $au);
            
            $sphinx_attr = $values;
        }
        
        foreach($ids as $id) {
            if($au <= 0) {
                continue;
            }
            
            $au --;
            
            $this->updateSphinxAttributes('priv_name_id', $sphinx_attr, $id);
        }
    }
    
    
    function setCompany($values, $ids) {
        $user_ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->user} SET company_id = '$values' WHERE id IN($user_ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
        
        $this->updateSphinxAttributes('company_id', $values, $user_ids);
    }
        
    
    function removeSubscription($values, $ids) {
        $include_comment_subs = true; // delete comments subs
        $subs_model = Singleton('SubscriptionModel');
        $subs_model->unsubscribeByUserId($this->model->idToString($ids), $include_comment_subs);
    }
    
    
    function addSubscription($values, $ids) {
        if($values) {
            $this->model->subscribe($values, $ids);
        }
    }


    function setSubscription($values, $ids) {
        $include_comment_subs = false; // delete comments subs
        $subs_model = Singleton('SubscriptionModel');
        $subs_model->unsubscribeByUserId($this->model->idToString($ids), $include_comment_subs);
        
        $this->addSubscription($values, $ids);
    }
    
    
    function trash($ids) {
        $objs = array();
        foreach ($ids as $id) {
            $data = $this->model->getById($id);
            $obj = new User;
            $obj->collect($id, $data, $this->model, 'save');
            $objs[] = $obj;
        }
        
        $this->model->trash($ids, $objs);
    }
}
?>