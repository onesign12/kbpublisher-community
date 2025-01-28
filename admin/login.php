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


$app_dir = str_replace('\\', '/', getcwd()) . '/';     // trying to guess admin directory

require_once $app_dir . 'config.inc.php';
require_once $app_dir . 'config_more.inc.php';

require_once APP_CLIENT_DIR . 'client/inc/common.inc.php';

//echo xdebug_get_profiler_filename();
@session_name($conf['session_name']);
session_start();

$controller = new KBClientController();
$controller->setDirVars($setting);
$controller->setModRewrite(false);

// $controller->checkRedirect();
// $controller->checkAuth();
// $controller->checkAutoLogin();
// $controller->checkAuthRegisteredOnly();
// $controller->checkPasswordExpiered();
// $controller->checkMustread($setting, $controller);

// custom code 
$controller->admin_login = true;
$allowed_view = ['login', 'mfa'];
if(!in_array($controller->view_id, $allowed_view)) {
    $controller->view_id = 'login';
}

$msg = AppMsg::getMsg('user_msg.ini', false, false, false);
$header_title = $setting['header_title'] ?: $conf['product_name'];
$header_title = sprintf('%s (%s)', $header_title, $msg['admin_area_msg']);
$nsetting = [
    'header_title'         => $header_title,
    'header_logo'          => '',
    'header_logo_mobile'   => '',
    'view_header'          => 1,
    'header_background'    => '',
    'header_color'         => '',
    'login_btn_background' => '',
    'login_btn_color'      => '',
    'page_to_load_tmpl'    => ''
];
// <--

$reg->setEntry('controller', $controller);

$manager = &KBClientLoader::getManager($setting, $controller);
$view    = &KBClientLoader::getView($controller, $manager);
$manager->setting = array_replace($manager->setting, $nsetting);

$page = new KBClientPageRenderer($view, $manager);
$page->display();

ob_end_flush();
?>