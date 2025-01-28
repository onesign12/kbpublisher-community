<?php

/**
 * Synchronize entry_hits table with kb|file_entry.hits
 * If entry was removed, records in entry_hits stay valid.
 */
function syncHits() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    $model = new ReportUsageInsertModel();

    $updated = array();
    $entry_types = array(1, 2, 3); // article, file, news
    
    foreach($entry_types as $type) {   
        $updated[$type] = 0;

        $entries =& $model->GetEntryHits($type);
        if ($entries) {
            while ($ent = $entries->FetchRow()) {
                if ($ent['h_hits'] < $ent['hits']) {
                    $cron->logCritical('Inconsistent database. %s', print_r($ent, 1));
                    $exitcode = 0;
                }
                if ($model->UpdateHits($type, $ent['entry_id'], $ent['h_hits'])) {
                    $updated[$type] += 1;
                } else {
                    $exitcode = 0;
                }
            }
        } else {
            $exitcode = 0;
        }
    }
    
    $cron->logNotify("Updated: %d article(s), %d file(s), %d news", 
                                $updated[1], $updated[2], $updated[3]);

    return $exitcode;
}


function updateReportSummary() {
    $exitcode = 1;
    $do_check = 1; // set FALSE to skip checking time (don't forget to set back to TRUE!)

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    $model = new ReportUsageInsertModel();
    $model->setReportUsageModel();

    $timestamp = time() - (24 * 60 * 60); // previous day
    $daystart_ts = mktime(0,0,0,date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
    
    if ($do_check && ($timestamp - $daystart_ts) > 75 * 60) { // 1 hour 15 minutes
        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        $date_format = '%Y-%m-%d';
        $sec_format = '%I:%M:%S %p';
        if(isset($conf['lang']['sec_format'])) {
            $sec_format = $conf['lang']['sec_format'];
        }
        
        $str = 'Daily scheduled task (updateReportSummary) should be executed just after midnight. Now it\'s %s,  it\'s more than 15 minutes later and statistics could be incorrect because of that.';
        $cron->logCritical($str, _strftime($sec_format));
        $exitcode = 0;
    }

    if ($do_check && $model->getReportRecord(NULL, date('Y-m-d', $timestamp))) {
        $cron->logCritical('updateReportSummary have been executed today already.');
        $exitcode = 0;
        
    } else {
        $funcs = array(
            'insertArticleHitReport',
            'insertFileHitReport',
            'insertNewsHitReport',
            'insertLoginReport',
            'insertRegistrationReport',
            'insertCommentReport',
            'insertFeedbackReport',
            'insertArticleNewReport',
            'insertFileNewReport',
            'insertArticleUpdatedReport',
            'insertFileUpdatedReport'
            );

        foreach ($funcs as $fn) {
            $cron->logNotify("Running $fn()");
            $exitcode = ($model->$fn($timestamp) ? $exitcode : 0);
        }
    }

    return $exitcode;
}


function updateReportEntry() {
    $exitcode = 1;
    $do_check = 1; // set FALSE to skip checking time (don't forget to set back to TRUE!)

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    
    $reg =& Registry::instance();
    $conf =& $reg->getEntry('conf');
    
    $model = new ReportEntryUsageInsertModel;
    
    $timestamp = time() - (24 * 60 * 60); // previous day
    $daystart_ts = mktime(0,0,0,date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
    
    if ($do_check && ($timestamp - $daystart_ts) > 75 * 60) { // 1 hour 15 minutes
        $date_format = '%Y-%m-%d';
        $sec_format = '%I:%M:%S %p';
        if(isset($conf['lang']['sec_format'])) {
            $sec_format = $conf['lang']['sec_format'];
        }
        
        $str = 'Daily scheduled task (updateReportEntry) should be executed just after midnight. Now it\'s %s,  it\'s more than 15 minutes later and statistics could be incorrect because of that.';
        $cron->logCritical($str, _strftime($sec_format));
        $exitcode = 0;
    }

    if ($do_check && $model->getReportRecord(date('Y-m-d', $timestamp))) {
        $cron->logCritical('updateReportEntry have been executed today already.');
        $exitcode = 0;
        
    } else {
        $funcs = array(
            'insertArticleHitReport',
            'insertFileHitReport',
            'insertNewsHitReport'
            );

        foreach ($funcs as $fn) {
            $cron->logNotify("Running $fn()");
            $exitcode = ($model->$fn($timestamp, $conf['lang']['week_start']) ? $exitcode : 0);
        }
    }

    return $exitcode;
}


function updateSearchReport() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new SearchSuggestModel;
    
    $report = $model->isEmptyReport();
    if($report === false) {
        $exitcode = 0;
        return $exitcode;
    }    
              
    if(!$report) {

        $ret = $model->populateReport();   
        if($ret === false) {
            $exitcode = 0;
            return $exitcode;
        }

        $cron->logNotify('Report populated');

    } else {
    
        $added = 0;
        $updated = 0;
    
        $timestamp = time() - (24 * 60 * 60); // previous day        
        $result = &$model->getSearchLogsResult($timestamp);
                                                              
        if($result === false) {
            $exitcode = 0;
            return $exitcode;
        }
        
        while($row = $result->FetchRow()) {
            
            // update
            $ret = $model->updateReport($row['search_string'], $row['num']); 
            if($ret === false) {
                $exitcode = 0;
                return $exitcode;
            }


            // add
            if(!$ret) {
                $ret2 = $model->addReport($row['search_string'], $row['num']);
                if($ret2 === false) {
                    $exitcode = 0;
                    return $exitcode;
                }
                $added++;
            } else {
                $updated++;
            }
        }
  
        $cron->logNotify('Added: %s, Updated: %s', $added, $updated);
    }
    
    return $exitcode;
}


function syncUserActivityReport() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new UserActivityModel;
    
    $pattern = sprintf("%s/user_activity_*.log", APP_CACHE_DIR);
    $pattern = str_replace('//', '/', $pattern);
    $log_files = glob($pattern);
    
    $parsed = 0;
    foreach($log_files as $file) {

        preg_match('#.*?(\d{4}-\d{2}-\d{2})_(\d)+\.log#', $file, $match);
        $user_id = $match[2];
        $date = $match[1];
        
        // check if parsed for this day 
        $is_parsed = $model->checkUserActivity($user_id, $date);
        if ($is_parsed === false) {
            $exitcode = 0;
            continue;
        }

        // remove file if parsed
        if($is_parsed) {
            $ret = unlink($file);
            $cron->logNotify('Log file for user id - %d, dated %s is parsed already, skipping...', $user_id, $date);
            continue;
        }
		
        $data = array();
        $lines = file($file);
        foreach ($lines as $line) {
            $line = explode('|', trim($line));
            $line[6] = sprintf('INET_ATON("%s")', (ip2long($line[6]) === false) ? 0 : $line[6]);
            $data[] = $line;
        }

        // add 
        $result = $model->addUserActivity($data);
        if ($result === false) {
            $exitcode = 0;
        } else {
            $parsed++;
            $ret = unlink($file);
        }
    }

    $cron->logNotify('%d log file(s) parsed.', $parsed);
    
    return $exitcode;
}


function freshUserActivityReport() {
    
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new UserActivityModel;
    
    $months = SettingModel::getQuickCron(1, 'user_activity_time');
    if ($months === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $result = $model->freshUserActivity($months);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $result = $cron->manager->optimizeTable($model->tbl->user_activity);
    if ($result === false) {
        $exitcode = 0;
    }
    
    $cron->logNotify('User activity older than %d months removed.', $months);
    
    return $exitcode;
}
?>