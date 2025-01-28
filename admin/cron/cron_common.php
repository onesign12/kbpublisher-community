<?php

$argv = (isset($_SERVER['argv'])) ? $_SERVER['argv'] : array();
define('CRON_DEBUG', in_array('--debug', $argv)); // use --debug parameter for full output to STDOUT
define('CRON_WIPE', in_array('--wipe', $argv));   // use --wipe to close zombie sessions

// In case the script is exectuted from outside CRON directory
$cron_dir = str_replace('\\', '/', dirname(__FILE__));
chdir($cron_dir);

require_once '../config.inc.php';
require_once '../config_more.inc.php';

$conf['use_ob_gzhandler'] = false;
$conf['ssl_admin'] = false;
$conf['debug_db_error'] = 'cron'; // to have string in db error

require_once '../common.inc.php';
require_once 'inc/Cron.php';

// if called not http try set $_SERVER['DOCUMENT_ROOT']   
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $dir = str_replace('\\', '/', dirname(__FILE__)) . '/';
    $pos = strpos($dir, $conf['admin_home_dir']);
    if ($pos !== false) {
        $_SERVER['DOCUMENT_ROOT'] = substr($dir, 0, $pos);
    } else {
        exit('$_SERVER["DOCUMENT_ROOT"] is not set! You should manually specify it in admin/config.inc.php' . "\n");
    }
}

// if not allowed http
if(isset($_SERVER['REQUEST_METHOD'])) {   
    if(strpos($_SERVER['REQUEST_URI'], 'run.php') !== false) {
        exit('HTTP requests not allowed.');
    }
    
    if(!$setting['cron_allow_http']) {
        exit('HTTP requests not allowed.');
    
    } elseif($allow_ip = $setting['cron_http_ip']) {
        if(!IPUtil::isValidQuick($allow_ip)) {
            exit('HTTP requests IP not allowed.');
        }
    }
}


$win = (substr(PHP_OS, 0, 3) == "WIN");
$include_separator = ($win) ? ';' : ':';
set_include_path(get_include_path() . $include_separator . '.'); // need to search cron dir for files!
@set_time_limit(3600);    // one hour


/**
 * Global function (because object method can be used in PHP > 4.3.0 only)
 */
function cronErrorHandler($errno, $errstr, $errfile, $errline) {
    
    // if error occurs here standart error handler will be called
    if (!(error_reporting() & $errno)) { // use previous settings
        return;
    }

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT));

    // if ($errno != E_NOTICE) { // ignore
    // if ($errno != E_NOTICE && $errno != E_DEPRECATED) { // ignore
    if ($errno != E_NOTICE && $errno != E_DEPRECATED && $errno != E_STRICT) { // ignore
                
        if (is_null($cron->_log)) {
            // let's call default error handler
            $msg = "Cannot put error message in custom log.\nlevel: %d, message: %s, file: %s, line: %u";
            $msg = sprintf($msg, $errno, $errstr, $errfile, $errline);
            trigger_error($msg, ($errno == E_USER_ERROR ? E_USER_ERROR : E_USER_NOTICE));
        } else {
            $cron->logCritical("Error occured! level: %d, message: %s, file: %s, line: %u",
                $errno, $errstr, $errfile, $errline);
            if ($errno == E_USER_ERROR) { // we cannot handle other ERRORs
                die();
            }
        }
    }
}


function cronFatalErrorHandler() {
    
    $error = error_get_last();
    
    // fatal error, E_ERROR === 1
    if($error && $error['type'] === E_ERROR) {
        
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];

        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        $cron =& $reg->getEntry('cron');

        $cron->logCritical("Error occured! level: %d, message: %s, file: %s, line: %u",
                    $errno, $errstr, $errfile, $errline);
        
        $lcred = $cron->getLogCredentials($conf);
        extract($lcred);
        $buffer = $cron->_log->getBuffer('_critical', true);
        
        $crit_res = $cron->_send2pool($cron->cron_mail_critical_period, $admin_email,
            $critical_subject, CRITICAL_FILENAME, $buffer);
               
        $started_cron = $cron->manager->isStartedCronLog($cron->_magic);
        if($started_cron) {
            $cron_log_id = $started_cron['0'];
            $exitcode = 0;
            $append = false;
            
            $cron->manager->finishCronLog($cron_log_id, $buffer, $exitcode, $append);
        }
    }
}

?>