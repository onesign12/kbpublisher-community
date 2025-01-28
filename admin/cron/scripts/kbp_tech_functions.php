<?php
function setupTest($run_magic, $mail_cron_test = true) {
    $exitcode = 1;
    $exitcode_test = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
                
    // cron
    $model = new KBPReportModel;
    $model->error_die = false;
    
    $rq = array();
    $rp = array();
    $action = new KBPReportAction($rq, $rp);
    
    // settings
    $sm = new SettingModel();
    $model->setting = $sm->getSettings('1, 2, 134, 140, 141');
    
    $a = array();    
    $setup_msg = 'Setup Test: ';
    
    $items = array();
    foreach($model->items as $k => $v) {
        foreach($v as $k2 => $v2) {
            $items[] = $v2;
        }
    }
    
    foreach ($items as $key) {
        $method = 'check' . str_replace('_', '', ucwords($key));
        
        if($key == 'file_dir') {
            $a[$key] = $action->$method($model);
            
            if(!$a[$key]['code']) {
                $exitcode_test = 0; 
                $cron->logCritical($setup_msg . $a[$key]['msg']);
            
            // also check kbp_dir/deleted and kbp_dir/history
            // not included in setup report
            } else {

                $sdirs = [
                    $model->setting['file_dir'] . 'deleted',
                    $model->setting['file_dir'] . 'history',
                ];
                foreach($sdirs as $dir) {
                    if(is_dir($dir) && !is_writable($dir)) {
                        $cron->logCritical($setup_msg . sprintf('Directory %s is not writeable', $dir));
                    }
                }
            }
        
        } elseif($key == 'cron') {
            
            $first_cron_ts = $model->getFirstCronExecution();
            if($first_cron_ts === false) {
                $exitcode = 0;
            }
                    
            if($first_cron_ts) {
                $a[$key] = $action->$method($model);
                
                if(!$a[$key]['code']) {
                    $exitcode_test = 0; 
                    $cron->logCritical($setup_msg . $a[$key]['msg']);
                }
            }
        
        } else {
            
            $a[$key] = $action->$method($model);
            
            if(!$a[$key]['code']) {
                $exitcode_test = 0; 
                $cron->logCritical($setup_msg . $a[$key]['msg']);
            }
        }
    }

    // if error in some test
    if ($exitcode_test == 0) {
        $link = APP_ADMIN_PATH . 'index.php?module=setting&page=admin_setting&sub_page=kbpreport';
        $cron->logNotify($setup_msg . 'for details please see ' . $link);
    }
    
    $data_string = serialize($a);
    
    $ret = $model->saveReport($data_string);
    if($ret === false) {
        $exitcode = 0;
    }

    return $exitcode;
}


function cleanCacheDirectory($days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    if(!is_dir(APP_CACHE_DIR)) {
        $cron->logCritical('Directory (%s) does not exist.', APP_CACHE_DIR);
        $exitcode = 0;
        return $exitcode;
    }
    
    $pattern = "#export_\w{32}$#";
    $deleted = _removeCacheFiles($pattern, 'dir', 0);
    
    $pattern = "#cache_\w{32}_\w{32}$#";
    // $pattern = "#cache_".md5('pdf')."_\w{32}$#";// pdf is a group here
    $deleted += _removeCacheFiles($pattern, 'file', $days);
    
    $cron->logNotify('%d file(s) removed.', $deleted);
    
    return $exitcode;
}


function _removeCacheFiles($pattern, $type, $days = 0) {
    $d = new MyDir;
    $d->one_level = true;
    $d->full_path = true;
    
    if($type == 'dir') {
        $items = &$d->getDirs(APP_CACHE_DIR);
    } else {
        $items = &$d->getFilesDirs(APP_CACHE_DIR);
    }
    
    $items = preg_grep($pattern, $items);
    
    $deleted = 0;
    foreach($items as $v) {
        $ret = $d->removeFilesDirs($v, $days);
        if($ret) {
            $deleted++;                
        }
    }
    
    return $deleted;
}


function cleanDraftDirectory() {
    $exitcode = 1;
    $deleted_files = 0;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new FileDirectoryModel;
    
    $setting = SettingModel::getQuickCron(1);
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }

    if(empty($setting['file_dir'])) {
        $cron->logCritical('File directory is not defined.');
        $exitcode = 0;
        return $exitcode;
    }

    $draft_dir = $setting['file_dir'] . 'draft/';
    if(!is_dir($draft_dir)) {
        $cron->logNotify('Directory (%s) does not exist. 0 file(s) removed.', $draft_dir);
        return $exitcode;
    }
    
    $d = new MyDir;
    $d->one_level = true;
    $d->full_path = true;
    $d->setAllowedExtension(array_filter(explode(',', $setting['file_allowed_extensions'])));
    $d->setDeniedExtension(array_filter(explode(',', $setting['file_denied_extensions'])));
    
    $files = $d->getFilesDirs($draft_dir);
    $drafts = $model->getDraftFiles($draft_dir, false);
    
    $dead_drafts = array_diff($files, array_keys($drafts));
    
    // echo '<pre>' . print_r($files, 1) . '</pre>';
    // echo '<pre>' . print_r(array_keys($drafts), 1) . '</pre>';
    // echo '<pre>' . print_r($dead_drafts, 1) . '</pre>';
    // exit;
    
    foreach($dead_drafts as $v) {
        $ret = $d->removeFilesDirs($v);
        if($ret) {
            $deleted_files ++;          
        }
    }
    
    $cron->logNotify('%d file(s) removed.', $deleted_files);
    
    return $exitcode;
}


