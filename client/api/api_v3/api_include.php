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


require_once APP_CLIENT_DIR . 'client/inc/common.inc.php';

require_once API_DIR . 'KBApiLoader.php';
require_once API_DIR . 'KBApiResponce.php';
require_once API_DIR . 'KBApiController.php';
require_once API_DIR . 'KBApiModel.php';
require_once API_DIR . 'KBApiError.php';
require_once API_DIR . 'KBApiValidator.php';
require_once API_DIR . 'KBApiCommon.php';
require_once API_DIR . 'KBApiUtil.php';
require_once API_DIR . 'KBApiEntryModel.php';


// set api format, need it before any KBApiError call
$conf['api_format'] = 'json';
if(isset($_GET['format'])) {
    if(!in_array($_GET['format'], array('xml', 'json'))) {
        KBApiError::error(25, KBApiError::parseMsg('invalid', 'format'));
    }
    $conf['api_format'] = $_GET['format'];
}

// set api version, need it before any KBApiError call
$conf['api_version'] = 3; // by default recent, eleontev 07/04/2022
if(isset($_GET['version'])) {
    if(!in_array($_GET['version'], array_keys($valid_versions))) {
        KBApiError::error(25, KBApiError::parseMsg('invalid', 'version'));
    }
    $conf['api_version'] = $_GET['version'];
}


// api access 
if(empty($setting['api_access'])) {
    KBApiError::error(28);
}

// only https allowed
$setting['api_secure_port'] = 443;
if($setting['api_secure']) { 
    if($_SERVER['SERVER_PORT'] != $setting['api_secure_port']) {
        KBApiError::error(21);
    }    
}

$required = array('accessKey', 'timestamp', 'signature');
KBApiValidator::validateGetArguments($required);


// retrive private key, session id, etc
$public_key = KBApiController::getRequestVar('accessKey');
$public_key = addslashes($public_key);
$user_ip = WebUtil::getIP();

$am = new KBApiModel();
$info = $am->getApiInfoByPublicKey($public_key);

if(empty($info)) {
    KBApiError::error(3);
}

// with session in db
// serilaized session saved in db and we should control to delete/empty it. 
if($info['access'] == 1) { // $info['access'] = 2; //as public user, not logged
    $hash = md5($info['private_key'] . $info['user_id'] . 'wq2TR4&5');
    
    $minutes_valid = 60;
    $session = $am->getSession($info['user_id'], $minutes_valid);
    
    $_SESSION = array();
    if(!empty($session['session'])) {
        $_SESSION = unserialize($session['session']);
        $_SESSION['auth_']['thua'] = $hash;
    }
    
    if(!AuthPriv::isAuthSession($conf['auth_check_ip'])) {
        AuthPriv::logout(false);
        $auth = new AuthPriv;
        $auth->setSessionRegenerateId(false);
        $auth->setCheckIp($conf['auth_check_ip']);
        
        $options = [
            'hash' => $hash,
            'auth_concurrent' => 1
        ];
        $auth->doAuthByValue(array('id' => $info['user_id']), $options);
    
        // fix for api, as it does not have session_id, need some uniq value
        $auth->authToSessionApi($info['user_id'], $public_key, $hash);
        
        // $reg =& Registry::instance();
        // $reg->setEntry('auth', $auth);
    
        $session = serialize($_SESSION);
        $am->saveSession($session, $info['user_id'], $user_ip);
    }
}
// echo '<pre>', print_r(@$_SESSION,1), '<pre>';

$cc = new KBClientController();
$cc->setDirVars($setting);
$cc->arg_separator = '&';
$reg->setEntry('controller', $cc);

$view = new KBClientView();
$reg->setEntry('view', $view);

$controller = new KBApiController();
$controller->setUrlVars();
$controller->setDirVars($setting);

KBApiValidator::validateCall($controller->call, $controller->call_map);
KBApiValidator::validateSignature($info['private_key'], $controller);

$manager = &KBApiLoader::getManager($setting, $cc, $controller->call);
$api     = &KBApiLoader::getApi($controller, $manager);

// echo '<pre>', print_r(get_class ($manager),1), '<pre>';
// echo '<pre>', print_r(get_class ($api),1), '<pre>';

KBApiValidator::validateRequest($controller->request_method, $api->allowed_requests);


// responce
$responce = KBApiResponce::factory($api->format);
echo $responce->process($api, $controller, $manager);
?>