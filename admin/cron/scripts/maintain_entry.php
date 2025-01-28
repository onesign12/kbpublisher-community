<?php
include_once 'utf8/utils/validation.php';
include_once 'utf8/utils/bad.php';

// if tags updated in Tag module, 
// we should update keywords in meta_keywords
function syncTagKeywords($force_update = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2,3); // article, files, news
    $rule_id = 3; // sync_meta_keywords
    $updated = 0;
    
    // need to test if you need this
    // if($force_update) {
    //     return syncTagKeywordsForce($cron, $model, $rule_id, $force_update);
    // }

    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        $tag_id = $row['entry_id'];
        $tag_action = $row['value1'];
        // echo 'Task: ', print_r($row, 1);
        // exit;
        
        foreach($entry_types as $entry_type) {
        
            $result2 = $model->getEntryIdToTag($tag_id, $entry_type);
            if ($result2 === false) {
                $exitcode = 0;
                continue;
            }
            
            while($row2 = $result2->FetchRow()) {
                $entry_id = $row2['entry_id'];
                // echo 'entry_id: ', $entry_id, "\n";
                // exit;
                
                $tags = $model->getTagsToEntry($entry_id, $entry_type);
                if ($tags === false) {
                    $exitcode = 0;
                    continue;
                }
                
                // echo 'Tags to entry: ', print_r($tags, 1), "\n";
                // exit;
                
                $delim = CommonTagModel::getKeywordDelimeter();
                $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
                // echo 'keywords: ', $keywords, "\n";
                // exit;
                
                $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
                if($ret === false) {
                    $exitcode = 0;
                    continue;
                }
                                
                $updated += $ret;

            } // -> while
                        
        } // --> entry_type
        
        
        if($exitcode == 1) { // all is ok
            $ret = $model->statusEntryTask(0, $rule_id, $tag_id);
            if ($ret === false) {
                $exitcode = 0;
            }    
        }
        
    } // --> tasks


    $ret = $model->deleteEmptyTagToEntry($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    } else {
        $ret = $model->removeEntryTasks($rule_id);
        if ($ret === false) {
            $exitcode = 0;
        }        
    }

    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// get all values from tag_to_entry, and update meta_keywords
