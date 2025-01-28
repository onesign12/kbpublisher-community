<?php

require_once 'eleontev/Dir/mime_content_type.php'; 


function spyDirectoryFiles($record_id = false) {
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new FileDirectoryModel;
    $model->fe_model = new FileEntryModel_dir;
    $model->fe_model->error_die = false;
    
    $model->s3_model = new FileEntryModel_s3;
    $model->s3_model->error_die = false;
    
    $model->fd_model = new FileDraftModel;
    $model->fd_model->error_die = false;
    $model->fd_model->mail_use_pool = true;
    
    $rq = array();
    $rp = array('step_comment' => '');
    $draft_action = new FileDraftAction($rq, $rp);
    
    
    $entry_type = $model->fe_model->entry_type;
    $rules = $model->getRules();

    if ($rules === false) {
        $exitcode = 0;
        return $exitcode;
    }

    if (empty($rules)) {
        $cron->logNotify('There are no directory rules');
        return $exitcode;
    }

    if($record_id) {
        if(!isset($rules[$record_id])) {
            $cron->logNotify('There is no directory rule with ID: %d', $record_id);
            return $exitcode;            
        }
        
        $rules_[$record_id] = $rules[$record_id];
        $rules = $rules_;
    }


    // settings
    $setting = SettingModel::getQuickCron(array(1,12));
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $setting = $model->fe_model->setFileSetting($setting);
    $model->setting = &$model->fe_model->setting;
    $model->s3_model->setting = &$model->fe_model->setting;
    
    $cat_records = $model->fe_model->getCategoryRecords();
    
    $aws_s3_access = false;
    if($setting['aws_s3_allow'] && _isS3RuleExists($rules)) { // amazon s3
        $aws_s3_access = true;
        try {
            $kbs3 = new KBS3Client();
            $kbs3->setRegion();
            $kbs3->connect();
            $kbs3->s3->listBuckets(); // just to catch connection error
        
        } catch (\Aws\S3\Exception\S3Exception $e) {    
            $aws_s3_access = false;
            $msg = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();            
            $cron->logCritical('AWS S3 error: ' . $msg . ' All AWS S3 rules skipped.');
            $exitcode = 0;
        }
    }
    
    $added = 0;
    $updated = 0;
    $deleted = 0;
    $statused = 0;
    $skipped = 0;
    $submitted = 0;

    // get files for directory rules
    foreach ($rules as $record_id => $rule) {
    
        if($rule['rule_id'] == 2 && !$aws_s3_access) { // amazon s3
            continue;
        }
    
        $fmanager = ($rule['rule_id'] == 1) ? $model->fe_model : $model->s3_model;
        
        try {
             
            if(!$fmanager->isDirectoryAllowed($rule['directory'])) {
                $cron->logCritical('Directory (%s) does not exist or it is not readable', rtrim($rule['directory'], '/'));
                $exitcode = 0;
                continue;
            }
            
        } catch (S3Exception $e) {
            
            $msg = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();            
            $cron->logCritical($msg);
        }
        
        // files in db for add/update
        // remove / from begining in dircetory and in db select ignoring starting /
        $files_db = $model->getFiles(ltrim($rule['directory'], '/'), $rule['parse_child']);
        if($files_db === false) {
            $exitcode = 0;
            continue;
        }
        
        // checking categories
        $obj = unserialize($rule['entry_obj']);
        foreach($obj->getCategory() as $category_id) {
            if (empty($cat_records[$category_id])) {
                $cron->logCritical('Category is missing (Rule ID: %s, Category ID: %s). Skipping to the next rule...', $record_id, $category_id);
                $exitcode = 0;
                continue 2;
            }
        }
        
        
        if ($rule['is_draft']) {
            $drafts_db = $model->getDraftFiles($rule['directory'], $rule['parse_child']);
        }
        
        
        $one_level = (empty($rule['parse_child'])) ? true : false;
        $files_dir = array();
        if($f = $fmanager->readDirectory($rule['directory'], ['one_level' => $one_level])) {
            // $files_dir = ExtFunc::multiArrayToOne($f['files']);
            $foptions = $f['options'];
            $files_dir = $f['files'];
        }
        // $files_dir = $fmanager->readDirectory($rule['directory'], ['one_level' => $one_level]);
        // $files_dir = ExtFunc::multiArrayToOne($files_dir);

        $sign = ($one_level) ? '' : '*';
        $cron->logNotify('%s file(s) found in a directory %s', count($files_dir), $rule['directory'] . $sign);
        
        $controller = new AppController();
        $controller->setWorkingDir();

        
        // echo 'rule: ', print_r($rule, 1);
        // echo 'files_db: ', print_r($files_db, 1);
        // echo 'files_dir: ', print_r($files_dir, 1);
        // exit;

        foreach(array_keys($files_dir) as $k) {
            
            $file = $files_dir[$k];
            $file_options = (isset($foptions[$k])) ? $foptions[$k] : array();
                
            $action = false;
            $file_id = null;
            $draft_id = null;
            $update_values = array();
            
                                     
            // file exists in db
            if (isset($files_db[$file])) {
                $match_file = $files_db[$file];

                // compare by md5hash
                if (!empty($match_file['md5hash'])) {
                    $hash = $fmanager->getMD5File($file, $file_options);
                    if ($hash != $match_file['md5hash']) {
                        $action = 'update';
                    }

                // compare by date_updated
                } else {
                    $date_modified = $fmanager->getFileTime($file, $file_options);
                    $date_update = strtotime($match_file['date_updated']);
                    if ($date_modified > $date_update) {
                        $action = 'update';
                    }
                }
 
                if($action) {
                    $file_id = $match_file['id'];
                    $keep_keys = array('date_posted', 'author_id', 'downloads');
                    foreach($keep_keys as $v) {
                        $update_values[$v] = $match_file[$v];
                    }
                }
                
            } elseif ($rule['is_draft'] && isset($drafts_db[$file])) {
                
                $match_file = $drafts_db[$file];

                // compare by md5hash
                if (!empty($match_file['md5hash'])) {
                    $hash = $fmanager->getMD5File($file, $file_options);
                    if ($hash != $match_file['md5hash']) {
                        $action = 'update';
                    }

                // compare by date_updated
                } else {
                    $date_modified = $fmanager->getFileTime($file, $file_options); 
                    $date_update = strtotime($match_file['date_updated']);
                    if ($date_modified > $date_update) {
                        $action = 'update';
                    }
                }
 
                if($action) {
                    $draft_id = $match_file['draft_id'];
                    $keep_keys = array('date_posted', 'author_id');
                    foreach($keep_keys as $v) {
                        $update_values[$v] = $match_file[$v];
                    }
                }
                
            } else {
                $action = 'insert';
            }

            if($action) {
                $data = $fmanager->getFileData($file, $file_options);    
                $data = $model->getData($file, $data, $fmanager, ($fmanager instanceof FileEntryModel_dir));
                
                $obj = unserialize($rule['entry_obj']);
                $updater_id = $obj->get('author_id');            
    
                $obj->set(array_merge($data, $update_values));
                $obj->set('id', $file_id);
                $obj->set('updater_id', $updater_id);
                
                // as it is new field it could miss in $rule['entry_obj'] 20.09.2012
                $obj->set('filename_index', $data['filename_index']);
                $obj->set('filename_disk', $data['filename_disk']); // 23.07.2015
                // $obj->set('addtype', $fmanager->addtype); // 15.01.2021
                
                // echo '<pre>', print_r($rule, 1), '</pre>';
                // echo '<pre>', print_r($obj, 1), '</pre>';
                // echo '<pre>', print_r($obj->get(), 1), '</pre>';
                // echo '<pre>', print_r(unserialize($rule['entry_obj']), 1), '</pre>';
                // echo '<pre>', print_r(array_merge($data, $update_values), 1), '</pre>';
                // continue;
                
                if ($rule['is_draft'] && empty($file_id)) {
                    
                    $draft_obj = new FileDraft;
                    $draft_obj->populate($rp->vars, $obj, $model->fe_model);
                    $draft_obj->set('id', $draft_id);
                    $draft_obj->set('updater_id', $updater_id);
                    
                    $ret = $model->addDraft($draft_obj, $action);
                    
                    if ($ret && $action == 'insert') {
                        $options = array(
                            'source' => 'dir_rule', 
                            'user_id' => $updater_id
                            );
                        
                        $workflow = $model->fd_model->getAppliedWorkflow($options);
                        
                        // echo '<pre>', print_r($workflow, 1), '</pre>';
                        if ($workflow) {
                            $draft_obj->set('id', $ret);
                            $step_comment = 'Automatically submitted via directory rules.';
                            
                            $draft_action->submitForApproval($obj, $model->fe_model, $draft_obj, $model->fd_model, $controller, $workflow, $step_comment);
                            $submitted ++;
                        }
                    }
                    
                } else {
                    $ret = $model->addFile($obj, $action);
                }
                
                // on error we do not die, mark as skipped
                if ($ret === false) {
                    $cron->logCritical('Error adding file (%s), skipped.', $file);
                    $exitcode = 0;
                    $skipped ++;
                    
                } else {
                    
                    if ($action == 'insert') { 
                        $added ++;
                    } else {
                        $updated ++;
                    }
                }
            }
            
        } // --> foreach(array_keys($files_dir) as $k) {
        

        // removed, all files added, updated already for the rule 
        $files_db_key = array();
        foreach(array_keys($files_db) as $k) {
            $files_db_key[$files_db[$k]['id']] = $k;
        }
                    
        $files_missed = array_diff($files_db_key, $files_dir);
        
        if($files_missed) {

            $sign = ($one_level) ? '' : '*';
            $cron->logInform('Found links to %d file(s) in database but files are not present in directory %s', 
                                count($files_missed), $rule['directory'] . $sign);
            $cron->logInform("Missing file(s) ids: %s", implode(",", array_keys($files_missed)));
            
            $file_ids = array_keys($files_missed);
            $missed_action = $setting['directory_missed_file_policy'];

            if(strpos($missed_action, 'status') !== false) {
                $status = (int) preg_replace('#[^\d]#', '', $missed_action);                    
                $ret = $model->setFileStatus($file_ids, $status);
                if($ret) {
                    $statused += count($files_missed);
                } else {
                    $exitcode = 0;
                }

            } elseif($missed_action == 'delete') {
                $ret = $model->deleteFile($file_ids);
                if($ret) {
                    $deleted += count($files_missed);
                } else {
                    $exitcode = 0;
                }
            }
        }


        if(!$model->updateExecution($record_id)) {
            $exitcode = 0;
        }
        
    } // -> foreach ($rules as $record_id => $rule) {


    $cron->logNotify('%d file(s) added.', $added);
    $cron->logNotify('%d file(s) updated.', $updated);
    $cron->logNotify('%d file(s) skipped.', $skipped);
    $cron->logNotify('%d file(s) changed status.', $statused);
    
    if ($submitted) {
        $cron->logNotify('%d file(s) submitted for approval.', $submitted);
    }
    
    // if($skipped) {
        // $cron->logInform('%d file(s) skipped. Error on adding to database.', $skipped); // send by email
    // }
    
    if(!$deleted) {
        $cron->logNotify('%d file(s) deleted.', $deleted);
    } else {
        $cron->logInform('%d file(s) deleted.', $deleted); // send by email
    }
    
    return $exitcode;
}


