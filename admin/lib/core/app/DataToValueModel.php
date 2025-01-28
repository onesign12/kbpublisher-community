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


class DataToValueModel extends BaseModel
{

    var $tables = array(
        'value' => 'data_to_user_value',
        'value_string' => 'data_to_user_value_string',
        'user'
    );

    var $rules = array(
      'kb_category_to_role_read'    => 1,
      'kb_category_to_role_write'   => 5,
      'kb_entry_to_role_read'       => 101,
      'kb_entry_to_role_write'      => 105,
      
      'kb_draft_to_role_write'      => 103,
      'kb_category_to_user_admin'   => 3,
      
      'file_category_to_role_read'  => 2,
      'file_category_to_role_write' => 6,
      'file_entry_to_role_read'     => 102,
      'file_entry_to_role_write'    => 106,
      
      'file_draft_to_role_write'    => 104,
      'file_category_to_user_admin' => 4,
      
      'feedback_user_admin'         => 10,
      
      'news_entry_to_role_read'     => 107,
      'news_entry_to_role_write'    => 108
    );

    // this rules will be saved to table data_to_user_value_string
    var $rule_ids_save_string = array(101,102,103,104,105,106,107,108);

    // this will be used in mustread cron to get rules
    var $private_read_rules_to_entry_type = array(
        1 => 101, 2 => 102, 3 => 107, 11 => 1, 12 => 2 
    );


