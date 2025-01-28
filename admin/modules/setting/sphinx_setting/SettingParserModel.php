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

namespace SphinxSetting;

use SettingParserModelCommon;
use SphinxIndexModel;



 
class SettingParserModel extends SettingParserModelCommon
{
    
    var $tables = array('entry_task');
     
    
    function callOnSave($values, $old_values) {
		
        $save = !empty($_POST['submit']); // save button
        $restart = !empty($_POST['submit1']); // save button
        $old_dir = $old_values['sphinx_data_path'];
        		
        if (!empty($values['sphinx_enabled'])) {
			
            $values['sphinx_lang'] = implode(',', $values['sphinx_lang']);
            if (is_array($old_values['sphinx_lang'])) {
                $old_values['sphinx_lang'] = implode(',', $old_values['sphinx_lang']);
            }
            
            $diff = array_diff_assoc($values, $old_values);
            
            unset($diff['sphinx_test_mode']);
            unset($diff['sphinx_host']);
            
            if ($restart || !empty($diff)) { // something changed
                $setting_id = $this->getSettingIdByKey('sphinx_enabled');
                $this->setSettings(array($setting_id => 2));
                
				$sphinx_model = new SphinxIndexModel();
				$sphinx_model->setSphinxRestartTasks($old_dir);
            }
        
		
		// on -> off 
        } elseif ($old_values['sphinx_enabled'] == 1) { // on -> off
				
			$sphinx_model = new SphinxIndexModel();
            $sphinx_model->setSphinxStopTasks($old_dir);
        }
    }
    
    
    function getSphinxTask($key, $active_only = false, $no_error = false) {
        
        $rule_id = array_search('sphinx_' . $key, $this->entry_task_rules);
        
        $sql = "SELECT * FROM {$this->tbl->entry_task} WHERE rule_id = %d %s %s";
        $active_param = ($active_only) ? 'AND active = 1' : '';
        $error_param = ($no_error) ? 'AND failed_message = ""' : '';
        $sql = sprintf($sql, $rule_id, $active_param, $error_param);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
}
?>