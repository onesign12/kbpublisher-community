<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+



class RoleModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'user_role', 'user_role', 'user', 'user_to_role', 'data_to_user_value');
    
/*
    var $role_rules = array('kb_category_to_role_read',
                            'file_category_to_role_read',
                            'kb_category_to_role_write',
                            'file_category_to_role_write',
                            'kb_entry_to_role_read',
                            'file_entry_to_role_read'
                            );
*/
    
    var $role_rules = array(1,2,5,6,101,102);    
    
    
    
    function getRecordsSql() {
        $sql = "SELECT r.*, COUNT(ur.user_id) as user_num
        FROM {$this->tbl->table} r
        LEFT JOIN {$this->tbl->user_to_role} ur ON r.id = ur.role_id     
        WHERE {$this->sql_params} 
        GROUP BY r.id
        {$this->sql_params_order}";
        return $sql;
    }    
    
     
    function &getRecords() {
        $data = array();
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $data[$row['id']] = $row;
        }

        return $data;
    }    
    
    
    // FOR FORM SELECT // --------------------
    
    function getSelectRangeByParentId($parent_id) {
        $sql = "SELECT id, title 
        FROM {$this->tbl->table} c
        WHERE c.parent_id IN($parent_id)
        ORDER BY title";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }    
    
    
    // just for select
    function getSelectRecords() {    
        $sql = "SELECT id as id1, id, parent_id, title, sort_order 
        FROM {$this->tbl->user_role} c
        ORDER BY title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    // get helper array in format id=>level
    function &getTreeHelperArray($arr, $parent_id = 0) {
        
        if(!$arr) { return array(); }
        
        $tree = new TreeHelper();
        foreach($arr as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }
        
        return $tree->getTreeHelper($parent_id);
    }
    
    
    // to generate form select range $arr from getSelectRecords
    function &getSelectRange($roles = false, $parent_id = 0, $pref = '-- ') {
        
        if($roles === false) {
            $roles = $this->getSelectRecords();
        }
        
        if(!$roles) { 
            $data = array();
            return $data; 
        }
        
        $tree_helper = &$this->getTreeHelperArray($roles, $parent_id);
        foreach($tree_helper as $id => $level) {
    
            $p = ($level == 0) ? '' : str_repeat($pref, $level);
            $data[$id] = $p . $roles[$id]['title'];
        }
        
        return $data;
    }
    
    
    // to generate form select range $arr from getSelectRecords
    function &getSelectRangeFolow($roles = false, $parent_id = 0, $pref = ' -> ') {
        
        if($roles === false) {
            $roles = $this->getSelectRecords();
        }
        
        if(!$roles) { 
            $data = array();
            return $data; 
        }
        
        $tree_helper = &$this->getTreeHelperArray($roles, $parent_id);
        foreach($tree_helper as $id => $level) {
        
            if($level == 0) {
                $data[$id] = $roles[$id]['title'];
                $prev[$level] = $roles[$id]['title'];
            
            } else {
                $data[$id] = $prev[$level-1] . $pref . $roles[$id]['title'];
                $prev[$level] = $data[$id];
            }
        }
        
        return $data;
    }    
    
    
    function getChildRoles($roles, $role_id) {
        
        if($roles === false) {
            $roles = $this->getSelectRecords();
        }
        
        $tree = new TreeHelper();
        foreach($roles as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }
        
        return $tree->getChildsById($role_id);
    }    
    
    
    function getChilds($rows, $id) {
        return $this->getChildRoles($rows, $id);
    }        
    
    
    function getParentRoles($roles, $role_id) {
        
        if($roles === false) {
            $roles = $this->getSelectRecords();
        }
        
        return TreeHelperUtil::getParentsById($roles, $role_id);
    } 
    
    
    // generate arrays for js (DynamicOptionList) $arr from getSelectRecords
    function &getSortJsArray($arr) {
        
        $values = array();
        foreach($arr as $v) {
            $str =  "'%s: %s', '%s'";
            $values[$v['parent_id']][] = sprintf($str, 'AFTER', addslashes($v['title']), $v['sort_order']);
        }
        
        
        $sort = new TableSortOrder(); 
        $sort->db = &$this->db;
        $str = '';
        unset($sort->extra_range['sort_default']);
        foreach($sort->extra_range as $k => $v) {
            $str .= sprintf("'%s', '%s', ", $v, $k);
        }
        
        foreach($values as $k => $v) {
            $values[$k] = "'" . $k . "'," . $str . implode(',', $v);
        }
        
        return $values;
    }    
        
    
    // ACTIONS // ---------------------
    
    //  return sort val and dod nessesary updates in sort_order field
    function sortManipulation(&$obj) {
        
        $parent_id = $obj->get('parent_id');
        
        $sort = new TableSortOrder();
        $sort->db = &$this->db;
        $sort->table = $this->tbl->user_role;
        $sort->name_field = 'title';
        $sort->sort_field = 'sort_order';
        $sort->setMoreSql("parent_id = '{$parent_id}'",
                          "parent_id = '{$parent_id}'");
        
        $sort->new_after_item_addon = ($obj->get('id')) ? 0 : 1;
        $val = $sort->getDoSort($obj->get('sort_order'), $obj->get('id'));
        $obj->set('sort_order', $val);
    }
    
    
    function save($obj, $action = 'insert') {
        
        $this->sortManipulation($obj);
        
        if($action == 'insert') {
            $id = $this->add($obj);
            
        } else {
            $id = (int) $obj->get('id');
            $this->update($obj, $id);
        }
        
        return $id;
    }    
    
    
    
    // DELETE RELATED // ---------------------
    
    function isRoleInUseByUser($record_id) {
        $sql = "SELECT COUNT(*) AS num 
        FROM {$this->tbl->user_to_role} ur 
        WHERE ur.role_id = %d";
        
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
    
    
/*    function isRoleInUseByRecord($record_id) {
        $role_rules = implode(',', $this->role_rules);
        $sql = "SELECT COUNT(*) AS num 
        FROM {$this->tbl->data_to_user_value} uv
        WHERE uv.rule_id IN({$role_rules}) 
        AND uv.user_value = %d";
        
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }*/    
    
    
    // delete role
    function deleteRole($record_id) {
        $sql = "DELETE FROM {$this->tbl->user_role} WHERE id IN($record_id)";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    // remove all records from kbp_data_to_user_value 
    // so no any article or category will be assigned to this role 
    function deleteAssignedReferences($record_id) {
        $role_rules = implode(',', $this->role_rules);
        $sql = "DELETE FROM {$this->tbl->data_to_user_value} WHERE user_value = '%d' AND rule_id IN({$role_rules})";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // change parent when delete a role
    function setRoleToParent($record_id, $parent_id) {
        $sql = "UPDATE {$this->tbl->user_role} 
        SET parent_id = '$parent_id'
        WHERE parent_id = '$record_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // change user to parent role when delete a role
    function setRoleEntryToParent($record_id, $parent_id) {
        $sql = "UPDATE {$this->tbl->user_to_role} 
        SET role_id = '$parent_id'
        WHERE role_id = '$record_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // change sort_order for all categories with the same parent
    function setRoleOrder($record_id, $parent_id) {
        
        $sql = "SELECT sort_order FROM {$this->tbl->user_role} WHERE id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $order = $result->Fields('sort_order');
        
        $sql = "UPDATE {$this->tbl->user_role} 
        SET sort_order = (sort_order-1)
        WHERE parent_id = '$parent_id'
        AND sort_order > $order";
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }    
    
    
    function delete($id, $parent_id = 0) {
        
        $ret = 'notdeleteable_entry';
        
        // Updated: 17 Oct 2008 no any user with this role - no need in this role 
        // remove all assigned records
        
        // some entry assigned to this role
        //$in_use = $this->isRoleInUseByRecord($id);
        //if($in_use) {
        //    return $ret;
        //}
        
        // user with this role
        $in_use = $this->isRoleInUseByUser($id);
        
        
        // top level role is in use, notdeleteable
        if($parent_id == 0 && $in_use) {
            $ret = 'notdeleteable_entry';
        
        // top level role but not in use
        } elseif($parent_id == 0 && !$in_use) {
            
            //DBUtil::begin();
            $this->setRoleOrder($id, $parent_id);            
            $this->deleteRole($id);
            $this->setRoleToParent($id, $parent_id);
            $this->deleteAssignedReferences($id);
            //DBUtil::commit(); 
            
            $ret = 'success';
            
        // child role, all values assigned to parent
        } else {
            
            //DBUtil::begin();
            $this->setRoleOrder($id, $parent_id);
            $this->deleteRole($id);
            $this->setRoleToParent($id, $parent_id);
            $this->setRoleEntryToParent($id, $parent_id);
            $this->deleteAssignedReferences($id);
            //DBUtil::commit();
            
            $ret = 'success';
        }
        
        return $ret;
    }
}
?>