function cleanDeletedDirectory($days) {
    $exitcode = 1;
    $deleted_files = 0;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $setting = SettingModel::getQuickCron(1);
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }

    if(empty($setting['file_dir'])) {
        $cron->logCritical('File directory is not defined.');
        $exitcode = 0;
        return $exitcode;
    }

    $deleted_dir = $setting['file_dir'] . 'deleted/';
    if(!is_dir($deleted_dir)) {
        $cron->logNotify('Directory (%s) does not exist. 0 file(s) removed.', $deleted_dir);
        return $exitcode;
    }
    
    $d = new MyDir;
    $d->one_level = true;
    $d->full_path = true;
    $d->setAllowedExtension(array_filter(explode(',', $setting['file_allowed_extensions'])));
    $d->setDeniedExtension(array_filter(explode(',', $setting['file_denied_extensions'])));
    
    $files = $d->getFilesDirs($deleted_dir);
    foreach($files as $filename) {
        $ret = $d->removeFilesDirs($filename, $days);
        if($ret) {
            $deleted_files ++;
        }
    }
    
    $cron->logNotify('%d file(s) removed.', $deleted_files);
    
    return $exitcode;
}


function setupValidate() {
    KBValidateLicense::sendRequest('license_check');
    return 1;
}


function getNotUsedHtmlEditorFiles($delete_file = false) {
    $exitcode = 1;
    $not_used_files = array();
    $deleted = 0;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;
    
    $tables = array(
        'kb_entry'         => 'body',
        'kb_entry_history' => 'entry_data',
        'entry_draft'      => 'entry_obj',
        'news'             => 'body',
        'article_template' => 'body',
        'stuff_data'       => 'data_string' // page design custom blocks
    );    
    
    $d = new MyDir;
    $d->one_level = false;
    $d->full_path = true;
    $d->setSkipDirs('_thumbs');
    $d->setSkipFiles('.htaccess', '.DS_Store');
    
    $upload_dir = SettingModel::getQuick(1, 'html_editor_upload_dir');
    $files = &$d->getFilesDirs($upload_dir);
    $files = ExtFunc::multiArrayToOne($files);
    $files = array_chunk($files, 200);
    set_time_limit(600);
    
    foreach($files as $k => $files_) {
        if($k) {
            sleep(10); // sleep each files chunk
        }
        
        foreach($files_ as $file) {
            // $fshort = str_replace($upload_dir, '', $file);
            $fshort = basename($file);
            $fsearch = str_replace(' ', '%20', $fshort);
            $fsearch = addslashes($fsearch);
            
            $found = false;
            foreach($tables as $table => $field) {
                $in_use = $model->isKbUploadFileInUse($table, $field, $fsearch);
                if ($in_use === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
            
                if($in_use) {
                    break;
                }
            } 
                
            if(!$in_use) {
                $not_used_files[] = $file;
                $not_used_files_short[] = $fshort;
            
                if($delete_file) {
                    if(unlink($file)) {
                        $deleted++;
                    }
                    
                }
            }
        }
    }
    
    $short_log = '';
    if($not_used_files) {
        $short_log = "\n" . implode("\n", $not_used_files_short);
        $short_log = str_replace('%', '%%', $short_log); // escape %
        
        $filename = APP_CACHE_DIR . 'not_used_htmleditor_files.log';
        $files_log = implode("\n", $not_used_files);
        $ret = FileUtil::write($filename, $files_log, true);
    }    
    
    $cron->logNotify("%d not used file(s) found.{$short_log}", count($not_used_files));
    
    if($deleted) {
        $cron->logNotify("%d not used file(s) deleted.", $deleted);
    }
    
    return $exitcode;
}

// not implemented
// function getMissedFiles($delete_file = false) {
//     $exitcode = 1;
//     $missed_files = array();
//     $deleted = 0;
// 
//     $reg =& Registry::instance();
//     $cron =& $reg->getEntry('cron');
// 
//     $manager =& $cron->manager;
//     $model = new FileEntryModel;
//     $
// 
// 
//     foreach($files as $k => $files_) {
//         if($k) {
//             sleep(10); // sleep each files chunk
//         }
// 
//         foreach($files_ as $file) {
//             // $fshort = str_replace($upload_dir, '', $file);
//             $fshort = basename($file);
//             $fsearch = str_replace(' ', '%20', $fshort);
//             $fsearch = addslashes($fsearch);
// 
//             $found = false;
//             foreach($tables as $table => $field) {
//                 $in_use = $model->isKbUploadFileInUse($table, $field, $fsearch);
//                 if ($in_use === false) {
//                     $exitcode = 0;
//                     return $exitcode;
//                 }
// 
//                 if($in_use) {
//                     break;
//                 }
//             } 
// 
//             if(!$in_use) {
//                 $not_used_files[] = $file;
//                 $not_used_files_short[] = $fshort;
// 
//                 if($delete_file) {
//                     if(unlink($file)) {
//                         $deleted++;
//                     }
// 
//                 }
//             }
//         }
//     }
// 
//     $short_log = '';
//     if($not_used_files) {
//         $short_log = "\n" . implode("\n", $not_used_files_short);
// 
//         $filename = APP_CACHE_DIR . 'not_used_htmleditor_files.log';
//         $files_log = implode("\n", $not_used_files);
//         $ret = FileUtil::write($filename, $files_log, true);
//     }    
// 
//     $cron->logNotify("%d not used file(s) found.{$short_log}", count($not_used_files));
// 
//     if($deleted) {
//         $cron->logNotify("%d not used file(s) deleted.", $deleted);
//     }
// 
//     return $exitcode;
// }

?>