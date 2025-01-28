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


class SearchLogModel extends AppModel
{

    var $tables = array('table' => 'log_search', 'user' => 'user');
    
    
    function getById($id) {
        list($user_id, $user_ip, $date_search) = explode('_', $id);
        $this->setSqlParams(sprintf("AND user_id = %d", $user_id));
        $this->setSqlParams(sprintf("AND user_ip = '%s'", addslashes($user_ip)));
        $this->setSqlParams(sprintf("AND date_search = '%d'", $date_search));
        $data = $this->getRecords();
        return $data[0];
    }
    

    function getRecordsSql() {
        $sql = "SELECT 
            l.*,
            u.username,            
            INET_NTOA(l.user_ip) AS user_ip_formatted,
            UNIX_TIMESTAMP(l.date_search) AS date_search_ts
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
    
    
    function getSearchTypeSelectRange() {
        $msg = AppMsg::getMsg('ranges_msg.ini', 'public', 'search_in_range');
        
        $data[0] = $msg['all'];
        $data[1] = $msg['article'];
        $data[2] = $msg['file'];
        $data[3] = $msg['news'];
        
        return $data;
    }
    
    
    function getSearchLink($params) {
        
        if(empty($params)) {
            return '';
        }
        
        if(isset($params['Msg'])) {
            unset($params['Msg']);
        }
        
        if(isset($params['EntryID'])) {
            unset($params['EntryID']);
        }    
    
        return APP_CLIENT_PATH . 'index.php?' . http_build_query($params);
    }
    
    
    function getStartDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_search)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
   
}
?>