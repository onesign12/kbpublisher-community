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
            ue.value3 AS 'private_key',
            ut.value2 AS 'session',
            ue.value1 AS 'access'
        FROM ({$this->tbl->user} u, 
              {$this->tbl->user_extra} ue)
        LEFT JOIN {$this->tbl->user_temp} ut 
            ON ut.rule_id = %d AND u.id = ut.user_id AND ut.active = 1
        WHERE ue.rule_id = %d /* extra table rule_id */
        AND ue.user_id = u.id
        AND ue.value2 = '%s' /* public key */ 
        AND ue.value1 IN (1,2)    /* api active for user */";
        $sql = sprintf($sql, $this->temp_rule_id, $this->extra_rule_id, $public_key);
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
        $this->deleteSession($user_id);
        return $this->addSessionId($session, $user_id, $user_ip);
    }
    

    function deleteSession($user_id) {
        $sql = "DELETE FROM {$this->tbl->user_temp} 
        WHERE rule_id = '{$this->temp_rule_id}' 
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>