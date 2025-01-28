<?php
    
// when mustread is new and no one populated yet
function populateUsersToMustread() {
    return _populateUsersToMustread(true);
}

// when add new registered users
function appendUsersToMustread() {
    return _populateUsersToMustread(false);
}
    

function _populateUsersToMustread($first_run) {
    
    $exitcode = 1; // well

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MustreadModel();
    $model->error_die = false;
    
    $users_added = 0;
    
    if($first_run) {
       $result =& $model->getMustreadFirstRunRecordsResult();
    } else {
        $result =& $model->getMustreadRecordsResult();
    }
    
    if($result) {
        
        while ($row = $result->FetchRow()) {
            $mustread_id = $row['id'];
            $entry_id = $row['entry_id'];
            $entry_type = $row['entry_type'];
            
            $emanager = DataConsistencyModel::getEntryManager('admin', $entry_type);
            
            $is_entry = $emanager->isEntryAvailable($entry_id);
            if($is_entry === false) {
                $exitcode = 0;
                break;
            }
            
            if(!$is_entry) { // record not available by status or deleted
                continue;
            }
            
            $rules = $model->getMustreadRule($mustread_id);
            if($rules === false) {
                $exitcode = 0;
                break;
            }
            
            $entry_roles = $emanager->getRoleReadById($entry_id);            
            $cat_roles = array();
            if($entry_type != 3) { // skip news
                foreach($emanager->getCategoryById($entry_id) as $cat_id) {
                    $cat_roles += $emanager->cat_manager->getRoleReadById($cat_id);
                }
            }
            
            // $model->emptyUsers($mustread_id); // to test only 
            
            // echo '<pre>', print_r($rules,1), '<pre>';
            // echo '<pre>', print_r($entry_roles,1), '<pre>';
            // echo '<pre>', print_r($cat_roles,1), '<pre>';
            // continue;
            
            $affected = $model->populateUsersToMustread($emanager, $mustread_id, $rules, $entry_roles, $cat_roles);
            if($affected === false) {
                $exitcode = 0;
                break;
            }
            
            $users_added += $affected;
        }
    
    } else {
        $exitcode = 0;
    }

    $cron->logNotify('(%d) mustreads to user created', $users_added);

    return $exitcode;
}

    
function notifyUsersAboutMustread($days = array(0)) {  
    
    $exitcode = 1; // well

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MustreadModel();
    $sender = new AppMailSender();

    $processed = 0;
    
    foreach($days as $days_after) {
    
        $result =& $model->getUsersToNotifyResult($days_after);    
        if($result) {
            while ($row = $result->FetchRow()) {
                
                $pool_id = _sendMustreadEntryNotification($sender, $row);
                
                if ($pool_id === false) {
                    $exitcode = 0;
                    $cron->logCritical('Cannot send notification into pool: %s.', print_r($row, 1));
                    
                // } elseif($pool_id === 'no_user_to_send') {
                    // $cron->logNotify('No user to send email notification.');
                    
                // only on firts run not on remind
                } elseif($days_after == 0) {
                    
                    $ret = $model->markAsNotified($row['mustread_id'], $row['user_id']);
                    if (!$ret && $pool_id !== 'no_user_to_send') {
                        $ret = $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    }

                    if (!$ret) {
                        $exitcode = 0;
                    } else {
                        $processed++;
                    }
                    
                } else {
                    $processed++;
                }
            }
    
        } else {
            $exitcode = 0;
        }
    }


    $cron->logNotify('(%d) notifications sent.', $processed);

    return $exitcode;
}


function remindUsersAboutMustread($days) {
    return notifyUsersAboutMustread($days);
}


function _sendMustreadEntryNotification($sender, $row) {
    
    $view_map = BaseModel::$entry_type_to_view;
    $cc = &AppController::getClientController();
    
    $more = ['mstr' => 1];
    $row['link'] = $cc->getFolowLink($view_map[$row['entry_type']], false, $row['entry_id'], false, $more);

    // entry data, returns false on db error
    $entry = $sender->model->getEntryDataByEntryType($row['entry_id'], $row['entry_type']);
    if($entry === false) {
        return false;
    }

    $row['title'] = $entry['title'];
    $row['type'] = $sender->model->getEntryTypeTitleByEntryType($row['entry_type']);
    $row['account_link'] = $cc->getFolowLink('member');
    
    $pool_id = $sender->sendMustreadEntryNotification($row['user_id'], $row);
    
    // notifications
    $options = array(
        'letter_key'  => $sender->letter_key,
        'ntf_key'     => 'mustread',
        'ntf_message' => $row['note'],
        'ntf_users'   => $row['user_id']
    );

    $ntf_sent = AppNotificationSender::send($options, $row);

    return $pool_id;
}


function disactivateExpiredMustreads() {
    
    $exitcode = 1; // well

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MustreadModel();
    
    $processed = 0;
    
    $affected = $model->disactivateExpiredMustreads();
    if($affected === false) {
        $exitcode = 0;
    } else {
       $processed = $affected; 
    }

    $cron->logNotify('(%d) records deactivated', $processed);

    return $exitcode;
}
?>