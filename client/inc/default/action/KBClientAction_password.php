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

// this is to reset/remind password

class KBClientAction_password extends KBClientAction_common
{

    var $link_lifetime = 30; // mins
    var $hide_user_not_found = 1;
    

    function &execute($controller, $manager) {

        // check if login allowed, disable as also used by admin area
        // if($manager->getSetting('login_policy') == 9) {
            // $controller->go();
        // }
        
        if($manager->is_registered) {
            $controller->go();
        }
        
        
        $action = $controller->msg_id;
        
        if($action == 'reset') {
            $view = $this->reset($controller, $manager);
        
        } elseif($action == 'sent') {
            $view = $this->passwordLinkSent($controller, $manager);
        
        } elseif($action == 'set') {
            $view = $this->set($controller, $manager);
        
        } elseif($action == 'unlock') {
            $view = $this->unlockAccount($controller, $manager);
        
        } else {
            $view = $this->requestResetLink($controller, $manager);
        }
        
        return $view;
    }       
    
    
    // user click forgot password and fill form and click ok
    function requestResetLink($controller, $manager) {
        
        $view = &$controller->getView('password');
       
        if(isset($this->rp->submit)) {

            $pass = new PasswordUtil($manager);
            $pass->setExtSql('AND active = 1');
            $email = addslashes(stripslashes($this->rp->vars['email']));

            $errors = $view->validate($this->rp->vars, $manager);

            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } elseif(!$user_id = $pass->isEmailExists($email)) {
                
                if(!$this->hide_user_not_found) {
                    $v = new Validator($this->rp->vars, false);
                    $v->setError('user_not_exists_msg', '1');  
                    $errors = $v->getErrors();

                    $this->rp->stripVars(true);
                    $view->setErrors($errors);
                    $view->setFormData($this->rp->vars);
                
                } else {
                    $_SESSION['resent_code_'] = $pass->generatePassword(0, 4);
                    $controller->go('password', false, false, 'sent');
                }

            } else {

                $resent_code = $pass->generatePassword(0, 4);
                $reset_code = $pass->generatePassword(4, 4);
                $pass->setUserResetPassword($user_id, $resent_code, $reset_code);

                $more = array('rc' => $reset_code);
                $link = $controller->getFolowLink('password', false, false, 'reset', $more);
                $user = $manager->getUserInfo($user_id);

                $sent = $manager->sendResetPasswordLink($user, $reset_code, $link);

                if($sent) {
                    $_SESSION['resent_code_'] = $resent_code;
                    $controller->go('password', false, false, 'sent');

                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'password_reset_not_sent';
                }
            }
        }

        return $view;
    } 


    // when reset (forgot password) link sent show ok and resend btn
    function passwordLinkSent($controller, $manager) {
        
        $view = &$controller->getView('password_sent');
        $view->hide_user_not_found = $this->hide_user_not_found;
        
        if(isset($this->rp->submit)) {
            
            $pass = new PasswordUtil($manager);
                                        
            $code = addslashes($_SESSION['resent_code_']);
            $reset_min = $this->link_lifetime;
            
            $user_id = $pass->getUserByResentPasswordCode($code, $reset_min);
            
            if(!$user_id) {
                $controller->go('password', false, false, 'password_reset_error');
            
            } else {

                $this->rp->stripVars();

                $reset_code = $pass->getResetCodeByResentPasswordCode($code);
                $more = array('rc' => $reset_code);
                $link = $controller->getFolowLink('password', false, false, 'reset', $more);
                $user = $manager->getUserInfo($user_id);

                $sent = $manager->sendResetPasswordLink($user, $reset_code, $link);

                if($sent) {
                    $controller->go('password', false, false, 'sent');

                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'password_reset_not_sent';
                }
            }
        }
        
        return $view;     
    }
    
    
    // set new password when use forgot password
    function reset($controller, $manager) {
        
        if(empty($this->rq->rc)) {
            $controller->goStatusHeader('404');
        }
        
        $view = &$controller->getView('password_reset');
        
        
        if(isset($this->rp->submit)) {
            
            $pass = new PasswordUtil($manager);

            $errors = $view->validate($this->rp->vars, $manager);
                        
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $reset_code = addslashes($this->rq->rc);
                $reset_min = $this->link_lifetime;
                
                $user_id = $pass->getUserByResetPasswordCode($reset_code, $reset_min);
                
                if(!$user_id) {
                    $controller->go('password', false, false, 'password_reset_error');
                
                } else {

                    $this->rp->stripVars();
                    
                    $password = HashPassword::getHash($this->rp->password);
                    $pass->setPassword($user_id, $password);
                    $pass->unsetUserResetPassword($user_id);
                    
                    $m = new UserModel();
                    $sent = $m->sendPasswordChanged($user_id);

                    $controller->go('login', false, false, 'password_reset_success');
                }
            }
        }
        
        return $view;     
    }


    // used when we need new users to create a password
    function set($controller, $manager) {
        
        if(empty($this->rq->rc)) {
            $controller->goStatusHeader('404');
        }
        
        $view = &$controller->getView('password_reset');
        
        
        if(isset($this->rp->submit)) {
            
            $pass = new PasswordUtil($manager);

            $errors = $view->validate($this->rp->vars, $manager);
                        
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $reset_code = addslashes($this->rq->rc);
                $reset_min = $this->link_lifetime;
                
                $user_id = $pass->getUserByResetPasswordCode($reset_code, $reset_min);
                
                if(!$user_id) {
                    $controller->go('password', false, false, 'password_reset_error');
                
                } else {

                    $this->rp->stripVars();
                    
                    $password = HashPassword::getHash($this->rp->password);
                    $pass->setPassword($user_id, $password);
                    $pass->unsetUserResetPassword($user_id);
                    
                    $manager->setUserStatus($user_id, 1);

                    $controller->go('login', false, false, 'password_reset_success');
                }
            }
        }
        
        return $view;     
    }
}
?>