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


class SetupView_setup extends SetupView
{

    function &execute($manager) {
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'setup.html');
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        if($this->errors) {
            foreach($this->errors['key'] as $k => $v) {
                $tpl->tplAssign($v['field'] . '_class', 'errorField');
            }
        }
    
        $setup_type = $this->getFormDataByKey('setup_type');
        if(!$setup_type) {
            $setup_type = $manager->getSetupData('setup_type');
        }
        
        $setup_ch = ($setup_type) ? 'ch_' . $setup_type : 'ch_install';
        $tpl->tplAssign($setup_ch, $this->getChecked(1));
        
        // disable upgrade option for 1.0 release
        if(file_exists(APP_PLUGIN_DIR . 'community.php')) {
            $tpl->tplAssign('ch_upgrade', $this->getDisabled(1));
        }
        
        $display = (!$setup_type || $setup_type == 'install') ? 'none' : 'block';
        $tpl->tplAssign('op_display', $display);
        
        
        // old config
        //$tpl->tplAssign('config_display', 'block');
        //if($setup_type == 'upgrade') {
            $setup_upgrade = ($_POST) ? $this->getFormDataByKey('setup_upgrade') 
                                      : $manager->getSetupData('setup_upgrade');
            $display = (strpos($setup_upgrade, '20_') !== false) ? 'none' : 'block';
            $tpl->tplAssign('config_display', $display);


            $skip_config = ($_POST) ? $this->getFormDataByKey('old_config_file_skip') 
                                    : $manager->getSetupData('old_config_file_skip');
            $tpl->tplAssign('ch_skip_config', $this->getChecked($skip_config));
        //}        
        
        // select range, and valid versions to validate user value
        $range = array();
        $range['75_to_80']  = 'v7.5 - 7.5.4 %s v8.0';
        $range['70_to_80']  = 'v7.0 - 7.0.2 %s v8.0';
        $range['60_to_80']  = 'v6.0 - 6.0.2 %s v8.0';
        $range['55_to_80']  = 'v5.5 - 5.5.1 %s v8.0';
        $range['50_to_80']  = 'v5.0 - 5.0.2 %s v8.0';
        $range['45_to_80']  = 'v4.5 - 4.5.3 %s v8.0';
        $range['402_to_80'] = 'v4.0.2       %s v8.0';
        $range['352_to_80'] = 'v3.5.2       %s v8.0';
        $range['301_to_80'] = 'v3.0.1       %s v8.0';
        $range['20_to_80']  = 'v2.0         %s v8.0';
        
        $delim_to = '&nbsp;&#x2192;&nbsp;';
        foreach($range as $k => $v) {
            $range[$k] = sprintf($v, $delim_to);
        }
        
        
        $select = new FormSelect();
        $select->select_tag = false;
        $select->strip_values = false;
        $select->css_class = 'setupUpgradeSelect';
        
        $select->setFormMethod($_POST);
        $select->setSelectName('setup_upgrade');
        $select->setRange($range);
        
        $selected = ($su = $manager->getSetupData('setup_upgrade')) ? $su : '75_to_80';
        $tpl->tplAssign('setup_upgrade_select', $select->select($selected));
                
        
        $tpl->tplAssign($this->getDefaultsValues());
        $tpl->tplAssign($manager->getSetupData());
        $tpl->tplAssign($this->getFormData());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getDefaultsValues() {
        
        //$dir = getcwd();
        $dir = APP_INSTALL_DIR;
        $dir = explode('/', $dir);
        $dir = array_slice($dir, 0, count($dir) - 2);
        $dir = implode('/', $dir) . '/kb_old/admin/config.inc.php';
        
        $vars['old_config_file'] = $dir;
        
        return $vars;
    }
}

// unset($_SESSION['setup_']['old_config_file']);
// unset($_SESSION['setup_']);
?>