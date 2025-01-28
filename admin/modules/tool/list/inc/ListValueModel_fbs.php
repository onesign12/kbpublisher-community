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



class ListValueModel_fbs extends ListValueModel
{

    var $custom_tables = array('reftable' => 'feedback', 'data_to_user_value');
    var $admin_user_id = 10; // role id in data_to_user 
    
    
    function __construct() {
        parent::__construct();
        $this->dv_manager = new DataToValueModel();
    }
    
    
    function getRecordsBySupervisor($supervisor_id) {
        $sql = "SELECT lv.*
            FROM ({$this->tbl->table} lv,
                {$this->tbl->data_to_user_value} dv)
            WHERE dv.rule_id = {$this->admin_user_id}
                AND dv.data_value = lv.list_value
                AND dv.user_value = '{$supervisor_id}'
                AND {$this->sql_params} {$this->sql_params_order}";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }    
    
    
    function inUse($id) {
        $row = $this->getById($id);
        $list_value = $row['list_value'];
        
        $sql = "SELECT 1 AS field 
        FROM {$this->tbl->reftable} 
        WHERE subject_id = '{$list_value}' 
        LIMIT 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('field');        
    }
    
    
    // ADMIN USER //-----------------
    
    function _getAdminUserById($record_id, $list_view = false) {
            $select = ($list_view) ? '*' : 'dv.user_value, dv.user_value AS id1';
            return $this->dv_manager->getDataById($record_id, 
                                           $this->admin_user_id, 
                                            $select,
                                            $list_view);
    }
    
    
    function saveAdminUserToCategory($users, $record_id) {
        $this->dv_manager->saveData($users, $record_id, $this->admin_user_id);
    }
    
    
    function deleteAdminUserToCategory($record_id) {
        $this->dv_manager->deleteData($record_id, $this->admin_user_id);
    }
    
    
    // used when wrong submit to generate list 
    function getAdminUserByIds($ids) {
        $sql = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) 
        FROM {$this->tbl->user} u
        WHERE u.id IN ($ids)";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();    
    }
    

    function getAdminUserById($record_id, $list_view = false) {
        
        $data = array();
        $ids = $this->_getAdminUserById($record_id, $list_view);
        
        if($ids) {
            if($list_view) {
                foreach($ids as $category_id => $v) {
                    $data[$category_id] = $this->getAdminUserByIds(implode(',', $v));
                }
                
            } else {
                $data = $this->getAdminUserByIds(implode(',', $ids));
            }
        }

        return $data;
    }
    
    
    function save($obj, $action = 'insert') {
        
        if($action == 'insert') {
            $id = $this->add($obj);
            $this->saveAdminUserToCategory($obj->getAdminUser(), $obj->get('list_value'));
            
        } else {
            $id = (int) $obj->get('id');
            $this->update($obj, $id);
            $this->deleteAdminUserToCategory($obj->get('list_value'));
            $this->saveAdminUserToCategory($obj->getAdminUser(), $obj->get('list_value'));
        }
        
        return $id;
    }
}
?>