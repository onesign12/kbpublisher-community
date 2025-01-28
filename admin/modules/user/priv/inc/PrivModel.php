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

/*
in priv module table, kbp_priv_module 
  0 - no active hiddel in all places
  1 - active, display in menu and availabe to set priv in priveledge if check_priv = 1 
  2 - not display in menu, but available to set priv in priveledge if check_priv = 1 
        
        For example used for Trash menu. 
*/

class PrivModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'priv_name', 'priv_name', 'priv', 'priv_rule', 'priv_module', 'user');
    var $priv_values = array('select', 'insert', 'update', 'status', 'delete');
    
    var $status_model;
    
    
    function __construct() {
        parent::__construct();
        
        $this->sort_model = new TableSortOrder();
        $this->sort_model->db = &$this->db;
        $this->sort_model->table = $this->tbl->priv_name;
        $this->sort_model->name_field = 'name';
        $this->sort_model->sort_field = 'sort_order';
    }    
    

    function getById($id) {
        $this->setSqlParams("AND n.id = '{$id}'", 'id');
        $data = $this->getRecords();
        return $data[$id];
    }


    function getByIds($ids) {
        
        $data = array();
        $msg = AppMsg::getMsgs('privileges_msg.ini');
        
        $sql = "
        SELECT *,
            IFNULL(n.description, 'msg') AS 'desc'
        FROM {$this->tbl->priv_name} n
        WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            
            if(empty($row['name'])) {
                $row['name'] = $msg[$row['id']]['name'];
            }
            
            if($row['desc'] == 'msg') {
                $row['description'] = $msg[$row['id']]['description'];
            }
            
            $data[$row['id']] = $row;
        }
        
        return $data;
    }
    
    
    function getRecords() {
        
        $data = array();
        $msg = AppMsg::getMsgs('privileges_msg.ini');
        
        $sql = "
        SELECT 
            n.*,
            IFNULL(n.description, 'msg') AS 'desc',
            COUNT(u.id) AS user_num
        FROM {$this->tbl->priv_name} n
        LEFT JOIN {$this->tbl->priv} p ON p.priv_name_id = n.id
        LEFT JOIN {$this->tbl->user} u ON u.id = p.user_id
        
        WHERE {$this->sql_params}
        GROUP BY n.id
        {$this->sql_params_order}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            
            if(empty($row['name'])) {
                $row['name'] = $msg[$row['id']]['name'];
            }
            
            if($row['desc'] == 'msg') {
                $row['description'] = $msg[$row['id']]['description'];
            }            
            
            $data[$row['id']] = $row;
        }
        
        return $data;
    }
    
    
    function getSortSelectRange() {
        $this->setSqlParams(false, 'id');
        $this->setSqlParamsOrder("ORDER BY sort_order");
        $data = $this->getRecords();
        foreach($data as $k => $v) {
            $range[$v['sort_order']] = $this->sort_model->after_word . ': ' . $v['name'];
        }
        
        $data = $this->sort_model->extra_range + $range;
        return $data;
    }        
    
    
    // PRIV RULES // ---------------------
    
    function getPrivModules() {
    
        $data = array();
        $msg_top = AppMsg::getMsg('menu_msg.ini', false, 'top', 1);
        $msg_all = AppMsg::getMsg('menu_msg.ini', false, false, false);
        
        $sql = "
        SELECT
            m.id, 
            m.parent_id, 
            m.module_name, 
            m.check_priv, 
            m.own_priv,
            m.status_priv, 
            m.what_priv,
            m.extra_priv
        FROM 
            {$this->tbl->priv_module} m 
            
        WHERE 1 
            AND m.active IN (1,2) 
            AND m.id != 0
            AND m.check_priv = 1
        ORDER BY m.sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            
            if($row['what_priv']) {
                $row['what_priv'] = explode(',', $row['what_priv']);
            } else {
                $row['what_priv'] = $this->priv_values;
            }
            
            if($row['extra_priv']) {
                $row['extra_priv'] = explode(',', $row['extra_priv']);
            } else {
                $row['extra_priv'] = array();
            }
            
            $row['title'] = ($row['parent_id'] == 0) ? $msg_top[$row['module_name']] : $msg_all[$row['module_name']];            
            $data[$row['id']] = $row;
        }
        
        return $data;
    }
    
    
    function getPrivRules($priv_name_id) {
        $data = array();
        $sql = "
        SELECT 
            *
        FROM 
            {$this->tbl->priv_rule} 
        WHERE 1
            AND priv_name_id = '{$priv_name_id}'
            AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
        
            if($row['status_priv']) {
                $row['status_priv'] = unserialize($row['status_priv']);
            }
        
            if($row['optional_priv']) {
                $row['optional_priv'] = unserialize($row['optional_priv']);
            }
        
            $row['what_priv'] = explode(',', $row['what_priv']);
            $data[$row['priv_module_id']] = $row; 
        }
    
        return $data;
    }
    
    
    // get helper array in format id=>level
    function getTreeHelperArray($arr, $parent_id = 0) {
        
        if(!$arr) { return array(); }
        
        $tree = new TreeHelper();
        foreach($arr as $k => $row) {
            $tree->setTreeItem($row['id'], $row['parent_id']);
        }
        
        return $tree->getTreeHelper($parent_id);
    }
    
    
    function getMaxPrivLevel() {
        $sql = "SELECT MAX(sort_order) AS 'num' FROM {$this->tbl->priv_name}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }


    // -- Dependable -- // ---------------------

    // modify $priv, set dependable
    function parseDependablePriv($priv) {

        // drafts, if article/files set as draft only we add draft oriv
        $modules = array(101 => 103, 201 => 203, 205 => 203);
        $actions = array('insert', 'update');

        foreach($modules as $module_id => $dependable_id) {
            foreach($actions as $action) {
                if($this->isPrivOptionalInArray($priv, $module_id, $action, 'draft')) {
                    if($this->isPrivInArray($priv, $dependable_id, $action) == false) {
                        $this->addPrivToArray($priv, $dependable_id, $action);
                    }
                }  
            }
        }

        return $priv;
    }


    function isPrivInArray($priv, $module_id, $action) {

        $ret = false;

        // module checking
        if(isset($priv[$module_id]['what_priv'])) {
            $what_priv = $priv[$module_id]['what_priv'];
            if(in_array($action, $what_priv)) {
                $ret = true;
            }
        }

        // self checking
        if(!$ret) {
            $self_actions = array('update', 'status', 'delete');
            if(in_array($action, $self_actions)) {
                $ret = $this->isPrivInArray($priv, $module_id, 'self_' . $action);
            }
        }

        return $ret;
    }


    function isPrivOptionalInArray($priv, $module_id, $action, $optional) {

        $ret = false;

        if($this->isPrivInArray($priv, $module_id, $action)) {
            if(isset($priv[$module_id]['optional_priv'][$action])) {
                $op = $priv[$module_id]['optional_priv'][$action];
                if(in_array($optional, $op)) {
                    $ret = true;
                }
            }   
        }

        return $ret;
    }


    function addPrivToArray(&$priv, $module_id, $action) {
        $priv[$module_id]['what_priv'][] = $action;
        $priv[$module_id]['what_priv'] = array_unique($priv[$module_id]['what_priv']);
    }
    
    
    // ACTIONS // ---------------------
    
    function addPrivRule($priv, $priv_name_id) {
                
        $priv_values = $this->priv_values;
        unset($priv_values[0]); // unset select    
        foreach($priv_values as $v) { $priv_values[] = 'self_' . $v; }
        $priv_values = implode(' ', $priv_values);

        $priv = $this->parseDependablePriv($priv);

        // echo '<pre>', print_r($priv, 1), '</pre>';
        // exit;

        $data = array();
        foreach($priv as $module_id => $v) {
            
            if(empty($v['what_priv'])) {
                continue;
            }
            
            $what_priv_flip = array_flip($v['what_priv']);
            $apply_to_child = (!empty($v['apply_to_child'])) ? $v['apply_to_child'] : 0;
            
            // status priv
            $status_priv = '';
            if(!empty($v['status_priv'])) {
                foreach($v['status_priv'] as $action => $statuses) {
                    if(!in_array($action, $v['what_priv']) && !in_array('self_' . $action, $v['what_priv'])) {
                        unset($v['status_priv'][$action]);
                    }
                }
            
                $status_priv = addslashes(serialize($v['status_priv']));
            }

            // optional priv
            $optional_priv = '';
            if(!empty($v['optional_priv'])) {
                foreach($v['optional_priv'] as $action => $privs) {
                    if(!in_array($action, $v['what_priv']) && !in_array('self_' . $action, $v['what_priv'])) {
                        unset($v['optional_priv'][$action]);
                    }
                }
                
                $optional_priv = addslashes(serialize($v['optional_priv']));
            }
            
            
            // generate correct priv
            preg_match("#".implode('|', $v['what_priv'])."#", $priv_values, $match);
            if($match) {
                preg_match("#select|self_select#", implode(' ', $v['what_priv']), $match);
                if(!$match) { array_unshift($v['what_priv'], 'select'); }
            }
            
            $what_priv    = implode(',', $v['what_priv']); 
                       
            $data[] = array($priv_name_id, 
                            $module_id, 
                            $what_priv,
                            $status_priv,
                            $optional_priv,
                            $apply_to_child,
                            1);
        }
        
        if($data) {
            $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->priv_rule} 
                                    (priv_name_id, priv_module_id, what_priv, status_priv, optional_priv, apply_to_child, active) 
                                     VALUES ?", $data);
            
            return $this->db->Execute($sql) or die(db_error($sql));        
        }
    }    
        
    
    function save($obj, $action = 'insert') {
        
        $action = (!$obj->get('id')) ? 'insert' : 'update';
        $priv = $obj->getPriv();
        
        if($action == 'insert') {
            
            // 1 for admin only
            if($obj->get('sort_order') == 1) { // not possible to set
                $obj->set('sort_order', 2);
            }
            
            $id = $this->add($obj);
            $this->addPrivRule($priv, $id);
            
        } else {
            $id = (int) $obj->get('id');
            
            // for admin always 1
            if(!$this->isPrivEditable($id)) { // not possible to set
                $obj->set('sort_order', 1);
            } elseif($obj->get('sort_order') == 1) { // not possible to set
                $obj->set('sort_order', 2);
            }
            
            $this->update($obj, $id);
            if($priv) {
                $this->deletePrivRule($id);
                $this->addPrivRule($priv, $id);
            }
        }
        
        return true;
    }
    
    
    function saveSortOrder($ids) {
        $sort_order = 2;
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
    
    
    function updateSortOrder2($old_order, $new_order, $id) {
        $this->sort_model->manipulate_more_sql = sprintf('id != %s', $id);
                
        if (!$old_order) {
            $sign = '+';
            $sign2 = '>=';
            
        } elseif ($old_order == $new_order) {
            if ($new_order == 1) {
                return;
            }
            
            $sign = '+';
            $sign2 = '>=';
            
        } else {
            if (($old_order - $new_order) > 0) {
                $sign = '+';
                $sign2 = '>=';
                $sign3 = '<=';
                
            } else {
                $sign = '-';
                $sign2 = '<=';
                $sign3 = '>=';
                
                $this->sort_model->manipulate_more_sql .= ' AND sort_order != 2';
            }
            
            $this->sort_model->manipulate_more_sql .= sprintf(' AND sort_order %s %s', $sign3, $old_order);
        }
        
        $this->sort_model->update($sign, $sign2, $new_order);
        
    }
    
    
    function getDefaultRecords() {
        $sql = "SELECT * FROM {$this->tbl->priv_name}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $data['name'] = $result->GetArray();
    
        $sql = "SELECT * FROM {$this->tbl->priv_rule}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $data['priv'] = $result->GetArray();
        
        return $data;
    }
    
    
    // DELETE RELATED // ---------------------
    
    function isPrivEditable($record_id) {
        $sql = "SELECT editable FROM {$this->tbl->priv_name} n WHERE n.id = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('editable');                
    }
    
    
    function isPrivInUse($record_id) {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->priv} p WHERE p.priv_name_id = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
    
    
    function setPrivOrderOnDelete($record_id) {
        $sql = "SELECT sort_order FROM {$this->tbl->priv_name} WHERE id = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $order = $result->Fields('sort_order');
        
        $this->sort_model->updateOnDelete($order);
    }        
    
    
    function deletePriv($record_id) {
        $sql = "DELETE FROM {$this->tbl->priv_name} WHERE id = '%d'";
        $sql = sprintf($sql, $record_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deletePrivRule($record_id) {
        $sql = "DELETE FROM {$this->tbl->priv_rule} WHERE priv_name_id = '%d'";
        $sql = sprintf($sql, $record_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function delete($record_id) {
        //$this->setPrivOrderOnDelete($record_id);
        $this->deletePriv($record_id);
        $this->deletePrivRule($record_id);
    }
}
?>