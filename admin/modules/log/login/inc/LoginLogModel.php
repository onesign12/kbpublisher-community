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



class LoginLogModel extends AppModel
{

    var $tables = array('table' => 'log_login', 'user' => 'user');
    
    
    function getById($id) {
        list($user_id, $date_login) = explode('_', $id);
        $this->setSqlParams(sprintf("AND user_id = %d", $user_id));
        $this->setSqlParams(sprintf("AND date_login = '%d'", $date_login));
        $data = $this->getRecords();
        return $data[0];
    }
    

    function getRecordsSql() {
        $sql = "SELECT 
            l.*,
            u.username,
            INET_NTOA(l.user_ip) AS user_ip_formatted,
            UNIX_TIMESTAMP(l.date_login) AS date_login_ts
        FROM {$this->tbl->table} l
        LEFT JOIN {$this->tbl->user} u ON u.id = l.user_id
        WHERE {$this->sql_params}
        {$this->sql_params_order}";
        
        return $sql;
    }
    
    
    function getUserIdByUsername($username) {
        $sql = "SELECT id FROM {$this->tbl->user} WHERE username = '{$username}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function getStartDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_login)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getLoginTypeSelectRange($msg) {
        $msg = AppMsg::getMsg('log_msg.ini', false, 'login_type');
        $types = LoggerModel::getAuthTypes();
        
        $types_order = array(
            'local', 'saml', 'ldap', 
            'remote', 'social', 'user', 'auto', 'api'
        );
       
        $range = array();
        foreach($types_order as $v) {
            $range[$types[$v]] = $msg[$v];
        }
        
        return $range;
    }    
    
    function getLoginStatusSelectRange($msg) {
        $msg = AppMsg::getMsg('log_msg.ini', false, 'login_status');
        $data[1] = $msg['success'];
        $data[2] = $msg['failed'];
        $data[3] = $msg['error'];
                
        return $data;
    }    
}
?>