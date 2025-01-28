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

class KBApiModel extends BaseModel
{

    var $tbl_pref_custom = '';
    var $tables = array('user', 'user_extra', 'user_temp');
    var $temp_rule_id = 2; // rule id in table user_temp
    var $extra_rule_id = 1; // rule id in table user_extra
     
     
    function getApiInfoByPublicKey($public_key) {
        $sql = "SELECT 
            u.id AS 'user_id',
            u.username,
            ue.value3 AS 'private_key',
            ue.value1 AS 'access'
        FROM ({$this->tbl->user} u, 
              {$this->tbl->user_extra} ue)
        WHERE ue.rule_id = %d /* extra table rule_id */
        AND ue.user_id = u.id
        AND ue.value2 = '%s' /* public key */ 
        AND ue.value1 IN (1,2)";  /* api active for user */
        
        $sql = sprintf($sql, $this->extra_rule_id, $public_key);
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->FetchRow();
    }
     
     
    function getSession($user_id, $valid_minutes = 60) {
        $sql = "SELECT 
            value2 AS 'session',
            user_ip
        FROM {$this->tbl->user_temp}
        WHERE rule_id = %d
        AND user_id = %d
        AND CURRENT_TIMESTAMP < DATE_ADD(value_timestamp, INTERVAL %s MINUTE)
        AND active = 1";

        $sql = sprintf($sql, $this->temp_rule_id, $user_id, $valid_minutes);
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->FetchRow();
    }
        
    
    function addSessionId($session_id, $user_id, $user_ip) {
        $sql = "INSERT {$this->tbl->user_temp} SET 
        rule_id = '{$this->temp_rule_id}',
        user_id = '{$user_id}',
        user_ip = IFNULL(INET_ATON('{$user_ip}'), 0),
        value2 = '{$session_id}',
        active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function saveSession($session, $user_id, $user_ip) {
        //$this->deleteSession($user_id);
        $this->setSessionInactive($user_id);
        return $this->addSessionId($session, $user_id, $user_ip);
    }


    function setSessionInactive($user_id) {
        $sql = "UPDATE {$this->tbl->user_temp} SET active = 0
        WHERE rule_id = '{$this->temp_rule_id}' AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSession($user_id) {
        $sql = "DELETE FROM {$this->tbl->user_temp} 
        WHERE rule_id = '{$this->temp_rule_id}' 
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>