<?php
// will be notified active users and which date_lastsent  < news date
// date_lastsent created on user creation
// if user inactive email not sent so date_lastsent not updated 
// once activated all previous emails will be sent - we mau need to change it
// by changing date_lastsent for all not active users

class SubscriptionNewsModel extends SubscriptionCommonModel
{

    var $tbl_pref_custom = '';
    var $tables = array('news', 'user_subscription', 'user');

    var $entry_types = '3';

    
    function &getSubscribers($user_active_status, $latest_date) {
        $sql = "SELECT s.user_id, s.date_lastsent
            FROM 
                ({$this->tbl->user_subscription} s,
                {$this->tbl->user} u)
            WHERE 1
            AND u.id = s.user_id
            AND u.active IN(%s)
            AND s.entry_type = '{$this->entry_types}'
            AND (s.date_lastsent <= '%s' OR s.date_lastsent IS NULL)";

        $sql = sprintf($sql, $user_active_status, $latest_date);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }    
    
    
    /**
     * @param datetime $lastsent
     */
    function getRecentEntriesForUser($user, $lastsent) {
        
        $emanager = new NewsEntryModel($user, 'read');
        
        $sql = "SELECT e.id, e.title, e.date_posted AS 'date'
            FROM 
                {$this->tbl->news} e
                {$emanager->entry_role_sql_from}
            WHERE 1
                AND e.active = 1
                AND e.date_posted > '%s'
                AND NOT e.private & %d
                AND {$emanager->entry_role_sql_where}
            {$emanager->sql_params_group}";
        
        $sql = sprintf($sql, $lastsent, $this->private_rule['list']);
        
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }
    
    
    // get latest news, skip all future news
    function getLatestEntryDate() {
        $sql = "SELECT MAX(date_posted) AS 'last_date' FROM {$this->tbl->news} 
            WHERE active = 1 
            AND date_posted <= NOW()";
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