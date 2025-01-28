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


class KBClientAction_login extends KBClientAction_common
{

    function &execute($controller, $manager) {
    
        if(!$controller->admin_login) {

            // check access for registerd only 
            // and change "login_policy" to avoid never ended redirect
            if($manager->getSetting('kb_register_access')) {
                $manager->setting['login_policy'] = 2; // allow login, hiden link
            }
            
            // check if login allowed, 9 = not allowed
            if($manager->getSetting('login_policy') == 9) {
                $controller->go();
            }
            
            if($manager->is_registered) {
                $controller->go();
            }
        }
        
        $rvars = AuthProvider::getRemoteAuthVars();
        $auth_remote = $rvars['auth_remote'];
        $auth_auto = $rvars['auth_auto'];
        $load_remote_error = $rvars['load_remote_error'];
        
        $view = &$controller->getView('login');
        $view->auth_remote = $auth_remote;
        $view->auth_auto = $auth_auto;

        $msg_id = $view->msg_id;
        // $view->msg_id = false;
        
        $log = new LoggerModel;
        
                
        if(!isset($this->rp->submit)) {
            $authtime = (strpos($msg_id, 'authtime') !== false);
            $authtime = (strpos($msg_id, 'auth_expired') !== false) ?: $authtime;
            $enter = (strpos($msg_id, 'enter') !== false);
            $mustread = (isset($_GET['mustread']));
            if(in_array($msg_id, array_keys($view->login_msg)) || $authtime || $enter) {
                if($authtime) {
                    $msg_id_1 = 'authtime';
                } elseif($enter) {
                    $msg_id_1 = 'enter';
                } elseif($mustread) {
                    $msg_id_1 = 'mustread';
                } else {
                    $msg_id_1 = $msg_id;
                }
                
                $view->msg_id = $view->login_msg[$msg_id_1];
            }
        }
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        // saml
        if (AuthProvider::isSamlAuth()) {
            $return_url = $this->getRedirectLink($controller, $msg_id);
            
            if (!empty($this->rq->return)) {
                $return_url = $this->rq->return;
            }
            
            $view->sso_return_url = $return_url;
        }
        
        // normal login
        if(isset($this->rp->submit)) {
            
            $log->putLogin('Initializing...');
            if($load_remote_error) {
                $log->putLogin($load_remote_error);
            }
            
            AuthPriv::logout();
            $errors = $view->validate($this->rp->vars, $manager, $log);
            
            if($errors) {
                sleep(1);
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                if($view->isRotatePassword($manager)) {
                    $view->controller->go('account', false, false, 'password'); 
                }
                
                $this->goRedirect($controller, $msg_id);
            }
        
        // auto login in remote authentication
        } elseif ($auth_auto) {
            
            $r = AuthPriv::isRemoteAutoAuth();
            AuthPriv::logout();
            AuthPriv::setRemoteAutoAuth($r);
            
            $log->putLogin('Initializing...');
            if($load_remote_error) {
                $log->putLogin($load_remote_error);
            }
            
            $auth = Auth::factory('Remote');
            $auth->log = &$log;
            $user = $auth->autoAuth();

            if($user !== false) {
                $errors = $view->validate($user, $manager, $log, false);
    
                if($errors) {
                    // $view->setErrors($errors);
        
                } else {
                    $this->goRedirect($controller, $msg_id);
                }
                
            } else {
                
                // for remote write last login to file to debug 
                $log->writeLoginLogFile();
            }
        
        // SAML
        } elseif (AuthProvider::isSamlAuth() && !empty($this->rq->sso)) {
            
            AuthPriv::logout();
            
            AuthProvider::loadSaml();
            $auth_setting = AuthProvider::getSettings();
            
            $ol_auth = AuthSaml::getOneLogin($auth_setting, $auth_setting['saml_sso_binding']);
            $ol_auth->login($return_url);
            
            exit;
        }
        
        return $view;
    }


    function goRedirect($controller, $msg_id) {
        $link = $this->getRedirectLink($controller, $msg_id);
        $controller->goUrl($link);
    }
    
    
    function getRedirectLink($controller, $msg_id) {
        
        // go to admin
        if($controller->admin_login) {
            $link = APP_ADMIN_PATH . 'index.php?module=home&page=home';
            if(!empty($_SESSION['ref_'])) {
                $link = APP_ADMIN_PATH . 'index.php?' . str_replace('&amp;', '&', WebUtil::unserialize_url($_SESSION['ref_']));
            }
            
            return $link;
        }
        
        // when want to dowload and sent to login
        if($msg_id == 'file') { 
            $msg_id = 'download'; 
        }

        $msg_go = false;
        
        // go to post form if comment
        if($msg_id == 'comment') { 
            $msg_go = 'post'; 
        }
    
        // should keep msg param on submit to be redirectd to required place
        // msg we should reset to be redirected correct after login
        $search = array(
            'authtime_', 'category', 'enter', 
            'password_reset_success', 'registration_confirmed', 
            'rpfailed', 'failed',
            '_'); // _ should be last
        $view_id = str_replace($search, '', $msg_id);
        if(empty($view_id)) {
            $view_id = 'index';
        }
        
        // user account, member
        if($msg_id == 'account') {
            $view_id = 'account';
        }
        
        // we have only entry_id in url
        $cat_views = array('index', 'files');
        if(in_array($view_id, $cat_views)) {
            $this->category_id = $this->entry_id;
            $this->entry_id = false; 
        }
        
        // search params
        $more = array();
        if($view_id == 'search') {
            $more = $controller->getSearchParamsOnLogin($_GET);
        }
        
        // subscribe, entry_type param
        if($view_id == 'subscribe' & isset($_GET['t'])) {
            $more = array('t' => (int) $_GET['t']);
        }
        
        // mustread
        if(isset($_GET['mustread'])) {
            $more = array('mustread' => 1);    
        }
        
        $link = $controller->getLink($view_id, $this->category_id, $this->entry_id, $msg_go, $more);        
        // echo '<pre>msg_id: ', print_r($msg_id, 1), '</pre>';
        // echo '<pre>view_id: ', print_r($view_id, 1), '</pre>';
        // echo '<pre>link: ', print_r($link, 1), '</pre>';
        // exit;
        
        return $link;
    }
    

}


    // check ip ban            

    // if($allowed = (int) $manager->getSetting('login_ban_ip')) {
    //     $ban_manager = BanModel::factory('login');
    //
    //     if($date_banned = $ban_manager->isBan(array('ip'=>$allowed))) {
    //         $view->msg_id = '';
    //
    //     } else {
    //         $ban_setting = $view->getBanSetting($manager, $this->rp->username);
    //         $try_period = $manager->getSetting('login_ban_try_period');
    //         $ban_period = $manager->getSetting('login_ban_set_period');
    //
    //         $to_ban = false;
    //         foreach($ban_setting as $rule => $v) {
    //             if($v['allowed']) {
    //                 $real_tries = $ban_manager->getBadTries($rule, $v['value'], $v['allowed'], $try_period);
    //                 $rest_tries = $v['allowed'] - $real_tries;
    //
    //
    //                 // if($rest_tries <= 0) {
    //                 //     $ban_manager->ban($rule, $v['value'], $ban_period, $admin_reason = false, $user_reason = false);
    //                 //     $to_ban = $rule;
    //                 //     break;
    //                 // }
    //             }
    //         }
    //
    //     }
    // }


?>