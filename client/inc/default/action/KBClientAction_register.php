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


class KBClientAction_register extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        // check if registration is not allowed
        if(!$manager->getSetting('register_policy')) { 
            $controller->go();
        }
        
        // check if registered
        if($manager->is_registered) {
            $controller->go();
        }
        
        $view = $this->getForm($controller, $manager);
        
        return $view;
    }
    
    
    function getForm($controller, $manager) {
        
        $view = &$controller->getView('register');
        $view->update = false;
        
        $obj = new User;
        $manager_2 = new UserModel;
        $manager_2->use_priv = true;
        $manager_2->use_role = true;
        $manager_2->use_old_pass = false;
        
        
        if(isset($this->rp->submit)) {

            $errors = $view->validate($this->rp->vars, $manager, $manager_2);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $sent = $manager->sendConfirmRegistration($this->rp->vars, $view);
            
                if($sent) {
        
                    $_SESSION['kb_reg_user_'] = serialize($this->rp->vars);
        
                    $substr_values = [
                        'username' => 50, 'email' => 100, 
                        'first_name' => 50, 'middle_name' => 50, 'last_name' => 100,
                        'phone' => 20, 'address' => 255, 'address2' => 255, 'city' => 50, 'zip' => 20
                    ];
                    $this->rp->setSubstrValues($substr_values);
                    $this->rp->stripVars();
                    
                    $obj->set($this->rp->vars);
                    $obj->set('id', NULL);
                    $obj->set('active', 3); // unconfirmed
                    $obj->setUsername(1); // 1 to force set as email
                    $obj->setPassword();

                    // remove default news subscription
                    if (empty($this->rp->vars['subsc_news'])) {
                        $obj->setSubscription(NULL);
                    }
                    
                    $priv = $manager->getSetting('register_user_priv');
                    if($priv) {
                        $obj->setPriv($priv);
                    }
                    
                    $role = $manager->getSetting('register_user_role');
                    if($role) {
                        $obj->setRole($role);
                    }
                                
                    $manager_2->save($obj);
                    $controller->go('confirm', false, false, 'sent');
                
                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'confirmation_not_sent';
                }
            }
        }
        
        return $view;
    }
        
}
?>