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


class CommonCategoryModel extends AppModel
{

    var $role_skip_categories = array();


    function getRecordsSql() {

        $sql = "
        SELECT
            c.*,
            c.id AS category_id
        FROM
            {$this->tbl->category} c
        WHERE {$this->sql_params}
        {$this->sql_params_order}";

        return $sql;
    }


    function &getRecords() {
        $data = array();
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);

        while($row = $result->FetchRow()){
            $data[$row['id']] = $row;
        }

        return $data;
    }


    function getEntriesNum($ids) {
        $sql = "SELECT category_id, COUNT(*) FROM {$this->tbl->entry_to_category}
        WHERE category_id IN($ids) GROUP BY category_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getDraftsNum($ids) {
        if(!AppPlugin::isPlugin('draft')) {
            return array();
        }
        
        if($this->entry_type == 11) {
            $dmanager = new KBDraftModel();
        } else {
            $dmanager = new FileDraftModel();
        }
        
        return $dmanager->getDraftsNumPerCategory($ids);
    }


    // get helper array in format id=>level
    function &getTreeHelperArray($arr, $parent_id = 0, $add_parent = false) {

        if(!$arr) {
            $data = array();
            return $data;
        }

        $tree = new TreeHelper();
        foreach($arr as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }

        return $tree->getTreeHelper($parent_id);
    }


    // FOR FORM SELECT // ---------------------

    function getSelectRangeByParentId($parent_id) {
        $sql = "SELECT id, name
        FROM {$this->tbl->category} c
        WHERE {$this->sql_params}
        AND c.parent_id IN($parent_id)
        ORDER BY name";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // just for select
    function getSelectRecords() {
        $sql = "SELECT id as id1, id, parent_id, name, sort_order, private, active
        FROM {$this->tbl->category} c
        WHERE {$this->sql_params}
        ORDER BY name";
        
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result->GetAssoc();
    }


    // get private categories ids
    function getCategoryPrivateIds($action = 'write') {
        $p = $this->private_rule[$action];
        $sql = "SELECT id as id1, id FROM {$this->tbl->category} c WHERE private & {$p}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // to generate form select range $arr from getSelectRecords
    function &getSelectRange($arr, $parent_id = 0, $pref = '-- ') {

        if($arr === false) {
            $arr = $this->getSelectRecords();
        }

        $data = array();
        if(!$arr) {
            return $data;
        }

        $tree_helper = &$this->getTreeHelperArray($arr, $parent_id);
        foreach($tree_helper as $id => $level) {

            $p = ($level == 0) ? '' : str_repeat($pref, $level);
            $data[$id] = $p . $arr[$id]['name'];
        }

        return $data;
    }


    // to generate form select range $arr from getSelectRecords
    function &getSelectRangeFolow($arr = false, $parent_id = 0, $pref = ' -> ') {

        if($arr === false) {
            $arr = $this->getSelectRecords();
        }

        $data = array();
        if(!$arr) {
            return $data;
        }

        $tree_helper = &$this->getTreeHelperArray($arr, $parent_id);
        foreach($tree_helper as $id => $level) {

            if($level == 0) {
                $data[$id] = $arr[$id]['name'];
                $prev[$level] = $arr[$id]['name'];

            } else {
                $data[$id] = $prev[$level-1] . $pref . $arr[$id]['name'];
                $prev[$level] = $data[$id];
            }
        }

        return $data;
    }


    // , $include_self_id = true
    function getChildCategories($arr, $cat_id) {

        if($arr === false) {
            $arr = $this->getSelectRecords();
        }

        $tree = new TreeHelper();
        foreach($arr as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }

        return $tree->getChildsById($cat_id);
    }


    function getChilds($rows, $id) {
        return $this->getChildCategories($rows, $id);
    }


    function getParentCategories($arr, $cat_id) {

        if($arr === false) {
            $arr = $this->getSelectRecords();
        }
        
        return TreeHelperUtil::getParentsById($arr, $cat_id);
    }


    function getParents($rows, $id) {
        return $this->getParentCategories($rows, $id);
    }


    // get all records, ignore private
    function getSortRecords($parent_id) {
        $sql = "SELECT id as id1, id, parent_id, name, sort_order
        FROM {$this->tbl->category} c
        WHERE {$this->sql_params}
        AND parent_id = '{$parent_id}'
        ORDER BY sort_order";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getCategorySortPublicSelectRange() {
        
        $types = array();
        $types[11]['module'] = 'knowledgebase';
        $types[11]['setting_id'] = 104; // entry_sort_order
        $types[12]['module'] = 'file';
        $types[12]['setting_id'] = 118; // entry_sort_order
        
        $msg = AppMsg::getMsgs('setting_msg.ini', $types[$this->entry_type]['module'], 'entry_sort_order');
        // $msg = array_filter($msg, function($k) { return (strpos($k, 'option_') !== false); }, ARRAY_FILTER_USE_KEY);
        foreach(array_keys($msg) as $k2) {
            if(strpos($k2, 'option_') === false) {
                unset($msg[$k2]);
            }
        }
        
        $sm = new SettingModel();
        $defaults = $sm->getRangeById($types[$this->entry_type]['setting_id']);// entry_sort_order
        $defaults = explode(',', $defaults);
    
        $range = array_combine($defaults, $msg);
        return $range;
    }


    // CATEGORY PRIVATE/ROLES // ----------------------------

    function isUserPrivIgnorePrivate() {
        if(!AppPlugin::isPlugin('private')) {
            return true;
        }
        
        return in_array($this->user_priv_id, $this->no_private_priv);
    }


    function &getCategoriesNotInUserRole($action = 'write', $entries_ids = false) {

        $data = array();
        if($this->isUserPrivIgnorePrivate()) {
            return $data;
        }

        $private_write_cats = $this->getCategoryPrivateIds($action);
        if(!$private_write_cats) {
            return $data;
        }

        if($entries_ids) {
            $entries_ids = is_array($entries_ids) ? $entries_ids : explode(',', $entries_ids);
            $entries_ids = array_intersect($entries_ids, $private_write_cats);
        } else {
            $entries_ids = &$private_write_cats;
        }


        $user_role_ids = array();
        if($this->user_role_id) {
            $user_role_ids_temp = $this->role_manager->getChildRoles(false, $this->user_role_id);
            foreach($user_role_ids_temp as $role_id => $role_ids) {
                $user_role_ids[] = $role_id;
                $user_role_ids = array_merge($user_role_ids, $role_ids);
            }
        }


        $m = DataToValueModel::instance();

        $action = (is_array($action)) ? $action : array($action);
        foreach($action as $_action) {
            $rule_id = ($_action == 'write') ? $this->role_write_id : $this->role_read_id;
            $rule = array($rule_id);
            $data_to_role = $m->getDataIds($rule, $entries_ids);

            foreach($data_to_role as $cat_id => $roles) {
                $result = array_intersect($user_role_ids, $roles);
                if(!$result) {
                    $data[$cat_id] = $cat_id;
                }
            }
        }

        // echo "<pre>User role ids: "; print_r($user_role_ids); echo "</pre>";
        // echo "<pre>Categories not in user role: "; print_r($data); echo "</pre>";
        return $data;
    }


    function getPrivateParams() {
        $ret = false;
        $private = $this->getCategoriesNotInUserRole('write');
        if($private) {
            $ret = sprintf('AND id NOT IN(%s)', implode(',', $private));
        }

        return $ret;
    }
    
    
    function isPrivate($id) {
        $sql = "SELECT private FROM {$this->tbl->category} WHERE id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('private');
    }    


    // ADMIN USER //-----------------

    function _getAdminUserById($record_id, $list_view = false) {
            $select = ($list_view) ? '*' : 'dv.user_value, dv.user_value AS id1';
            return $this->dv_manager->getDataById($record_id,
                                           $this->admin_user_id,
                                            $select,
                                            $list_view);
    }


    function saveAdminUserToCategory($users, $record_id) {
        $this->dv_manager->saveData($users, $record_id, $this->admin_user_id);
    }


    function deleteAdminUserToCategory($record_id) {
        $this->dv_manager->deleteData($record_id, $this->admin_user_id);
    }


    // used when wrong submit to generate list
    function getAdminUserByIds($ids) {
        $sql = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name)
        FROM {$this->tbl->user} u
        WHERE u.id IN ($ids)";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getAdminUserById($record_id, $list_view = false) {

        $data = array();
        $ids = $this->_getAdminUserById($record_id, $list_view);

        if($ids) {
            if($list_view) {
                foreach($ids as $category_id => $v) {
                    $data[$category_id] = $this->getAdminUserByIds(implode(',', $v));
                }

            } else {
                $data = $this->getAdminUserByIds(implode(',', $ids));
            }
        }

        return $data;
    }
    
    
    function &getUser($user_id, $one_user = true) {
        // $sql = "SELECT id, username, first_name, last_name, email, phone  // 2018-02-22 eleontev
        $sql = "SELECT id AS user_id, id, username, first_name, last_name, email, phone 
        FROM {$this->tbl->user} WHERE id IN($user_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $ret = ($one_user) ? $result->FetchRow() : $result->GetAssoc();
        if(!$ret) { $ret = array(); }
        return $ret;
    }   
    
    
    // SUPERVISORS (admin users) for automation, drafts, etc.
    
    // goes recursively from $category_id to parents and return first found supervisor
    function getSupervisorsByCatId($category_id, $categories = array(), $supervisors_arr = array()) {
        
        if($supervisors_arr) {
            $supervisors = (isset($supervisors_arr[$category_id])) ? $supervisors_arr[$category_id] : array();
        } else {
            $supervisors = $this->getAdminUserById($category_id, true);
            $supervisors = (isset($supervisors[$category_id])) ? $supervisors[$category_id] : array();
        }

        if (empty($supervisors)) {
            if (!$categories) {
                $categories = $this->getSelectRecords();
            }

            $parent_id = $categories[$category_id]['parent_id'];
            return ($parent_id) ? $this->getSupervisorsByCatId($parent_id, $categories, $supervisors_arr) : array();

        } else {
            return array_keys($supervisors);
        }
    }
    
    
    // used in automations and drafts when smth asigned to category suppervisors
    // if found in main category, skip all other category supervisors
    // else return all found supervisors
    function getSupervisors($category_ids, $categories = array(), $supervisors_arr = array()) {

        $supervisors = array();
        // $category_ids = (is_array($category_ids)) ? $category_ids : array($category_ids);
        $main_category_id = $category_ids[0];
        
        foreach($category_ids as $category_id) {
            
            $supervisors_cat = $this->getSupervisorsByCatId($category_id, $categories, $supervisors_arr);
            
            // collect all supervisors
            $supervisors = array_merge($supervisors, $supervisors_cat);
            
            // it will work for first row only, it is main category
            if($supervisors && $category_id == $main_category_id) {
                break;
            }
        }
        
        $supervisors = array_unique($supervisors);
        
        return $supervisors;
    }
    
    
    // returns an array containing supervisors for given $entry_to_categories 
    // and their parents
    function getSupervisorsArray($entry_to_categories, $categories) {
        
        $category_ids = array();
        
        foreach ($entry_to_categories as $entry_id => $v) {
            $cat_ids = array_keys($v);
            $category_ids = array_merge($category_ids, $cat_ids);
            
            foreach ($cat_ids as $cat_id) { // adding all parents
                $parent_ids = TreeHelperUtil::getParentsById($categories, $cat_id);
                $category_ids = array_merge($category_ids, $parent_ids);
            }
        }
        
        $category_ids = array_unique($category_ids);
        $category_ids = implode(',', $category_ids);
        
        $cat_to_supervisor = $this->getAdminUserById($category_ids, true);
        
        return $cat_to_supervisor;
    }


    // ROLES // ----------

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


    // CATEGORY TO ROLE // -----

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


    function saveRoleToCategory($private, $read_roles, $write_roles, $record_id) {
        if(!AppPlugin::isPlugin('private')) {
            return;
        }
        
        if(PrivatePlugin::isPrivateRead($private)) {
            $this->dv_manager->saveData($read_roles, $record_id, $this->role_read_id);
        }
            
        if(PrivatePlugin::isPrivateWrite($private)) {
             $this->dv_manager->saveData($write_roles, $record_id, $this->role_write_id);
        }
    }
    
    
    function saveRoleToCategoryObj($obj, $id) {
        $this->saveRoleToCategory($obj->get('private'), $obj->getRoleRead(), $obj->getRoleWrite(), $id);
    }    


    function deleteRoleToCategory($record_id) {
        $this->dv_manager->deleteData($record_id, $this->role_read_id);
        $this->dv_manager->deleteData($record_id, $this->role_write_id);
    }


/*
    function addRoleToChildCategory($private, $read_roles, $write_roles, $record_id) {
        if(PrivatePlugin::isPrivateRead($private)) {
            $this->dv_manager->saveData($read_roles, $record_id, $this->role_read_id);
        }
            
        if(PrivatePlugin::isPrivateWrite($private)) {
             $this->dv_manager->saveData($write_roles, $record_id, $this->role_write_id);
        }
    }
    
    
    function addRoleToChildCategoryObj($obj, $id) {
        $this->saveRoleToCategory($obj->get('private'), $obj->getRoleRead(), $obj->getRoleWrite(), $id);
    }*/



    // ACTIONS // ---------------------

    //  return sort val and do nessesary updates in sort_order field
    function sortManipulation(&$obj) {

        $parent_id = $obj->get('parent_id');

        $sort = new TableSortOrder();
        $sort->db = &$this->db;
        $sort->table = $this->tbl->category;
        $sort->name_field = 'name';
        $sort->sort_field = 'sort_order';
        $sort->setMoreSql("parent_id = '{$parent_id}'",
                          "parent_id = '{$parent_id}'");

        $sort->new_after_item_addon = ($obj->get('id')) ? 0 : 1;
        $val = $sort->getDoSort($obj->get('sort_order'), $obj->get('id'));
        $obj->set('sort_order', $val);
    }


    function save($obj, $action = 'insert') {

        $this->sortManipulation($obj);
        $obj->set('active_real', $obj->get('active'));
        
        if(in_array($action, array('insert', 'clone'))) {
            $id = $this->add($obj);
            
            $this->saveRoleToCategoryObj($obj, $id);
            $this->saveAdminUserToCategory($obj->getAdminUser(), $id);
            
            //apply to childs
            // if($obj->get('private')) {
            //     if($childs = $this->getChilds(false, $id)) {
            //         $this->setChildCategoryPrivate($id);
            //         $this->addRoleToChildCategoryObj($obj, $id);
            //     }    
            // }
            
            // status to child
            $this->statusChild($obj->get('active'), $id);

        } else {

            $id = (int) $obj->get('id');
            $this->update($obj, $id);

            $this->deleteRoleToCategory($id);
            $this->saveRoleToCategoryObj($obj, $id);

            $this->deleteAdminUserToCategory($id);
            $this->saveAdminUserToCategory($obj->getAdminUser(), $id);
            
            // status to child
            $this->statusChild($obj->get('active'), $id);
        }

        return $id;
    }
    
    
    // SORT // -----------------------
    
    function saveSortOrder($ids) {
        $sort_order = 1;
        foreach ($ids as $id) {
            $this->updateSortOrder($id, $sort_order);
            $sort_order ++;
        }
    }
    
    
    function updateSortOrder($id, $sort_order) {
        $sql = "UPDATE {$this->tbl->table} SET sort_order = %s WHERE id = %s";
        $sql = sprintf($sql, $sort_order, $id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    // SUBSCRIPTION // -----------------------

    function deleteSubscription($entry_id, $entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        $sql = "DELETE FROM {$this->tbl->user_subscription}
        WHERE entry_id IN (%s) AND entry_type = %d";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }


    // UPDATE // -----------------------------

    function setPrivate($private, $record_id) {
        $sql = "UPDATE {$this->tbl->category}
        SET private = '{$private}'
        WHERE id IN ($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    // ACTIVE // ------------------------------
    
    function statusCategory($status, $record_id) {
        $this->status($status, $record_id);
        $this->statusChild($status, $record_id);
        
    }
    
    
    function status($value, $id, $field = 'active', $keep_field = false) {
        $sql = "UPDATE {$this->tbl->table} 
        SET active='%d', active_real='%d' WHERE id IN (%s)";
        $sql = sprintf($sql, $value, $value, $this->idToString($id));
        
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }


    function statusChild($value, $cat_id, $child_ids = array()) {
                
        if(!$child_ids) {
            $child_ids = $this->getChilds(false, $cat_id);
        }
        
        if(!$child_ids) {
            return 0;
        }
        
        // parent set active
        if($value) {
            $sql = "UPDATE {$this->tbl->table} SET active = active_real WHERE id IN(%s)";
        
        // parent set not active
        } else {
            $sql = "UPDATE {$this->tbl->table} SET active = 0 WHERE id IN(%s)";
        }

        // echo '<pre>', print_r($parent_id, 1), '</pre>';
        // echo '<pre>', print_r($child_ids, 1), '</pre>';
        // return;

        $child_ids_str = is_array($child_ids) ? implode(',', $child_ids) : $child_ids; 
        $sql = sprintf($sql, $child_ids_str) or die(db_error($sql));
        
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $this->db->Affected_Rows();
    }


    // when user wants to set a category actuve 
    // but parent are not active 
    function statusParent($value, $child_id) {

        $parent_ids = $this->getParents($child_id);
        if(!$child_ids) {
            return true;
        }
        
        $sql = "UPDATE kbp_category SET active = 1 WHERE id IN(%s)";
        $sql = sprintf($sql, implode(',', $parent_ids)) or die(db_error($sql));
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }    
    

    // DELETE RELATED // ---------------------

    // return how much entries for current if some product assigned to category
    function isCategoryInUse($record_id) {    
        $num = $this->getEntriesNum($record_id);
        return array_sum($num);
    }


    // change sort_order for all categories with the same parent
    function setCategoryOrder($record_id, $parent_id) {

        $sql = "SELECT sort_order FROM {$this->tbl->category} WHERE id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $order = $result->Fields('sort_order');

        $sql = "UPDATE {$this->tbl->category}
        SET sort_order = (sort_order-1)
        WHERE parent_id = '$parent_id'
        AND sort_order > $order";

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    // delete category
    function deleteCategory($record_id) {
        $sql = "DELETE FROM {$this->tbl->category} WHERE id IN (?)";
        return $this->db->Execute($sql, $record_id) or die(db_error($sql));
    }


    // change parent when delete a category
    function setCategoryToParent($record_id, $parent_id) {
        $sql = "UPDATE {$this->tbl->category}
        SET parent_id = '$parent_id'
        WHERE parent_id = '$record_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    // change entry to parent category when delete a category
    function setCategoryEntryToParent($record_id, $parent_id) {
        $sql = "UPDATE IGNORE {$this->tbl->entry_to_category}
        SET category_id = '$parent_id'
        WHERE category_id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));

        // delete all not updated, in case if some entries alrteady assigned to
        // parent category (parent_id)
        $sql = "DELETE FROM {$this->tbl->entry_to_category} WHERE category_id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    

    // chnage category_id in kb_entry  when delete a category
    function setCategoryEntryMain($record_id, $parent_id) {
        $sql = "UPDATE {$this->tbl->entry}
        SET category_id = '$parent_id', date_updated = date_updated
        WHERE category_id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function delete($id, $parent_id = 0) {

        $ret = 'notdeleteable_category';
        $in_use = $this->isCategoryInUse($id);
        $in_use_draft = array_sum($this->getDraftsNum($id));
        
        if($in_use_draft) {
            return $ret;
        }
        
        // top level category is in use, notdeleteable
        if($parent_id == 0 && $in_use) {
            $ret = 'notdeleteable_category';

        // top level category but not in use
        } elseif($parent_id == 0 && !$in_use) {

            $this->setCategoryOrder($id, $parent_id);
            $this->deleteCategory($id);
            $this->setCategoryToParent($id, $parent_id);

            $this->deleteRoleToCategory($id);
            $this->deleteAdminUserToCategory($id);
            $this->deleteSubscription($id);

            // not tested, normally cat should be correct here no need to update 
            // $row_parent = $this->getById($parent_id);
            // $this->statusChild($row_parent['active'], $parent_id);

            $ftype = ($this->entry_type == 11) ? 1 : 2;
            $cf_manager = new CommonCustomFieldModel();
            $cf_manager->deleteFieldToCategory($id, $ftype);

            $ret = 'success';

        // child category, all values assigned to parent
        } else {

            $this->setCategoryOrder($id, $parent_id);
            $this->deleteCategory($id);
            $this->setCategoryToParent($id, $parent_id);
            $this->setCategoryEntryToParent($id, $parent_id);
            $this->setCategoryEntryMain($id, $parent_id);
            
            $this->deleteRoleToCategory($id);
            $this->deleteAdminUserToCategory($id);
            $this->deleteSubscription($id);

            // $row_parent = $this->getById($parent_id);
            // $this->statusChild($row_parent['active'], $parent_id);

            $ftype = ($this->entry_type == 11) ? 1 : 2;
            $cf_manager = new CommonCustomFieldModel();
            $cf_manager->deleteFieldToCategory($id, $ftype);

            $ret = 'success';
        }

        return $ret;
    }
}
?>