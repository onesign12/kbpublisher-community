<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class CommonTriggerEntryModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('table'=>'trigger', 'trigger',
        'entry_draft', 'entry_draft_workflow');
    
    var $trigger_type;
    var $trigger_types = array(
        'trigger' => 1,
        'automation' => 2,
        'workflow' => 4
    );

    var $entry_type;
    var $entry_types = array(
        'tr_article' => 1,
        'am_article' => 1,
        'wf_article' => 1,
        'tr_file'    => 2,
        'am_file'    => 2,
        'wf_file'    => 2,
        'am_email'   => 30
    );
    
    
    function getById($id) {
        $data = parent::getById($id);
        
        if ($data['trigger_key']) {
            $predefined = $this->getpredefinedValues($data['trigger_key']);
            $data['title'] = (empty($data['title'])) ? $predefined['title'] : $data['title'];
            $data['subject'] = (empty($data['subject'])) ? $predefined['subject'] : $data['subject'];
        }
        
        return $data;
    }
    
    
    static $user_search_str = array(
        'cond'   => 'r";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:%s:"%s";}', // author/updater (by the last character)
        'action' => 's:4:"rule";a:3:{i:0;s:%s:"%s";' // consists of 3 elements - email only
    );
    
    
    function getpredefinedValues($trigger_key = false) {
        return AppMsg::getMsg('trigger_predefined_msg.ini', false, $trigger_key);
    }
    
    
    function unpackActions($workflow) {
        
        $actions = unserialize($workflow['action']);

        // setting titles
        if (!empty($workflow['trigger_key'])) {
            $actions = $this->setPredefinedStepTitles($actions, $workflow['trigger_key']);
        }
        
        return $actions;
    }
    
    
    function setPredefinedStepTitles($actions, $trigger_key) {
        
        $titles = $this->getPredefinedValues($trigger_key);
        
        foreach (array_keys($actions) as $step_num) {
            if(empty($actions[$step_num]['title'])) {
                if (!empty($titles['title_step_' . $step_num])) {
                    $actions[$step_num]['title'] = $titles['title_step_' . $step_num];    
                }
            }
        }
        
        return $actions;
    }
    
    
    function setPredefinedTitles($rows) {
        
        $predefined_titles = $this->getpredefinedValues();
        
        $data = array();
        foreach ($rows as $id => $row) {
            if(empty($row['title'])) {
                $row['title'] = $predefined_titles[$row['trigger_key']]['title'];
            }
            
            $data[$id] = $row;
        }
        return $data;
    }
    
    
    function getRecordsByStatus() {
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();
        $rows = $this->setPredefinedTitles($rows);
        
        $data = array(1 => array(), 0 => array());
        foreach ($rows as $row) {
            $data[$row['active']][] = $row;
        }
        
        return $data;
    }
    
    
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
    
    
    function getMaxSortOrder() {
        $sql = "SELECT MAX(sort_order) AS 'num' FROM {$this->tbl->table} 
        WHERE entry_type = '{$this->entry_type}'
        AND trigger_type = '{$this->trigger_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $val = $result->Fields('num');
        
        return ($val) ? $val : 0;
    }
    
    
    // DELETE RELATED // ---------------------
    
    function deleteTriggerByEntryType() {
        $sql = "DELETE FROM {$this->tbl->table}
        WHERE entry_type = '{$this->entry_type}'
        AND trigger_type = '{$this->trigger_type}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // DUMP // ----------------------------
    
    function runDefaultSql($trigger_type, $sql) {
        $reg =& Registry::instance();
        $sql = str_replace('{prefix}', $reg->getEntry('tbl_pref'), $sql);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function getDefaultSql($options = '') {
        
        $this->setSqlParams('AND trigger_type = ' . $this->trigger_type);
        $this->setSqlParams('AND entry_type = ' . $this->entry_type);
        $this->setSqlParams('AND trigger_key != ""');
        $sql = $this->getRecordsSql();
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();
        
        $trigger_sql = "(%s,%s,%s,'%s','','%s',2,'%s','%s','',%s,%s)";
        $email_actions = array('email', 'email_group', 'email_user_grouped', 'email_group_grouped');
        
        $trigger_types = array(
            1 => 'trigger',
            2 => 'automation'
        );
        
        $lines = array(1 => array(), 2 => array());
        foreach ($rows as $row) {
        
            $action = unserialize($row['action']);
            if (in_array($action[1]['item'], $email_actions)) {
                unset($action[1]['rule'][1]);
                unset($action[1]['rule'][2]);
            }
            
            $lines[$row['trigger_type']][] = sprintf(
                $trigger_sql,
                'NULL',
                $row['entry_type'],
                $row['trigger_type'],
                $row['trigger_key'],
                $options,
                $row['cond'],
                serialize($action),
                $row['sort_order'],
                $row['active']);
        }
        
        $default_sql = "INSERT INTO {prefix}trigger VALUES\n%s;";
        
        $data = array();
        foreach ($trigger_types as $type_id => $name) {
            if (!empty($lines[$type_id])) {
                $data[$type_id] = sprintf($default_sql, implode(",\n", $lines[$type_id]));
            }
        }
        
        return $data;
    }
    
    
    function getDefaultSqlSettingKey() {
        $keys = array();
        
        $keys[1][1] = 'default_sql_trigger_article';
        $keys[1][2] = 'default_sql_trigger_file';
        
        $keys[2][1] = 'default_sql_automation_article';
        $keys[2][2] = 'default_sql_automation_file';
        
        $keys[4][1] = 'default_sql_workflow_article';
        $keys[4][2] = 'default_sql_workflow_file';
        
        return $keys[$this->trigger_type][$this->entry_type];
    }
    
}
?>