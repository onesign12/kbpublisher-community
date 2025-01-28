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

class SetupAction_initial extends SetupAction
{

    var $dirs = array('html_editor_upload_dir', 'file_dir', 'cache_dir');


    function &execute($controller, $manager) {
        
        $view = $controller->getView();
        
        if(isset($this->rp->setup)) {
            
            $errors = $this->validate($this->rp->vars, $manager);
            
            if(!$errors) {
                $errors = $this->process($this->rp->vars, $manager);
            }            
            
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
    
    
    function validate(&$values, $manager) {
        
        $required = array('http_host', 'document_root', 
                          'client_home_dir', 'client_home_dir', 
                          'html_editor_upload_dir', 'file_dir', 'cache_dir');
        
        $v = new Validator($values, true);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        return $v->getErrors();        
    }
    
    
    function process(&$values, $manager) {
    
        $v = new Validator($values, true);
        
        $dirs = array('document_root', 'client_home_dir', 'admin_home_dir');
        foreach($dirs as $dir) {
            if(!is_dir($values[$dir])) {
                $v->setError('dir_not_exist', $dir);
            }        
        }
        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        
        $dirs = array('cache_dir', 'file_dir', 'html_editor_upload_dir');
        foreach($dirs as $dir) {
            if(!is_dir($values[$dir])) {
                @mkdir($values[$dir], 0755);
                @chmod($values[$dir], 0755);
            }            
            
            $v->writeable('dir_not_writable_msg', $dir);
        }
        
        return $v->getErrors();
    }
}
?>