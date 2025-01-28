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

class KBClientAction_password_set_test_to_delete extends KBClientAction_common
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
                $controller->go('confirm', false, false, 'registration_not_confirmed');
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
    
    
    function validate($values, $user) {
         
        $required = array('ec');

        $v = new Validator($values, false);
        $v->required('required_msg', $required);
        
        if($v->getErrors()) {
            return $v->getErrors();
        }

        if(!$user) {
            $v->setError('confirm_text_msg', 'user', 'user');
            return $v->getErrors();
            
        } else {
            
        }
        
        
        return $v->getErrors();
    }
}
?>