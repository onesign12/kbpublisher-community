<?php



/**
 * Deletes old mail messages from the database pool.
 */
function freshDbMail($status, $days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');

    $sender = new AppMailSender();
    if ($sender->model->freshPool($status, $days)) {
        $cron->logNotify('Messages older than %d days were deleted (status: %s).', $days, $status);
    } else {
        $exitcode = 0;
    }

    return $exitcode;
}


/**
 * Sends mail messages from the database pool.
 * (regular, to users)
 */
function dbMail($frequency = 5) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    $send_per_hour = SettingModel::getQuickCron(134, 'mass_mail_send_per_hour');
    if ($send_per_hour === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $call_per_hour = floor(60 / $frequency); 
    $send_per_session = floor($send_per_hour / $call_per_hour);

    // user active status
    $active_status = $manager->getUserActiveStatus();

    $sent = 0;
    $sender = new AppMailSender();
    
    $force_ltype = array(21); // workflows first
    $result =& $sender->model->getPoolRecordsResult($send_per_session, $force_ltype);
    if($result) {
        
        $num_send = $result->RecordCount();
        $cron->logNotify('%u message(s) to be sent.', $num_send);
        
        // initialize $mail object once
        $s = array('smtp_keep_alive' => true);
        $mail =& $sender->getMailerObj(NULL, $s);

        while ($row = $result->FetchRow()) {

            $mail->ClearAllRecipients();

            // set values for corresponding fields
            $message = unserialize($row['message']);
            if(!is_array($message)) {
                $msg = sprintf('Cannot unserialize message array, set status failed (2) for pool ID: %d', $row['id']);
                $sender->model->markFailedPool($row['id'], $msg, 2);
                $cron->logCritical($msg);
                $exitcode = 0;
                continue;
            }
            
            $sender->populateMailFromArray($mail, $message);
            
            if ($mail->Send()) {
                $sent += 1;
                $sender->model->markSentPool($row['id']);
            
            } else {
                
                $sender->model->markFailedPool($row['id'], $mail->ErrorInfo);
                $cron->logCritical("Cannot send mail: {$mail->ErrorInfo}");
                $exitcode = 0;
                $row['failed'] ++;
                
                $num_failed_user_check = 6;
                $num_failed = 31;
                
                $pattern = array("#[\n\r]#","#\s{2,}#");
                $replace = ' ';
                
                // no recipients
                $r = count($mail->getToAddresses()) + count($mail->getCcAddresses()) + count($mail->getBccAddresses());
                if ($r < 1) {
                    $msg = sprintf('There are no recipients, 
                    set status failed (2) for pool ID: %d', $row['id']);
                    $msg = preg_replace($pattern, $replace, $msg);
                    
                    $sender->model->markFailedPool($row['id'], $msg, 2, false);
                    $cron->logCritical($msg);
                
                // all failed
                } elseif($row['failed'] >= $num_failed) {
                    $msg = sprintf('Maximum number of tries (%d) achived, 
                    set status failed (2) for pool ID: %d', $num_failed, $row['id']);
                    $msg = preg_replace($pattern, $replace, $msg);
                    
                    $sender->model->markFailedPool($row['id'], $msg, 2, false);
                    $cron->logCritical($msg);

                // user
                } elseif($row['failed'] >= $num_failed_user_check) {

                    // check if user exists
                    if(isset($message['to']) && count($message['to']) == 1) {
                                            
                        $user_email = $message['to'][0][0];
                        $user = $sender->model->getUserByEmail($user_email, false);
                        
                        $error = false;
                        if($user === false) {
                            $exitcode = 0;
                            
                        } elseif(!$user) {
                            $error = true;
                            $msg = sprintf('There is no user with email: %s. 
                            Set status failed (2) for pool ID: %d', $user_email, $row['id']);
                            
                        } elseif (!in_array($user['active'], $active_status)) {
                            $error = true;
                            $msg = sprintf('User status is inactive. 
                            Set status failed (2) for pool ID: %d', $row['id']);
                        
                        } elseif (!Validate::getRegex('email', $user_email, true)) {
                            $error = true;
                            $msg = sprintf('User emial (%s) is incorrect. 
                            Set status failed (2) for pool ID: %d', $user_email, $row['id']);
                        }
                        
                        if($error) {
                            $msg = preg_replace($pattern, $replace, $msg);
                            $sender->model->markFailedPool($row['id'], $msg, 2, false);
                            $cron->logCritical($msg);
                        }
                        
                    }
                }
                                
                                
            } // -> if ($mail->Send()) {
        
        }
    
    } else {
        $exitcode = 0;
    }
    
    $cron->logNotify('%u message(s) sent.', $sent);

    return $exitcode;
}


/**
 * Sends mail messages from the file-pool.
 * (mostly to admin)
 */
function periodicMail($period, $test = 1) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');

    list($sent, $exitcode) = $cron->pool->sendMail($period, $exitcode);
    $cron->logNotify('%u message(s) sent (period: %s).', $sent, $period);

    return $exitcode;
}
?>