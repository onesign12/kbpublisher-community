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



class UserImportModel extends ImportModel
{    
    
    function getMoreFields($fields, $data, $filename) {
            
        $set = array();
        $set[] = 'grantor_id = ' . $this->model->user_id;
       
        $import_data = array(
            'SUBSTRING(MD5(RAND()) FROM 1 FOR 8)'
        );
        
        if(!empty($data['role'])) {
            $import_data[] = sprintf('"|%s"', implode(',', $data['role']));
        }
        
        $set[] = sprintf('import_data = CONCAT(%s)', implode(',', $import_data));
        
        if(array_search('date_registered', $fields) === false) {
            $set[] = 'date_registered = NOW()';
        }
        
        // from extra fields
        if(!empty($data['company_id'])) {
            $set[] = 'company_id = ' . $data['company_id'];
        }
        
        // echo '<pre>', print_r($set, 1), '</pre>';
        return $set;
    }
    
    
    function setPasswords() {
        $sql = "UPDATE {$this->model->tbl->user} 
            SET password = MD5(SUBSTRING_INDEX(import_data, '|', 1)),
                date_updated = date_updated
            WHERE import_data IS NOT NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function setPasswords2() {
        $sql = "UPDATE {$this->model->tbl->user} 
        SET password = MD5(SUBSTRING(MD5(username) FROM 1 FOR 8)),
            import_data = SUBSTRING(MD5(username) FROM 1 FOR 8),
            date_updated = date_updated
        WHERE password = ''";
        
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }


    function saveRoles($user_ids, $role_ids) {
        $data = array();
        foreach ($user_ids as $user_id) {
            foreach ($role_ids as $role_id) {
                $data[] = array($user_id, $role_id);
            }
        }
    
        $sql = MultiInsert::get("INSERT IGNORE {$this->model->tbl->user_to_role} (user_id, role_id)
                                VALUES ?", $data);
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }

    
    function getImportedUsersResult() {
        $sql = "SELECT * FROM {$this->model->tbl->user} WHERE import_data IS NOT NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
        return $result;
    }
    
    
    function getImportedUsersCount() {
        $sql = "SELECT COUNT(*) as num FROM {$this->model->tbl->user} WHERE import_data IS NOT NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function eraseImportData() {
        $sql = "UPDATE {$this->model->tbl->user}
            SET import_data = NULL,
                date_updated = date_updated
            WHERE import_data IS NOT NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function setDateRegistered() {
        $sql = "UPDATE {$this->model->tbl->user} 
        SET date_registered = NOW(),
            date_updated = date_updated
        WHERE date_registered IS NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
}
?>