function syncTagKeywordsForce($cron, $model, $rule_id, $entry_type) {
    $exitcode = 1;
    $updated = 0;
        
    $result = $model->getEntryIdToTagAll($entry_type);
    if ($result === false) {
        $exitcode = 0;
        return;
    }
    
    while($row = $result->FetchRow()) {
        $entry_id = $row['entry_id'];
        
        $tags = $model->getTagsToEntry($entry_id, $entry_type);
        if ($tags === false) {
            $exitcode = 0;
            continue;
        }
                
        $delim = CommonTagModel::getKeywordDelimeter();
        $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
        
        $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
        if($ret === false) {
            $exitcode = 0;
            continue;
        }
                        
        $updated += $ret;

    } // -> while


    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// convert meta_keywords to tags
// get tags from entry_task table, it is populated on upgrade to v5.0
// and in import articles ...
function updateTagKeywords() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_type = 1; // array(1,2,3); // article, files, news
    $rule_id = 2; // update_meta_keywoeds, create tags from keywords
    $tag_visible = 0;
    
    $added = 0;
    $updated = 0;

    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        $entry_id = $row['entry_id'];
        $meta_keywords = $row['value1'];
        
        if(_strpos($meta_keywords, ',') !== false || _strpos($meta_keywords, ';') !== false) {
            $entry_keywords = preg_split('/[,;]/', $meta_keywords, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $entry_keywords = preg_split('/[\s]+/', $meta_keywords, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        $entry_keywords = array_map(array('TagModel', 'parseTagOnAdding'), $entry_keywords);
        
        // echo $meta_keywords, "\n";
        // echo print_r($entry_keywords, 1);
        // echo "\n=============\n";
        // continue;
        
        foreach($entry_keywords as $keyword) {
            
            if(empty($keyword)) {
                continue;
            }
            
            $escaped_keyword = addslashes($keyword);
            $tag_id = $model->getTagIdByTitle($escaped_keyword);
            if ($tag_id === false) {
                $exitcode = 0;
                break;
            }

            if(!$tag_id) {
                $tag_id = $model->addTag($escaped_keyword, $tag_visible);
                if ($tag_id === false) {
                    $exitcode = 0;
                    break;
                }
                $added++;
            }
            
            $tags_to_entry = array($tag_id, $entry_id, $entry_type);
            $ret = $model->addTagsToEntry($tags_to_entry); 
            if ($ret === false) {
               $exitcode = 0;
               break;
            }
            
        }
        
        if($exitcode == 1) { // all is ok

            $tags = $model->getTagsToEntry($entry_id, $entry_type);
            if ($tags === false) {
               $exitcode = 0;
               continue;
            }

            $delim = CommonTagModel::getKeywordDelimeter();
            $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
            // echo 'keywords: ', $keywords, "\n";
            // exit;

            $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
            if($ret === false) {
               $exitcode = 0;
               continue;
            }

            $updated += $ret;

            $ret = $model->statusEntryTask(0, $rule_id, $entry_id);
            if ($ret === false) {
               $exitcode = 0;
            }
        }
    
    }

    $ret = $model->removeEntryTasks($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    }

    $cron->logNotify('%d tag(s) added.', $added);
    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// $force_update  = 1 // update all articles 
// $force_update  = 3 // update all news
function updateBodyIndex($force_update = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $rule_id = 1; // update body_index    
    $updated = 0;

    if($force_update) {
        return updateBodyIndexForce($cron, $manager, $model, $rule_id, $force_update);
    }
     
    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        $body = $model->getBody($row['entry_id'], $row['entry_type']);
        if ($body === false) {
            $exitcode = 0;
        
        } elseif($body) {
            
            if(!utf8_compliant($body)) {
                $body = utf8_bad_replace($body, '');
            }
            
            $body_index = KBEntryModel::getIndexText($body);
            $body_index = RequestDataUtil::addslashes($body_index);
            $ret = $model->updateBodyIndex($row['entry_id'], $row['entry_type'], $body_index);
            if ($ret === false) {
                $entry_type_msg = $manager->record_type[$row['entry_type']];
                $cron->logCritical('Cannot update index for %s id: %d.', $entry_type_msg, $row['entry_id']);
                $exitcode = 0;
            } else {
                $updated++;
                
                $ret = $model->statusEntryTask(0, $rule_id, $row['entry_id'], $row['entry_type']);
                if ($ret === false) {
                    $exitcode = 0;
                }
            }
            
        }
    }
  
    $ret =& $model->removeEntryTasks($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    }    
  
    $cron->logNotify('%d index(s) updated.', $updated);

    return $exitcode;    
}


function updateBodyIndexForce($cron, $manager, $model, $rule_id, $entry_type) {
    $exitcode = 1;    
    $updated = 0;

    $result =& $model->getEntryBodyIndex($entry_type);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        if(!utf8_compliant($row['body'])) {
            $row['body'] = utf8_bad_replace($row['body'], '');
        }
        
        $body_index = KBEntryModel::getIndexText($row['body']);
        $body_index = RequestDataUtil::addslashes($body_index);
        $ret = $model->updateBodyIndex($row['entry_id'], $row['entry_type'], $body_index);
        if ($ret === false) {
            $entry_type_msg = $manager->record_type[$row['entry_type']];
            $cron->logCritical('Cannot update index for %s id: %d.', $entry_type_msg, $row['entry_id']);
            $exitcode = 0;
        } else {
            $updated++;
        }
    }
  
    $cron->logNotify('%d index(s) updated.', $updated);

    return $exitcode;    
}


function updateFileContent($force_update = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    // $model = DataConsistencyModel::getEntryManager('admin', 2);
    // $model = new MaintainEntryModel;
    $model = new FileDirectoryModel;
    $model->fe_model = new FileEntryModel_dir;

    $updated = 0;
    $parsed = 0;
    $not_accessible = [];
    $not_extracted = [];

    // settings
    $setting = SettingModel::getQuickCron(array(1,12));
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $fsetting = $model->fe_model->setFileSetting($setting);

    $extensions = implode('|', array_keys(FileTextExctractor::$classes));
    $like_sql =  sprintf("AND REGEXP_LIKE(filename, '\.(%s)$')", $extensions);
    $model->setSqlParams($like_sql);
    $model->setSqlParams("AND addtype != 3");// skip amazon
    if(!$force_update) {
        $model->setSqlParams("AND filetext = ''");
    }
    
    $result = $model->getFilesResult();
    if ($result === false) {
         $exitcode = 0;
         return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        $parsed++;
    
        $file = FileEntryUtil::getFileDir($row, $fsetting['file_dir']);

        if(!$file) {
            $str = 'ID: %d, File: %s';
            $path = FileEntryUtil::getFilePath($row, $setting['file_dir'], true);
            $not_accessible[$row['id']] = sprintf($str, $row['id'], $path);
        
        } else {
                
            $text = addslashes($model->fe_model->extractFileText($file, $fsetting));
            if($text) {
                $result2 = $model->setFileText($row['id'], $text);
                if ($result2 === false) {
                    $exitcode = 0;
                    continue;
                } else {
                    $updated++;
                }
            } else {
                $str = 'ID: %d, File: %s';
                $path = FileEntryUtil::getFilePath($row, $setting['file_dir'], true);
                $not_extracted[$row['id']] = sprintf($str, $row['id'], $path);
            }
        }
    }
    
    $cron->logNotify('%d file(s) parsed.', $parsed);
    $cron->logNotify('%d file(s) updated.', $updated);
    
    if($not_extracted) {
        $files = $model->getFilesFilteredLink($not_extracted);
        $cron->logNotify("Unable to get text from %d file(s).\n%s", count($not_extracted), $files);
    }

    if($not_accessible) {
        $files = $model->getFilesFilteredLink($not_accessible);
        $cron->logNotify('%d file(s) not accessible.\n%s', count($not_accessible), $files);
    }

    return $exitcode;
}


function deleteArticleHistoryNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $deleted = 0;
    $result = $model->deleteHistoryEntryNoItem('kb', 1);
    if ($result === false) {
         $exitcode = 0;
    } else {
        $deleted = $result;
    }

    $cron->logNotify('%d missed records(s) deleted.', $deleted);

    return $exitcode;
}


function deleteFileHistoryNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $deleted = 0;
    $ids = $model->getHistoryEntryNoItem('file', 2);
    if ($ids === false) {
         $exitcode = 0;
         
    } else {
        $h_manager = new FileEntryHistoryModel;
        $h_manager->error_die = false;
        $h_manager->setFileDirs(SettingModel::getQuickCron(1, 'file_dir'));
        foreach($ids as $entry_id) {
            $ret = $h_manager->deleteRevisionAll($entry_id);
            if($ret === false) {
                $exitcode = 0;
            } else {
                $deleted++;
            }
        }
    }

    $cron->logNotify('%d missed records(s) deleted.', $deleted);

    return $exitcode;
}


function freshEntryAutosave($seconds) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $result = $model->freshEntryAutosave($seconds);
    if ($result === false) {
        $exitcode = 0;
    }
    
    return $exitcode;
}


function unlockEntries($seconds) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $result = $model->unlockEntries($seconds);
    if (!$result) {
        $exitcode = 0;
    }
    
    return $exitcode;
}


function deleteDraftNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1, 2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteDraftNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteWorkflowHistoryNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteWorkflowHistoryNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteFeaturedNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteFeaturedNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteMustreadNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteMustreadNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}

?>