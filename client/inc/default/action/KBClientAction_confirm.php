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

class KBClientAction_confirm extends KBClientAction_common
{

    function &execute($controller, $manager) {
    
        // check if registration allowed
        if(!$manager->getSetting('register_policy')) {
            $controller->go();
        }
        
        //if($manager->is_registered) {
        //    $controller->go();
        //}    
        
        // just redirect if no confirm str or message
        if(!$this->msg_id && !isset($this->rq->ec)) {
            $controller->go();
        }
        
        
        $action = $controller->msg_id;
        
        if($action == 'sent') {    
            $view = $this->confirmSent($controller, $manager);
        
        } else {
            $view = $this->confirm($controller, $manager);
        }
        
        return $view;
    }
    
    
    function confirm($controller, $manager) {
        
        $view = &$controller->getView();
    
        if(isset($this->rq->ec) || isset($this->rp->submit)) {

            $values['ec'] = (isset($this->rq->ec)) ? $this->rq->ec : $this->rp->ec;    
            
            $user = array();
            if($values['ec']){

                $code = addslashes(stripslashes($values['ec']));
                if($user = $manager->isUser($code)) {

                    // TODO: maybe we need a period of time when user can  approve registration
                    //$manager->setting['register_approval_period'] = 24;

                    // TODO: maybe to make some message "Account cofirmed ...."
                    // user is not uncomfirmed status but trying to access confirmation, 3 = unconfirmed
                    if($user['active'] != 3) {
                        $controller->go();
                    }
                }
            }
            
            
            if(!$user) {
                $controller->go('success_go', false, false, 'registration_not_confirmed');
            }            
            
            
            $errors = array(); //$this->validate($values, $user);
            if($errors) {
            
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } else {
        
                $user_id = $user['id'];            
                if($manager->getSetting('register_approval')) {
                    $sent = $manager->sendApproveRegistrationAdmin($user_id);
                    if(!$sent) {
                        $controller->go('success_go', false, false, 'registration_not_confirmed');
                    }
    
                    $sent = $manager->sendApproveRegistrationUser($user_id);
                    $manager->setUserStatus($user_id, 2);
                    $manager->setUserGrantor($user_id, 0); // then it will be updated by approver id
                    $controller->go('success_go', false, false, 'registration_confirmed_approve');

                } else {
                    $sent = $manager->sendRegistrationConfirmed($user_id, $view);
                    $manager->setUserStatus($user_id, 1);
                    $manager->setUserGrantor($user_id, $user_id); 
                    $controller->go('success_go', false, false, 'registration_confirmed');        
                }
            }
        }
        
        return $view;
    }
    
    
    // a form after submit registration form 
    // with option to resend confirmation email
    function confirmSent($controller, $manager) {
        
        $view = &$controller->getView('confirm_sent');
        
        if(isset($this->rp->submit)) {
            
            $vars = unserialize($_SESSION['kb_reg_user_']);
            $sent = $manager->sendConfirmRegistration($vars, $view);
        
            if($sent) {
                $controller->go('confirm', false, false, 'sent');
            
            } else {
                $view->msg_id = 'confirmation_not_sent';
            }
        }
        
        return $view;
    }

}
?>