function _isS3RuleExists($rules) {
    $ret = false;
    foreach ($rules as $record_id => $rule) {
        if($rule['rule_id'] == 2) {
            $ret = true;
            break;
        }
    }
    
    return $ret;
}


// parse all the files and report all "bad", unable to download
function checkFilesAvailability($active_only = true) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new FileDirectoryModel;
    $model->fe_model = new FileEntryModel_dir;
    $model->fe_model->error_die = false;
    
    $parsed = 0;
    $not_accessible = [];

    // settings
    $setting = SettingModel::getQuickCron(1);
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $published_status = 'all';
    if($active_only) {
        $published_status = ListValueModel::getEntryPublishedStatuses(2);    
        if ($published_status === false) {
            $exitcode = 0;
            return $exitcode;
        }
    
        $published_status = implode(',', $published_status[2]);
        $model->setSqlParams("AND e.active IN({$published_status})");
    }
    
    $s3_file_exist = $model->isS3FilesExists();
    if ($s3_file_exist === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $aws_s3_access = false;
    if($s3_file_exist && KBS3Client::isAllowedToRead()) { // amazon s3
        $aws_s3_access = true;
        try {
            
            $kbs3 = new KBS3Client();
            $kbs3->setRegion();
            $kbs3->connect();
            $kbs3->s3->listBuckets(); // just to catch connection error
        
        } catch (\Aws\S3\Exception\S3Exception $e) {    
        
            $msg = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();            
            $cron->logCritical('AWS S3 error: ' . $msg);
            $exitcode = 0;
            $aws_s3_access = false;
        }
    }
    
    $setting = $model->fe_model->setFileSetting($setting);
    
    $result = $model->getFilesResult();
    if (!$result) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        $parsed++;
        
        try {
            if(!$aws_s3_access && $row['addtype'] == 3) { // unable to connect defined above
               $file = false; 
            } else {
                $file = FileEntryUtil::getFileDir($row, $setting['file_dir']);
            }
            
            if(!$file) {
                $str = 'ID: %d, File: %s';
                $path = FileEntryUtil::getFilePath($row, $setting['file_dir'], true);
                $not_accessible[$row['id']] = sprintf($str, $row['id'], $path);
            }
            
        } catch (\Aws\S3\Exception\S3Exception $e) {
            
            $msg = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();            
            $cron->logCritical($msg);
            $exitcode = 0;
        }
    }

    $cron->logNotify('%d file(s) parsed.', $parsed);
    
    if($not_accessible) {
        $files = $model->getFilesFilteredLink($not_accessible);
        $cron->logNotify('%d file(s) not accessible.\n%s', count($not_accessible), $files);
    }
    
    return $exitcode;
}

?>