<?php

use SSilence\ImapClient\ImapClientException;
use SSilence\ImapClient\ImapConnect;
use SSilence\ImapClient\ImapClient as Imap;


function executeAutomations() {
    
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
               
    $model = new AutomationModel;
    
    $run = 0;
    $failed = 0;
    $entry_types = array(
        1 => 'article', 
        2 => 'file');
    
    $run_tasks = 0;
    
    $total_tasks = $model->getAutomationsCount();
    if($total_tasks === false) {
        $exitcode = 0;
        return $exitcode;
    }        
    
    $fmsg = 'Number of automated tasks: %d, %d tasks run, %d actions performed, %d actions failed';
    if (empty($total_tasks)) {
        $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
        return $exitcode;
    }
    
    $te_manager = new TriggerEntryModel;
    
    foreach($entry_types as $entry_type => $entry_type_str) {
        
        $model->etype = $entry_type_str;
        $model->emanager = DataConsistencyModel::getEntryManager('admin', $entry_type);
        $model->emanager->error_die = false;
        $model->emanager->tag_manager->error_die = false;
        
        $cf_manager = new CommonCustomFieldModel($model->emanager);
        
        $categories = $model->emanager->getCategoryRecords();   
        
        $automations = $model->getAutomations($entry_type);
        if($automations === false) {
            $exitcode = 0;
            continue;
        }
        
        $automations = $te_manager->setPredefinedTitles($automations);
        
        $map_file = sprintf('automation/inc/map_%s_automation.php', $entry_type_str);
        require APP_PLUGIN_DIR . $map_file;
        
        $cond = TriggerParserCondition::factory($entry_type_str . '_automation');
        
        foreach ($automations as $automation) {

            $conditions = unserialize($automation['cond']);
            $has_category_condition = strpos($automation['cond'], 's:8:"category";');
            $model->emanager->select_type = ($has_category_condition) ? 'category' : 'index';
            
            $automation_actions = unserialize($automation['action']);
            
            $sql = $cond->getSql($automation['cond_match'], $conditions, $categories, $model->emanager);
            $model->emanager->setSqlParams('AND ' . $sql, null, true);
            $model->emanager->setSqlParamsGroup('GROUP BY e.id');
            
            $num_entries = $model->emanager->getCountRecords();
            if($num_entries === false) {
                $exitcode = 0;
                continue;
            }
  
            $str = 'Task: "%s", %d entries match';
            $cron->logNotify($str, $automation['title'], $num_entries);
            
            $msg = array();
            $msg[] = sprintf($str, $automation['title'], $num_entries);
            
            
            $errors = array();
            $performed_actions = array();
            $failed_actions = array();
            
            $limit = 50; // select and update limit per query
            $run_times = ceil($num_entries/$limit);            
            
            // for($offset = 0; $offset < $num_entries; $offset += $limit){
            for($i = 1; $i <= $run_times; $i++){
                
                $entries = $model->emanager->getRecords($limit, 0);
                if($entries === false) {
                    $exitcode = 0;
                    continue;
                }
                
                if (!empty($entries)) {
                    if($i == 1) { // once per task
                        $run_tasks ++;
                    }
                    
                    // custom fields
                    $ids = $model->getValuesArray($entries);
                    $custom_rows = $cf_manager->getCustomDataByIds($ids);
                    $custom_fields = $cf_manager->getCustomFieldByEntryType(); // all active custom fields
                    
                    // checkboxes
                    $ch_value = array();
                    $_msg = AppMsg::getMsgs('common_msg.ini');
                    $ch_value['on'] = $_msg['yes_msg'];
                    $ch_value['off'] = $_msg['no_msg'];
                    
                    $custom = array();
                    foreach ($entries as $entry) {
                        $cvalues = (!empty($custom_rows[$entry['id']])) ? $custom_rows[$entry['id']] : array();
                        $custom_data = CommonCustomFieldView::getCustomData($cvalues, $cf_manager, $ch_value);
                        
                        foreach ($custom_fields as $custom_id => $v1) {
                            if (!empty($custom_data[$custom_id])) {
                                $custom[$entry['id']][$custom_id] = $custom_data[$custom_id];
                                
                            } else { // not set
                                $custom[$entry['id']][$custom_id]['title'] = $v1['title'];
                                // $custom[$entry['id']][$custom_id]['value'] = $custom_data[$entry['id']][$custom_id]; February 3, 2021
                                $custom[$entry['id']][$custom_id]['value'] = '';
                            }
                        }
                    }

                    foreach ($automation_actions as $action) {

                        $method = $actions[$action['item']]['func'];
                        $params = array($entries, $action['rule'], $automation);
                        
                        $extra_params = array();
                        if (!empty($actions[$action['item']]['func_params'])) {
                            $extra_params = $actions[$action['item']]['func_params'];
                        }
                        
                        if (!empty($custom)) {
                            $extra_params['custom'] = $custom;
                        }
                        
                        if (!empty($extra_params)) {
                            $params[] = $extra_params;
                        }
                        
                        // echo '<pre>', print_r($method,1), '<pre>';
                        // echo '<pre>', print_r($params,1), '<pre>';
                        // continue;

                        $ret = $model->runAction($method, $params);
                        // $ret = false;
                        if($ret === false) {
                            $failed_actions[] = $action['item'];
                            $failed ++;
							$cron->logCritical('Automated task action failed, method - %s', $method);

                        } else {
                            if(isset($ret['note'])) {
                                $cron->logNotify($ret['note']);
                            }
                            
                            $performed_actions[] = $action['item'];
                            $run ++;    
                        }
                    }
                }
                
            } // ->  for(...
  

			/*if (!empty($failed_actions)) {
                $performed_actions_msg = (empty($performed_actions)) ? 'none' : implode(', ', $performed_actions);
                $failed_actions_msg = implode(', ', $failed_actions);

                $error_msg = 'Some actions have not been completed. Performed actions: %s, Failed actions: %s';
                $error_msg = sprintf($error_msg, $performed_actions_msg, $failed_actions_msg);

                $exitcode = 0;
                $ret = $model->logFailed($automation, $error_msg);
                if($ret === false) {
                    $exitcode = 0;
                }

            } else {
                $msg[] = 'All actions have been successfully completed';
                $ret = $model->logFinished($automation, implode("\n", $msg));
                if($ret === false) {    
                    $exitcode = 0;
                }
            }*/
			
        }
    }
    
    $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
    
    return $exitcode;
}


