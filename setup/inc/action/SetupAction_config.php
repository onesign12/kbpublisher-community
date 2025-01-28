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


class SetupAction_config extends SetupAction
{

    function &execute($controller, $manager, $_data = false) {
        
        $view = $controller->getView();
        
        
        $config_file = APP_ADMIN_DIR . 'config.inc.php';
        $config_tmpl = APP_ADMIN_DIR . 'config.inc.tmpl.php';
        
        $data = ($_data) ? $_data : $manager->getSetupData();
        
        // default values, avoid error in config with empty value (just in case)
        $data['ssl_admin'] = (isset($data['ssl_admin'])) ? $data['ssl_admin'] : 0;
        $data['ssl_client'] = (isset($data['ssl_client'])) ? $data['ssl_client'] : 0;
        
        // dirs
        $short_dir = array('client_home_dir', 'admin_home_dir');
        foreach($short_dir as $k => $v) {
            $data[$v] = preg_replace('#' . preg_quote($data['document_root']) . '#i', '', $data[$v]);
            $data[$v] = str_replace('//', '/', '/' . $data[$v] . '/');
        }
        
        $data['cache_dir'] = str_replace('//', '/', $data['cache_dir'] . '/');
        

        // hidden values 
        $vars = $this->getDefaultValues();
        $vars['session_name'] = md5($data['http_host'] . $manager->generatePassword(3, 2));
        if($manager->isUpgradeWithConfig()) {
            $vars = $this->getUpgradeValues($manager, $vars);
        }
        
        $data = $data + $vars;


        // generate file        
        $file = new tplTemplatez($config_tmpl);
        $file->clean_html = false;
        $file->strip_vars = true;
        $file->tplParse($data);
        $config_content = $file->tplPrint(1);
        $config_content = trim($config_content);
        
        // save
        // $ret = FileUtil::write($config_file, $config_content, true);
        // if($ret) {
        //     if (!$_data) {
        //         $controller->go($controller->getNextStep());
        //
        //     } else {
        //         return $config_content;
        //     }
        // }
        
        // save,  July 13, 2021
        if (!$_data) {
            $ret = FileUtil::write($config_file, $config_content, true);
            if($ret) {
                if (!$_data) {
                    $controller->go($controller->getNextStep());
                
                } else {
                    return $config_content;
                }   
            }
            
        // in cloud 
        } else {
            return true;
        }
        
        // download
        if(!empty($this->rq->file)) {
            $this->sendFileDownload($config_content);
            exit;
        }
        
        // check config if manually updated 
        if(isset($this->rp->setup)) {
            
            $errors = $this->validate($config_content, $manager, $config_file);
        
            if($errors && !$manager->getSetupData('config_file_check')) {
                // $manager->setSetupData(array('config_file_check' => 1));
                $this->rp->stripVars(true);
                $view->setErrors($errors);
            
            } else {
                $controller->go($controller->getNextStep());
            }
        }
        
        $view->config_content = $config_content;
        $view->msg_vars['file'] = $config_file;        
        
        return $view;
    }
    
    
    function validate($config_content, $manager, $config_file) {
        
        $v = new Validator($values, true);
                
        if(file_exists($config_file)) {
            include $config_file;
            
            preg_match("#allow_setup'\]\s*=\s*(\d)#", $config_content, $match);
            $allow_setup = (isset($match[1])) ? $match[1] : 'qqq';
            //echo '<pre>', print_r($allow_setup, 1), '</pre>';
            //echo '<pre>', print_r($conf['allow_setup'], 1), '</pre>';
        
            preg_match("#db_user'\]\s*=\s*(?:\"|')(.*?)(?:\"|')#", $config_content, $match);
            $db_user = (isset($match[1])) ? $match[1] : 'eee';
            //echo '<pre>', print_r($db_user, 1), '</pre>';
            //echo '<pre>', print_r($conf['db_user'], 1), '</pre>';
            
            if(($conf['allow_setup'] != $allow_setup) || ($conf['db_user'] != $db_user)) {
                $tags = array('file' => $config_file);
                $msg = AppMsg::getMsgs('error_msg.ini', 'setup', 'config_file_notice', 1);
                $msg = BoxMsg::factory('error', $msg, $tags);
                $v->setError($msg, '', '', 'formatted');
            }
        }        
        
        return $v->getErrors();        
    }
    
    
    function sendFileDownload($data) {
                
        $params['data'] = $data;
        $params['gzip'] = false;
        $params['contenttype'] = 'application/x-httpd-php'; //text/plain
        $filename = 'config.inc.php';
        
        return WebUtil::sendFile($params, $filename);
    }
    
    
    function getDefaultValues() {
        
        $vars = array(
            'session_name'       => 1, // will be set after this called
            'auth_check_ip'      => 1,
            'auth_remote'        => 1, // changed to 1, 2017-12-09 settings moved to db
            'use_ob_gzhandler'   => 0,
            'timezone'           => '',
            'db_names'           => ''
        );
        
        return $vars;
    }


    function getUpgradeValues($manager, $default_vars) {
                        
        $file = $manager->getSetupData('old_config_file');
        include $file;
        
        $vars = array();
        foreach($default_vars as $k => $v) {
            $vars[$k] = (isset($conf[$k])) ? $conf[$k] : $v;
        }
        
        return $vars;
    }
}

// unset($_SESSION['setup_']['old_config_file']);
?>