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


class KBClientView_login extends KBClientView_common
{
    
    var $page_modal = true;
    
    var $login_msg = array(
        'comment'  => 'need_to_login_comment',
        'contact'  => 'need_to_login_contact',
        'enter'    => 'need_to_login_enter',
        'send'     => 'need_to_login_send',

        'afile'    => 'need_to_login_file',
        'file'     => 'need_to_login_file',
        'download' => 'need_to_login_file',
        'getfile'  => 'need_to_login_file',
    
        'entry'    => 'need_to_login_entry',
        'news'     => 'need_to_login_entry',

        'category' => 'need_to_login_category',
        'index'    => 'need_to_login_category',
        'files'    => 'need_to_login_category',

        'authtime' => 'need_to_login_authtime',
        'subscribe'=> 'need_to_login_subscribe',
        'mustread' => 'need_to_login_mustread',
        
        'rpfailed' => 'sso_params_error',
        'failed'   => 'login_failed',
    );
    
    
    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->meta_title = $this->msg['login_msg'];
        $this->nav_title = $this->msg['login_msg'];
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        return $this->getForm($manager);
    }
    

    function &getForm($manager) {
                                                            
        $tpl = new tplTemplatez($this->getTemplate('login_form.html'));

/*
        if ($manager->isUserBlocked()) {
            $errors['key'][]['msg'] = 'user_block_cookie';
            $this->setErrors($errors);
            
            $tpl->tplAssign('disabled', 'disabled'); 
        }
*/
        $tpl->tplAssign('error_msg', $this->getErrors());
        if(!$this->controller->admin_login) {
            $tpl->tplAssign('action_link', $this->getLink('all'));
        }
        
        $saml_only = (AuthProvider::isSamlAuth() && AuthProvider::isSamlOnly());
        if (!$saml_only) {
            $tpl->tplSetNeededGlobal('login_form');
        }
        
        $forgot_password = true;
        $forgot_password_link = $this->getLink('password');
        
        if($this->auth_remote) {
            $fpassword = AuthRemote::getPasswordLinkParams($forgot_password_link);
            $forgot_password = $fpassword['block'];
            $forgot_password_link = $fpassword['link'];
        }
        
        if($forgot_password) {
            $tpl->tplAssign('forgot_password_link', $forgot_password_link);
            $tpl->tplSetNeeded('/forgot_password');            
        }
        
        if($this->useCaptcha($manager, 'auth')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'auth', 'placeholder'));
        }
        
        if($manager->getSetting('register_policy')) {
            $tpl->tplAssign('register_link', $this->getLink('register'));
            $tpl->tplSetNeeded('/register_link');
        }
        
        // remember
        if($this->controller->isAutoLoginAllowed()) {
            $d = $this->getFormData();
            $tpl->tplAssign('remember_ch', $this->getChecked((isset($d['remember']))));
            $tpl->tplSetNeeded('/remember_me');
        }
        
        if(AuthProvider::isSamlAuth()) {
            $auth_setting = AuthProvider::getSettings();
            
            $more = array('sso' => 1, 'return' => $this->sso_return_url);
            $tpl->tplAssign('sso_link', $this->getLink('login', false, false, false, $more));
            
            $r = array('name' => $auth_setting['saml_name']);
            $login_via_msg = AppMsg::replaceParse($this->msg['login_via_msg'], $r);
            $tpl->tplAssign('login_via', $login_via_msg);
            
            $tpl->tplSetNeeded('/sso_link');
        }
        
        // social login, oath
        $social_login_block = false;
        
        if(AppPlugin::isPlugin('auth')) {
            $providers = AuthSocial::getProviderList();
            $providers = ($this->controller->admin_login) ? [] : $providers;

            foreach ($providers as $provider => $color) {
                if(SettingModel::getQuickCron(164, $provider . '_auth')) {
                    $social_login_block = true;
                    
                    $v = array();
                    $v['provider'] = $provider;
                    $v['color'] = $color;
                    
                    $auth = AuthSocial::factory($provider);
                    $v['auth_link'] = $auth->getLoginLink();
                    
                    $r = array('name' => ucwords($provider));
                    $v['login_via_msg'] = AppMsg::replaceParse($this->msg['login_via_msg'], $r);
                    
                    $tpl->tplParse($v, 'social_login/button');
                }
            }
        }

        if ($social_login_block) {
            $tpl->tplSetNested('social_login/button');
            $tpl->tplParse(null, 'social_login');
        }
        
        if($manager->getSetting('auth_allow_email') || $manager->getSetting('username_force_email')) {
            $this->msg['login_username_msg'] = $this->msg['login_email_msg'];
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager, $log, $check_csrf = true) {
        
        $auth = Auth::factory(($this->auth_remote) ? 'Remote' : 'Priv');
        $auth->setCheckIp($manager->getSetting('auth_check_ip'));
        $auth->log = &$log;
        
        // if not remote then local here and not ldap
        $auth_type = ($this->auth_remote) ? AuthProvider::getAuthType() : 'local';
        
        $v = new Validator($values, false);
        
        if($check_csrf) { // need to disable for remote auto auth
            $v->csrfCookie();
        }
        $v->required('required_msg', array('username', 'password'));
        
        if(!$this->auth_auto) {
            if($error = $this->validateCaptcha($manager, $values, 'auth', $values['username'])) {
                $v->setError($error[0], $error[1], $error[2], $error[3]);
            }
        }
                
        if($error = $v->getErrors()) {
            $log->putLogin(AppMsg::errorMessageString($error));
            $exitcode = 3;
        
        } else { 
            
            try {
                $ret = $auth->doAuth($values['username'], $values['password'], ['mfa' => true]);
            
            // mfa required
            } catch(AuthPrivException $e) {
                
                $url = $this->controller->getLink('mfa');
                if($this->controller->admin_login) {
                    $url =  $link = APP_ADMIN_PATH . 'login.php?View=mfa';
                }
                
                //$log->putLogin('MFA required...');
                $values['auth_type'] = $auth_type;
                $values['msg_id'] = $this->msg_id;
                $values['entry_id'] = $this->entry_id;
                $values['log'] = serialize($log->_log['_login']);
                MfaAuthenticator::setSession($values);

                $this->controller->goUrl($url);            
            
            // this exeptions goes from remote, ldap auth
            // saml on endpoint.php
            } catch(Exception $e) {
            
                $error_code = $e->getCode();
            
                // email exists, go to enter password to merge account
                if ($error_code == $auth::EMAIL_EXISTS_ERROR) {
                    $return_url = $this->controller->getLink('sso', false, false, 'merge');
            
                } elseif($error_code == $auth::WRONG_REMOTE_PARAMS_ERROR) {
                    $return_url = $this->controller->getLink('login', false, false, 'rpfailed');
            
                } else {
                    $return_url = $this->controller->getLink('login', false, false, 'rpfailed');
                }
            
                $this->controller->goUrl($return_url);
            }
                                    
            if($ret) { // executes on success and no mfa
                $auth->postAuth();
                $exitcode = 1;

                if(isset($values['remember']) && $this->controller->isAutoLoginAllowed()) {
                    $ret = $auth->setRememberAuth(AuthPriv::getUserId());
                
                    $m = new UserModel();
                    $m->sendRememberAuthSet(AuthPriv::getUserId(), $ret);
                }

            } else {

                $msg = sprintf('Login failed. (Username: %s)', $values['username']);
                $log->putLogin($msg);
                $exitcode = 2;
                                                  
                $msg = AppMsg::getMsgs('error_msg.ini', false, 'login_failed', 1); 
                $vars = array(); //array('count' => $msg_num); 
                $v->setError(BoxMsg::factory('error', $msg, $vars), 'auth', 'auth', 'formatted');
            }
        }
        
        $auth->logAuth($values, $exitcode, $auth_type);
        
        return $v->getErrors();
    }
    
    
    function isRotatePassword($manager) {
        $ret = false;
        $user_id = AuthPriv::getUserId();
        
        $password_rotation_freq = $manager->getSetting('password_rotation_freq');
        if($password_rotation_freq) {
            
            $pass = new PasswordUtil(new UserModel);
            if($pass->isPasswordExpiered($user_id, $password_rotation_freq)) {
                $pass->setPassExpired(1);
                $pass->saveExpieredPassword($user_id, addslashes($values['password']));
                
                if($num = $manager->getSetting('password_rotation_useold')) {
                    $pass->refreshSavedPasswords($user_id, $num);
                }
                
                $ret = true;
            }
        
            return $ret;
        }
    }
    
        
    // function getBanSetting($manager, $username, $ip = false) {
    // 
    //     if($ip === false) {
    //         $ip = WebUtil::getIP();
    //     }
    // 
    //     $ip = ($ip == 'UNKNOWN') ? 0 :  $ip;
    // 
    //     $s = array();
    //     $manager->setting['login_ban_ip'] = 7;
    //     if($allowed = (int) $manager->getSetting('login_ban_ip')) {
    //         $s[1]['ip'] = $allowed;
    //         $s[2]['ip']['allowed'] = $allowed;
    //         $s[2]['ip']['value']   = $ip;
    //     }
    // 
    //     $manager->setting['login_ban_username'] = 3;
    //     if($allowed = (int) $manager->getSetting('login_ban_username')) {
    //         $s[1]['username'] = $allowed;
    //         $s[2]['username']['allowed'] = $allowed;
    //         $s[2]['username']['value']   = $username;
    //     }
    // 
    //     return $s;
    // }
    
    // checking if banned
    /*$ban = BanModel::factory('login'); 

    $username = addslashes($values['username']);
    $log_manager = new LoginLogModel;  
    $user_id = ($ui_ = $log_manager->getUserIdByUsername($username)) ? $ui_ : 0;
    $user_ip = WebUtil::getIP();

    $ban_params = array('user_id' => $user_id, 'ip' => $user_ip, 'username' => $username);
    $is_ban = $ban->isBan($ban_params);

    if ($is_ban) {
        $auth = false;
        $error_key = 'user_banned';             
    }*/
    
    /* $ban_setting = $this->getBanSetting($manager, $values['username'], true);
     if($ban_setting) {
         $ban = BanModel::factory('login');
         if($date_banned = $ban->isBan(array('username' => $values['username']))) {    
             $v->setError('captcha_text_msg', 'ban', 'ban');
         }
     }*/
}
?>