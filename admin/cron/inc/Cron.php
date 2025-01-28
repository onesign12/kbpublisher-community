<?php

define('CRON_RESTART_TIMEOUT', 60 * 60); // force to close not finished previous session
define('CRON_RESTART_MESSAGE', 'Zombie killed'); // output to set in DB for closed previous session
define('CRITICAL_FILENAME', 'critical_messages'); // filename in the MailPool
define('INFORM_FILENAME', 'inform_messages'); // filename in the MailPool
define('CRITICAL_SUBJECT', 'Critical messages from CRON'); // mail subject
define('INFORM_SUBJECT', 'Informational messages from CRON'); // mail subject

class Cron
{
    var $_magic;
    var $_calls = array();
    var $_log;
    var $_uniq; // unique identifier of execution
    var $manager;
    var $pool;
    var $cron_mail_critical_period = 'hourly';
    var $skip_log = false; // if true does log/write to table, could be need for separete task


    function __construct($magic) {

        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        $reg->setEntry('cron', $this);

        set_error_handler('cronErrorHandler');
        register_shutdown_function('cronFatalErrorHandler');

        $this->_magic = $magic;
        $this->_log = NULL;
        $this->manager = new CronModel(); // DB is inside
        $this->pool = new MailPool(APP_MAIL_POOL_DIR);

        $log = array();
        $log[$this->_magic] = array('type' => 'buffer');
        $log['_critical'] = array('type' => 'buffer'); // separate buffer
        $log['_inform'] = array('type' => 'buffer'); // separate buffer
        if (CRON_DEBUG) {
            $log['_debug'] = array('type' => 'echo'); // separate buffer
        } else {
            $log['_debug'] = array('type' => 'none'); // separate buffer
        }

        $this->_log = new LogUtil($log);
    }


    /**
     * Adds function to calling list for run().
     * Added function should return values:
     *   true (!= 0) - all went well (no critical error, admin should not be disturbed).
     *   false (0) - some critical error happened, alarm!
     *
     * @param string $file Filename to include (PHP script).
     * @param string|array $func Function specification (as for call_user_func().
     * @param array $params List of parameters for function execution (references don't work).
     */
    function add($file, $func, $params = array()) {
        $this->_calls[] = array('file' => $file, 'func' => $func, 'params' => $params);
    }


    function _canRun() {
        $result = true;

        if ($result && !isset($this->manager->magic_to_number[$this->_magic])) {
            $this->logCritical('Incorrect magic value (%s). Cannot convert it to number.', $this->_magic);
            $result = false;
        }

        if ($result && empty($_SERVER['HTTP_HOST'])) {
            $this->logCritical("\$_SERVER['HTTP_HOST'] is not set! You should manually specify it in admin/config.inc.php");
            $result = false;
        }

        return $result;
    }


