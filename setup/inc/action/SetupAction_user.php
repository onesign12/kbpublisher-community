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

class SetupAction_user extends SetupAction
{

    function &execute($controller, $manager) {
        
        $view = $controller->getView();
        
        if(isset($this->rp->setup)) {
            
            $errors = $this->validate($this->rp->vars, $manager);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                $manager->setSetupData($this->rp->vars);
                $controller->go($controller->getNextStep());
            }
        }
        
        return $view;
    }
    
    
    function validate($values, $manager) {
        
        $required = array('first_name', 'last_name', 'email', 'username');
        
        $v = new Validator($values);

        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
                
        $v->regex('email_msg', 'email', 'email');
                
        if($v->getErrors()) {
            return $v->getErrors();
        }
    }
    
}
?>