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

class Setting extends AppObj
{
     
    var $properties = array();
    var $hidden = array();
    
    
    function set($data, $value = false, $strip_vars = 'none') {
        foreach($data as $k => $v) {
            $this->properties[$k] = $v;
        }
    }
    

    function validate($values, $manager) {
        if($manager->wizard_group_id) {
            return $this->_validateGroup($values, $manager);
        } else {
            return $this->_validate($values, $manager, $manager->module_name);
        }
    }

    
    // values should have module id. [134] => Array([smtp_port] => 25, [mailer] => smtp ...);
    // used in settings wizard
    function _validateGroup($values, $manager) {
        $module_names = array();
        foreach($values as $module_id => $v) {
            if(!isset($module_names[$module_id])) {
                $module_names[$module_id] = $manager->getModuleName($module_id);
            }
        }
            
        foreach($module_names as $module_id => $module_name) {
            $ret = $this->_validate($values[$module_id], $manager, $module_name);
            if ($ret) {
                return $ret;
            }
        }
    }


    function _validate($values, $manager, $module_name) {
        $is_file = false;
        $file[] = APP_MODULE_DIR . $module_name . '/SettingValidator.php';
        $file[] = APP_PLUGIN_DIR . $module_name . '/SettingValidator.php';
        $file[] = APP_MODULE_DIR . 'setting/' . $module_name . '/SettingValidator.php';
        foreach($file as $v) {
            if(file_exists($v)) {
                require_once $v;
                $is_file = true;
                break;
            }
        }
        
        if(!$is_file) {
            return false;
        }
        
        $class = str_replace('_', '', $module_name) . '\SettingValidator';
        $v = new $class();
        $this->errors = $v->validate($values);
                
        if($this->errors) {
            return true;
        }
    }
	
	
	function prepareValues($values, $manager) {
        $parser = &$manager->getParser();
        $values_obj = $manager->formDataToObj($values);
        
        $values = $parser->parseReplacementsArray($values_obj);
        $values = $parser->parseInArray($values);
        $values = $parser->parseInArrayCloud($values);
	
		return $values;
	}
}
?>