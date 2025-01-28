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


class NotificationModel extends AppModel
{

    var $tables = array('table' => 'user_notification');
    
    var $types = array(
        'info'  => 1,
        'error' => 2
    );
     
     
    function __construct() {
        parent::__construct();
        $this->user_id = AuthPriv::getUserId();
    }
    
    
    function getStartDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_posted)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getNextNotificationId($id, $active) {
        return $this->_getNotificationId($id, '>', 'ASC', $active);
    }
    
    
    function getPrevNotificationId($id, $active) {
        return $this->_getNotificationId($id, '<', 'DESC', $active);
    }
    
        
    function _getNotificationId($id, $sign, $direction, $active) {
        $active_sql = ($active) ? 'active = 1' : 1;
        $sql = "SELECT id FROM {$this->tbl->table} 
            WHERE user_id = %d AND id %s %d AND {$active_sql}
            ORDER BY id %s";
        $sql = sprintf($sql, $this->user_id, $sign, $id, $direction);
        
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function getUserNotificationsCount() {
        static $num;
        if($num === null) {
            $num = $this->_getUserNotificationsCount();
        }
        
        return $num;
    }
    
    
    function _getUserNotificationsCount() {
        $sql = "SELECT COUNT(id) as num FROM {$this->tbl->table} 
            WHERE user_id = %d AND active = 1";
        $sql = sprintf($sql, $this->user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getUserNotifications($limit = -1) {
        $sql = "SELECT * FROM {$this->tbl->table} 
            WHERE user_id = {$this->user_id} AND active = 1 
            ORDER BY id DESC";
        $result = $this->db->SelectLimit($sql, $limit, 0) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function addNotification($subject, $title, $message, $user_ids, $type, $key) {
        $type = $this->types[$type];
        $title = addslashes(trim($title));
        $message = addslashes($message);
        
        $data = array();
        foreach($user_ids as $user_id) {
            $data[] = array($user_id);
        }
        
        $sql = "REPLACE {$this->tbl->table} (user_id, notification_type, notification_key, subject, title, message) VALUES ?";
        $sql = MultiInsert::get($sql, $data, array($type, $key, $subject, $title, $message));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
    
    
    // on cron not to insert the same 
    // function isNotificationExist($message, $user_id, $key) {
    //
    //     $sql = "SELECT message FROM {$this->tbl->table}
    //     WHERE user_id = %d
    //     AND notification_key = '%s'
    //     AND date_posted > DATE_SUB(NOW(), INTERVAL 1 DAY)";
    //     $sql = sprintf($sql, $user_id, $key);
    //     $result = $this->db->SelectLimit($sql, 1) or die(db_error($sql));
    //     $db_message = $result->Fields('message');
    //
    //     $ret = false;
    //     if($message) {
    //         $ret = (md5($message) == md5($db_message));
    //     }
    //
    //     echo md5($message), "\n";
    //     echo $message, "\n";
    //     echo "================", "\n";
    //
    //     echo md5($db_message), "\n";
    //     echo $db_message, "\n";
    //
    //     var_dump($ret);
    //
    //     return $ret;
    // }
    
    
    function mark($value, $id = false) {
        $sql = "UPDATE {$this->tbl->table} SET active = '%d' WHERE user_id = %d";
        $sql = sprintf($sql, $value, $this->user_id);
        
        if ($id) {
            $sql .= sprintf(' AND id IN (%s)', $this->idToString($id));
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
    
    
    function delete($record_id) {
        $record_id = $this->idToString($record_id);
        
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN (%s) AND user_id = %d";
        $sql = sprintf($sql, $record_id, $this->user_id);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
     
}
?>