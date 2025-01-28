<?php
if (isset($_GET['conf'])) { return; }
elseif (!isset($conf))    { return; }


$use_ob_gzhandler = $conf['use_ob_gzhandler'];
if($use_ob_gzhandler) {
    $z = strtolower(ini_get('zlib.output_compression'));
    if($z && $z != 'off') {
        $use_ob_gzhandler = false;
    }
}

if($use_ob_gzhandler) {
    $v_ = (isset($_GET['View'])) ? $_GET['View'] : '';
    if($v_ == 'file' || $v_ == 'afile' || strpos($v_, 'pdf') !== false) {
        $use_ob_gzhandler = false;
    }
}

if($use_ob_gzhandler) { ob_start("ob_gzhandler"); }
else                  { ob_start(); }

// debug
if($conf['debug_speed']) {
    require_once 'speed/_dima_timestat.php';
}

// if using https
if($conf['ssl_client'] && empty($conf['ssl_skip_redirect'])) {
    $port = ($conf['ssl_client'] == 1) ? 443 : $conf['ssl_client'];
    if($_SERVER['SERVER_PORT'] != $port) {
        header("Location: " . APP_CLIENT_PATH);
        exit();
    }
}

// includes
require_once 'autoload/autoload.php';
require_once 'vendor/autoload.php';
require_once APP_ADMIN_DIR . 'common_share.inc.php';


$client_dir = APP_CLIENT_DIR . 'client/inc/';
require_once $client_dir . 'KBClientLoader.php';
require_once $client_dir . 'KBClientBaseModel.php';
require_once $client_dir . 'KBClientModel.php';
require_once $client_dir . 'KBClientView.php';
require_once $client_dir . 'KBClientController.php';
require_once $client_dir . 'KBClientAction.php';
require_once $client_dir . 'KBClientPageRenderer.php';


// db
$reg =& Registry::instance();
$reg->setEntry('tbl_pref', $conf['tbl_pref']);
$reg->setEntry('conf', $conf);
$reg->setEntry('extra', $conf['extra']);

$db = &DBUtil::connect($conf);
$reg->setEntry('db', $db);

// settings
$setting = KBClientModel::getSettings(array(0, 1, 2, 10, 100, 140, 141, 150));
$setting['auth_check_ip'] = $conf['auth_check_ip'];
$setting['view_template'] = 'default';      // looking for template in this dir
$setting['view_style'] = 'default';         // looking for css with this name
$reg->setEntry('setting', $setting);

// timezone, set db timezone only if timezone updated in settings
if($setting['timezone'] !== 'system') {
    if(date_default_timezone_set($setting['timezone']) === true) {
        DBUtil::setTimezone($db, date("P"));
    }
}

// language
if(isset($conf['view_lang'])) { $setting['lang'] = $conf['view_lang']; };
require_once APP_MSG_DIR . $setting['lang'] . '/config_lang.php';

define('APP_LANG', $setting['lang']);
define('XAJAX_DEFAULT_CHAR_ENCODING', $conf['lang']['meta_charset']); // for xajax

require_once 'core/app/AppAjax.php';
?>