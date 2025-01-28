<?php

$dir = str_replace('\\', '/', dirname(__FILE__)) . '/';
$app_dir = str_replace('setup', 'admin', $dir);
// define('APP_ADMIN_DIR', $app_dir);
// define('APP_MSG_DIR', APP_ADMIN_DIR . 'lang/');

require_once $app_dir . 'config.inc.php';
require_once $app_dir . 'config_more.inc.php';

chdir($dir);

// errors
set_error_handler('errorHandler');
register_shutdown_function('fatalErrorShutdownHandler');

function errorHandler($errno, $message, $file, $line) {
    $php_8_suppressed = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    $er = error_reporting();
    
    if ($er === 0 || $er === $php_8_suppressed) { 
        return false;
    }
    
    // if error occurs here standart error handler will be called
    if (!(error_reporting() & $errno)) { // use previous settings
        return;
    }
    
    if (error_reporting()) {   
        if ($errno != E_NOTICE && $errno != E_DEPRECATED && $errno != E_STRICT) { // ignore
        // if ($errno != E_DEPRECATED && $errno != E_STRICT) { // ignore
            echo sprintf('Code: %s, Message: %s, File: %s, Line: %s', $errno, $message, $file, $line);
            exit(69);
        }
    }
}

function fatalErrorShutdownHandler() {
    $last_error = error_get_last();
    if (isset($last_error['type']) && $last_error['type'] === E_ERROR) { // fatal error
        errorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
}


$win = (substr(PHP_OS, 0, 3) == "WIN");
$include_separator = ($win) ? ';' : ':';
$include_path = array();
$include_path[] = $app_dir . 'lib';
$include_path[] = $app_dir . 'lib/Pear';

ini_set('include_path', implode($include_separator, $include_path));

require_once 'eleontev/Assorted.inc.php';
require_once 'autoload/AppLoader.php';
require_once 'speed/_dima_timestat.php';

AppLoader::register(['app', 'setup']);
require_once 'vendor/autoload.php';

$controller = new SetupController();
$controller->working_dir = $dir;
//$controller->home_path = $home_path;
$controller->mod_rewrite = false;

$reg =& Registry::instance();
$conf['debug_db_error'] = 'cloud';

$lang = 'en';
require_once APP_MSG_DIR . $lang . '/config_lang.php';

$reg->setEntry('conf', $conf);
$reg->setEntry('lang', $lang);
$reg->setEntry('dir', $dir);
$reg->setEntry('controller', $controller);

if(!defined('APP_LANG')) {
    define('APP_LANG', $lang);
}


$test = (!empty($_GET['test']));
$debug = (!empty($_GET['debug']));

if($test) {
    $values['setup_upgrade'] = '801_to_802';
    
    $values['db_host']      = "localhost";
    $values['db_base']      = "kbp_7542";
    $values['db_user']      = "root";
    $values['db_pass']      = "root";
    $values['db_driver']    = "mysqli";             // no other were tested 
    $values['tbl_pref']     = 'kbp_';
    
    $values['client_home_dir'] = '';
    $values['admin_home_dir'] = '';
    $values['document_root'] = '';
    $values['cache_dir'] = '';
    $values['http_host'] = '';

    // $values += $this->getDefaultValues();
    $values['session_name'] = md5('test');
    
} else {
    //$values = json_decode($argv[1], true);
    $values = file_get_contents('php://stdin');
    $values = json_decode($values, true);
}

$values['is_cloud'] = true;

if (!$values) {
    exit(64);
}

$values['setup_type'] = 'upgrade';

// we have one class, version_map for version above 45
// list here for convinience 
$version_map = array(    
    '602*_to_602*' => 'skip',     // no sql updates
    '70*_to_702'   => '70_to_702',
    '70*_to_75*'   => '70_to_75',
    '75*_to_75*'   => 'skip', // no sql updates
    '75*_to_80*'    => '75_to_80',
    '80*_to_80*'   => 'skip', // no sql updates
);


if (isset($version_map[$values['setup_upgrade']])) {
    $values['setup_upgrade'] = $version_map[$values['setup_upgrade']];

} else {
    
    foreach($version_map as $in => $out) {
        $search = str_replace('*', '(\d*)', $in);    
        $search = "#^{$search}$#";
        preg_match($search, $values['setup_upgrade'], $match);
        if(!empty($match[0])) {
            $version_from = $match[1];
            $setup_upgrade = str_replace('[num]', $version_from, $out);
            $values['setup_upgrade'] = $setup_upgrade;
            
            // echo '<pre>in: ', print_r($in, 1), '</pre>';
            // echo '<pre>search: ', print_r($search, 1), '</pre>';
            // echo '<pre>match: ', print_r($match, 1), '</pre>';
            // echo '<pre>setup_upgrade: ', print_r($setup_upgrade, 1), '</pre>';
            
            break;
        }
    }
}


$class = SetupModelUpgrade::getClass($values['setup_upgrade']);
if (!class_exists($class)) {
    if($test) {
        echo 'class: ', $class, "<br/>\n";
        echo 'Exit with code 65, upgrade class not found!';
    }
    
    exit(65);
}

if($debug) {
    // http://localhost/kbp/kbp_dev/setup/cloud.php?test=1&setup_upgrade=60_to_601
    echo '<pre>', print_r($values,1), '<pre>';
    echo 'class: ', $class, "<br/>\n";
    exit;
}

$manager = new SetupModel;
$ret = $manager->connect($values);
if($ret !== true) {
    echo $ret;
    exit(66);
}

$values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
$manager->setTables($values['tbl_pref']);
$ret = $manager->checkPrefixOnUpgrade();
if($ret !== true) {
    echo 'Wrong prefix!';
    exit(67);
}

// $ret = $manager->getSetting('kbp_version');
// if($ret !== true) {
//     echo 'Wrong prefix!';
//     exit(67);
// }

// by  this we can skip updated databases already in cloud
// $version_cur = str_replace('.', '', $ret);
// if($version_cur >= $values['version_to']) { 
//     echo "You database is already up to date and has version: {$version_cur} !";
//     exit(0);
// }

$action = new SetupAction_upgrade;

// dryrun
$errors = false;
if(empty($values['skip_dryrun'])) {
    $dryrun = true;
    $errors = $action->processDryRun($values, $manager, $values['setup_upgrade']);
}

// upgrade
if(!$errors) {
    $dryrun = false;
    $errors = $action->process($values, $manager, $values['setup_upgrade']);
}

if (is_array($errors)) {
    echo $errors['formatted'][0]['msg'];
    exit(68);
}

if (empty($values['config'])) {
    $action = new SetupAction_config;
    $action->setVars($controller, $manager);
    $config = $action->execute($controller, $manager, $values);
    echo $config;
}

exit(0);

?>
