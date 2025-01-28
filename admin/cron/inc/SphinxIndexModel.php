<?php

class SphinxIndexModel extends AppModel
{
    
    var $tables = array('entry_task', 'log_sphinx');
    
    var $action_types = array(
        1 => 'main',
        2 => 'delta',
        3 => 'start',
        4 => 'stop',
        5 => 'structure'
    );


	function setSphinxRestartTasks($old_dir) {
		$rule_keys = array(
			'sphinx_files', 
			'sphinx_restart',
			'sphinx_index'
			);
		
		$data = array();
		foreach($rule_keys as $v) {
			$rule_id = array_search($v, $this->entry_task_rules);
			$value1 = (in_array($v, array('sphinx_stop','sphinx_restart'))) ? $old_dir : NULL;
			$data[] = array($rule_id, 0, $value1);
		}
		
		$this->saveTask($data);
	}


	function setSphinxStopTasks($old_dir) {
		$this->setSphinxTask('stop', $old_dir);
	}


    function setSphinxTask($key, $value = NULL) {
		$rule_id = array_search('sphinx_' . $key, $this->entry_task_rules);
		$data = array(array($rule_id, 0, $value));
		$this->saveTask($data);
    }

	
    function saveTask($data) {		
        $sql = "REPLACE {$this->tbl->entry_task} (rule_id, entry_id, value1) VALUES ?";
        $sql = MultiInsert::get($sql, $data);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
	
    
    function markFailedTask($rule_id, $msg) {
        $sql = "UPDATE {$this->tbl->entry_task}
            SET failed = failed + 1, failed_message = %s
            WHERE rule_id = %d";
        $sql = sprintf($sql, $this->db->Quote($msg), $rule_id);
        
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function completeTask($rule_id) {
        $sql = "UPDATE {$this->tbl->entry_task} 
            SET active = 0, failed = 0, failed_message = ''
            WHERE rule_id = %d";
        $sql = sprintf($sql, $rule_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;       
    }
    
    
    function log($action_key, $output, $exitcode = 1, $entry_type = 0) {
        if (strlen(trim($output)) == 0) {
            return true;
        }
        
        $action_type = array_search($action_key, $this->action_types);
        $output = Utf8::encode($output);
        
        $sql = "INSERT {$this->tbl->log_sphinx}
            (entry_type, action_type, output, exitcode)
            VALUES (%d, %d, %s, %d)";
        $sql = sprintf($sql, $entry_type, $action_type, $this->db->Quote($output), $exitcode);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function getLastLog($action_key) {
        $action_type = array_search($action_key, $this->action_types);
        $sql = "SELECT * FROM {$this->tbl->log_sphinx} WHERE action_type = %d ORDER BY date_executed DESC LIMIT 1";
        $sql = sprintf($sql, $action_type);
        
        $result = $this->db->Execute($sql);

        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        $row = $result->FetchRow();
        return ($row) ? $row : array();
    }
    
}

?>