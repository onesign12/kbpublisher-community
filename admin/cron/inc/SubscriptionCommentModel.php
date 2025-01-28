<?php
class SubscriptionCommentModel extends SubscriptionCommonModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('kb_entry', 'kb_comment', 'user_subscription', 'user');

    var $entry_types = '31';
    
        
    /**
     * @param datetime $lastsent
     */
    function getRecentEntriesForUser($user) {
        $sql = "SELECT e.id, e.title, e.date_commented AS 'date'
        FROM 
            ({$this->tbl->kb_entry} e,
            {$this->tbl->user_subscription} s)
        
        WHERE 1
            AND s.entry_id = e.id
            AND s.user_id = %d
            AND s.entry_type = %d
            AND (s.date_lastsent <= e.date_commented OR s.date_lastsent IS NULL)";
                
        $sql = sprintf($sql, $user['user_id'], $this->entry_types);
        //echo print_r($sql, 1), "\n=============\n";
    
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }
        
    
    function getLatestEntryDate() {
        $sql = "SELECT MAX(date_posted) AS 'last_date' FROM {$this->tbl->kb_comment} WHERE active = 1";
        $result = $this->db->SelectLimit($sql, 1, 0);
        if ($result) {
            return $result->Fields('last_date');
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }    
}
?>