<?php
class SubscriptionEntryModel extends SubscriptionCommonModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('kb_entry', 'file_entry', 'kb_comment',
                        'kb_category', 'file_category',
                        'kb_entry_to_category', 'file_entry_to_category', 
                        'user_subscription', 'user');

    var $type_to_number = array('article' => 1, 'file' => 2);
    var $entry_types = '1,2,11,12';


    function &getEntryManager($user, $type) {
        if($type == 1 || $type == 11) {
            
            $emanager = new KBEntryModel($user, 'read');
            $emanager->title_field = 'e.title';
        
        } else {            
            
            $emanager = new FileEntryModel($user, 'read');
            $emanager->title_field = 'e.filename';
        }
    
        return $emanager;
    }


    function getUserSubscriptions($user_id) {
    
        $sql = "SELECT s.entry_type, s.entry_type AS 'type' 
            FROM 
                {$this->tbl->user_subscription} s
            WHERE s.user_id = %d
            AND s.entry_type IN ({$this->entry_types})";

        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
    }


    function getUserSubscribedCategories($user_id, $entry_type) {

        $sql = "SELECT s.entry_id, s.date_lastsent
            FROM 
                {$this->tbl->user_subscription} s
            WHERE s.user_id = %d
            AND s.entry_type = %d";

        $sql = sprintf($sql, $user_id, $entry_type);
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
    }


    function &getUpdatedEntries($user, $type, $emanager, $published, 
                                    $date_field = 'e.date_updated', $is_mail = true) {
        
        $sql = "SELECT 
            e.id, 
            {$emanager->title_field} AS 'title', 
            {$date_field} AS 'date'
        FROM 
            ({$emanager->tbl->entry} e,
            {$emanager->tbl->category} cat,
            {$this->tbl->user_subscription} s)
        {$emanager->entry_role_sql_from}
        
        WHERE 1
            AND e.category_id = cat.id
            AND cat.active = 1
            AND e.active IN(%s)
            AND s.entry_id = e.id
            AND s.user_id = %d
            AND s.entry_type = %d
            AND (s.date_lastsent <= {$date_field} OR s.date_lastsent IS NULL)
            AND s.is_mail IN(%s)
            AND %s
            AND {$emanager->entry_role_sql_where}
            {$emanager->sql_params_group}";                
        
        $is_mail = ($is_mail) ? 1 : '0,1';
        $sql = sprintf($sql, $published, $user['user_id'], $type, $is_mail, 
                            $emanager->getCategoryRolesSql(false));
        // echo print_r($sql, 1), "\n=============\n";

        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            $result = $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    

    // subscribed to some categories
    function &getCategoryEntries($last_sent, $emanager, $published, $cats, $date_field = 'e.date_updated') {
                
        // need distinct because one entry could belong to many categories
        $sql = "SELECT DISTINCT 
            e.id, 
            {$emanager->title_field} AS 'title', 
            {$date_field} AS 'date',
            IF(date_posted > '{$last_sent}', 1, 0) AS 'new'
        FROM 
            ({$emanager->tbl->entry} e,
            {$emanager->tbl->category} cat,
            {$emanager->tbl->entry_to_category} e_to_cat)
        {$emanager->entry_role_sql_from}
        
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id        
            AND cat.active = 1
            AND e_to_cat.category_id IN(%s)
            AND e.active IN(%s)
            AND {$date_field} > '%s'
            AND %s
            AND NOT e.private & %d
            AND {$emanager->entry_role_sql_where}";                
                
        $sql = sprintf($sql, $cats, $published, $last_sent, $emanager->getCategoryRolesSql(false), $this->private_rule['list']);
        // echo print_r($sql, 1), "\n=============\n";

        $data = false;
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            $data = array(0=>array(), 1=>array());
            while($row = $result->FetchRow()) {
                $data[$row['new']][$row['id']] = array('title'=>$row['title'], 'date'=>$row['date']);
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $data;
    }
    

    // subscribed to all categories
    function &getAllEntries($last_sent, $emanager, $published, $date_field = 'e.date_updated') {
                
        // no need in distinct because of AND e.category_id = cat.id
        $sql = "SELECT 
            e.id, 
            {$emanager->title_field} AS 'title', 
            {$date_field} AS 'date',
            IF(date_posted > '{$last_sent}', 1, 0) AS 'new'
        FROM 
            ({$emanager->tbl->entry} e,
            {$emanager->tbl->category} cat)
        {$emanager->entry_role_sql_from}
        
        WHERE 1
            AND e.category_id = cat.id
            AND cat.active = 1
            AND e.active IN(%s)
            AND {$date_field} > '%s'
            AND %s
            AND NOT e.private & %d
            AND {$emanager->entry_role_sql_where}
            {$emanager->sql_params_group}";                
                
        $sql = sprintf($sql, $published, $last_sent, $emanager->getCategoryRolesSql(false), $this->private_rule['list']);
        // echo '<pre>', print_r($sql, 1), '</pre>';

        $data = false;
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            $data = array(0=>array(), 1=>array());
            while($row = $result->FetchRow()) {
                $data[$row['new']][$row['id']] = array('title'=>$row['title'], 'date'=>$row['date']);
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $data;
    }


    // get all available categories
    function getAllCategories($entry_type) {
        $table = ($entry_type == 11) ? $this->tbl->kb_category : $this->tbl->file_category;
        $sql = "SELECT id AS 'cid', id, parent_id FROM {$table}";
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
    
        return $result;
    }
    

    // COMMENTED // ----------

    function &getCommentedEntries($user, $type, $emanager, $published) {
        return $this->getUpdatedEntries($user, $type, $emanager, $published, 
                                            'e.date_commented', false);
    }


    function &getCommentedCategoryEntries($last_sent, $emanager, $published, $cats) {
        return $this->getCategoryEntries($last_sent, $emanager, $published, 'e.date_commented');
    }
        

    function &getAllCommentedEntries($last_sent, $emanager, $published) {
        return $this->getAllEntries($last_sent, $emanager, $published, 'e.date_commented');
    }
    

    function getLatestEntryDate($type) {
        if($type == 'article') {
            $sql = "SELECT MAX(date_updated) AS 'last_date' FROM {$this->tbl->kb_entry}";
            
        } elseif($type == 'file') {
            $sql = "SELECT MAX(date_updated) AS 'last_date' FROM {$this->tbl->file_entry}";
            
        } else {
            $sql = "SELECT MAX(date_posted) AS 'last_date' FROM {$this->tbl->kb_comment} WHERE active = 1";
        }
        
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