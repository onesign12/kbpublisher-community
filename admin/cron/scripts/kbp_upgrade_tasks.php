<?php
// here we keep all upgrades tasks 
// for example when upgrading to  7.0 need to upgrade history
    
// when upgraded to 7.0, history changed, 
// we need to add live version to revisions     
function upgradeHistory() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    // $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_type = 1; // article
    $rule_id = 101; // upgrade history
    $updated = 0;
    
    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $history = new KBEntryHistoryModel();
    $history->error_die = false;
    
    while($row = $result->FetchRow()) {
        
        $entry_id = $row['entry_id'];
        $new_data = $history->getArticleData($entry_id);
        if ($new_data === false) {
            $exitcode = 0;
            continue;
        }
        
        $new_data['history_comment'] = 'Automatically created revision!';
        $history_data = $history->getHistoryFields($new_data);
        $rev_data = $history->parseData($entry_id, $history_data, $new_data, array());
        
        $ret = $history->addRevision($entry_id, RequestDataUtil::addslashes($rev_data));
        if ($ret === false) {
            $exitcode = 0;
            continue;
        }
        
        $ret = $model->statusEntryTask(0, $rule_id, $entry_id);
        if ($ret === false) {
            // $ret = $history->deleteRevision($entry_id, $rev_data[1]['revision_num']);
            $ret = $history->deleteRevision($entry_id, $rev_data['new']['revision_num']);
            $exitcode = 0;
            
        } else {
            $updated++;
        }
    }

    //remove all executed tasks (not active tasks)
    $ret = $model->removeEntryTasks($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    }

    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}
?>