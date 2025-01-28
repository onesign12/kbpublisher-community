<?php

function sphinxTasks() {
	if(SphinxModel::isSphinxSingleInstance()) {
		return _sphinxTasksSingleInstance();
	} else {
		return _sphinxTasksMultipleInstance();
	}
}


function _sphinxTasksSingleInstance() {
    $exitcode = 1;
    $task_attempts = 3;
    	
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    // settings
    $setting = SettingModel::getQuickCron(array(1,141));
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $helper = SphinxTaskHelper::factory($setting);
    $model = new SphinxIndexModel;
    $m_model = new MaintainEntryModel;

    $sm = new SettingModel;
    $setting_id = $sm->getSettingIdByKey('sphinx_enabled');

    $tasks =& $m_model->getEntryTasks(array(4,5,6,7));
    if ($tasks === false) {
        $exitcode = 0;
        return $exitcode;
    }

	// echo '<pre>', print_r($tasks,1), '<pre>';
	// exit;

    // stop task
    $rule_id = 6;
    @$files_task = $tasks[$rule_id];
    if ($files_task) {
        
		$ret = $helper->emptyConfig();
        if ($ret !== true) {
            $cron->logCritical($ret);
            $exitcode = 0;
			return $exitcode;
		}
		
        $ret = $helper->cloudRestart();
        if ($ret === true) {
            $ret = $model->completeTask($rule_id);
            if ($ret === false) {
				$cron->logCritical($ret);
                $exitcode = 0;
            }
                
        } else {
            $cron->logCritical($ret);
            // $model->markFailedTask($rule_id, $ret);
            $exitcode = 0;
			
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
        }
		
		if($exitcode === 0) {
			return $exitcode;
		}
    }


    // files task
    $rule_id = 7;
    @$files_task = $tasks[$rule_id];
    if ($files_task) {
        
        $ret = $helper->generateStructure();
        if ($ret === true) {
            $ret = $model->completeTask($rule_id);
            if ($ret === false) {
				$cron->logCritical($ret);
                $exitcode = 0;
            }
                
        } else {
            $cron->logCritical($ret);
            // $model->markFailedTask($rule_id, $ret);
            $exitcode = 0;
			
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            
            $rule_id = 4; // restart task completed as well
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
        }
		
		if($exitcode === 0) {
			return $exitcode;
		}
    }


    // restart
	$rule_id = 4;
	@$restart_task = $tasks[$rule_id];
    if ($restart_task) {        
        $ret = $helper->cloudRestart();
        if ($ret === true) {
	        $ret = $model->completeTask($rule_id);
	        if ($ret === false) {
				$cron->logCritical($ret);
	            $exitcode = 0;
	        }
            
        } else {
            $cron->logCritical($ret);
			// $model->markFailedTask($rule_id, $ret);
            $exitcode = 0;
        
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
		}
		
		if($exitcode === 0) {
			return $exitcode;
		}
    }
	
    
    // reindex task
    $rule_id = 5;
    @$rebuild_task = $tasks[$rule_id];
    $entry_type = false;
    if ($rebuild_task) {
        $entry_type = $m_model->record_type[$rebuild_task['entry_type']];
        
        $ret = $helper->index('main', $entry_type);
        
        if ($ret === true) {
            $msg = $helper->getLogs();
			
	        preg_match_all('#total (\d+) docs#', $msg, $matches);
	        $new_docs = $matches[1];
            
            $ret = $model->log('main', $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
                
            $cron->logNotify('Indexing completed successfully. Collected entries: %d', array_sum($new_docs));
            
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // all good
            $sm->setSettings(array($setting_id => 1));
                
        } else {
            $cron->logCritical($ret);
            // $model->markFailedTask($rule_id, $ret);
			// $model->log('main', $ret, 0);
			$exitcode = 0;
                            
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            if ($ret === false) {
                $exitcode = 0;
            }
        }
    }
    
    return $exitcode;
}



function _sphinxTasksMultipleInstance() {
    $exitcode = 1;
    
    $task_attempts = 3;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    // settings
    $setting = SettingModel::getQuickCron(array(1,141));
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $helper = SphinxTaskHelper::factory($setting);
    
    $model = new SphinxIndexModel;
    $m_model = new MaintainEntryModel;
    
    if (!$setting['sphinx_enabled']) {
        
        // stop task
        $rule_id = 6;
        $stop_task =& $m_model->getEntryTask($rule_id);
        if ($stop_task === false) {
            $exitcode = 0;
            return $exitcode;
        }
        
        if ($stop_task) {
            
            $helper->settings['old_dir'] = $stop_task['value1'];
            
            $ret = $helper->stop();
            if ($ret === true) {
                $msg = $helper->getLogs();
                
                $ret = $model->log('stop', $msg);
                if ($ret === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
        
                $cron->logNotify('Sphinx server stopped successfully');
                
            } else {
                $cron->logCritical($ret);
                $model->log('stop', $ret, 0);
                
                $exitcode = 0;
                return $exitcode;
            }
            
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
        } else {
            $cron->logNotify('Sphinx search is disabled');
        }
        
        return $exitcode;
    }
    
    $sm = new SettingModel;
    $setting_id = $sm->getSettingIdByKey('sphinx_enabled');
    
    $dir = $setting['sphinx_data_path'];
    $config_path = $dir . 'sphinx.conf';
    
    
    // files task
    $rule_id = 7;
    $files_task =& $m_model->getEntryTask($rule_id);
    if ($files_task === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if ($files_task) {
        
        $ret = $helper->generateStructure();
        if ($ret === true) {
            $msg = $helper->getLogs();
            
            $ret = $model->log('structure', $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
                
            $cron->logNotify('Files generated successfully');
            
            // task completed
            $ret = $model->completeTask($rule_id);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
                
        } else {
            $cron->logCritical($ret);
            
            $model->markFailedTask($rule_id, $ret);
            $model->log('structure', $ret, 0);
            
            if ($files_task['failed'] + 1 == $task_attempts) { // attempts exceeded
                
                // task completed
                $ret = $m_model->statusEntryTask(0, $rule_id, 0);
                if ($ret === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
                
                $rule_id = 4; // restart task completed as well
                $ret = $m_model->statusEntryTask(0, $rule_id, 0);
                if ($ret === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
                
            }
            
            $exitcode = 0;
            return $exitcode;
        }
        
    }
    
    
    // restart task
    $rule_id = 4;
    $restart_task =& $m_model->getEntryTask($rule_id);
    if ($restart_task === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if ($restart_task) {
        if ($restart_task['failed'] >= $task_attempts) { // attempts exceeded
            $restart_task = false;
            
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
        } else {
            $helper->settings['old_dir'] = $restart_task['value1'];
        }
    }
    
    // checking if searchd is running
    $searchd_failure = false;
    if ($setting['sphinx_enabled'] == 1) {
        $searchd_status = SphinxSetting\SettingValidator::validateConnection($setting);
        $searchd_failure = ($searchd_status !== true);
        
        if ($searchd_failure) {
            $cron->logNotify('Searchd is not running. Trying to restart...');
        }
    }
    
    if ($searchd_failure || $restart_task) { // searchd
        $ret = $helper->stop();
        if ($ret === true) {
            $msg = $helper->getLogs();
            
            $ret = $model->log('stop', $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            $cron->logNotify('Sphinx server stopped successfully');
                
        } else {
            $cron->logCritical($ret);
            
            $ret = $model->log('stop', $ret, 0);
            
            $exitcode = 0;
            return $exitcode;
        }
        
        $ret = $helper->start();
        if ($ret === true) {
            $msg = $helper->getLogs();
            
            $ret = $model->log('start', $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            $cron->logNotify('Sphinx server started successfully');
            
        } else {
            $cron->logCritical($ret);
            
            if ($restart_task) {
                $model->markFailedTask(4, $ret);
            }
            
            $ret = $model->log('start', $ret, 0);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            $exitcode = 0;
            return $exitcode;
        }
    }

    if ($restart_task) {

        // task completed
        $ret = $model->completeTask($rule_id);
        if ($ret === false) {
            $exitcode = 0;
            return $exitcode;
        }
        
    }
    
    // rebuild task
    $rule_id = 5;
    $rebuild_task =& $m_model->getEntryTask($rule_id);
    if ($rebuild_task === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $entry_type = false;
    if ($rebuild_task) {
        $entry_type = (!empty($rebuild_task['entry_type'])) ? $m_model->record_type[$rebuild_task['entry_type']] : 0;
        //$ret = sphinxIndex(false, $entry_type);
        
        $ret = $helper->index('main', $entry_type);
        
        if ($ret === true) {
            $msg = $helper->getLogs();
			
	        preg_match_all('#total (\d+) docs#', $msg, $matches);
	        $new_docs = $matches[1];
            
            $ret = $model->log('main', $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
                
            $cron->logNotify('Indexing completed successfully. Collected entries: %d', array_sum($new_docs));
            
            // task completed
            $ret = $m_model->statusEntryTask(0, $rule_id, 0);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // all good
            $sm->setSettings(array($setting_id => 1));
                
        } else {
            $cron->logCritical($ret);
            
            $model->markFailedTask($rule_id, $ret);
            $model->log('main', $ret, 0);
            
            if ($rebuild_task['failed'] + 1 == $task_attempts) { // attempts exceeded
                
                // task completed
                $ret = $m_model->statusEntryTask(0, $rule_id, 0);
                if ($ret === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
                
                $rule_id = 4; // restart task completed as well
                $ret = $m_model->statusEntryTask(0, $rule_id, 0);
                if ($ret === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
                
            }
            
            $exitcode = 0;
            return $exitcode;
        }
        
    }
    
    return $exitcode;
}


function sphinxIndex($is_delta = false) {
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    // settings
    $setting = SettingModel::getQuickCron(array(1,141));
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if ($setting['sphinx_enabled'] != 1) {
        $cron->logNotify('Sphinx search is disabled');
        return $exitcode;
    }
    
    $helper = SphinxTaskHelper::factory($setting);
    
    $model = new SphinxIndexModel;
    $m_model = new MaintainEntryModel;
    
    $index_type = ($is_delta) ? 'delta' : 'main';
    
    // indexing
    $ret = $helper->index($index_type, false);
    if ($ret === true) {
        $log_needed = true;
        
        $msg = $helper->getLogs();
        
        preg_match_all('#total (\d+) docs#', $msg, $matches);
        $new_docs = $matches[1];
		$delta_msg = '';
		
        if ($index_type == 'delta') {
			$delta_msg = ' [delta]';
            
            $prev_log = $model->getLastLog('delta');
            if ($prev_log === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            if (!empty($prev_log)) {
                preg_match_all('#total (\d+) docs#', $prev_log['output'], $matches);
                $prev_docs = $matches[1];
                
                if ($prev_docs == $new_docs) { // got some updates
                    $log_needed = false;
                }
            }
        }
        
        if ($log_needed) {
            $ret = $model->log($index_type, $msg);
            if ($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }
        }
        
        $cron->logNotify('Indexing completed successfully%s. Collected entries: %d', $delta_msg, array_sum($new_docs));
        
    } else {
        
        $msg = "Indexing error:\n " . $ret;
        
        $ret = $model->log($index_type, $msg, 0);
        $cron->logCritical($msg);
        
        $exitcode = 0;
    }
    
    return $exitcode;
}

?>