<?php

/**
 * @param int $max_free_percent Maximum percent of unused storage space
 */
function optimizeTables($max_free_percent) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    
    $optimized = 0;

    $tables =& $manager->getOptimizeTables($max_free_percent);
    if (is_array($tables) && count($tables) > 0) {
        foreach ($tables as $tbl) {
            // $cron->logNotify('%s: optimizing...', $tbl['Name']);
            if ($manager->optimizeTable($tbl['Name'])) {
                // $cron->logNotify('...done');
                $optimized++;
            } else {
                $cron->logCriticalDB('Cannot optimize table %s.', $tbl['Name']);
                $exitcode = 0;
            }
        }
    
        $cron->logNotify('%d tables have been optimized.', $optimized);
        
    } else if (is_array($tables)) {
        $cron->logNotify('There are no tables to be optimized.');
    
    } else {
        $cron->logCriticalDB('Cannot get tables list for optimization.');
        $exitcode = 0;
    }

    return $exitcode;
}


/**
 * @param int $max_period_days Maximum days without checking (for separate tables)
 */
function repairTables($max_period_days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    $tables =& $manager->getRepairTables($max_period_days);
    if (is_array($tables) && count($tables) > 0) {
        foreach ($tables as $tbl) {
            if (!checkTable($tbl['Name'])) {
                $exitcode = 0;
            }
        }
        
        $cron->logNotify('%d tables have been checked.', count($tables));
        
    } else if (is_array($tables)) {
        $cron->logNotify('There are no tables to be checked.');
        
    } else {
        $cron->logCriticalDB('Cannot get list of tables for check/repair.');
        $exitcode = 0;
    }

    return $exitcode;
}


/**
 * @param string $t_name Name of table.
 */
function checkTable($t_name) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    // $cron->logNotify('%s: checking...', $t_name);
    list($e, $r) = $manager->checkTable($t_name);
    if ($e && count($r) > 0) {
        $status = $r[count($r) - 1]; // the last row of status results
        if ($status['Msg_type'] == 'status' && ($status['Msg_text'] == 'OK' ||
            strstr($status['Msg_text'], 'up to date'))) {
            // $cron->logNotify('...ok');
        } else {
            $cron->logCritical("Table %s is corrupted! Status:\n%s\nNow trying to repair it automatically. Nevertheless you should check the table manualy!", $t_name, print_r($r, true));
            $exitcode = 0;

            $cron->logNotify('trying to repair indexes...');
            if ($manager->repairTable($t_name)) {
                $cron->logNotify('...done (you should check the table %s manualy!)', $t_name);
            } else { // that would be second letter :)
                $cron->logCriticalDB('Cannot repair table %s.', $t_name);
                $exitcode = 0;
            }
        }
    } else {
        $cron->logCriticalDB('Cannot check table %s.', $t_name);
        $exitcode = 0;
    }

    return $exitcode;
}


/**
 * @param int $days Number of days in the log.
 */
function freshCronLog($days, $magic) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    // $magicals = array_keys($manager->magic_to_number);
    $magicals = $magic;
    
    foreach ($magicals as $magic) {
        if ($manager->freshCronLog($magic, $days)) {
            $cron->logNotify('Records older than %d days were deleted (%s).', $days, $magic);
        } else {
            $exitcode = 0;
        }
    }

    return $exitcode;
}


/**
 * @param int $days Number of days in the log.
 */
function freshLoginLog($days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    if (!$manager->freshLoginLog($days)) {
        $exitcode = 0;
    }

    return $exitcode;
}


/**
 * @param int $days Number of days in the log.
 */
function freshSearchLog($days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    if (!$manager->freshSearchLog($days)) {
        $exitcode = 0;
    }

    return $exitcode;
}


function freshUserNotifications($days) {
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    if (!$manager->freshUserNotifications($days)) {
        $exitcode = 0;
    }
    
    return $exitcode;
}


function freshUserTemp() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

     
    $user_rules = UserModelExtra::$temp_rules;

    // rules to days to keep
    $rules = array(
        'reset_password' => 1,
        'reset_username' => 1,
        'api_session' => 1,
        'auth_id' => 7 // will be freshed by active = 0
    );

    foreach($rules as $rule => $num_days) {
        $rule_id = $user_rules[$rule];
        if (!$manager->freshUserTemp($rule_id, $num_days)) {
            $exitcode = 0;
        }
    }

    return $exitcode;
}


function freshUserAuthToken() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;

    if (!$manager->freshUserAuthToken()) {
        $exitcode = 0;
    }

    return $exitcode;
}


function freshSphinxLog($days) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    
    if ($manager->freshSphinxLog($days)) {
        $cron->logNotify('Records older than %d days were deleted', $days);
        
    } else {
        $exitcode = 0;
    }

    return $exitcode;
}

?>