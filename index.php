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


$app_dir = str_replace('\\', '/', getcwd()) . '/admin/';     // trying to guess admin directory
//$app_dir = '/path_to/kb/admin/';                           // set it manually


/* DO NOT MODIFY */
//if(!is_file($app_dir . 'config.inc.php')) {
//  echo 'Wrong path! Set correct value for $app_dir at the top of index.php <br /><br />';
//}

require_once $app_dir . 'config.inc.php';
require_once $app_dir . 'config_more.inc.php';

if($conf['allow_setup']) {
    $setup_dir = str_replace('index.php', '', $_SERVER['PHP_SELF']) . 'setup/index.php';
    header("Location: " . $setup_dir);
    exit;
}

require_once APP_CLIENT_DIR . 'client/inc/common.inc.php';

//echo xdebug_get_profiler_filename();
@session_name($conf['session_name']);
session_start();


$controller = new KBClientController();
$controller->setDirVars($setting);
$controller->setModRewrite($setting['mod_rewrite']);
//$controller->setModRewrite(false);
//exit;

$controller->checkRedirect();
$controller->checkAuth();
// $controller->checkAuthByAccessKey(0);
$controller->checkAutoLogin();
$controller->checkAuthRegisteredOnly();
$controller->checkPasswordExpiered();
// $controller->checkMustread($setting, $controller);

$reg->setEntry('controller', $controller);


$manager = &KBClientLoader::getManager($setting, $controller);
$view    = &KBClientLoader::getView($controller, $manager);

// $m = new MustreadModel;
// $mustreads = $m->getUserMustreads(AuthPriv::getUserId(), false, 1);
// echo '<pre>', print_r($mustreads,1), '</pre>';

$page = new KBClientPageRenderer($view, $manager);

// example of adding global variable to template
// in template you should have a template tag - {global_test}
// $page->assign('global_test', '<b>test global variable</b>');

// echo $page->render();
$page->display();

ob_end_flush();
?>
