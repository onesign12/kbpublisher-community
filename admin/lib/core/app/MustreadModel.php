<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class MustreadModel extends AppModel
{

    var $custom_tables =  array(
        'entry_mustread', 'entry_mustread_to_rule', 'entry_mustread_to_user',
        'user', 'user_to_role', 'priv',
        'kb_entry', 'file_entry', 'news', 'entry_trash'
    );

    var $entry_type; 
    
    static $items = array(
        1 => array('type' => 'all'), 
        2 => array('type' => 'staff'),
        3 => array('type' => 'user', 'items' => true),
        4 => array('type' => 'priv', 'items' => true),
        5 => array('type' => 'role', 'items' => true)
    );
    

    function getUserMustreadsCount($user_id, $entry_type = false) {
        $type_sql = ($entry_type) ? sprintf('m.entry_type = %d', $entry_type) : 1;
        $sql = "SELECT COUNT(*) as num
            FROM {$this->tbl->entry_mustread} m, {$this->tbl->entry_mustread_to_user} m_to_user
            WHERE m.id = m_to_user.mustread_id
            AND m.active = 1
            AND {$type_sql}
            AND m_to_user.user_id = %d AND m_to_user.date_confirmed IS NULL";
            
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }


    function getUserMustreads($user_id, $entry_type = false, $force_read = false) {
        $type_sql = ($entry_type) ? sprintf('m.entry_type = %d', $entry_type) : 1;
        $force_sql = ($force_read !== false) ? sprintf('m.force_read = %d', $force_read) : 1;
        $sql = "SELECT m.entry_id, m.entry_type, m.force_read
            FROM {$this->tbl->entry_mustread} m, {$this->tbl->entry_mustread_to_user} m_to_user
            WHERE m.id = m_to_user.mustread_id
            AND m.active = 1
            AND {$type_sql} 
            AND {$force_sql}
            AND m_to_user.user_id = %d AND m_to_user.date_confirmed IS NULL";
            
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetArray();
    }


    // if user need confirm it
    function isEntryMustreadByUser($entry_id, $entry_type, $user_id) {
        $sql = "SELECT m.id 
            FROM {$this->tbl->entry_mustread} m, {$this->tbl->entry_mustread_to_user} m_to_user
            WHERE m.id = m_to_user.mustread_id
            AND m.entry_id = %d AND m.entry_type = %d AND m.active = 1 
            AND m_to_user.user_id = %d AND m_to_user.date_confirmed IS NULL";
            
        $sql = sprintf($sql, $entry_id, $entry_type, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }


    function isEntryMustread($entry_id, $entry_type) {
        $sql = "SELECT m.id 
            FROM {$this->tbl->entry_mustread} m
            WHERE m.entry_id = %d AND m.entry_type = %d AND m.active = 1";
            
        $sql = sprintf($sql, $entry_id, $entry_type);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }


    function markAsConfirmed($mustread_id, $user_id) {
        $sql = "UPDATE {$this->tbl->entry_mustread_to_user}
            SET date_confirmed = NOW() 
            WHERE mustread_id = %d AND user_id = %d";
        $sql = sprintf($sql, $mustread_id, $user_id);        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    

    // ACCOUNT // ----------------
    
    function getPopulatedMustreadForUserSql($user_id) {
        
        $pstatus = array();
        foreach (ListValueModel::getEntryPublishedStatuses('1,2,3') as $k => $v) {
            $pstatus[$k] = implode(',', $v);
        }
        
        $sql = "SELECT mr.*, mr_to_user.*, 
            IF(mr_to_user.date_confirmed, 1, 0) as status_read,
            -- COALESCE(f.filename, e.title, n.title) as title
            COALESCE(e.title, n.title) as title
            -- e.title as title
            
        FROM ({$this->tbl->entry_mustread_to_user} mr_to_user,
              {$this->tbl->entry_mustread} mr)
        
        LEFT JOIN {$this->tbl->kb_entry} e 
            ON mr.entry_id = e.id AND mr.entry_type = 1 
            AND e.active IN ({$pstatus[1]})
        
        -- LEFT JOIN {$this->tbl->file_entry} f
        --     ON mr.entry_id = f.id AND mr.entry_type = 2
        --     AND f.active IN ({$pstatus[2]})
        --
        LEFT JOIN {$this->tbl->news} n
            ON mr.entry_id = n.id AND mr.entry_type = 3
            AND n.active IN ({$pstatus[3]})
        
        WHERE mr_to_user.user_id = '{$user_id}'
            AND mr.id = mr_to_user.mustread_id
            -- AND COALESCE(e.id, f.id, n.id) 
            AND COALESCE(e.id, n.id) 
            -- AND e.id IS NOT NULL 
            AND {$this->sql_params}
            
        {$this->sql_params_order}";
        
        // echo '<pre>', print_r($sql,1), '<pre>';
        return $sql;
    }


    // ADMIN FUNCTIONS // -----------------

    function getMustreadByEntryId($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $sql = "SELECT * FROM {$this->tbl->entry_mustread}
        WHERE entry_id = '{$entry_id}'
        AND entry_type = '{$entry_type}'
        ORDER BY id DESC";
        $result = $this->db->SelectLimit($sql, 1) or die(db_error($sql));
        $mustread = ($fr = $result->FetchRow()) ? $fr : array();
        
        if($mustread) {
            $mustread_id = $mustread['id'];
        
            $sql = "SELECT rule, value FROM {$this->tbl->entry_mustread_to_rule}
            WHERE mustread_id = '{$mustread_id}'";
            $result = $this->db->Execute($sql) or die(db_error($sql));
        
            $data = array();
            while($row = $result->FetchRow()) {
                $rule = self::$items[$row['rule']]['type'];
                $mustread['rules'][$rule] = $row['rule'] ;
                if(isset(self::$items[$row['rule']]['items'])) {
                    $mustread[$rule][] = $row['value'];
                }
            }
        }
        
        return $mustread;
    }
    
    
    function getMustreadByEntryIds($entry_ids, $entry_type = false ) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $sql = "SELECT m.entry_id, m.* FROM {$this->tbl->entry_mustread} m
        WHERE entry_id IN ({$entry_ids})
        AND entry_type = '{$entry_type}'
        AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $mustreads = $result->GetAssoc();
        
        if($mustreads) {
            $mustread_ids = $this->getValuesString($mustreads, 'id');
        
            $sql = "SELECT mr.rule, mr.value, m.entry_id 
            FROM {$this->tbl->entry_mustread} m, {$this->tbl->entry_mustread_to_rule} mr
            WHERE m.id IN ({$mustread_ids})
            AND m.id = mr.mustread_id";
            $result = $this->db->Execute($sql) or die(db_error($sql));
        
            $data = array();
            while($row = $result->FetchRow()) {
                $rule = self::$items[$row['rule']]['type'];
                $mustreads[$row['entry_id']]['rules'][$rule] = $row['rule'] ;
                if(isset(self::$items[$row['rule']]['items'])) {
                    $mustreads[$row['entry_id']][$rule][] = $row['value'];
                }
            }
        }
        
        return $mustreads;
    }
    
    
    function deactivate($mustread_id) {
        $sql = "UPDATE {$this->tbl->entry_mustread} SET active = 0, date_created = NULL 
            WHERE id = '{$mustread_id}'";
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function emptyUsers($mustread_id) {
        $sql = "DELETE FROM {$this->tbl->entry_mustread_to_user} WHERE mustread_id = '{$mustread_id}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function updateMustread($values, $record_id, $entry_type = false) {
        
         // not to to delete not active mustreads
        // if(empty($values['on']) && $values['active'] == 0) {
        if(empty($values['on']) && empty($values['active'])) {
            return;
        }
        
        // not to delete but set inactive disabled mustreads by user in form
        if(empty($values['on']) && $values['id'] && $values['active'] == 1) { 
            $this->deactivate($values['id']);
            $this->emptyUsers($values['id']);
            return;
        }
        
        $reset_users = (!empty($values['reset']) || $values['active'] == 0) ? true : false;
        // $this->deleteByMustreadId($values['id'], $reset_users);
        $this->deleteByEntryId($record_id, $entry_type, $reset_users);
        $mustread_id = $this->saveMustread($values, $record_id, $entry_type);
        return $mustread_id;
    }
    
    
    function saveMustread($values, $record_id, $entry_type = false) {

        if(empty($values['on']) || empty($values['rules'])) { 
            return; 
        }
                
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);

        // normalize just in case
        if(isset($values['rules']['all'])) {
            $values['rules'] = array('all' => $values['rules']['all']);
        } elseif(isset($values['rules']['staff'])) {
            $values['priv'] = array();
        }
        
        $mustread_ids = array();
        
        // entry_mustread
        $insert_mustread_id = (empty($values['id'])) ? 'NULL' : $values['id'];
        $notify = (empty($values['notify'])) ? 0 : 1;
        $force_read = (empty($values['force_read'])) ? 0 : 1;
        $date_valid = ($values['date_valid_on']) ? $values['date_valid'] : 'NULL';
        
        // set inactive if valid is less then current
        // will help with expiered from drafts
        $active = 1; 
        if($values['date_valid_on']) {
            $active = ($values['date_valid'] >= date('Ymd')) ? 1 : 0;
        }
              
        $date_created = ($values['date_created']) ? $values['date_created'] : 'NOW()';
        $date_created = (!empty($values['reset'])) ? 'NOW()' : $date_created;
        
        foreach($record_id as $entry_id) {
            
            $data = array($insert_mustread_id, $entry_id, $entry_type, AuthPriv::getUserId(), 
                            $date_created, $date_valid, $values['note'], $notify, $force_read, $active);
            
            $sql = "REPLACE {$this->tbl->entry_mustread} 
            (id, entry_id, entry_type, updater_id, date_created, date_valid ,note, notify, force_read, active) VALUES ?";
            $sql = MultiInsert::get($sql, $data);
            $this->db->Execute($sql) or die(db_error($sql));
            $mustread_id = ($insert_mustread_id == 'NULL') ? $this->db->Insert_ID() : $insert_mustread_id;
            $mustread_ids[] = $mustread_id;
            
            // entry_mustread_to_rule
            $data = array();
            foreach($values['rules'] as $type => $type_num) {
                $val = (isset($values[$type])) ? array_filter($values[$type]) : array(0);
                foreach($val as $v) {
                    $data[] = array('mustread_id' => $mustread_id, 'type' => (int) $type_num, 'value' => (int) $v);
                }
            }
            
            if($data) {
                $sql = "INSERT {$this->tbl->entry_mustread_to_rule} (mustread_id, rule, value) VALUES ?";
                $sql = MultiInsert::get($sql, $data);
                $this->db->Execute($sql) or die(db_error($sql));
            }
        }
        
        return (count($mustread_ids) == 1) ? $mustread_id : $mustread_ids;
    }

    
    // delete mustread if no article 
    function deleteMissedMustreads($entry_type = false) {
        if(AppPlugin::isPlugin('mustread') === false) {
            return true;
        }
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $table = $this->record_type_to_table[$entry_type];
        
        $sql = "SELECT entry_id, entry_id AS eid  FROM {$this->tbl->entry_mustread} m
        LEFT JOIN {$this->tbl->$table} e ON e.id = m.entry_id 
        WHERE m.entry_type = '{$this->entry_type}'
        AND e.id IS NULL";
        $result = $this->db->_Execute($sql) or die(db_error($sql));
        $entry_ids = $result->GetAssoc();
        
        if($entry_ids) {
            $this->deleteByEntryId(implode(',', $entry_ids));
        }
    }
    
    
    // when delete article, delete all mustreads
    function deleteByEntryId($entry_id, $entry_type = false, $reset_users = true) {
        if(AppPlugin::isPlugin('mustread') === false) { //never installed, no table
            return true;
        }
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $uselect = ($reset_users) ? "mr, mr_to_rule, mr_to_user" : "mr, mr_to_rule";
        $ujoin   = ($reset_users) ? "LEFT JOIN {$this->tbl->entry_mustread_to_user} mr_to_user 
                                        ON mr.id = mr_to_user.mustread_id" : "";        
        $sql = "DELETE {$uselect}
            FROM {$this->tbl->entry_mustread} mr
            JOIN {$this->tbl->entry_mustread_to_rule} mr_to_rule ON mr.id = mr_to_rule.mustread_id
            {$ujoin}
            WHERE mr.entry_id IN ({$entry_id}) 
            AND mr.entry_type = '{$entry_type}'";
            
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    

    // when update artcle and want to replace mustread
    function deleteByMustreadId($id, $reset_users = true) {
        $uselect = ($reset_users) ? "mr, mr_to_rule, mr_to_user" : "mr, mr_to_rule";
        $ujoin   = ($reset_users) ? "LEFT JOIN {$this->tbl->entry_mustread_to_user} mr_to_user 
                                        ON mr.id = mr_to_user.mustread_id" : "";

        $sql = "DELETE {$uselect}
            FROM {$this->tbl->entry_mustread} mr
            JOIN {$this->tbl->entry_mustread_to_rule} mr_to_rule ON mr.id = mr_to_rule.mustread_id
            {$ujoin}
            WHERE mr.id IN ({$id})";
            
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    

    // SCHEULED TASKS, NOTIFY // --------------------
    
    function disactivateExpiredMustreads() {
        $sql = "UPDATE {$this->tbl->entry_mustread}
            SET active = 0
            WHERE active = 1 AND date_valid IS NOT NULL AND date_valid <= CURDATE()";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }

        return $this->db->affected_rows();
    }
    
    
    function getMustreadRecordsResult() {
        $sql = "SELECT * FROM {$this->tbl->entry_mustread} WHERE active = 1";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }


    function getMustreadFirstRunRecordsResult() {
        $sql = "SELECT mr.* FROM {$this->tbl->entry_mustread} mr
            WHERE mr.active = 1 
            AND NOT EXISTS (SELECT mr_to_user.mustread_id 
                FROM {$this->tbl->entry_mustread_to_user} mr_to_user 
                WHERE  mr.id = mr_to_user.mustread_id)";
                        
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }


    function getMustreadRule($mustread_id) {
        $sql = "SELECT * FROM {$this->tbl->entry_mustread_to_rule} WHERE mustread_id = %d";
        $sql  = sprintf($sql, $mustread_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['rule']][] = $row['value'];
        }
        
        return $data;
    }
    
    
    function _parseRoleSql($emanager, $roles, $table) {
        
        static $all_roles;
        if(empty($all_roles)) {
            $all_roles = $emanager->role_manager->getSelectRecords();
        }
    
        $no_private_priv = implode(',', $emanager->no_private_priv);
    
        $roles_sql = array('join' => '', 'where' => 1);
        if($roles) {
            foreach($roles as $role_id) {
                $roles += $emanager->role_manager->getParentRoles($all_roles, $role_id);
            }    
    
            $roles = implode(',', $roles);
            $roles_sql['join'] = "LEFT JOIN {$this->tbl->user_to_role} {$table}
                ON {$table}.user_id = u.id AND {$table}.role_id IN ({$roles})";
            $roles_sql['where'] = "({$table}.user_id IS NOT NULL OR priv.priv_name_id IN({$no_private_priv}))";
        }  
        
        return $roles_sql;  
    }
    
    
    function populateUsersToMustread($emanager, $mustread_id, $rules, $entry_roles, $cat_roles, $limit = false) {
        
        $affected_rows = 0;
            
        $entry_roles_sql = $this->_parseRoleSql($emanager, $entry_roles, 'role');
        $cat_roles_sql = $this->_parseRoleSql($emanager, $cat_roles, 'role2');
       
        $active_status = ListValueModel::getEntryPublishedStatuses(4);
        $active_status = implode(',', $active_status[4]);
        
        $select = "u.id, {$mustread_id}";
        
        $left_join = 
            "LEFT JOIN {$this->tbl->entry_mustread_to_user} mr_to_user 
                ON mr_to_user.mustread_id = '{$mustread_id}' AND mr_to_user.user_id = u.id
            LEFT JOIN {$this->tbl->priv} priv ON priv.user_id = u.id
            {$entry_roles_sql['join']}
            {$cat_roles_sql['join']}
            WHERE mr_to_user.user_id IS NULL 
            AND {$entry_roles_sql['where']}
            AND {$cat_roles_sql['where']}";

        foreach($rules as $rule => $value) {
            $rule_name = self::$items[$rule]['type'];
            $value_ids = implode(',', $value);
                
            switch ($rule_name) {
            
            case 'all': // ------------------------------
                $sql = "SELECT {$select}
                    FROM {$this->tbl->user} u 
                    {$left_join} 
                    AND u.active IN({$active_status})";
                break;
            
            case 'staff': // ------------------------------
                $sql = "SELECT {$select}
                    FROM ({$this->tbl->user} u, {$this->tbl->priv} p)
                    {$left_join}
                    AND u.id = p.user_id 
                    AND u.active IN({$active_status})";
                break;
                            
            case 'user': // ------------------------------
                $sql = "SELECT {$select}
                    FROM {$this->tbl->user} u 
                    {$left_join}
                    AND u.id IN ($value_ids) 
                    AND u.active IN({$active_status})";
                break;
            
            case 'priv': // ------------------------------
                $sql = "SELECT {$select}
                    FROM ({$this->tbl->user} u, {$this->tbl->priv} p)  
                    {$left_join}
                    AND p.priv_name_id IN ($value_ids) AND u.id = p.user_id 
                    AND u.active IN({$active_status})";
                break;
                            
            case 'role': // ------------------------------
                
                $value_ids = explode(',', $value_ids);
                foreach($value_ids as $v) {
                    $value_ids = array_merge($value_ids, $emanager->role_manager->getChildRoles(false, $v));
                }
                $value_ids = implode(',', array_unique($value_ids));
            
                $sql = "SELECT {$select}
                    FROM ({$this->tbl->user} u, {$this->tbl->user_to_role} r) 
                    {$left_join}
                    AND r.role_id IN ($value_ids) AND u.id = r.user_id 
                    AND u.active IN({$active_status})";
                break;
            }
            
            $sql . ' GROUP BY u.id'; 
            if($limit) { // on article update not to make huge insert
                $sql = $sql . sprintf(' ORDER BY lastauth DESC LIMIT %d', $limit);
            }
            
            $sql_insert = "INSERT IGNORE INTO {$this->tbl->entry_mustread_to_user} (user_id, mustread_id) ";
            $sql = $sql_insert . $sql; 
            
            $result = $this->db->Execute($sql);
            if (!$result) {
                return $this->db_error2($sql);
            }
            
            $affected_rows += $this->db->affected_rows();
        }
    
        return $affected_rows;
    }
    
    
    function getMustreadToNotifyResult() {
        $sql = "SELECT mr.* FROM  {$this->tbl->entry_mustread} mr
            WHERE mr.notify = 1 AND mr.active = 1";
    
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function getUsersToNotifyResult($days_after = 0) {
        
        if(!$days_after) {
            $where = 'mr_to_user.date_notified IS NULL';
        } else {
            $where = 'mr_to_user.date_confirmed IS NULL
                AND DATEDIFF(mr_to_user.date_added, CURDATE()) = -%d';
            $where = sprintf($where, $days_after);
        }
        
        $sql = "SELECT mr.*, mr_to_user.* 
            FROM ({$this->tbl->entry_mustread} mr, {$this->tbl->entry_mustread_to_user} mr_to_user)
            LEFT JOIN {$this->tbl->entry_trash} t ON t.entry_id = mr.entry_id AND t.entry_type = mr.entry_type
            WHERE mr.id = mr_to_user.mustread_id
            AND mr.notify = 1
            AND mr.active = 1
            AND t.id IS NULL /* not in trash */
            AND {$where}";
        
        $result = $this->db->Execute($sql);
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function markAsNotified($mustread_id, $user_id) {
        $sql = "UPDATE {$this->tbl->entry_mustread_to_user}
            SET date_notified = NOW() 
            WHERE mustread_id = '%d' AND user_id = '%d'";
        $sql = sprintf($sql, $mustread_id, $user_id);
        
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }
    
} 
?>