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

$app_dir = str_replace('\\', '/', getcwd()) . '/admin/';   // trying to guess admin directory
//$app_dir = '/path_to/kb/admin/';                         // set it manually

$ssl_port = 443;

/* DO NOT MODIFY */
//if(!is_file($app_dir . 'config.inc.php')) {
//  echo 'Wrong path! Set correct value for $app_dir at the top of index.php <br /><br />';
//}

require_once $app_dir . 'config.inc.php';
require_once $app_dir . 'config_more.inc.php';

$conf['ssl_client'] = 0;
// define conf
$conf['auth_check_ip'] = 0;
$conf['debug_db_error'] = 'api';

$valid_versions = array(
    1 => 'api_v1',
    2 => 'api_v2',
    3 => 'api_v3'
);
$version = (isset($_GET['version'])) ? (int) $_GET['version'] : 1;
$version = (in_array($version, array_keys($valid_versions))) ? $version : 1;
$api_dir = APP_CLIENT_DIR . 'client/api/' . $valid_versions[$version] . '/';
DEFINE('API_DIR', $api_dir);

require_once API_DIR . 'api_include.php';

ob_end_flush();
?>