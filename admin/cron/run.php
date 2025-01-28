<?php
$_SERVER['argv'][] = '--debug';
require_once 'cron_common.php';

$cli = getopt('t:f:a:h::', ['task:', 'file:', 'args:', 'help']);

$args = [];
if(isset($cli['a']) || isset($cli['args'])) {
    $args = (isset($cli['a'])) ? $cli['a'] : $cli['args'];
    $args = (is_array($args)) ? $args : array($args);
    $args = array_filter($args, 'escapeshellarg');
    unset($cli['a'], $cli['args']);

    foreach($args as $k => $v) {
        if(strpos($v, ',') !== false) {
            $args[$k] = explode(',', $v);
        }
    }
}

$help = <<< EOT
Usage: run.php [OPTION]...
Run one cron task. 

Mandatory arguments:
-t, --task          script to be called.

Optional
-f, --file          file to be called.
-a, --args          arguments for a script. If multidimensional array required use commas as separator.
-h, --help          this help

Example:
run.php -t updateFileContent 
run.php -f reports.php -t updateReportSummary
run.php -t periodicMail -a daily
run.php -t setupTest -a daily,weekly -a 1
run.php -h -t setupTest #get arguments list

Exit status:
 0  if OK,
 1  if problems

EOT;


$cli = array_filter($cli, 'escapeshellarg');

if(empty($cli)) {
    echo $help;
    exit(0);
}

if(isset($cli['h']) || isset($cli['help'])) {
    // if file need another help
    if(!isset($cli['t']) && !isset($cli['task'])) {
        echo $help;
        exit(0);
    }
}

if(!isset($cli['t']) && !isset($cli['task'])) {
    echo $help;
    exit(0);
}

$script = (isset($cli['t'])) ? $cli['t'] : $cli['task'];

if(!isset($cli['f']) && !isset($cli['file'])) {
    $file = findScriptFile(APP_ADMIN_DIR . 'cron/scripts/', $script);
    if(!$file) {
        echo sprintf("Task '%s' not found!", $script), PHP_EOL;
        exit(0);
    } elseif(is_array($file)) {
        $str = sprintf("Found multipe tasks with name '%s', please specify -f filename!", $script);
        echo $str, PHP_EOL;
        echo 'Files:', PHP_EOL, '- ' . implode(PHP_EOL . '- ', $file), PHP_EOL;
        exit(0);
    }
} else {
    $file = (isset($cli['f'])) ? $cli['f'] : $cli['file'];
}

// echo '<pre>' . print_r($cli, 1) . '</pre>';
// echo '<pre>' . print_r($args, 1) . '</pre>';
// exit;

// check params
include_once $file;
$reflect = new ReflectionFunction($script);
$need_args = $reflect->getParameters();

// script help
if(isset($cli['h']) || isset($cli['help'])) {
    $msg = [];
    
    if($need_args) {
        $msg[] = sprintf("Arguments: \n - %s\n", implode("\n - ", $need_args)); 
        // foreach($need_args AS $param) { $args_msg[] = $param->name; }
        // $args_msg = implode('-a ', $args_msg);
        
    } else {
        $msg[] = "No arguments required.";    
        // $args_msg = '';
    }
    
    // $args_msg = '';
    // $str = "Usage: run.php -t %s%s";
    // $msg[] = sprintf($str, $script, $args_msg);
    $help = implode("\n", $msg);
    
    echo $help;
    exit(0);
}


$required_params = $reflect->getNumberOfRequiredParameters();
if($required_params > count($args)) {
    $str = "Argument count error. Function %s expect folowing arguments: \n - %s\n";
    echo sprintf($str, $script, implode("\n - ", $need_args));
    exit(0);
}


$cron = new Cron('_run_');

if($args) {
    $cron->add($file, $script, $args);
} else {
    $cron->add($file, $script);
}

$cron->run();

exit(1);


function findScriptFile($folder, $search, $extension = 'php') {
    $found = [];    
    foreach(glob($folder . sprintf("*.%s", $extension)) as $file) {
        $contents = file_get_contents($file);
        if(strpos($contents, $search) !== false) {
            $found[] = $file;
        }
    }

    return (count($found) == 1) ? $found[0] : $found;
}
?>