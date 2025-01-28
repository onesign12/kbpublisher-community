<?php
require_once 'config.inc.php';
require_once 'config_more.inc.php';
require_once 'common.inc.php';

$msg = (!empty($_GET['msg'])) ? urlencode($_GET['msg']) : false; 
$page = (!empty($msg)) ? 'login.php?msg=' . $msg : 'login.php';
$full = (!empty($_GET['full'])) ? true : false;

$auth_setting = AuthProvider::getSettings();

// if (AuthProvider::isSamlAuth() && $auth_setting['saml_slo_endpoint'] && $full) { // saml
//
//     $cc = AppController::getClientController();
//
//     $relay_state = APP_ADMIN_PATH . $page;
//     $more_slo = array('return' => APP_ADMIN_PATH . 'index.php?module=home&page=home');
//     $slo_url = $cc->getLink('logout', false, false, false, $more_slo);
//
//     header('Location: ' . $cc->_replaceArgSeparator($slo_url));
//     exit;
// }


session_name($conf['session_name']);
session_start();

if (AuthProvider::isSamlAuth() && AuthPriv::isSaml() && $auth_setting['saml_slo_endpoint'] && $full) {
    
    $controller = new AppController();
    
    $more = array('return' => APP_ADMIN_PATH . $page);
    $link = array('logout', false, false, false, $more);
    $link = $controller->getClientLink($link);
    
    header("Location: " . $link);
    exit;
}

AuthPriv::logout($full);

header("Location: " . $page);
exit;
?>