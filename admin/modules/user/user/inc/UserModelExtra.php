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


// Manage extra user tables

class UserModelExtra extends AppModel
{

    var $tables = array(
        'user', 'user_extra', 'user_temp', 'user_auth_token', 'user_to_sso'
    );

    static $extra_rules = array(
        'api' => 1,
        'mfa' => 2
    );

    static $temp_rules = array(
        'reset_password' => 1,
        'api_session'    => 2,
        'reset_username' => 3,
        'old_password'   => 4, // keep users old passwords to not using old pass in rotation
        // 'old_email' => 5 // to keep previous emails in case if chenged ?
        'auth_id'        => 6, // to keep auth session id to not allow concurent logins
        'user_invited'   => 7 // 
    );


    function getExtraById($user_id, $filters = array(), $table = 'user_extra') {
        $filter_sql = ($filters) ? ModifySql::getWhereSqlFromArr($filters) : 1;
        $sql = "SELECT * FROM {$this->tbl->$table} 
        WHERE user_id = %d AND active = 1 AND %s";
        
        $sql = sprintf($sql, $user_id, $filter_sql);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getExtraRuleById($user_id, $rule_id, $table = 'user_extra') {
        $data = $this->getExtraById($user_id, ['rule_id' => $rule_id], $table);
        return (isset($data[$rule_id])) ? $data[$rule_id] : array();
    }


    // in list records
    function getExtraByIds($user_id) {
        $data = array();
        $sql = "SELECT * FROM {$this->tbl->user_extra} WHERE user_id IN ({$user_id}) AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $data[$row['user_id']][$row['rule_id']] = $row;
        }

        return $data;
    }


    // function _saveExtra($table, $values, $user_id, $sql_command) {
    function _saveExtra($values, $user_id, $sql_command, $table = 'user_extra') {
        $more = array('user_id' => $user_id);
        foreach(array_keys($values) as $rule_id) {
            $more['rule_id'] = $rule_id;
            $id_keys = array('rule_id', 'user_id');
            $sql = ModifySql::getSql($sql_command, $this->tbl->$table, $values[$rule_id], $more, $id_keys);
            $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    

    function saveExtra($values, $user_id) {
        $this->_saveExtra($values, $user_id, 'REPLACE');
    }


    function updateExtra($values, $user_id) {
        $this->_saveExtra($values, $user_id, 'UPDATE');
    }


    function addExtra($values, $user_id) {
        $this->_saveExtra($values, $user_id, 'INSERT IGNORE');
    }


    function statusExtraRule($rule_id, $user_id, $status, $table = 'user_extra') {
        $sql = "UPDATE {$this->tbl->$table} SET active = %d
            WHERE user_id = %d AND rule_id = %d";
        $sql = sprintf($sql, $status, $user_id, $rule_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteExtraRule($rule_id, $user_id, $table = 'user_extra') {
        $sql = "DELETE FROM {$this->tbl->$table} WHERE user_id IN (%s) AND rule_id = %d";
        $sql = sprintf($sql, $user_id, $rule_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // delete all for user by user_id
    function deleteExtra($record_id, $skip_ids = false, $table = 'user_extra') {
        $skip_sql = ($skip_ids) ? "rule_id NOT IN ({$skip_ids})" : 1;
        $sql = "DELETE FROM {$this->tbl->$table} WHERE user_id IN ({$record_id}) AND {$skip_sql}";
        return $this->db->Execute($sql) or die(db_error($sql));
    }

    
    // TEMP // ---------------------------
    
    
    function getTempById($user_id, $filters = array()) {
        return $this->getExtraById($user_id, $filters, 'user_temp');
    }
    
    
    function getTempRuleById($user_id, $rule_id) {
        return $this->getExtraRuleById($user_id, $rule_id, 'user_temp');
    }
    
    
    function addTemp($values, $user_id, $sql_command = 'INSERT') {
        if(empty($values['user_ip'])) {
            $user_ip = WebUtil::getIP();
            $user_ip = ($user_ip == 'UNKNOWN') ? 0 :  $user_ip;
        } else {
            $user_ip = $values['user_ip'];
        }

        $values['user_ip'] = "IFNULL(INET_ATON('{$user_ip}'), 0)";
        $values['user_id'] = $user_id;

        $id_keys = array('rule_id', 'user_id');
        $sql = ModifySql::getSql($sql_command, $this->tbl->user_temp, $values, false, $id_keys);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // will remove all and add one
    function saveTemp($values, $user_id) {
        $this->deleteTempRule($values['rule_id'], $user_id);
        $this->addTemp($values, $user_id);
    }
    
    
    function updateTemp($values, $user_id) {
        $this->addTemp($values, $user_id, 'UPDATE');
    }
    
    
    function deleteTemp($user_id, $skip_ids = array()) {
        $this->deleteExtra($user_id, $skip_ids, 'user_temp');
    }
    
    
    function statusTempRule($rule_id, $user_id, $status) {
        $this->statusExtraRule($rule_id, $user_id, $status, 'user_temp');
    }    
    
    
    function deleteTempRule($rule_id, $user_id) {
        $this->deleteExtraRule($rule_id, $user_id, 'user_temp');
    }
    
    
    //  REMEMBER AUTH // --------------------
    
    function saveRememberAuth($id, $user_id, $token, $remote_token, $date_expired) {
        $sql = "REPLACE {$this->tbl->user_auth_token} SET 
            id = %s,
            user_id = '%d',
            token = '{$token}',
            remote_token = '{$remote_token}',
            date_expired = '{$date_expired}'";
        
        $sql = sprintf($sql, $id, $user_id); // id could be NULL
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }
    
    
    function getRememberAuth($selector) {
        $sql = "SELECT a.*, u.username
            FROM {$this->tbl->user_auth_token} a, {$this->tbl->user} u 
            WHERE  a.id = '%d'
            AND a.date_expired >= CURDATE()
            AND a.user_id = u.id";
        
        $sql = sprintf($sql, $selector);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        if($row = $result->FetchRow()) {
            $sql = "SELECT sso_provider_id, sso_user_id 
                FROM {$this->tbl->user_to_sso} WHERE user_id = %d";
            $sql = sprintf($sql, $row['user_id']);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            $row['ruid'] = $result->GetAssoc();
        }
        
        return $row;
    }

    
    function deleteRememberAuth($selector) {
        $sql = "DELETE FROM {$this->tbl->user_auth_token} WHERE  id = '%d'";
        $sql = sprintf($sql, $selector);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function resetRememberAuth($user_id) {
        $sql = "DELETE FROM {$this->tbl->user_auth_token} WHERE  user_id = '%d'";
        $sql = sprintf($sql, $user_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
}
?>