    static function &instance() {
        static $registry;
        if (!$registry) { $registry = new DataToValueModel(); }
        return $registry;
    }    
    
    
    function getRuleId($rule) {
        return $this->rules[$rule];
    }
    
    
    function getSupervisorRuleIds() {
        
        $keys = array(
            'kb_category_to_user_admin',
            'file_category_to_user_admin',
            'feedback_user_admin'
            );
        
        
        $rules = $this->rules;
        $srules = array_intersect_key($rules, array_flip($keys));
        return $srules;
    }
    
    
    // get assigned ids for value($record_id)
    // record id could be array
    function getDataIds($rule_id, $record_id = false) {
        
        $data = array();
        $rule_id = (is_array($rule_id)) ? implode(',', $rule_id) : $rule_id;
        $params = 1;
        if($record_id) {
            if(is_array($record_id)) {
                $record_id = implode(',', $record_id);
            }
            
            $params = "dv.data_value IN ($record_id)";        
        }

        
        $sql = "
        SELECT 
            dv.*
        FROM 
            {$this->tbl->value} dv
        WHERE 1
            AND dv.rule_id IN ({$rule_id})
            AND {$params}";
        
        $data = array();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        //echo "<pre>"; print_r($sql); echo "</pre>";
        
        while($row = $result->FetchRow()) {
            $data[$row['data_value']][] = $row['user_value'];
        }
        
        return $data;
    }
    
    
    function &getDataById($record_id, $rule_id, $select, $one_to_many = false) {
        
        $data = array();
        $rule_id = (is_array($rule_id)) ? implode(',', $rule_id) : $rule_id;
        
        $sql = "
        SELECT 
            {$select}
        FROM 
            {$this->tbl->value} dv
        WHERE 1
            AND dv.rule_id IN ({$rule_id})
            AND dv.data_value IN ($record_id)";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        if($one_to_many) {
            $data = array();
            while($row = $result->FetchRow()) {
                $data[$row['data_value']][$row['user_value']] = $row['user_value'];
            }
        } else {
            $data = $result->GetAssoc();
        }
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";
        return $data;
    }    
    
    // conbined for read/write in one select 
    function &getDataWithRuleById($record_id, $rule_id, $select = '*', $one_to_many = false) {
        
        $data = array();
        $rule_id = (is_array($rule_id)) ? implode(',', $rule_id) : $rule_id;
        
        $sql = "
        SELECT 
            {$select}
        FROM 
            {$this->tbl->value} dv
        WHERE 1
            AND dv.rule_id IN ({$rule_id})
            AND dv.data_value IN ($record_id)";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        if($one_to_many) {
            while($row = $result->FetchRow()) {
                $data[$row['rule_id']][$row['data_value']][$row['user_value']] = $row['user_value'];
            }
        } else {
            while($row = $result->FetchRow()) {
                $data[$row['rule_id']][$row['user_value']] = $row['user_value'];
            }
        }
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";
        return $data;
    }    
    
    
    function saveData($data, $record_id, $rule) {
        
        if(!$data) { return; }
        
                
        $rule_id = (is_numeric($rule)) ? $rule : $this->getRuleId($rule);
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        
        foreach($data as $data_id) {
            foreach($record_id as $entry_id) {
                $_data[] = array($entry_id, $data_id);
            }
        }
        
        $ins = new MultiInsert;
        $ins->setFields(array('data_value', 'user_value'), 'rule_id');
        $ins->setValues($_data, $rule_id);
        $sql = $ins->getSql($this->tbl->value);
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;
        
        $ret = $this->db->Execute($sql) or die(db_error($sql));
        
        // to string
        if(in_array($rule_id, $this->rule_ids_save_string)) {
            $ret = $this->saveDataString($data, $record_id, $rule);
            if($ret !== true) {
                $this->deleteData(implode(',', $record_id), $rule_id);
                die($ret);
            }
        }
        
        return $ret;
    }


    // save to s_data_to_user_value_string
    function saveDataString($data, $record_id, $rule) {
        
        if(!$data) { return true; }
        
                
        $rule_id = (is_numeric($rule)) ? $rule : $this->getRuleId($rule);
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        
        foreach($record_id as $entry_id) {
            $_data[] = array($rule_id, $entry_id, implode(',', $data));
        }

        
        $ins = new MultiInsert;
        $ins->setFields(array('rule_id', 'data_value', 'user_value'));
        $ins->setValues($_data);
        $sql = $ins->getSql($this->tbl->value_string);
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;
        
        // $ret = $this->db->Execute($sql) or die(db_error($sql));
        $result = $this->db->Execute($sql);
        if(!$result) {
            return db_error($sql);
        }
        
        return true;
    }


    // DELETE // -----------------

    function deleteData($record_id, $rule_id) {
        $sql = "DELETE FROM {$this->tbl->value} 
        WHERE rule_id = '{$rule_id}' AND data_value IN({$record_id})";
        $ret = $this->db->Execute($sql) or die(db_error($sql));
        
        if(in_array($rule_id, $this->rule_ids_save_string)) {
            $ret = $this->deleteDataString($record_id, $rule_id);
        }
    }
    
    
    function deleteDataString($record_id, $rule_id) {
        $sql = "DELETE FROM {$this->tbl->value_string} 
        WHERE rule_id = '{$rule_id}' AND data_value IN({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));        
    }



    function deleteDataByUserValue($record_id, $rule_id) {
        $sql = "DELETE FROM {$this->tbl->value} 
        WHERE rule_id IN ({$rule_id}) AND user_value IN ({$record_id})";
        $ret = $this->db->Execute($sql) or die(db_error($sql));
        
        $rule_ids = explode(',', $rule_id);
        $rule_ids_save_string = array_intersect($this->rule_ids_save_string, $rule_ids);
        if($rule_ids_save_string) {
            $rule_ids_save_string = implode(',', $rule_ids_save_string);
            $ret = $this->deleteDataStringByUserValue($record_id, $rule_ids_save_string);
        }
    }
    
    
    function deleteDataStringByUserValue($record_id, $rule_id) {
        $sql = "DELETE FROM {$this->tbl->value_string} 
        WHERE rule_id IN ({$rule_id}) AND user_value IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    // use for deleteOnTrash
    function deleteDataMissed($rule_id) {
        $sql = "DELETE s FROM {$this->tbl->value} s
        LEFT JOIN {$this->tbl->user} e ON e.id = s.user_value
        WHERE s.rule_id IN ({$rule_id}) AND e.id IS NULL";
        $ret = $this->db->Execute($sql) or die(db_error($sql));
        
        $rule_ids = explode(',', $rule_id);
        $rule_ids_save_string = array_intersect($this->rule_ids_save_string, $rule_ids);
        if($rule_ids_save_string) {
            $rule_ids_save_string = implode(',', $rule_ids_save_string);
            $ret = $this->deleteDataStringMissed($rule_ids_save_string);
        }
    }


    function deleteDataStringMissed($rule_id) {
        $sql = "DELETE s FROM {$this->tbl->value_string} s
        LEFT JOIN {$this->tbl->user} e ON e.id = s.user_value
        WHERE s.rule_id IN ({$rule_id}) AND e.id IS NULL";
        return $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>