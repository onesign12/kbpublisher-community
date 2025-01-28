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


class SetupView_initial extends SetupView
{

    function &execute($manager) {
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'initial.html');
        $tpl->tplAssign('user_msg', $this->getErrors());
    
        if($this->errors) {
            foreach($this->errors['key'] as $k => $v) {
                $var = $v['field'] . '_error_class';
                $tpl->tplAssign($var, 'errorField');
            }
        }

        $vars = $this->getDefaultsValues();
        if($manager->isUpgradeWithConfig()) {
            $vars = $this->getUpgradeValues($manager, $vars);
            //$tpl->tplAssign('phrase_msg', $this->getPhraseMsg('upgrade_values'));
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($manager->getSetupData());
        $tpl->tplAssign($this->getFormData());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getDefaultsValues() {
        
        $vars = array();
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        $doc_root = str_replace('\\', '/', $doc_root);
        $doc_root = preg_replace("#/$#", '', $doc_root);
        $current_dir = str_replace(array('\\'), '/', getcwd());
        
        $vars['http_host'] = $_SERVER['HTTP_HOST'];
        $vars['document_root'] = $doc_root;
        
        $vars['client_home_dir'] = str_replace('/setup', '', $current_dir);
        $vars['admin_home_dir'] =  str_replace('setup', '', $current_dir) . 'admin';
        
        $vars['html_editor_upload_dir'] = $doc_root . '/kb_upload';
        $vars['file_dir'] = preg_replace('#/[\w\.-]+$#', '/kb_file', $doc_root);
        $vars['cache_dir'] = preg_replace('#/[\w\.-]+$#', '/kb_cache', $doc_root);
        
        foreach($vars as $k => $v) {
            $vars[$k] = str_replace('//', '/', $vars[$k]);
        }
        
        // echo "<pre>"; print_r($vars); echo "</pre>";
        return $vars;
    }
    
    
    function getUpgradeValues($manager, $default_vars) {
        
        $vars = $default_vars;
        $file = $manager->getSetupData('old_config_file');
        include $file;
        
        $conn = $manager->connect($conf);
        if($conn === true) {
            $manager->setTables($conf['tbl_pref']);
            $v = $manager->getSetting('html_editor_upload_dir');
            if(empty($v['error'])) {
                $vars['html_editor_upload_dir'] = $v['val'];
            }
            
            $v = $manager->getSetting('file_dir');
            if(empty($v['error'])) {
                $vars['file_dir'] = $v['val'];
            }            
        }
        
        //echo '<pre>', print_r($vars, 1), '</pre>';
        $doc_root = (isset($conf['document_root'])) ? $conf['document_root'] 
                                                    : $default_vars['document_root'];
        
        if(!empty($conf['http_host'])) {
            $vars['http_host'] = $conf['http_host'];
        }
    
        $vars['document_root'] = $doc_root;
        
        $vars['client_home_dir'] = $doc_root . $conf['client_home_dir'];
        $vars['admin_home_dir'] =  $doc_root . $conf['admin_home_dir'];
        
        $vars['cache_dir'] = $conf['cache_dir'];
        
        $vars['ssl_client_ch'] = $this->getChecked($conf['ssl_client']);
        $vars['ssl_admin_ch'] = $this->getChecked($conf['ssl_admin']);
                
        // echo '<pre>', print_r($vars, 1), '</pre>';
        return $vars;
    }
}
?>