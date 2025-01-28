<?php



class UserModel_activity extends UserModel
{

    
    var $critical_keys = ['article_workflow', 'file_workflow', 'file_rule'];
    
    
    function getUserActivities($user_id, $critical_only = false) {
        
        if (!is_array($user_id)) {
            $user_id = array($user_id);
        }        
        
        
        $funcs = [
            'getDependentWorkflows'   => ['crit' => 1], 
            'getDependentAutomations' => ['crit' => 1],
            'getUserDirectoryRules'   => ['crit' => 1],
            'getUserEntries'          => ['param' => 'artice'],
            'getUserEntries'          => ['param' => 'file'],
            'getSupervisedEntries'    => [],
            'getAssignedDrafts'       => [],
        ];
        
        $pluginable = AppPlugin::getPluginsFiltered('activity', true);
        foreach($pluginable as $k => $plugin) {
            if(!AppPlugin::isPlugin($plugin)) {
                unset($funcs[$k]);
            }
        }
        
        $data = array();
        foreach($funcs as $k => $v) {            
            $param = (isset($v['param'])) ? $v['param'] : null; 
            if($critical_only && !isset($v['crit'])) {
                continue;
            }
            
            $data[] = call_user_func_array(array($this, $k), array($user_id, $param));
        }
        
        return $this->_parseActivityData($user_id, $data);
    }
    
    
    function getCritActivities($activities) {
        $data = [];
        foreach($this->critical_keys as $v) {
            if(isset($activities[$v])) {
                $data[$v] = $activities[$v];
            }
        }
    
        return $data;
    }
    
    
    function _parseActivityData($user_ids, $data) {
        $_data = array();
        foreach ($user_ids as $id) {
            $_data[$id] = array();
        }
        
        foreach ($data as $v) {
            foreach ($v as $user_id => $user_stat) {
                $_data[$user_id] += $user_stat;
            }
        }
        
        foreach($_data as $user_id => $v) {
            $_data[$user_id] = array_filter($v);
        }
        
        return $_data;
    }
    
    
    function getUserEntries($user_ids, $entry_type) {
        $user_ids_str = implode(',', $user_ids);
        
        $sql = "SELECT id, author_id, updater_id
            FROM %s
            WHERE author_id IN (%s) OR updater_id IN (%s)";
        $table = ($entry_type == 'article') ? $this->tbl->kb_entry : $this->tbl->file_entry; 
        $sql = sprintf($sql, $table, $user_ids_str, $user_ids_str);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        foreach ($user_ids as $user_id) {
            $data[$user_id][$entry_type . '_author'] = 0;
            $data[$user_id][$entry_type . '_updater'] = 0;
        }
        
        while($row = $result->FetchRow()) {
            
            foreach ($user_ids as $user_id) {
                if ($row['author_id'] == $user_id) {
                    $data[$user_id][$entry_type . '_author'] ++;
                }
                
                if ($row['updater_id'] == $user_id) {
                    $data[$user_id][$entry_type . '_updater'] ++;
                }
            }
            
        }
        
        return $data;
    }
    
    
    function getSupervisedEntries($user_ids) {
            
        $rules = $this->dv_manager->getSupervisorRuleIds();
           
        $sql = "SELECT rule_id, data_value, user_value 
            FROM {$this->tbl->data_to_user_value}
            WHERE user_value IN (%s)
                AND rule_id IN(%s)";
        $sql = sprintf($sql, implode(',', $user_ids), implode(',', $rules));
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        foreach ($user_ids as $user_id) {
            foreach ($rules as $rule => $rule_id) {
                $data[$user_id][$rule] = 0;
            }
        }
        
        while($row = $result->FetchRow()) {
            foreach ($user_ids as $user_id) {
                if ($row['user_value'] == $user_id) {
                    $rule_id2 = array_search($row['rule_id'], $rules);
                    $data[$user_id][$rule_id2] ++;
                }
            }
        }
        
        return $data;    
    }
    
    
    function getDependentWorkflows($user_ids) {
        $trigger_type = 4;
        
        $rules = array(
            1 => 'article_workflow',
            2 => 'file_workflow');
        
        $serialized_str_cond = WorkflowEntryModel::$user_search_str['cond'];
        $serialized_str_act = WorkflowEntryModel::$user_search_str['action'];
        
        return $this->_getTriggers($user_ids, $trigger_type, $rules, $serialized_str_cond, $serialized_str_act);
    }
    
    
    function getDependentAutomations($user_ids) {
        $trigger_type = 2;
        
        $rules = array(
            1 => 'article_automation',
            2 => 'file_automation');
        
        $serialized_str_cond = TriggerEntryModel::$user_search_str['cond'];
        $serialized_str_act = TriggerEntryModel::$user_search_str['action'];
        
        return $this->_getTriggers($user_ids, $trigger_type, $rules, $serialized_str_cond, $serialized_str_act);   
    }
    
    
    function _getTriggers($user_ids, $trigger_type, $rules, $serialized_str_cond, $serialized_str_act) {
        $str = array();
        
        foreach ($user_ids as $user_id) {
            $_str = sprintf($serialized_str_cond, strlen($user_id), $user_id);
            $str[] = sprintf("(cond LIKE '%%%s%%'", $_str);
            
            $_str = sprintf($serialized_str_act, strlen($user_id), $user_id);
            $str[] = sprintf("action LIKE '%%%s%%')", $_str);
        }
        
        $sql = "SELECT id, trigger_type, entry_type, cond, action
            FROM {$this->tbl->trigger}
            WHERE trigger_type = %d
                AND %s";
        $sql = sprintf($sql, $trigger_type, implode(' OR ', $str));
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        foreach ($user_ids as $user_id) {
            foreach ($rules as $rule) {
                $data[$user_id][$rule] = 0;
                $data[$user_id][$rule] = 0;
            }
        }
        
        while($row = $result->FetchRow()) {
            foreach ($user_ids as $user_id) {
                $str_cond = sprintf($serialized_str_cond, strlen($user_id), $user_id);
                $str_act = sprintf($serialized_str_act, strlen($user_id), $user_id);
                
                if (strpos($row['cond'], $str_cond) !== false) {
                    $data[$user_id][$rules[$row['entry_type']]] ++;
                    
                } elseif (strpos($row['action'], $str_act) !== false) {
                    $data[$user_id][$rules[$row['entry_type']]] ++;
                }
            }
        }
        
        return $data;
    }
    
    
    function getUserDirectoryRules($user_ids) {
        
        $serialized_str = 's:9:"author_id";s:%s:"%s";';
        $str = array();
        
        foreach ($user_ids as $user_id) {
            $_str = sprintf($serialized_str, strlen($user_id), $user_id);
            $str[] = sprintf("entry_obj LIKE '%%%s%%'", $_str);
        }
        
        $sql = "SELECT id, entry_obj FROM {$this->tbl->entry_rule} WHERE %s";
        $sql = sprintf($sql, implode(' OR ', $str));
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        foreach ($user_ids as $user_id) {
            $data[$user_id]['file_rule'] = 0;
        }
        
        while($row = $result->FetchRow()) {
            foreach ($user_ids as $user_id) {
                $str = sprintf($serialized_str, strlen($user_id), $user_id);
                if (strpos($row['entry_obj'], $str) !== false) {
                    $data[$user_id]['file_rule'] ++;
                }
            }
        }
        
        return $data;
    }
    
    
    function getAssignedDrafts($user_ids) {
        $rules = array(
            1 => 'article_draft',
            2 => 'file_draft');
            
        $sql = "SELECT d.entry_type, da.assignee_id

            FROM {$this->tbl->entry_draft} d
            
            LEFT JOIN (
                 {$this->tbl->entry_draft_workflow} dw1
                 INNER JOIN (
                     SELECT MAX(dw.id) as id, dw.draft_id FROM {$this->tbl->entry_draft_workflow} dw
                     GROUP BY dw.draft_id
                 ) dw2 ON dw1.id = dw2.id
            ) ON dw1.draft_id = d.id
            
            LEFT JOIN {$this->tbl->entry_draft_workflow_to_assignee} da
            ON da.draft_workflow_id = dw1.id
            
            WHERE assignee_id IN (%s)";
            
        $sql = sprintf($sql, implode(',', $user_ids));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        foreach ($user_ids as $user_id) {
            foreach ($rules as $rule) {
                $data[$user_id][$rule] = 0;
                $data[$user_id][$rule] = 0;
            }
        }
        
        while($row = $result->FetchRow()) {
            foreach ($user_ids as $user_id) {
                $data[$row['assignee_id']][$rules[$row['entry_type']]] ++;
            }
        }
        
        return $data;
    }
    
    
    function updateSupervisor($new_id, $old_id) {
        $rules_ids = implode(',', $this->dv_manager->getSupervisorRuleIds());
        $sql = "UPDATE {$this->tbl->data_to_user_value}
            SET user_value = '%s'
            WHERE rule_id IN({$rules_ids})
                AND user_value = '%s'";
        $sql = sprintf($sql, $new_id, $old_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));  
    }   
}
?>