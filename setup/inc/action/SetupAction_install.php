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

class SetupAction_install extends SetupAction
{

    function &execute($controller, $manager) {
        
        $view = $controller->getView();

        if(isset($this->rp->setup)) {
            
            $data = $manager->getSetupData();
            if(empty($data['password'])) {
                $data['password'] = $manager->generatePassword(3, 4);
                $manager->setSetupData(array('password' => $data['password']));
            }            
            
            $errors = $this->process($data, $manager);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
            
            } else {
                $controller->go($controller->getNextStep());
            }
        }
        
        return $view;
    }
    
    
    function process(&$values, $manager) {
        
        $v = new Validator($values, false);
                
        $values = $this->parseDirectoryValues($values);
        $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);

        $ret = $manager->connect($values);
        $mysqlv = $manager->getMySQLVersion();
        
		// install
        $file = 'db/install.sql';
        $data = FileUtil::read($file);
        $manager->setSetupData(array('sql_file' => $file));
		
        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $values['tbl_pref']), $mysqlv);
        $ret = $manager->executeArray($sql_array);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
		// intro sql 
        $file = 'db/intro.sql';
        $data = FileUtil::read($file);
        $manager->setSetupData(array('sql_file' => $file));
        
		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $values['tbl_pref']), $mysqlv);
        
        if(!AppPlugin::isPlugin('news')) {
            foreach(array_keys($sql_array) as $k) {
                if(strpos($sql_array[$k], $values['tbl_pref'] . 'news') !== false) {
                    unset($sql_array[$k]);
                }
            }
        }
        
        $ret = $manager->executeArray($sql_array);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();            
        }
        
		// settings 
        $manager->setTables($values['tbl_pref']);

        $ret = $manager->setUser($values);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();            
        }
        
        $ret = $manager->setSupportEmail($values['email']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
                
        $ret = $manager->setAdminEmail($values['email']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        $ret = $manager->setFileDirectory($values['file_dir']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        $ret = $manager->setFckDirectory($values['html_editor_upload_dir']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }

        $ret = $manager->setLanguage($values['lang']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');        
        $ret = $manager->setVersion($conf['product_version']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        // default sql, article automation
        $ret = $manager->setDefaultSql($values, true);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }      
    }
        
    
    function parseDirectoryValues($data) {
        
        $short_dir = array('client_home_dir', 'admin_home_dir');
        $dir       = array('document_root', 'cache_dir', 'file_dir', 'html_editor_upload_dir');
        
        foreach($data as $k => $v) {
        
            if(!in_array($k, $short_dir) && !in_array($k, $dir)) {
                continue;
            }
            
            $data[$k] = str_replace('//', '/', $v . '/');
        }
        
        return $data;
    }
}
?>