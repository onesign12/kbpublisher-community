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


class SetupView_db extends SetupView
{

    function &execute($manager) {
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'db.html');
        $tpl->tplAssign('user_msg', $this->getErrors());    
    
        $tpl->tplAssign('num_1', 1);
        $tpl->tplAssign('num_2', 2);
        if(!$manager->getSetupData('setup_type') || $manager->getSetupData('setup_type') == 'install') {
            $tpl->tplSetNeededGlobal('install');
        }
        
    
        // driver
        $mysql_ext_range = array(
            'mysqli' => 'MySQL(mysqli)', 
            'mysql' => 'MySQL(mysql)'
            );
            
        foreach($mysql_ext_range as $k => $v) {
            if(!extension_loaded($k)) {
                unset($mysql_ext_range[$k]);
            }
        }
        
        $select = new FormSelect();
        $select->setSelectName('db_driver');
        $select->setRange($mysql_ext_range);
        $tpl->tplAssign('db_driver_select', $select->select());
    
    
        $vars = $this->getDefaultsValues();
        if($manager->isUpgradeWithConfig()) {
            $vars = $this->getUpgradeValues($manager, $vars);
            //$tpl->tplAssign('phrase_msg', $this->getPhraseMsg('upgrade_values'));
        }
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($manager->getSetupData());    
        $tpl->tplAssign($this->getFormData());
        $tpl->tplAssign($this->msg);
        
        // $tpl->tplAssign('phrase_msg', $this->getPhraseMsg('db'));        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getDefaultsValues() {
        
        $vars['db_host'] = 'localhost';
        $vars['tbl_pref'] = 'kbp_';
        
        return $vars;
    }
    
    
    function getUpgradeValues($manager, $default_vars) {
        
        $vars = $default_vars;
        $file = $manager->getSetupData('old_config_file');
        include $file;
    
        $f = array('db_host', 'db_base', 'db_user', 'db_pass', 'db_driver', 'tbl_pref');
        foreach($f as $v) {
            $vars[$v] = $conf[$v];
        }
        
        return $vars;
    }    
}
?>