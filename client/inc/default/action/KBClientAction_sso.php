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


class KBClientAction_sso extends KBClientAction_common
{

    function &execute($controller, $manager) {

        if($manager->is_registered) {
            // $controller->go();
        }
                
        if(empty(AuthPriv::getUserData())) {
            $controller->go('login', false, false, 'sso_error');
        }
            
        $action = $controller->msg_id;
        
        if($action == 'merge' || $action == 'merge_failed') { 
            $view = $this->merge($controller, $manager);
        
        } else {
            $view = $this->incomplete($controller, $manager);
        
        }
        
        return $view;
    }
    
    
    // ldap and remote
    // this happens to merge current user account by email 
    // remote auth but email exist need verify it to merge
    // function mergeRemote($controller, $manager) {
    function merge($controller, $manager) {
        
        $view = &$controller->getView('sso_merge');
        $user_manager = new UserModel;

        $user_data = AuthPriv::getUserData();
        $view->remote_provider = $this->getSsoProviderName($user_data['remote_provider']);
        
        if(isset($this->rp->submit)) {

            $errors = $view->validate($this->rp->vars, $manager);
            if($errors) {

                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } else {

                $this->rp->stripVars(false);
                $user_data = RequestDataUtil::stripVars($user_data, array(), false);
                
                $log = new LoggerModel();
                $log->setLoginLogData(); // from session
                $log->putLogin('Merging accounts...');
                
                $auth_type = AuthProvider::getAuthType();
                $auth_type = (!empty($user_data['social'])) ? 'social' : $auth_type;
                
                $auth = Auth::factory('Priv');
                $user = $auth->getUserByValue(array('email' => $user_data['email']));
                
                if(!$user) {
                    $log->putLogin('Merging failed. Unable to get user by email.');
                    $log->addLogin(0, '', $auth_type, 2);                    
                    
                    $controller->go('this', false, false, 'merge_failed');
                }
                
                // mfa skipped here even is set on
                $ret = $auth->doAuth($user['username'], $this->rp->vars['password']);
                if(!$ret) {
                    $log->putLogin(sprintf('Login failed. (Username: %s)', $user['username']));
                    $log->addLogin(0, addslashes($user['username']), $auth_type, 2);
                    
                    $controller->go('this', false, false, 'merge_failed');
                }
                
                // remote/ldap/saml
                if(!empty($user_data['rewrite'])) {
                                        
                    $user_data['id'] = $user['id'];
                    $user_data['date_registered'] = $user['date_registered'];
                    
                    $umanager = new AuthRemoteModel;     
                    $umanager->saveUser($user_data);
                    
                    $user['username'] = $user_data['username'];
                
                // all providers
                } else {
                    
                    $sso = array(
                        'sso_user_id' => $user_data['remote_user_id'],
                        'sso_provider_id' => $user_data['remote_provider_id']
                    );
                
                    $user_manager->addSsoRecord($sso, AuthPriv::getUserId());
                }

                UserActivityLog::add('user', 'login');

                $user_id = $user['id'];
                $username = addslashes($user['username']);
                $exitcode = 1;
                
                $log->putLogin('Merging SSO account successful');
                $log->putLogin('Login successful');
                $log->addLogin($user_id, $username, $auth_type, $exitcode);

                $controller->go('success_go', false, false, 'merge_success'); // growl
            }
        }

        return $view;
    }
        

    // this happens only for Social sso
    function incomplete($controller, $manager) {
    
        $view = &$controller->getView('sso_incomplete');
        $user_manager = new UserModel;
        
        $user_data = AuthPriv::getUserData();
        $view->remote_provider = $this->getSsoProviderName($user_data['remote_provider']);
        
        if(isset($this->rp->submit)) {
            
            $errors = $view->validate($this->rp->vars, $user_manager);
            if($errors) {
            
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } else {

                $this->rp->stripVars(false);
                $user_data = RequestDataUtil::stripVars($user_data, array(), false);

                $log = new LoggerModel();
                $log->setLoginLogData(); // from session
                $log->putLogin('Completing account...');

                $auth_type = AuthProvider::getAuthType();
                $auth_type = (!empty($user_data['social'])) ? 'social' : $auth_type;

                $obj = new User;
                $obj->set($user_data);
                $obj->set('id', null);
                $obj->set('date_registered', null);
                $obj->set('email', $this->rp->vars['email']);
                $obj->set('username', $this->rp->vars['email']);
                $obj->set('password', WebUtil::generatePassword(4,3));
                                
                $obj->setPassword();
                $obj->setSso($user_data['remote_provider_id'], $user_data['remote_user_id']);
                            
                $user_id = $user_manager->save($obj);
                
                // mfa skipped here even if set on
                $auth = Auth::factory('Priv');
                $ret = $auth->doAuth($obj->get('username'), $obj->get('password'), false);
                if(!$ret) {
                    $log->putLogin('Login failed. (Username: %s)', $obj->get('username'));
                    $log->addLogin(0, '', $auth_type, 2);     
                    
                    $controller->go('this', false, false, 'login_failed');
                }
                
                UserActivityLog::add('user', 'login');
                
                $user = $user_manager->getById($user_id);
                $user_id = $user['id'];
                $username = addslashes($user['username']);
                $exitcode = 1;
                
                $log->putLogin('Completing SSO account successful');
                $log->putLogin('Login successful');
                $log->addLogin($user_id, $username, $auth_type, $exitcode);        
                
                $controller->go('index');
            }
        }
        
        return $view;
    }
    
    
    function getSsoProviderName($provider) {
        return ($provider == 'vk') ? strtoupper($provider) : ucfirst($provider);
    }
}
?>