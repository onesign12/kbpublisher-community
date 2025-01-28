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

class SetupAction_db extends SetupAction
{

    function &execute($controller, $manager) {
        
        $view = $controller->getView();
        
        if(isset($this->rp->setup)) {
            
            $errors = $this->validate($this->rp->vars, $manager);
            
            // if(!$errors) {
            //     $errors = $this->process($this->rp->vars, $manager);
            // }        
            
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
        
        $required = array('db_host', 'db_base', 'db_user');
        
        $v = new Validator($values, true);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
    
        
        // upgrade
        if($manager->isUpgrade()) {
    
            $ret = $manager->connect($values);
            if($ret !== true) {
                $v->setError($ret, '', '', 'formatted');
                return $v->getErrors();
            }
            
            //check prefix
            $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
            $manager->setTables($values['tbl_pref']);
            $ret = $manager->checkPrefixOnUpgrade();

            if($ret !== true) {
                $v->setError('db_prefix', '123');
                return $v->getErrors();
            }
            
            // check version
            $mysql = $manager->getMySQLVersion();
            if($mysql < 5.1) {
                $msg = $this->getMySQLVersionMsg($mysql);
                $v->setError($msg, '', '', 'formatted');
                return $v->getErrors();
            }
            
            
        // install
        } else {
            
            // if can connect then db exists, nothing to do
            $ret = $manager->connect($values, true);
            if($ret !== true) {
                
                // Unknown database, try to create one
                if($manager->db->ErrorNo() == 1049) {

                    $values_no_db_base = $values;
                    unset($values_no_db_base['db_base']);                
                    $ret_no_db_base = $manager->connect($values_no_db_base, true);

                    if($ret_no_db_base !== true) {
                        $v->setError($ret_no_db_base, '', '', 'formatted');
                        return $v->getErrors();
                    }

                    // check version
                    $mysql = $manager->getMySQLVersion();
                    if($mysql < 4.1) {
                        $msg = $this->getMySQLVersionMsg($mysql);
                        $v->setError($msg, '', '', 'formatted');
                        return $v->getErrors();
                    }

                    // create db
                    $ret = $manager->createDB($values['db_base'], 'utf8', 'utf8_general_ci');
                    if($ret !== true) {
                        $v->setError($ret, '', '', 'formatted');
                        
                        $tags = array('sql' => $manager->getCreateDbSqlMsg($values, 'utf8', 'utf8_general_ci'));
                        $msg = AppMsg::getMsgs('error_msg.ini', 'setup', 'create_sql', 1);
                        $msg = BoxMsg::factory('hint', $msg, $tags);
                        $v->setError($msg, '', '', 'formatted');
                        
                        return $v->getErrors();
                    }
                
                // another error for manager->connect($values);
                } else {
                    $v->setError($ret, '', '', 'formatted');
                    return $v->getErrors();
                }
            
            } // -> if($ret !== true) {         

        } // -> if($manager->isUpgrade()) {
                
    }
    
    
    function getMySQLVersionMsg($mysql) {
        $tags = array('required_db' => 'MySQL 5.1 >=', 'current_db' => 'MySQL ' . $mysql);
        $msg = AppMsg::getMsgs('error_msg.ini', 'setup', 'db_version', 1);
        return BoxMsg::factory('error', $msg, $tags);
    }
    
    
    
    function process(&$values, $manager) {
        
        $v = new Validator($values, false);
        
        // install
        if(!$manager->isUpgrade()) {
            $ret = $manager->createDB($db_name, 'utf8', 'utf8_general_ci');
            if($ret !== true) {
                $v->setError($ret, '', '', 'formatted');
                return $v->getErrors();
            }
                
        }

    }    
    
}
?>