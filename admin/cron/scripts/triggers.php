<?php

function executeTriggers($entry_type) {
    
    $map_file = sprintf('automation/inc/map_%s.php', $entry_type);
    require APP_PLUGIN_DIR . $map_file;
    
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
               
    $model = new TriggerModel;
    $model->entry_model = $model->getEntryManager($entry_type);
    
    $fired_total = 0;
    $performed_total = 0;
    
    $triggers = $model->getTriggers();
    if($triggers === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if (empty($triggers)) {
        $cron->logNotify('There are no triggers to execute.');
        return $exitcode;
    }
    
    
    $result = $model->getTriggersToRunResult();
    if ($result) {
        
        while ($row = $result->FetchRow()) {
            
            $state_before = unserialize($row['state_before']); // a state before saving
            $state_after = unserialize($row['state_after']); // a state after saving
            
            $changes = $model->compareStates($state_before, $state_after);
            if($changes === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            $errors = array();
            
            foreach ($triggers as $trigger) {
                $is_fired = ($trigger['cond_match'] == 2) ? true : false;
                
                $conditions = unserialize($trigger['cond']);
                
                if (empty($conditions)) { // got no conditions, fire the trigger
                    $is_fired = true;
                }
                
                foreach ($conditions as $condition) {
                    $method = $items[$condition['item']]['func'];
                    $params = array($state_after, $condition['rule'], $changes, $row['message_id']);
                    if (!empty($items[$condition['item']]['func_params'])) {
                        $extra_params = $items[$condition['item']]['func_params'];
                        $params[] = $extra_params;
                    }
                    $is_met = $model->isConditionMet($method, $params);
                    
                    if ($trigger['cond_match'] == 1 && $is_met) { // at least one condition is met, fire the trigger
                        $is_fired = true;
                        break;
                    }
                    
                    if ($trigger['cond_match'] == 2 && !$is_met) { // this condition doesn't met, so the trigger won't fire
                        $is_fired = false;
                        break;
                    }
                }
                
                if ($is_fired) { // this trigger's been fired, run the actions
                    $fired_total ++;
                    $cron->logNotify('"%s" trigger has been fired, entry ID: %d', $trigger['title'], $row['entry_id']);
                    
                    $msg = array();
                    $msg[] = 'Entry ID: ' . $row['entry_id'];
                    
                    $trigger_actions = unserialize($trigger['action']);
                    usort($trigger_actions, array($model, 'sortActions'));
                    
                    $performed_actions = array();
                    $failed_actions = array();
                    
                    foreach ($trigger_actions as $action) {
                        $method = $actions[$action['item']]['func'];
                        $params = array(&$state_after, $action['rule'], $trigger);
                        if (!empty($actions[$action['item']]['func_params'])) {
                            $extra_params = $actions[$action['item']]['func_params'];
                            $params[] = $extra_params;
                        }
                        
                        if($model->runAction($method, $params) === false) {
                            $failed_actions[] = $action['item'];
                                                        
                        } else {
                            $performed_actions[] = $action['item'];
                            $performed_total ++;
                        }
                    }
                    
                    if (!empty($failed_actions)) {
                        $exitcode = 0;
                        
                        $performed_actions_msg = (empty($performed_actions)) ? 'none' : implode(', ', $performed_actions);
                        $failed_actions_msg = implode(', ', $failed_actions);
                        
                        $error_msg = 'Trigger: %s, Performed actions: %s, Failed actions: %s';
                        $error_msg = sprintf($error_msg, $trigger['title'], $performed_actions_msg, $failed_actions_msg);
                        $errors[] = $error_msg;
                        
                        $msg[] = $error_msg;
                        if(!$model->logFailed($trigger, implode("\n", $msg))) {
                            return $exitcode;
                        }
                        
                    } else {
                        $msg[] = count($performed_actions) . ' action(s) have been successfully completed';
                        if(!$model->logFinished($trigger, implode("\n", $msg))) {
                            $exitcode = 0;
                            return $exitcode;
                        }
                    }
                    
                }
            }
            
            if (!empty($errors)) {
                $exitcode = 0;
                $failed_status = (($row['failed'] + 1) < $model->num_tries) ? 0 : 1;
                if(!$model->setStateFailed($row['id'], $failed_status, implode("\n", $errors))) {
                    return $exitcode;
                }
                    
            } else {
                if(!$model->setStateProcessed($row['id'])) {
                    $exitcode = 0;
                    return $exitcode;
                }
            }
        }
        
        $cron->logNotify('Total Stat: triggers fired %d times, %d actions performed', $fired_total, $performed_total);
    
    } else {
        $exitcode = 0;
        return $exitcode;
    }
    
    return $exitcode;
}

?>