function executeEmailAutomations() {
    require_once 'php-imap/autoload.php';
    
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
               
    $model = new AutomationModel;
    
    
    $run = 0;
    $failed = 0;
    $entry_types = array(
        30 => 'email'
    );
    
    $run_tasks = 0;
    
    $total_tasks = $model->getAutomationsCount();
    if($total_tasks === false) {
        $exitcode = 0;
        return $exitcode;
    }        
    
    $fmsg = 'Number of automated tasks: %d, %d tasks run, %d actions performed, %d actions failed';
    if (empty($total_tasks)) {
        $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
        return $exitcode;
    }
    
    $te_manager = new TriggerEntryModel;
    
    foreach($entry_types as $entry_type => $entry_type_str) {
        
        $model->etype = $entry_type_str;
        $model->emanager = new EmailParserEntryModel;
        $model->emanager->error_die = false;
        
        $automations = $model->getAutomations($entry_type);
        if($automations === false) {
            $exitcode = 0;
            continue;
        }
        
        $automations = $te_manager->setPredefinedTitles($automations);
        
        $automations_by_mailbox = array();
        foreach ($automations as $automation) {
            $automation_options = unserialize($automation['options']);
            $mailbox_id = $automation_options['mailbox_id'];
            
            $automations_by_mailbox[$mailbox_id][] = $automation; 
        }
        
        $map_file = sprintf('automation/inc/map_%s_automation.php', $entry_type_str);
        require APP_PLUGIN_DIR . $map_file;
        
        foreach ($automations_by_mailbox as $mailbox_id => $mailbox_automations) {
            
            // connecting to this mailbox
            $mailbox = $model->emanager->getMailbox($mailbox_id);
            $mailbox = unserialize($mailbox['data_string']);
            
            $cron->logNotify(sprintf('Connecting to %s...', $mailbox['host']));
            
            $setting = $mailbox;
            $setting['imap_user'] = $mailbox['user'];
            $setting['imap_pass'] = \EncryptedPassword::decode($mailbox['password']);
            
            try{
                $encryption = ($setting['ssl']) ? Imap::ENCRYPT_SSL : null;
                $imap = new Imap($setting['host'], $setting['imap_user'], $setting['imap_pass'], $encryption);
                
                $imap->selectFolder($setting['mailbox']);
                
                $message_num = $imap->countUnreadMessages();
                if (!$message_num) {
                    $cron->logNotify('Mailbox is empty');
                    continue;
                }
                
                $cron->logNotify(sprintf('%d email(s) found in the mailbox', $message_num));
                
                $unread_messages = $imap->getUnreadMessages();
                
                $messages = array();
                $message_actions = array();
                
                foreach ($unread_messages as $v) {
                    $body = ($v->message->html->body) ? $v->message->html->body : nl2br($v->message->text->body);
                    
                    $message = array(
                        'from' => sprintf('%s@%s', $v->header->details->from[0]->mailbox, $v->header->details->from[0]->host),
                        'to' => $v->header->to,
                        'cc' => $v->header->cc,
                        'subject' => $v->header->subject,
                        'body' => $body
                    );
                    
                    $uid = $v->header->uid;
                    $messages[$uid] = $message;
                    $message_actions[$uid] = array();
                    
                    foreach ($mailbox_automations as $automation) {
                        $conditions = unserialize($automation['cond']);
                        $automation_actions = unserialize($automation['action']);
                        
                        // checking if the conditions are met
                        $triggered = $model->triggerAutomation($automation['cond_match'], $conditions, $message);
                        if($triggered) {
                            
                            $run_tasks ++;
                            foreach($automation_actions as $akey => $aitem) {
                                
                                 // stop evaluating tasks, 
                                 // it will skip all next automations for this mailbox.
                                if($aitem['item'] == 'stop') {
                                    break 2;
                                }
                                               
                                $message_actions[$uid][] = $automation_actions[$akey];
                            }
                        }
                    }
                }
                
            } catch (ImapClientException $error) {
                $cron->logCritical($error->getMessage());
                $exitcode = 0;
                continue;
            }
            
            if (count($message_actions) > $mailbox['max_count']) {
                 $cron->logCritical('Mailbox is over quota, skipping...');
                 continue;
            }
            
            
            $emails = $model->getValuesArray($messages, 'from');
            $emails = array_unique($emails);
            
            $users = $model->getUserByEmail($emails);
            
            foreach ($message_actions as $uid => $_actions) {
                foreach ($_actions as $action) {
                    
                    $method = $actions[$action['item']]['func'];
                    $params = array($action['rule'], $messages[$uid], $users);
                    if (!empty($actions[$action['item']]['func_params'])) {
                        $extra_params = $actions[$action['item']]['func_params'];
                        $params[] = $extra_params;
                    }
                    
                    $ret = $model->runAction($method, $params);
                    if($ret === false) {
                        $failed_actions[] = $action['item'];
                        $failed ++;
                        $cron->logCritical('Automated task action failed, method - %s', $method);

                    } else {
                        $performed_actions[] = $action['item'];
                        $run ++;
                    }
                }
            }

            foreach (array_keys($messages) as $uid) {
                $imap->setFlagMessage($uid, '\Seen');
                //$imap->deleteMessage($uid);
            }
        }
        
        /*if (!empty($failed_actions)) {
            $performed_actions_msg = (empty($performed_actions)) ? 'none' : implode(', ', $performed_actions);
            $failed_actions_msg = implode(', ', $failed_actions);

            $error_msg = 'Some actions have not been completed. Performed actions: %s, Failed actions: %s';
            $error_msg = sprintf($error_msg, $performed_actions_msg, $failed_actions_msg);

            $exitcode = 0;
            $ret = $model->logFailed($automation, $error_msg);
            if($ret === false) {
                $exitcode = 0;
            }

        } else {
            $msg[] = 'All actions have been successfully completed';
            $ret = $model->logFinished($automation, implode("\n", $msg));
            if($ret === false) {    
                $exitcode = 0;
            }
        }*/
        
    }
    
    $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
    
    return $exitcode;
}


