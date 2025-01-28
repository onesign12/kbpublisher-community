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


class SetupAction_update extends SetupAction
{

    function &execute($controller, $manager) {
        
        $values = $manager->getSetupData();
        $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
        
        $manager = new SetupModelUpdate();        
        $manager->setTables($values['tbl_pref']);
        $manager->connect($values);

        $duplicated_email = $manager->isDuplicatedEmail();
        
        if(isset($this->rp->update_email) || $duplicated_email) {
            $manager = SetupModelUpdate::factory('email', $manager);
            $view = $this->processEmail($controller, $manager);

        } else {
            $controller->go($controller->getNextStep());
        }
        
        return $view;
    }
    
    
    // UPDATE EMAIL // ------------------
    
    function processEmail($controller, $manager) {
        
        $view = $controller->getView('update_email');
        $view->emanager =& $manager;
        
        if(isset($this->rp->setup)) {
            
            $errors = $this->validateEmail($this->rp->vars, $manager);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
            
                $this->rp->stripVars();
                foreach($this->rp->vars['email'] as $user_id => $email) {
                    $manager->updateEmail($email, $user_id);
                }
                
                $controller->go($controller->getCurrentStep());
            }
        }
        
        return $view;
    }


    
    function validateEmail($values, $manager) {
        
        $v = new Validator($values, false);

        // correct emails
        $error_emails = array();
        foreach($values['email'] as $user_id => $email) {
            if(!Validate::regex('email', $email, true)) {
                $error_emails[] = $user_id;
            }
        }

        if($error_emails) {
            $v->setError('email_incorrect', $error_emails);
            return $v->getErrors();
        }

        // uniques emails
        $is_unique = ($values['email'] == array_unique($values['email']));
        if(!$is_unique) {
            $not_unique = array_diff_assoc($values['email'], array_unique($values['email']));
            $v->setError('email_unique', array_keys($not_unique));
            return $v->getErrors();
        }
        
        // emails exists
        $error_emails = array();
        $user_ids = implode(',', array_keys($values['email']));
        foreach($values['email'] as $user_id => $email) {
            $email = addslashes(stripslashes($email));
            if($manager->isEmailExist($email, $user_ids)) {
                $error_emails[] = $user_id;
            }
        }

        if($error_emails) {
            $v->setError('email_exists', $error_emails);
            return $v->getErrors();
        }
    }

}
?>