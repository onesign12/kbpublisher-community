<?php

class UserActivityModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('user_activity');
    

    function addUserActivity($data) {
        $sql = "INSERT {$this->tbl->user_activity} 
        (date_action, entry_type, action_type, entry_id, user_id, extra_data, user_ip, date_month) VALUES ?";
        $sql = MultiInsert::get($sql, $data);
        $result =& $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result; 
    }
    
    
    function checkUserActivity($user_id, $date) {
        $sql = "SELECT COUNT(*) as num FROM {$this->tbl->user_activity}
        WHERE user_id = %d AND DATE(date_action) = '%s'";
        $sql = sprintf($sql, $user_id, $date);
        $result =& $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
            return fasle;
        }
        return $result->Fields('num'); 
    }
    
    
    function freshUserActivity($months) {
        $sql = "DELETE FROM {$this->tbl->user_activity} WHERE date_action < DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)";
        $result =& $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result; 
    }
    
}
?>