function executeEmailAutomations_v60() {
    
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
               
    $model = new AutomationModel;
    
    $run = 0;
    $failed = 0;
    $entry_types = array(
        30 => 'email'
    );
    
    $run_tasks = 0;
    
    $total_tasks = $model->getAutomationsCount();
    if($total_tasks === false) {
        $exitcode = 0;
        return $exitcode;
    }        
    
    $fmsg = 'Number of automated tasks: %d, %d tasks run, %d actions performed, %d actions failed';
    if (empty($total_tasks)) {
        $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
        return $exitcode;
    }
    
    $te_manager = new TriggerEntryModel;
    
    foreach($entry_types as $entry_type => $entry_type_str) {
        
        $model->etype = $entry_type_str;
        $model->emanager = new EmailParserEntryModel;
        $model->emanager->error_die = false;
        
        $automations = $model->getAutomations($entry_type);
        if($automations === false) {
            $exitcode = 0;
            continue;
        }
        
        $automations = $te_manager->setPredefinedTitles($automations);
        
        $automations_by_mailbox = array();
        foreach ($automations as $automation) {
            $automation_options = unserialize($automation['options']);
            $mailbox_id = $automation_options['mailbox_id'];
            
            $automations_by_mailbox[$mailbox_id][] = $automation; 
        }
        
        $map_file = sprintf('automation/inc/map_%s_automation.php', $entry_type_str);
        require APP_PLUGIN_DIR . $map_file;
        
        foreach ($automations_by_mailbox as $mailbox_id => $mailbox_automations) {
            
            // connecting to this mailbox
            $mailbox = $model->emanager->getMailbox($mailbox_id);
            $mailbox = unserialize($mailbox['data_string']);
            
            $cron->logNotify(sprintf('Connecting to %s...', $mailbox['host']));
            
            $setting = $mailbox;
            $setting['imap_user'] = $mailbox['user'];
            $setting['imap_pass'] = $mailbox['password'];
            
            $process_only_unseen = true;
            $delete_msg_on_read = false;
            $imap = new ImapParser($setting, $process_only_unseen, $delete_msg_on_read);
            
            if ($imap->open() === false) {
                $exitcode = 0;
                $cron->logCritical(sprintf('%s: Error to open IMAP connection - ', $mailbox_str, $imap->getLastError()));
                continue;
            }
            
            $message_numbers = $imap->getUnseenMsgNums();
            if (!$message_numbers) {
                 $cron->logNotify('Mailbox is empty');
                 continue;
            }
            
            $cron->logNotify(sprintf('%s email(s) found in the mailbox', count($message_numbers)));
            
            $messages = array();
            $message_actions = array();
            
            foreach ($message_numbers as $msgno) {
                
                $message = $imap->getMsgRequisits($msgno);
                if ($message === false) {
                    $cron->logCritical("Error processing Msgno#{$msgno}: " . $imap->getLastError());
                    $exitcode = 0;
                    continue;
                }
                
                $message['header'] = $imap->getHeader($msgno);
                
                $message['date'] = strtotime($message['date']);
                list($message_text, $is_html) = $imap->getMsgText($msgno);
                if ($message_text === false) {
                    $cron->logCritical("Error processing Msgno#{$msgno}: " . $imap->getLastError());
                    $exitcode = 0;
                    continue;
                }
                
                $message['body'] = nl2br($message_text);
                
                $messages[$msgno] = $message;
                $message_actions[$msgno] = array();
                
                foreach ($mailbox_automations as $automation) {
                    $conditions = unserialize($automation['cond']);
                    $automation_actions = unserialize($automation['action']);
                    
                    // checking if the conditions are met
                    $triggered = $model->triggerAutomation($automation['cond_match'], $conditions, $message);
                    if($triggered) {
                        
                        foreach($automation_actions as $akey => $aitem) {
                            
                             // stop evaluating tasks, 
                             // it will skip all next automations for this mailbox.
                              // if we had some actions before stop they executed but not counted as $run_tasks
                            if($aitem['item'] == 'stop') {
                                break 2;
                            }
                                           
                            $message_actions[$msgno][] = $automation_actions[$akey];
                        }
                        
                        $run_tasks ++;
                    }
                }
            }
            
            if (count($message_actions) > $mailbox['max_count']) {
                 $cron->logCritical('Mailbox is over quota, skipping...');
                 continue;
            }
            
            
            $emails = $model->getValuesArray($messages, 'from');
            $emails = array_unique($emails);
            
            $users = $model->getUserByEmail($emails);
            $msgs_uids_seen = array(); // uid of the message for removing
			
            foreach ($message_actions as $msgno => $_actions) {                
                foreach ($_actions as $action) {
                    
                    $method = $actions[$action['item']]['func'];
                    $params = array($action['rule'], $messages[$msgno], $users);
                    if (!empty($actions[$action['item']]['func_params'])) {
                        $extra_params = $actions[$action['item']]['func_params'];
                        $params[] = $extra_params;
                    }
					
                    $ret = $model->runAction($method, $params);
                    if($ret === false) {
                        $failed_actions[] = $action['item'];
                        $failed ++;
						$cron->logCritical('Automated task action failed, method - %s', $method);

                    } else {
                        $performed_actions[] = $action['item'];
                        $run ++;
                    }
                }
				
				$msgs_uids_seen[] = $imap->getMsgUid($msgno); // uid of the message for removing
            }
			
            // remove the mails
            foreach ($msgs_uids_seen as $uid) {
                if (!$imap->setMsgProcessed($uid)) {
                    $cron->logCritical($imap->getLastError());
                    $exitcode = 0;
                }
            }
            
            // $imap->deleteProcessedMsgs();
        }
        
		/*if (!empty($failed_actions)) {
            $performed_actions_msg = (empty($performed_actions)) ? 'none' : implode(', ', $performed_actions);
            $failed_actions_msg = implode(', ', $failed_actions);

            $error_msg = 'Some actions have not been completed. Performed actions: %s, Failed actions: %s';
            $error_msg = sprintf($error_msg, $performed_actions_msg, $failed_actions_msg);

            $exitcode = 0;
            $ret = $model->logFailed($automation, $error_msg);
            if($ret === false) {
                $exitcode = 0;
            }

        } else {
            $msg[] = 'All actions have been successfully completed';
            $ret = $model->logFinished($automation, implode("\n", $msg));
            if($ret === false) {    
                $exitcode = 0;
            }
        }*/

		
    }
    
    $cron->logNotify($fmsg, $total_tasks, $run_tasks, $run, $failed);
    
    return $exitcode;
}



?>