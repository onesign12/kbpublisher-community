<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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


class CommonEntryModel extends AppModel
{

    var $role_skip_categories = array();
    // var $categories = null;
    
    var $entry_role_sql_from;
    var $entry_role_sql_where = '1';
    
    // force index when will be assigned list view if no filter category
    var $entry_sql_force_index;
    
    
    function getById($record_id) {

        $sql = "SELECT 
            e.*, 
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            UNIX_TIMESTAMP(e.date_updated) AS tsu        
        FROM {$this->tbl->entry} e {$this->entry_role_sql_from}
        WHERE e.id = %d
        AND {$this->entry_role_sql_where}";
        
        $sql = sprintf($sql,$record_id);
        //echo '<pre>', print_r($sql, 1), '</pre>';
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }    
    
    
    // for home page
    function getStatRecords() {
        $s = ($this->sql_params_group) ? 'COUNT(DISTINCT(e.id))' : 'COUNT(*)';
        $sql = "SELECT e.active, {$s} AS 'num'
        FROM 
            ({$this->tbl->entry} e,
            {$this->tbl->category} cat)
            {$this->entry_role_sql_from}
            
        WHERE e.category_id = cat.id
        AND {$this->entry_role_sql_where}
        AND {$this->sql_params}
        GROUP BY e.active";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();
    }    
    
    
    // CATEGORIES // ---------------------------
    
    // return categories array for entry
    function &getCategoryById($record_id) {
        $data[1] = array();
        $data[0] = array();
        $sql = "SELECT category_id, is_main FROM {$this->tbl->entry_to_category} 
        WHERE entry_id IN ($record_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            $data[$row['is_main']][] = $row['category_id'];
        }
        
        $data = array_merge($data[1], $data[0]);
        return $data;
    }
    
    
    // get array with categories names to use in list records
    function &getCategoryByIds($record_id) {
        
        $sql = "
        SELECT
            c.name,         
            ec.entry_id,
            ec.is_main,
            ec.category_id,
            c.active
            
        FROM 
            {$this->tbl->category} c, 
            {$this->tbl->entry_to_category} ec
        WHERE 1
            AND ec.entry_id IN ($record_id) 
            AND c.id = ec.category_id
            ORDER BY ec.is_main DESC";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $data[$row['entry_id']][$row['category_id']]['title'] = $row['name'];
            $data[$row['entry_id']][$row['category_id']]['active'] = $row['active'];
        }
        