    /**
     * @return int 0: some critical error(s) happened, 1: no errors.
     */
    function run() {
        $total_exitcode = 1;
        $this->_uniq = time();

        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');

        $do_start = true;
        if ($this->_canRun()) {

            $started_cron = $this->manager->isStartedCronLog($this->_magic);

            // db error, trying to repair
            if($started_cron === false) {

                $t_name = $this->manager->tbl->log_cron;
                $this->logCritical('Cannot get if cron started');
                $this->logNotify('trying to repair indexes...');

                if ($this->manager->repairTable($t_name)) {
                    $this->logNotify('...done (you should check the table %s manualy!)', $t_name);

                    $this->logNotify('trying to get if cron started...');
                    $started_cron = $this->manager->isStartedCronLog($this->_magic);
                    if($started_cron === false) {
                        $this->logCritical('Cannot get if cron started');
                        $total_exitcode = 0;
                    }

                } else { // that would be second letter :)
                    $this->logCritical('Cannot repair table %s.', $t_name);
                    $total_exitcode = 0;
                }
            }


            if($started_cron) {
                list($running_id, $running_date) = $started_cron;
                if ($running_id) {
                    $total_exitcode = 0;
                    $this->logCritical('Previous session is not closed correctly. It is running yet or it has been terminated badly. Within some time this session will be restarted automaticaly.');
                    if (CRON_WIPE || (time() - $running_date > CRON_RESTART_TIMEOUT)) {
                        $this->logCritical('Closing previous session (zombie). Trying to start...');
                        $this->manager->finishCronLog($running_id, CRON_RESTART_MESSAGE, 0);
                    } else {
                        $do_start = false;
                    }
                }
            } else {
                $do_start = false;
            }

        } else {
            $do_start = false;
            $total_exitcode = 0;
        }

        if ($do_start) {

            $cron_log_id = 1;
            if(!$this->skip_log) {
                $cron_log_id = $this->manager->startCronLog($this->_magic);
            }

            if ($cron_log_id) {
                $this->logNotify('Started');
                
                $pluginable = AppPlugin::getPluginsFiltered('cron', true);
                
                if (count($this->_calls) > 0) {
                    foreach ($this->_calls as $call) {
                        if(isset($pluginable[$call['func']])) {
                            if(!AppPlugin::isPlugin($pluginable[$call['func']])) {
                                continue;
                            }
                        }
                        
                        $exitcode = $this->_runCall($call);
                        if (!$exitcode && $total_exitcode) {
                            $total_exitcode = 0;
                        }
                    }
                } else {
                    $this->logNotify('No function calls specified.');
                }

                $this->logNotify('Finished');

                $is_finished = 1;
                if(!$this->skip_log) {
                    $is_finished = $this->manager->finishCronLog(
                        $cron_log_id,
                        $this->_log->getBuffer($this->_magic, true),
                        $total_exitcode);
                }

                if (!$is_finished) {
                    $total_exitcode = 0;
                    $this->logCritical("Cannot update log record in the database!");
                }
            } else {
                $total_exitcode = 0;
                $this->logCritical('Cannot insert log record into the database! Skipping all jobs.');
            }
        }

        /*
         * admin email and period are used in both cases: for _critical and _inform
         */

        $lcred = $this->getLogCredentials($conf);
        extract($lcred);

        if ($cron_mail_critical) {
            if (empty($admin_email)) {
                $this->logCritical('admin_email is not set up!');
                print("Writing critical messages to STDOUT\n".
                    $this->_log->getBuffer('_critical', true));
                print("Writing informational messages to STDOUT\n".
                    $this->_log->getBuffer('_inform', true));

            } else {
                $crit_res = $this->_send2pool($this->cron_mail_critical_period, $admin_email,
                    $critical_subject, CRITICAL_FILENAME,
                    $this->_log->getBuffer('_critical', true));
                    
                $info_res = $this->_send2pool($this->cron_mail_critical_period, $info_email,
                    INFORM_SUBJECT, INFORM_FILENAME,
                    $this->_log->getBuffer('_inform', true));

                if (!$crit_res || !$info_res) {
                    /* Update the log record in database, append those last errors from _send2pool */
                    $total_exitcode = 0;

                    if(!$this->skip_log) {
                        $this->manager->finishCronLog(
                            $cron_log_id,
                            $this->_log->getBuffer($this->_magic, true),
                            $total_exitcode,
                            true);
                    }
                }
            }
        }

        return $total_exitcode;
    }


    function getLogCredentials($conf) {
        
        $admin_email = SettingModel::getQuick(134, 'admin_email');
        
        $data = array(
            'cron_mail_critical' => SettingModel::getQuick(1, 'cron_mail_critical'),
            'admin_email' => $admin_email,
            'info_email' => $admin_email,
            'critical_subject' => CRITICAL_SUBJECT
        );

        // different behaviour for cloud
        // logCritical goes to cloud admin email
        // logNotify always to owner admin email
        if(BaseModel::isCloud()) {
            $data['cron_mail_critical'] = 1;
            if(isset($conf['cloud_admin_email'])) {
                $data['admin_email'] = $conf['cloud_admin_email'];
                $data['critical_subject'] .= sprintf(" [%s]", $_SERVER['HTTP_HOST']);
            }
        }
        
        return $data;
    }


