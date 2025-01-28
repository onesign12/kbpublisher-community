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

class SetupAction_setup extends SetupAction
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
                if(!isset($this->rp->vars['old_config_file_skip'])) {
                    $manager->setSetupData(array('old_config_file_skip'=>0));
                }
                
                $manager->setSetupData($this->rp->vars);
                $controller->go($controller->getNextStep());
            }
        }
        
        return $view;
    }
    
    
    function validate(&$values, $manager, $version_rule = array()) {
        
        if($values['setup_type'] == 'install' || isset($values['old_config_file_skip'])) {
            return false;
        }
        
        if(strpos($values['setup_upgrade'], '20_') !== false) {
            return false;
        }
        
        $v = new Validator($values, false);        

        if(!is_file($values['old_config_file'])) {
            $v->setError('file_not_exist_msg', 'old_config_file', 'old_config_file');
            return $v->getErrors();            
        }
        
        // check for version in select and in config_more.php
        /*
        $config_version_new = false;
        $file = APP_ADMIN_DIR . 'config_more.inc.php';
        if($content = FileUtil::read($file)) {
            preg_match("#.*product_version.*'(\d\.\d(\.\d)?)'#", $content, $match);
            if(!empty($match[1])) {
                $config_version_new = str_replace('.', '', $match[1]);
            }
        }
        
        if(!$config_version_new) {
            return false;
        }
        */

        // add any spcial rules here 
/*
        $validate_range = array();
        $validate_range['45'] = array(45,451,452,453); // if from 45_...
        
        
        $file = dirname($values['old_config_file']) . '/config_more.inc.php';
        if($content = FileUtil::read($file)) {
            preg_match("#.*product_version.*'(\d\.\d(\.\d)?)'#", $content, $match);
            if(!empty($match[1])) {
                $config_version = str_replace('.', '', $match[1]);
                $choosed_version = preg_replace('#_to_\d+#', '', $values['setup_upgrade']);
                
                $valid_versions = array();
                if(isset($validate_range[$choosed_version])) {
                    $valid_versions = $validate_range[$choosed_version];
                } else {
                    $valid_versions = array($choosed_version);
                }
                
                    
                if(!in_array($config_version, $valid_versions)) {
                    $v->setError('wrong_version_from', 'wrong_version', 'wrong_version');
                    echo '<pre>', print_r('ERROR', 1), '</pre>';
                    // return $v->getErrors();
                } else {
                    echo '<pre>', print_r("OK"  , 1), '</pre>';
                }
            }
        }*/

        
        // echo '<pre>', print_r($values['setup_upgrade'], 1), '</pre>';
        // echo '<pre>config_version_new: ', print_r($config_version_new, 1), '</pre>';
        // echo '<pre>config_version: ', print_r($config_version, 1), '</pre>';
        // echo '<pre>choosed_version: ', print_r($choosed_version, 1), '</pre>';
        // echo '<pre>valid_versions: ', print_r($valid_versions, 1), '</pre>';
        // echo '<pre>', print_r($match, 1), '</pre>';
        
        // exit;
    }
    
}
?>