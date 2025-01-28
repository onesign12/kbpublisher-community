<?php
class SubscriptionCommonModel extends AppModel
{
    

    function &getSubscribers($user_active_status, $latest_date) {
    
        $sql = "SELECT DISTINCT s.user_id
            FROM 
                ({$this->tbl->user_subscription} s,
                {$this->tbl->user} u)
            WHERE 1
            AND u.id = s.user_id
            AND u.active IN(%s)
            AND s.entry_type IN ({$this->entry_types})
            AND (s.date_lastsent <= '%s' OR s.date_lastsent IS NULL)";

        $sql = sprintf($sql, $user_active_status, $latest_date);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function &getAllSubscribers($entry_types = false) {
        $entry_types = ($entry_types) ? $entry_types: $this->entry_types;
    
        $sql = "SELECT DISTINCT user_id
            FROM {$this->tbl->user_subscription}
            WHERE entry_type IN ({$entry_types})";
        
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function updateSubscription($user_id) {
        $sql = "UPDATE {$this->tbl->user_subscription} SET date_lastsent = NOW()
            WHERE user_id = %d AND entry_type IN ({$this->entry_types})";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function deleteSubscription($user_id, $entry_type, $entry_ids) {
        $sql = "DELETE FROM {$this->tbl->user_subscription}
            WHERE user_id = %d
            AND entry_type = %d
            AND entry_id IN (%s)";
        $sql = sprintf($sql, $user_id, $entry_type, $entry_ids);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }    
}

?>