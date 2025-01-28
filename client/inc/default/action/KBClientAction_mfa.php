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


class KBClientAction_mfa extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        // should be not completly logged
        if($manager->is_registered) {
           $controller->go();
        }
        
        if(!$sess = MfaAuthenticator::getSession()) {
            $controller->go();
        }
        
        $log = new LoggerModel;
        $log->_log['_login'] = unserialize($sess['log']);
        $user_id = intval($sess['user_id']);
        $auth_type = (isset($sess['auth_type'])) ? $sess['auth_type'] : false;
        
        $auth = new AuthPriv();
        $auth->log = &$log;
        $priv_id = $auth->_getUserPriv($user_id);
        
        if(!$secret = MfaAuthenticator::isRequired($user_id, $priv_id)) {
            $controller->go();
        }

        $mfa = MfaAuthenticator::getUserMfaData($user_id);
        $login = &$controller->getView('login');

        $view = &$controller->getView('mfa');
        $view->action = ($secret === true) ? 'setup' : 'pass';
        if($view->action == 'pass') {
            $view->mfa_type = $mfa['type'];
        }    
        
        // mfa
        if(isset($this->rp->submit)) {
            
            $vars = $this->rp->vars;
            $vars += $mfa;
            
            $errors = $view->validate($vars);
                
            if($errors) {
                if($vars['scratch']) {
                    $errors = $view->validateMfaScratch($vars);
                    $empty_scratch = ($errors) ? false : true;
                }
            }
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                
                if(!empty($vars['code'])) { // no log for empty input
                    $exitcode = 2;
                    $log->putLogin('MFA confirmation failed');
                    $auth->logAuth($sess, $exitcode, $auth_type);
                }
            
            } else {
                
                $this->rp->stripVars();
                
                MfaAuthenticator::destroySession();
                
                $log->putLogin('MFA confirmation successful');
                $ret = $auth->doAuthByValue(array('id' => $user_id));

                $exitcode = 1;
                $auth->postAuth();
                $auth->logAuth($sess, $exitcode, $auth_type);
                
                if(isset($sess['remember']) && $controller->isAutoLoginAllowed()) {
                    $ret = $auth->setRememberAuth(AuthPriv::getUserId());
                    
                    $m = new UserModel();
                    $m->sendRememberAuthSet(AuthPriv::getUserId(), $ret);
                }

                // reset scratch code go to account to take care reset mfa
                if(!empty($empty_scratch)) {
                    MfaAuthenticator::emptyScratch($user_id);
                    $controller->go('account', false, false, 'security'); 
                }
                
                if($login->isRotatePassword($manager)) {
                    $controller->go('account', false, false, 'password'); 
                }
                
                $laction = $controller->getAction('login');
                $laction->entry_id = $sess['entry_id'];
                $laction->goRedirect($controller, $sess['msg_id']);
            }
        
        
        // setup mfa
        } elseif(isset($this->rp->submit_setup)) { 
        
            $errors = $view->validate($this->rp->vars);
                
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            // no errors
            } else {
                   
                $this->rp->stripVars();
                
                $scratch = MfaAuthenticator::generateScratchCode($this->rp->vars['scratch']);
                
                $mfa = MfaAuthenticator::factory('app');
                $mfa->save($user_id, $this->rp->vars['secret'], $scratch['hash']);
                
                MfaAuthenticator::destroySession();
                
                $log->putLogin('MFA setup/confirmation successful');
                $ret = $auth->doAuthByValue(array('id' => $user_id));

                $exitcode = 1;
                $auth->postAuth();
                $auth->logAuth($sess, $exitcode, $auth_type);
            
                if(isset($values['remember']) && $controller->isAutoLoginAllowed()) {
                    $auth->setRememberAuth(AuthPriv::getUserId());
                }

                if($login->isRotatePassword($manager)) {
                    $controller->go('account', false, false, 'password'); 
                }
                
                $laction = $controller->getAction('login');
                $laction->entry_id = $sess['entry_id'];                
                $laction->goRedirect($controller, $sess['msg_id']);                
            }
        
        }
        
        return $view;    
    }

}
?>