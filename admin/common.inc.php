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

$use_ob_gzhandler = $conf['use_ob_gzhandler'];
if($use_ob_gzhandler) {
    $z = strtolower(ini_get('zlib.output_compression'));
    if($z && $z != 'off') {
        $use_ob_gzhandler = false;
    }
}

// file action should be always for download file
// ob_gzhandler will be disabled in this case 
if($use_ob_gzhandler) {
    if(@$_GET['action'] == 'file') {
        $use_ob_gzhandler = false;
    }
}

if($use_ob_gzhandler) { ob_start("ob_gzhandler"); } 
else                  { ob_start(); }

// debug
if($conf['debug_speed']) { 
    require_once 'speed/_dima_timestat.php';
}

// if using https`
if($conf['ssl_admin'] && empty($conf['ssl_skip_redirect'])) { 
    $port = ($conf['ssl_admin'] == 1) ? 443 : $conf['ssl_admin'];
    if($_SERVER['SERVER_PORT'] != $port) {
        header("Location: " . APP_ADMIN_PATH);
        exit();            
    }
}

// includes
require_once 'autoload/autoload.php';
require_once 'vendor/autoload.php';

require_once 'core/base/Controller.php';
require_once 'core/app/AppController.php';
require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';
require_once 'core/app/AppNavigation.php';
require_once 'core/app/PageRenderer.php';
require_once 'core/app/AppPlugin.php';
require_once 'eleontev/Navigation.php';

require_once APP_ADMIN_DIR . 'common_share.inc.php';


// db
$reg =& Registry::instance();
$reg->setEntry('tbl_pref', $conf['tbl_pref']);
$reg->setEntry('conf', $conf);
$reg->setEntry('extra', $conf['extra']);

$db = &DBUtil::connect($conf);
$reg->setEntry('db', $db);

// setting
$setting = SettingModel::getQuick(1);

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