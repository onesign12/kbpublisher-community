<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+



class CronModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array(
        'log_cron', 'log_login', 'log_search', 'log_sphinx',
        'user', 'user_to_role', 'priv', 
        'user_temp', 'user_notification', 'user_auth_token');
                        
    var $magic_to_number = array(
        '_test_' => 0, // dummy
        '_run_' => 6, // in script run.php
        'freq' => 1,
        'hourly' => 2,
        'daily' => 3,
        'weekly' => 4,
        'monthly' => 5);


    /**
     * Remove records older than $days
     *
     * @return bool
     */
    function freshCronLog($magic, $days) {
        $sql = "DELETE FROM {$this->tbl->log_cron}
            WHERE magic = %d AND DATEDIFF(NOW(), date_started) > %d";
        $sql = sprintf($sql, $this->magic_to_number[$magic], $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    /**
     * Remove records older than $days
     *
     * @return bool
     */
    function freshLoginLog($days) {
        $sql = "DELETE FROM {$this->tbl->log_login}
            WHERE DATEDIFF(NOW(), date_login) > %d";
        $sql = sprintf($sql, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    /**
     * Remove records older than $days
     *
     * @return bool
     */
    function freshSearchLog($days) {
        $sql = "DELETE FROM {$this->tbl->log_search}
            WHERE DATEDIFF(NOW(), date_search) > %d";
        $sql = sprintf($sql, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    /**
     * Remove records older than $days and not active 
     *
     * @return bool
     */
    function freshUserTemp($rule_id, $days) {
        $sql = "DELETE FROM {$this->tbl->user_temp} 
            WHERE rule_id = %d 
            AND (active = 0 OR DATEDIFF(NOW(), value_timestamp) > %d)";
        $sql = sprintf($sql, $rule_id, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    /**
     * Remove expired records 
     *
     * @return bool
     */
    function freshUserAuthToken() {
        $sql = "DELETE FROM {$this->tbl->user_auth_token} 
            WHERE CURDATE() > date_expired";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    /**
     * Remove records older than $days
     *
     * @return bool
     */
    function freshSphinxLog($days) {
        $sql = "DELETE FROM {$this->tbl->log_sphinx}
            WHERE DATEDIFF(NOW(), date_executed) > %d";
        $sql = sprintf($sql, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    function freshUserNotifications($days) {
        $sql = "DELETE FROM {$this->tbl->user_notification}
            WHERE DATEDIFF(NOW(), date_posted) > %d";
        $sql = sprintf($sql, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    /**
     * Insert log record into the database.
     *
     * @return mixed false: error, intval > 0: inserted ID
     */
    function startCronLog($magic) {
        $sql = "INSERT INTO {$this->tbl->log_cron} (date_started, magic) VALUES (NOW(), %d)";
        $sql = sprintf($sql, $this->magic_to_number[$magic]);
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $this->db->Insert_ID();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    /**
     * Update "started" log record in the database.
     *
     * bool $append Add the output to tail of the stored value of log_cron.output
     * @return bool Result of query execution.
     */
    function finishCronLog($id, $output, $exitcode, $append = false) {
        $sql = "UPDATE {$this->tbl->log_cron} SET 
            date_started = date_started, date_finished = NOW(), output = %s, exitcode = %d 
            WHERE id = %u";

        if ($append) {
            $outs = sprintf('CONCAT(output, %s)', $this->db->Quote($output));
        } else {
            $outs = $this->db->Quote($output);
        }

        $sql = sprintf($sql, $outs, intval($exitcode), intval($id));
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    /**
     * Check if this cron is running already.
     *
     * @return array (id, timestamp) or FALSE if there is no running process.
     */
    function isStartedCronLog($magic) {
        $locked = array(false, false);

        $sql = "SELECT id, UNIX_TIMESTAMP(date_started) AS ts_started 
            FROM {$this->tbl->log_cron}
            WHERE magic = %d 
            AND date_finished IS NULL";
        $sql = sprintf($sql, $this->magic_to_number[$magic]);
        $result = $this->db->SelectLimit($sql, 1, 0);
        if ($result) {
            $result = $result->GetArray();
            if (count($result) > 0) {
                $locked = array($result[0]['id'], $result[0]['ts_started']);
            }
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        return $locked;
    }
    
    
    /**
     * @return array array('Name' => ..., ...)
     */
    function &getOptimizeTables($max_free_percent) {
        $sql = "SHOW TABLE STATUS LIKE '%s%%'";
        $sql = sprintf($sql, $this->tbl_pref);
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $result->GetArray();
            /* This MySQL 5 query was replaced by the following PHP code.
                $sql = "SHOW TABLE STATUS WHERE Name LIKE '%s%%' AND Data_length > 0
                    AND ((Data_free * 100)/ Data_length >= %u)";
            */
            if (count($result) > 0) {
                foreach ($result as $k => $v) {
                    $len = intval($v['Data_length']);
                    $free = intval($v['Data_free']);
                    if ($len <= 0 || (($free * 100) / $len < $max_free_percent)) {
                        unset($result[$k]);
                    }
                }
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    function optimizeTable($t_name) {
        $sql = "OPTIMIZE TABLE `%s`";
        $sql = sprintf($sql, $t_name);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    function &getRepairTables($max_period_days) {
        $sql = "SHOW TABLE STATUS LIKE '%s%%'";
        $sql = sprintf($sql, $this->tbl_pref);
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $result->GetArray();
            /* This MySQL 5 query was replaced by the following PHP code.
                $sql = "SHOW TABLE STATUS WHERE Name LIKE '%s%%' AND Check_time <= (NOW() - INTERVAL %u DAY)";
            */
            if (count($result) > 0) {
                foreach ($result as $k => $v) {
                    $checked = strtotime($v['Check_time']);
                    $next_check = (time() - $max_period_days * 86400); /* 60*60*24 */
                    $next_check = $next_check + 900; /* 15*60 - plus 15 min, fix if running daily */
                    if ($checked > $next_check) {
                    // if ($checked > time() - $max_period_days * 86400 /* 60*60*24 */) {
                        unset($result[$k]);
                    }
                }
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }

    
    /**
     * @return array array($query_result, $records | false)
     */
    function checkTable($t_name) {
        $sql = "CHECK TABLE `%s` MEDIUM";
        $sql = sprintf($sql, $t_name);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return array($result, $result ? $result->GetArray() : false);
    }
    

    function repairTable($t_name) {
        $sql = "REPAIR TABLE `%s` QUICK";
        $sql = sprintf($sql, $t_name);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    // USER // ---------------------
    
    function getUserPrivId($user_id) {
        $sql = "SELECT priv_name_id FROM {$this->tbl->priv} WHERE user_id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->Fields('priv_name_id');
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;            
        }
    }    
    
    
    function getUserRoleId($user_id) {
        $sql = "SELECT role_id AS id, role_id FROM {$this->tbl->user_to_role} WHERE user_id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;            
        }
    }
    
    
    function getUserActiveStatus() {
        return UserModel::getEntryStatusPublished();
    }

}
?>