        return $data;
    }
    
    
    function getCategoryRecords() {
        return $this->cat_manager->getSelectRecords();
    }    
    
    
    // remove private from categories
    function &getCategoryRecordsUser($rows = false) {
        
        if(!$rows) {
            $rows = $this->getCategoryRecords();
        }
        
        foreach($this->role_skip_categories as $category_id) {
            if(isset($rows[$category_id])) {
                unset($rows[$category_id]);
            }
        }
        
        return $rows;
    }
        

    // range for form select
    function getCategorySelectRange($rows = false, $parent_id = 0, $pref = '-- ') {
        $rows = &$this->getCategoryRecordsUser($rows);
        return $this->cat_manager->getSelectRange($rows, $parent_id, $pref);
    }    
    
    
    // range for form select
    function getCategorySelectRangeFolow($rows = false, $parent_id = 0, $pref = ' -> ') {
        $rows = &$this->getCategoryRecordsUser($rows);
        return $this->cat_manager->getSelectRangeFolow($rows, $parent_id, $pref);
    }
    
    
    function getChilds($rows, $id) {
        
        $tree = new TreeHelper();
        foreach($rows as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }
        
        return $tree->getChildsById($id);
    }
    
    
    // AUTHORS & UPDATERS & DATES// ----------------
    
    function &getUser($user_id, $one_user = true) {
        $sql = "SELECT id AS user_id, id, username, first_name, last_name, email, phone 
        FROM {$this->tbl->user} WHERE id IN($user_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $ret = ($one_user) ? $result->FetchRow() : $result->GetAssoc();
        if(!$ret) { $ret = array(); }
        return $ret;
    }    
    
    
    function getLastViewed($id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "SELECT date_hit FROM {$this->tbl->entry_hits} 
        WHERE entry_id = {$id} AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('date_hit');
    }
    
    
    // ENTTRY ROLES/PRIVATE // ----------------------------
    
    function isUserPrivIgnorePrivate() {
        if(!AppPlugin::isPlugin('private')) {
            return true;
        }
        
        return in_array($this->user_priv_id, $this->no_private_priv);
    }
    
    
    function setEntryRolesSql($action = 'write', $for_drafts = false) {
        
        // no need private sql
        if($this->isUserPrivIgnorePrivate()) { 
            return;
        }
        
        if($this->user_role_id) {
            
            $user_role_ids = array();
            $user_role_ids_temp = $this->role_manager->getChildRoles(false, $this->user_role_id);
            foreach($user_role_ids_temp as $role_id => $role_ids) {
                $user_role_ids[] = $role_id;
                $user_role_ids = array_merge($user_role_ids, $role_ids);
            }
            
            // write
            $pattern = '(^|,)(' . implode('|', $user_role_ids) . ')(,|$)';
            $this->entry_role_sql_where = "IF(du.user_value, du.user_value REGEXP '{$pattern}', 1)";
            
            if($for_drafts) { 
                // if draft in worklow ignore all attributes
                // step_num = 0 means rejected to initail state, not in worklow
                $this->entry_role_sql_where 
                    = "IF(dw1.workflow_id AND dw1.step_num != 0, 1, 
                            IF(du.user_value, du.user_value REGEXP '{$pattern}', 1))";
            }
        
        } else {
            $user_role_ids = 123456789;
            $this->entry_role_sql_where = "du.data_value IS NULL"; // not show if any roles assigned
            
            if($for_drafts) { 
                $this->entry_role_sql_where = "IF(dw1.workflow_id AND dw1.step_num != 0, 1, du.data_value IS NULL)";
            }
        }
        
        $private = $this->private_rule[$action];
        $rule_id = ($action == 'write') ? $this->role_write_id : $this->role_read_id;
        
        $etable = ($for_drafts) ? 'd' : 'e' ;
        $this->entry_role_sql_from = "
            LEFT JOIN {$this->tbl->data_to_value_string} du 
            ON {$etable}.id = du.data_value 
            AND du.rule_id IN ({$rule_id})
            AND {$etable}.private & {$private}";
    }
    
    
    function setCategoriesNotInUserRole($action, $entries_ids = false) {
        $this->role_skip_categories = &$this->cat_manager->getCategoriesNotInUserRole($action);
    }
    

    function getCategoryRolesSql($category = true) {
        $role_skip_sql = array(1);
        if($category == 'e_to_cat.category_id') {
            $field = 'e_to_cat.category_id';
        } else {
            $field = ($category) ? 'c.id' : 'cat.id';
        }
        
        if($this->role_skip_categories) {
            $role_skip_sql = array();
            $role_skip_sql[] = sprintf('%s NOT IN(%s)', $field, implode(',', $this->role_skip_categories));
        }
        
        return implode(' ', $role_skip_sql);
    }
    
    
    function isCategoryNotInUserRole($categories) {
        return array_intersect($categories, $this->role_skip_categories);
    }
    
    
    // ROLES //
    
    function getChildRoles($rows, $id) {
        return $this->role_manager->getChildsById($id);
    }    
    
    
    function getRoleRecords() {
        return $this->role_manager->getSelectRecords();
    }        
    
    
    function getRoleSelectRange($arr = false) {
        return $this->role_manager->getSelectRangeFolow($arr, 0, ' :: ');
    }
    
    
    function getRoleRangeFolow($arr = false) {
        return $this->role_manager->getSelectRangeFolow($arr, 0, ' :: ');
    }
    
    
    // ENTRY TO ROLE // -----
    
    function _getRoleById($record_id, $role_rule_id, $list_view = false) {
        $select = ($list_view) ? '*' : 'dv.user_value, dv.user_value AS id1';
        return $this->dv_manager->getDataById($record_id, array($role_rule_id), $select, $list_view);
    }


    function getRoleReadById($record_id, $list_view = false) {
        return $this->_getRoleById($record_id, $this->role_read_id, $list_view);
    }


    function getRoleWriteById($record_id, $list_view = false) {
        return $this->_getRoleById($record_id, $this->role_write_id, $list_view);
    }


    function getRoleById($record_id, $list_view = false) {
        $ret['read'] = $this->getRoleReadById($record_id, $list_view);
        $ret['write'] = $this->getRoleWriteById($record_id, $list_view);
        return $ret;
    }


    function saveRoleReadToEntry($roles, $record_id) {
        $this->dv_manager->saveData($roles, $record_id, $this->role_read_id);
    }


    function saveRoleWriteToEntry($roles, $record_id) {
        $this->dv_manager->saveData($roles, $record_id, $this->role_write_id);
    }


    function saveRoleToEntry($private, $read_roles, $write_roles, $record_id) {
        if($private) {
            if(PrivatePlugin::isPrivateRead($private)) {
                $this->dv_manager->saveData($read_roles, $record_id, $this->role_read_id);
            }
                
            if(PrivatePlugin::isPrivateWrite($private)) {
                 $this->dv_manager->saveData($write_roles, $record_id, $this->role_write_id);
            }
        }
    }
    
    
    function saveRoleToEntryObj($obj, $id) {
        $this->saveRoleToEntry($obj->get('private'), $obj->getRoleRead(), $obj->getRoleWrite(), $id);
    }


    function deleteRoleToEntry($record_id) {
        $this->dv_manager->deleteData($record_id, $this->role_read_id);
        $this->dv_manager->deleteData($record_id, $this->role_write_id);
    }

    
    function deleteRoleWriteToEntry($record_id) {
        $this->dv_manager->deleteData($record_id, $this->role_write_id);
    }    
    
    
    function isEntryInUserRole($record_id) {
        $sql = "SELECT 1 FROM {$this->tbl->entry} e {$this->entry_role_sql_from}
        WHERE e.id = %d AND {$this->entry_role_sql_where}";
        $sql = sprintf($sql,$record_id);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return ($result->Fields(1));
    }    
       
    
    // STATUS, TYPE // ---------------------------------    
    
    function status($value, $id, $field = 'active', $keep_field = false) {
        return parent::status($value, $id, $field, 'date_updated');
    }
    
    
    function getListSelectRange($list_key, $active_only = true, $updated_value = false) {
        return ListValueModel::getListSelectRange($list_key, $active_only, $updated_value);
    }        

    
    function getEntryStatusData($list_key) {
        foreach(ListValueModel::getListData($list_key) as $list_value => $v) {
            $data[$v['list_value']] = array('title' => $v['title'],
                                            'color' => $v['custom_1']
                                            );
        }
        
        return $data;
    }    
    
    
    function getEntryStatusPublished($list_key) {
        $data = array();
        foreach(ListValueModel::getListData($list_key) as $list_value => $v) {
            if($v['custom_3'] == 1) {
                $data[$v['list_value']] = $v['list_value'];
            }
        }
        
        return $data;
    }
    
    
    // TAGS // ------------------------------------
    
    // used in bulk and triggers
    function setMetaKeywords($val, $ids) {
        $ids = (is_array($ids)) ? implode(',', $ids) : $ids;
        $delim = $this->tag_manager->getKeywordDelimeter();
        $val = ($val) ? implode($delim, $val) : '';

        $sql = "UPDATE {$this->tbl->entry}
        SET meta_keywords = '{$val}', date_updated = date_updated 
        WHERE id IN ($ids)";
        // if use sphinx we may need to update date_updated to reindex 
        // if not update index, it will be updated daily at noon

        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }

        return true;
    }
    
    
    // SORT // -------------------------------------
    
    function getSortRecords($category_id, $limit = false, $offset = 0) {
        $sql = "SELECT e.id, e.title AS 't', e_to_cat.sort_order AS 's'
        FROM 
            {$this->tbl->entry} e,
            {$this->tbl->entry_to_category} e_to_cat
        WHERE e_to_cat.category_id = '%d'
        AND e_to_cat.entry_id = e.id
        ORDER BY e_to_cat.sort_order";
        
        $sql = sprintf($sql, $category_id);
        
        if ($limit) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));           
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));        
        }
        
        return $result->GetAssoc();    
    }
    
    
    function getSortCurrentPosition($category_id, $entry_id) {
        $sql = "SELECT COUNT(*) AS num
        FROM {$this->tbl->entry_to_category} e_to_cat
        WHERE e_to_cat.category_id = '%d'
        AND e_to_cat.entry_id < '%d'
        ORDER BY e_to_cat.sort_order";
        
        $sql = sprintf($sql, $category_id, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');    
    }
  

    function getMaxSortOrder($category_ids) {
        $sql = "SELECT category_id, MAX(sort_order) AS max_sort_order
        FROM {$this->tbl->entry_to_category} 
        WHERE category_id IN ({$category_ids})
        GROUP BY category_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getMaxSortOrderValues($category) {
        
        $sort = array();
        $category_ids = $this->getCategoryIds($category);
        $sort_values = $this->getMaxSortOrder($category_ids);
    
        foreach(explode(',', $category_ids) as $category_id) {
            if(isset($sort_values[$category_id])) {
                $sort_values[$category_id] += 1;
            } else {
                $sort_values[$category_id] = 1;
            }
        }
        
        return $sort_values;
    }
    
    
    function getCategoryIds($category) {
        return implode(',', $category);
    }    
    
    // get sort order values using entry_is an d categories
    function getSortOrder($entry_id, $category_ids) {
        $sql = "SELECT category_id, sort_order FROM {$this->tbl->entry_to_category}
        WHERE entry_id = '{$entry_id}' AND category_id IN ({$category_ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    // get sort order values using entry_is
    // actually the same as getSortOrder($entry_id, $category_ids) {
    function getSortOrderByEntryId($entry_id) {
        $sql = "SELECT category_id, sort_order FROM {$this->tbl->entry_to_category}
        WHERE entry_id IN ({$entry_id})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getSortOrderValues($entry_id, $category) {
        $category_ids = $this->getCategoryIds($category);
        return $this->getSortOrder($entry_id, $category_ids);
    }
    
    
    //  return sort val and do nessesary updates in sort_order field
    function updateSortOrder($entry_id, $new_sort, $category, $action) {

        $category_ids = $this->getCategoryIds($category);
        
        $current_sort = array();
        if($action == 'update') {
            $current_sort = $this->getSortOrder($entry_id, $category_ids);
        }        
        
        if($action == 'insert') {
            $max_sort = $this->getMaxSortOrderValues($category);
        }
                
        $sort = new TableSortOrder();
        $sort->db = &$this->db;
        $sort->table = $this->tbl->entry_to_category;
        $sort->name_field = 'title';
        $sort->sort_field = 'sort_order';
        $sort->id_field = 'entry_id';
    
        $sort_value = array();
        foreach(explode(',', $category_ids) as $category_id) {
            
            if (isset($new_sort[$category_id]) && is_numeric($new_sort[$category_id])) { // already set
                $sort_value[$category_id] = $new_sort[$category_id];
                continue;
            }
            
            $new_so = (isset($new_sort[$category_id])) ? $new_sort[$category_id] : 'sort_end';

            if($new_so == 'sort_end' && $action == 'insert') {
                $sort_value[$category_id] = $max_sort[$category_id];
                continue;
            }
        
            //get the sort order
            if($new_so != 'sort_begin' && $new_so != 'sort_end') {
                
                // strip last param if any commented 16 May 2017 eleontev
                // now this function also accept soting like this 12_2
                // $new_so = substr($new_so, 0, strrpos($new_so, '_'));
                
                list($entry_id, $new_so) = explode('_', $new_so);
                $new_so = ($action == 'insert') ? $new_so+1 : $new_so;
            }
        
            //echo "<pre>"; print_r($new_so); echo "</pre>";
            //exit;
        
            $sort->setMoreSql("category_id = '{$category_id}'");
            $current_so = (isset($current_sort[$category_id])) ? $current_sort[$category_id] : 'none';
            
            if($current_so == 'none' && $action == 'update' && is_numeric($new_so)) {
                $new_so ++;
            }

            $sort_value[$category_id] = $sort->getDoSort($new_so, $entry_id, $current_so);
        }
         
        return $sort_value;    
    }    
    

    // not sure do we need it ???
    function updateSortOrderOnDelete($record_id) {
        
        $sort_values = $this->getSortOrderByEntryId($record_id);
        
        $sort = new TableSortOrder();
        $sort->db = &$this->db;
        $sort->table = $this->tbl->entry_to_category;
        $sort->name_field = 'title';
        $sort->sort_field = 'sort_order';
        
        foreach($sort_values as $category_id => $sort_value) {
            $sort->setMoreSql("category_id = '{$category_id}'");
            $sort->updateOnDelete($sort_value);
        }
    }
    
    
    function saveSortOrder($id, $category_id, $sort_order) {
        $sql = "UPDATE {$this->tbl->entry_to_category} 
            SET sort_order = '{$sort_order}'
            WHERE entry_id = '{$id}'
                AND category_id = '{$category_id}'";
            $this->db->Execute($sql) or die(db_error($sql));
    }
    

    // ENTRY TO CATEGORY
    
    function saveEntryToCategory($cat, $record_id, $sort_order, $add_categories = false) {
        
        $data = array();
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        $cat = (is_array($cat)) ? $cat : array($cat);
        
        $i = ($add_categories) ? 2 : 1; // all not main
        foreach($cat as $cat_id) {
            $sort = false;
            foreach($record_id as $entry_id) {
                $sort = (!$sort) ? $sort_order[$cat_id] : ++$sort;
                $is_main = ($i == 1) ? 1 : 0;
                $data[] = array($cat_id, $is_main, $entry_id, $sort);
            }
            
            $i++;
        }        
        
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->entry_to_category} (category_id, is_main, entry_id, sort_order) 
                                 VALUES ?", $data);
        
        //echo "<pre>"; print_r($data); echo "</pre>";
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
        
    
    // SCHEDULE // -------------------------
    
    function getScheduleByEntryId($entry_id, $entry_type = false) {
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $sql = "SELECT num, UNIX_TIMESTAMP(date_scheduled) as 'date', value as 'st', notify, note
        FROM {$this->tbl->entry_schedule} 
        WHERE entry_id = '{$entry_id}' 
        AND entry_type = '{$entry_type}'
        AND active = 1 
        ORDER BY num";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
    
    
    // in list
    function getScheduleByEntryIds($entry_ids, $entry_type = false) {
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $sql = "SELECT entry_id, num, date_scheduled as 'date', value as 'st', notify, note
        FROM {$this->tbl->entry_schedule} 
        WHERE entry_id IN ({$entry_ids}) 
        AND entry_type = '{$entry_type}'
        AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']][$row['num']] = $row;
        }
        
        return $data;
    }
    
    
    function getScheduleRepeatRange() {
        
        $range_required = array(
            'month_1', 'month_3', 'month_6', 'year_1', 'custom'
        );
        
        $range_msg = AppMsg::getMsgs('datetime_msg.ini', false, 'repeat_range');
        
        $range = array();
        foreach($range_required as $v) {
            $range[$v] = $range_msg[$v];
        }
        
        return $range;
    }
    
    
    function getScheduleFrequencyRange() {
        
        $range_required = array(
            'day', 'week', 'month', 'year'
        );
        
        $range_msg = AppMsg::getMsgs('datetime_msg.ini', false, 'frequency_range');
        
        $range = array();
        foreach($range_required as $v) {
            $range[$v] = $range_msg[$v];
        }
        
        return $range;
    }
    
    
    function saveSchedule($values, $record_id, $entry_type = false) {

                
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $data = array();
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);

        foreach($values as $num => $v) {
            foreach($record_id as $entry_id) {
                $data[] = array($entry_id, $entry_type, $num, $v['date'], $v['st'], 1, 1, $v['note']);
            }
        }
        
        $sql = "INSERT IGNORE {$this->tbl->entry_schedule} (entry_id, entry_type, num, date_scheduled, value, notify, active, note) VALUES ?";
        $sql = MultiInsert::get($sql, $data);
    
        //echo "<pre>"; print_r($values); echo "</pre>";
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r($_POST, 1), '</pre>';
        //exit;        
        
        if($data) {        
            return $this->db->Execute($sql) or die(db_error($sql));            
        }    
    }
    
    
    function deleteSchedule($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "DELETE FROM {$this->tbl->entry_schedule} 
        WHERE entry_id IN ({$record_id}) AND entry_type = '{$entry_type}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }    
    
    
    // LOCK // ----------------------------
    
    function setEntryLocked($record_id, $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;
        
        $sql = "REPLACE {$this->tbl->lock} SET 
        entry_id = '{$record_id}',
        entry_type = '{$entry_type}', 
        user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function setEntryReleased($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;    
        $sql = "DELETE FROM {$this->tbl->lock} WHERE entry_id = '{$record_id}' AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function isEntryLocked($record_id, $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT 1 FROM {$this->tbl->lock}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'
        AND user_id != '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }


    function getEntryLockedData($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;    
        $sql = "SELECT * FROM {$this->tbl->lock}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // AUTOSAVE // ----------------------------
    
    function autosave($id_key, $record_id, $entry_obj, $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;
        
        $sql = "[ACTION] {$this->tbl->autosave}
        SET id_key = '{$id_key}',
        entry_id = '{$record_id}',
        entry_type = '{$entry_type}', 
        user_id = '{$user_id}',
        entry_obj = '{$entry_obj}',
        active = 1 [WHERE]";
        
        $result = $this->_updateAutosave($sql, $id_key);
        if(!$result) {
            $this->_addAutosave($sql);
        }
    }

    
    function _updateAutosave($sql, $id_key) {
        $sql = str_replace(array('[ACTION]', '[WHERE]'), array('UPDATE', "WHERE id_key = '$id_key'"), $sql);
        $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Affected_Rows();
    }
    
    
    function _addAutosave($sql) {
        $sql = str_replace(array('[ACTION]', '[WHERE]'), array('INSERT IGNORE', ''), $sql); 
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteAutosave($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;    
        $sql = "DELETE FROM {$this->tbl->autosave} WHERE entry_id = '{$record_id}' AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteAutosaveByKey($key) {
        $sql = "DELETE FROM {$this->tbl->autosave} WHERE id_key = '{$key}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function isAutosaved($record_id, $date_updated, $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT 1 FROM {$this->tbl->autosave}
        WHERE entry_id = '{$record_id}'
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'
        AND date_saved > '{$date_updated}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }


    function getAutosavedData($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;    
        $sql = "SELECT * FROM {$this->tbl->autosave}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getAutosavedDataByKey($id_key) {
        $sql = "SELECT * FROM {$this->tbl->autosave} WHERE id_key = '{$id_key}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        return $result->FetchRow();
    }
    
    
    // DRAFTS // ------------------------------
 
    function isEntryDrafted($record_id, $entry_type = false) {
        if(!AppPlugin::isPlugin('draft')) {
            return false;
        }
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "SELECT id FROM {$this->tbl->draft}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }

    
    function getDraftedEntries($record_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "SELECT entry_id, entry_id AS eid 
        FROM {$this->tbl->draft}
        WHERE entry_id IN ({$record_id}) 
        AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function isApprovalLogAvailable($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "SELECT COUNT(*) as num
            FROM {$this->tbl->workflow_history}
            WHERE entry_id  = '{$entry_id}'
                AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }


    function getEntryApprovalLog($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "SELECT dw.*,
                u.username,
                u.first_name,
                u.last_name

            FROM {$this->tbl->workflow_history} dw
            LEFT JOIN {$this->tbl->user} u ON dw.user_id = u.id

            WHERE entry_id  = '{$entry_id}'
            AND entry_type = '{$entry_type}'
            ORDER BY dw.date_posted ASC";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();

        $data = array();
        foreach ($rows as $row) {
            $data[$row['draft_id']][] = $row;
        }

        return $data;
    }
    
    
    // HIT // ------------------------------
    
    function addHitRecord($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "INSERT {$this->tbl->entry_hits} SET entry_id = %d, entry_type = %d, hits = 0";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteHitRecord($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "DELETE FROM {$this->tbl->entry_hits} 
        WHERE entry_id IN (%s) AND entry_type = %d AND hits = 0";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // SUBSCRIPTION // -----------------------
    
    function isEntrySubscribedByUser($record_id,  $entry_type = false, $user_id = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $user_id = ($user_id) ? $user_id : $this->user_id;        
        
        $sql = "SELECT 1 FROM {$this->tbl->user_subscription}
        WHERE entry_id IN ({$record_id}) 
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }
    
    function deleteSubscription($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "DELETE FROM {$this->tbl->user_subscription} 
        WHERE entry_id IN (%s) AND entry_type = %d";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // MAIL // -----------------------------
        
    function getStatusKey($entry_id, $list_id) {
        $sql = "SELECT l.list_key 
        FROM 
            {$this->tbl->entry} e,
            {$this->tbl->list_value} l
        WHERE e.id = '{$entry_id}'
        AND l.list_id = '{$list_id}'
        AND l.list_value = e.active";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('list_key');    
    }
    
    
    // FEATURED // -----------------------------
    
    function increaseFeaturedEntrySortOrder($category_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "UPDATE {$this->tbl->entry_featured}
        SET sort_order = sort_order + 1
        WHERE category_id IN ({$category_id})
            AND entry_type = '{$entry_type}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteFeaturedEntry($entry_id, $category_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "DELETE FROM {$this->tbl->entry_featured}
            WHERE entry_id = {$entry_id}
                AND category_id = '{$category_id}'
                AND entry_type = '{$entry_type}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // TRIGGERS // -----------------------------
    
    function saveStates($initial_states, $final_states) {
        
                
        $data = array();        
        
        if (!empty($final_states['id'])) {
            $initial_states = array($initial_states);
            $final_states = array($final_states);
        }
        
        foreach($initial_states as $id => $state) {
            $initial_state = ($state) ? addslashes(serialize($state)) : '';
            $final_state = $final_states[$id];
            
            $entry_id = $final_state['id'];
            $final_state = addslashes(serialize($final_state));
            
            $data[] = array($entry_id, $initial_state, $final_state);
        }
        
        
        $sql = MultiInsert::get("INSERT {$this->tbl->trigger_to_run} (entry_id, state_before, state_after, entry_type) 
                                 VALUES ?", $data, array($this->entry_type));
                                 
        // $result = $this->db->Execute($sql);
        // if(!$result) {
        //     return $this->db_error2($sql);
        // }
        
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    
    // NEED IN API ----------------------------------
    
    function isCategoryExists($category_ids) {
        
        $category_num = is_array($category_ids) ? count($category_ids) : count(explode(',', $category_ids));
        $category_ids = is_array($category_ids) ? implode(',', $category_ids) : $category_ids;
        
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->category} WHERE id IN (%s)";
        $sql = sprintf($sql, $category_ids);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $found_num = $result->Fields('num');
        
        // echo '<pre>', print_r($found_num,1), '<pre>';
        // echo '<pre>', print_r($category_num,1), '<pre>';
        // exit;
        
        return ($found_num < $category_num) ? false : true;
    }
    
    
    // IN CRON // ----------------------------------------
    
    function isEntryAvailable($entry_id) {
        
        $pstatus = $this->getEntryStatusPublishedConcrete();
        $pstatus = implode(',', $pstatus);
        
        $sql = "SELECT id FROM {$this->tbl->table} WHERE id = %d AND active IN (%s)";
        $sql = sprintf($sql, $entry_id, $pstatus);
        $result = $this->db->Execute($sql);
        
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        // echo '<pre>', print_r($sql,1), '<pre>';
        $is_entry = $result->Fields('id');
        
        
        // check if at least one category is published
        // if($is_entry && $this->tbl->category) {
        if($is_entry && property_exists($this->tbl, 'category')) {
            $sql = "SELECT COUNT(*) as num 
                FROM {$this->tbl->category} c, {$this->tbl->entry_to_category} e_to_cat
                WHERE c.id = e_to_cat.category_id
                AND c.active = 1";
            $result = $this->db->Execute($sql);
        
            if (!$result) {
                trigger_error($this->db->ErrorMsg());
                return false;
            }
            
            $is_entry = $result->Fields('num');
        }
        
        return $is_entry;
    }
    
    // HISTORY // -------------------
    
    function getHistoryNum($entry_ids) {
        if(!AppPlugin::isPlugin('history')) {
            return [];
        }
        
        $sql = "SELECT entry_id, COUNT(*) FROM {$this->tbl->entry_history}
        WHERE entry_id IN($entry_ids) GROUP BY entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
}
?>