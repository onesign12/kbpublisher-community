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


class SetupAction_upgrade extends SetupAction_install
{

    function &execute($controller, $manager) {

        $view = $controller->getView('upgrade');

        if(isset($this->rp->setup)) {
            
            $data = $manager->getSetupData();
            
            // dryrun
            $errors = false;
            $skip = ($controller->getRequestVar('skip') || !empty($this->rp->skip));
            if(!$skip) {
                $dryrun = true;
                $errors = $this->processDryRun($data, $manager);
            }
            
            // upgrade
            if(!$errors) {
                $dryrun = false;
                $errors = $this->process($data, $manager);
            }
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->dryrun = $dryrun;
            
            } else {                
                $controller->go($controller->getNextStep());
            }
        }
        
        return $view;
    }
        
    
    function process(&$values, $manager, $_key = false) {
        
        $v = new Validator($values, false);
                
        $values = $this->parseDirectoryValues($values);    
        $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
        
        $key = ($_key) ? $_key : $manager->getSetupData('setup_upgrade');        
        $manager = SetupModelUpgrade::factory($key);
        
        ParseSqlFile::$dryrun = false;
        $ret = $manager->execute($values);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
    }


    function processDryRun($values, $manager, $_key = false) {
        
        $v = new Validator($values, false);
                
        $values = $this->parseDirectoryValues($values);    
        $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
        
        $prefix_to_copy = 'kbpdryrun_';
        
        $ret = $manager->connect($values);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        $ret = $manager->isUserHasPriv('CREATE TEMPORARY TABLES');
        if(!empty($ret['error'])) {
            $v->setError($ret['error'], '', '', 'formatted');
            return $v->getErrors();
        }
        
        if(!$ret) { // no priv 'CREATE TEMPORARY TABLES' exit from dry run
            return false;
        }
        
        
        $tables = $manager->getTables($values['tbl_pref']);
        if(!empty($tables['error'])) {
            $v->setError($tables['error'], '', '', 'formatted');
            return $v->getErrors();
        }
        
        $new_tables = array();
        foreach($tables as $key => $table) {
            $new_tables[$key] = $prefix_to_copy . $table;
        }
        
        $ret = $manager->dropTempTables($new_tables);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        foreach($tables as $key => $table) {
            $with_data = ($table == $values['tbl_pref'] . 'setting') ? true : false;
            $ret = $manager->copyTable($table, $new_tables[$key], $with_data);
            if($ret !== true) {
                $v->setError($ret, '', '', 'formatted');
                return $v->getErrors();
            }
        }        
        
        $key = ($_key) ? $_key : $manager->getSetupData('setup_upgrade');
        $manager = SetupModelUpgrade::factory($key);
        $manager->dryrun = true;
        
        $ret = $manager->connect($values);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }

        ParseSqlFile::$dryrun = true;
        $values['tbl_pref'] = $prefix_to_copy . $values['tbl_pref'];
        $ret = $manager->execute($values);
        
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
    }
    
}
?>