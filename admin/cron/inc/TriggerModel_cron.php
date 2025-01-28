<?php

// it not used and not sure we need it


class TriggerModel_cron extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('table' => 'trigger', 'trigger_to_run', 'log_trigger');
    
    var $entry_model;
    
    var $num_tries = 2;
    
    
    function getEntryManager($entry_type) {
        switch ($entry_type) {
            case 'article':
                
                $emanager = new KBEntryModel();
                break;	
        }
    
        return $emanager;
    }
    
    
    function logFinished($trigger, $output) {
        return $this->log($trigger, $output, 1);
    }
    
    
    function logFailed($trigger, $output) {
        return $this->log($trigger, $output, 0);
    }
    
    
    function log($trigger, $output, $exitcode) {
        $sql = "INSERT {$this->tbl->log_trigger}
            (trigger_id, trigger_type, entry_type, output, exitcode)
            VALUES (%s, %s, %s, '%s', %d)";
        $sql = sprintf($sql, $trigger['id'], $trigger['trigger_type'], $trigger['entry_type'], $output, $exitcode);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function getTriggersToRunResult() {
        $sql = "SELECT * FROM {$this->tbl->trigger_to_run} 
            WHERE status = 0
            ORDER BY id";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function setStateProcessed($id) {
        $sql = "UPDATE {$this->tbl->trigger_to_run}
            SET date_executed = NOW(), status = 1
            WHERE id = %d";
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function setStateFailed($id, $status, $error_msg = '') {
        $sql = "UPDATE {$this->tbl->trigger_to_run}
            SET date_executed = NOW(), status = %d, 
            failed = failed + 1, failed_message = %s 
            WHERE id = %d";
        $sql = sprintf($sql, $status, $this->db->Quote($error_msg), $id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function getTriggers() {
        $sql = "SELECT * FROM {$this->tbl->table}
            WHERE entry_type = %d
            AND trigger_type = 1
            AND active = 1";
        $sql = sprintf($sql, $this->entry_model->entry_type);
        
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetArray();
            
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
    }
    
    
    function compareStates($state_before, $state_after) {
        $data = array();
        
        if (!$state_before) {
            $state_before = array();
        }
        
        $diff = array_diff($state_after, $state_before);
        foreach ($diff as $k => $v) {
            $data[$k] = array(
                'before' => $state_before[$k],
                'after' => $state_after[$k]);
        }
        
        return $data;
    }
    
    
    function sortActions($a, $b) { // send an email at the end
        if ($a['item'] == 'email') {
            return 1;
            
        } elseif ($b['item'] == 'email') {
            return -1;
        }
        
        return 0;
    }
    
    
    // conditions
    function isConditionMet($method, $params) {
        $callback = array($this, $method);
        return call_user_func_array($callback, $params);
    }
    
    
    function checkArticleCondition($state, $rule, $changes) {
        $is_new = empty($changes['id']['before']);
        return ($rule[0] == 'created') ? $is_new : !$is_new;
    }
    
    
    function checkSimpleFieldCondition($state, $rule, $changes, $extra_params) {
        $val = $rule[1];
        $field = $extra_params['field'];
        
        if ($rule[0] == 'changed') {
            return (!empty($changes[$field]));
        }
        
        if ($rule[0] == 'changed_from') {
            if (!empty($changes[$field])) {
                return ($val == $changes[$field]['before']);
            }
            return false;
        }
        
        if ($rule[0] == 'changed_to') {
            if (!empty($changes[$field])) {
                return ($val == $changes[$field]['after']);
            }
            return false;
        }
        
        if ($rule[0] == 'not_changed') {
            return (empty($changes[$field]));
        }
        
        if ($rule[0] == 'not_changed_from') {
            if (!empty($changes[$field])) {
                return ($val != $changes[$field]['before']);
            }
            return true;
        }
        
        if ($rule[0] == 'not_changed_to') {
            if (!empty($changes[$field])) {
                return ($val != $changes[$field]['after']);
            }
            return true;
        }
        
        if ($rule[0] == 'is') {
            return $state[$field] == $val;
        }
        
        if ($rule[0] == 'is_not') {
            return $state[$field] != $val;
        }
    }
    
    
    // actions
    function runAction($method, $params) {
        $callback = array($this, $method);
        return call_user_func_array($callback, $params);
    }
    
    
    function setSimpleField($state, $rule, $trigger, $extra_params) {
        $sql = "UPDATE {$this->entry_model->tbl->table}
            SET %s = %s
            WHERE id = %s";
        $sql = sprintf($sql, $extra_params['field'], $rule[0], $state['id']);
        
        $result = $this->db->Execute($sql);
                                    
        if ($result === false) {
            trigger_error($this->db->ErrorMsg());
            
        } else {
            $state[$extra_params['field']] = $rule[0];
        }
        
        return $result;
    }

}
?>