    function _runCall($call) {
        $exitcode = 1;
        $callable_name = NULL;

        $is_included = include_once $call['file'];
        if ($is_included) {
            if (is_callable($call['func'], false, $callable_name)) {
                $this->logNotify('%s() started', $callable_name);
                ob_start();

                $params = (is_array($call['params'])) ? $call['params'] : array($call['params']);
                $exitcode = call_user_func_array($call['func'], $params);

                $output =& ob_get_contents();
                ob_end_clean();
                if (!empty($output)) {
                    $output = ", output: \n\t". str_replace(array("\r\n", "\n", "\r"), "\n\t", $output);
                }
                $this->logNotify('%s() finished, exitcode: %d (%s)%s',
                        $callable_name, $exitcode,
                        ($exitcode ? 'well' : 'critical error!'), $output);
            } else {
                $exitcode = 0;
                $this->logCritical("This function is not callable: \n%s",
                        print_r($call, true));
            }
        } else {
            $exitcode = 0;
            $this->logCritical("Can't include the file: \n%s\nInclude path: %s",
                    print_r($call, true), get_include_path());
        }

        return $exitcode;
    }


    /**
     * @param string $msg Message text (may be template for sprintf())
     * @param mixed $arg1_N Optional (values for sprintf())
     *
     * This does not send any email (look at Cron::Cron())
     * If CRON_DEBUG is defined it will echo $msg, otherwise it will just skip it.
     */
    function logNotify($msg) {
        $args = func_get_args();
        // $msg = call_user_func_array('sprintf', $args);
        $msg = $this->parseMsg($msg, $args);
        $this->_log->put(array('_debug', $this->_magic), $msg);
    }


    /**
     * @param string $msg Message text (may be template for sprintf())
     * @param mixed $arg1_N Optional (values for sprintf())
     *
     * Difference with logCritical only at Subject and name of file inside the pool.
     */
    function logInform($msg) {
        $args = func_get_args();
        // $msg = call_user_func_array('sprintf', $args);
        $msg = $this->parseMsg($msg, $args);
        $this->_log->put(array('_debug', '_inform', $this->_magic), $msg);
    }


    /**
     * @param string $msg Message text (may be template for sprintf())
     * @param mixed $arg1_N Optional (values for sprintf())
     *
     * Difference with logInform only at Subject and name of file inside the pool.
     */
    function logCritical($msg) {
        $args = func_get_args();
        // $msg = call_user_func_array('sprintf', $args);
        $msg = $this->parseMsg($msg, $args);
        $this->_log->put(array('_debug', '_critical', $this->_magic), $msg, 'critical');
    }


    /**
     * Log critical database error.
     * Adds DB error text to the message.
     *
     * @param string $msg Message text (may be template for sprintf())
     * @param mixed $arg1_N Optional (values for sprintf())
     */
    function logCriticalDB($msg) {
        $args = func_get_args();
        // $msg = call_user_func_array('sprintf', $args);
        $msg = $this->parseMsg($msg, $args);
        $this->logCritical('%s (database error: %s)', $msg, $this->manager->db->ErrorMsg());
    }


    private function parseMsg($msg, $args) {
        if(count($args) > 1) {
            $msg = call_user_func_array('sprintf', $args);
        }
    
        return $msg;
    }


    /**
     * Notify admin if there were some critical or informational messages.
     *
     * @return bool TRUE: operation was successful, FALSE: there were some errors
     */
    function _send2pool($period, $to, $subj, $filename, $txt) {
        if (strlen(trim($txt)) > 0) {
            $msg = "Cron (%s) messages:\n--- begin -----------------\n%s\n--- end -------------------\n\n";
            $msg = sprintf($msg, $this->_magic, $txt);

            $dir = $this->pool->getPeriodDir($period);
            if ($dir) {
                $fname = $dir . $filename;
                /* append if exists */
                if (file_exists($fname)) {
                    if (!FileUtil::write($fname, $msg, false)) {
                        trigger_error('Cannot append to the message file: '. $fname);
                    } else {
                        return true;
                    }
                } else {
                    if (!$this->pool->createFile($fname, $to, $subj, $msg)) {
                        trigger_error('Cannot create the message file: '. $fname);
                    } else {
                        return true;
                    }
                }
            }
        } else {
            return true;
        }
        return false;
    }


    /**
     * Pause(sleep) script execution
     */
    function pause($parse_once, $sleep_time = 15) {

        static $i = 1;

        if($parse_once) {
            $pause  = ($i % $parse_once) ? false : true;

            if($pause) {
                sleep($sleep_time);
                return $i;
            }

            $i++;
        }

        return false;
    }

}

?>