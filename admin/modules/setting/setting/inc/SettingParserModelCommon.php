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

 
class SettingParserModelCommon extends AppModel
{
    var $smodel;
	
	
	function getSettingModelObj() {
		if(empty($this->smodel)) {
			$this->smodel = new SettingModel();
		}
	
		return $this->smodel;
	}
	
	
    function getSettings($module_id, $setting_key = false, $ignore_parser = true) {
		$m = $this->getSettingModelObj();
		return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }

    
    function setSettings($data) {
		$m = $this->getSettingModelObj();
        return $m->setSettings($data);
    }
	
	
	function getSettingIdByKey($key) {
		$m = $this->getSettingModelObj();
        return $m->getSettingIdByKey($key);
	}
    
    
    function resetAuthSetting($values) {
        
        $auth_setting = array(
            'remote_auth' => 232, // ldap
            'remote_auth_script' => 233,
            'saml_auth' => 347
        );
        
        // in case if we need to allow SAML + any remote 
        // currently aonly ONE remote auth is allowed. this may help 
        // 1 = Both built-in and SAML Authentication allowed
        // $saml_mode = 0; //$this->getSettingIdByKey('saml_mode');
        // $current = key(array_intersect_key($auth_setting, $values));
        
        $data = array();
        foreach($auth_setting as $k => $id) {
            $data[$id] = 0;
            
            if(!empty($values[$k])) {
                $reset = true;
                unset($data[$id]);
            }
        }
        
        if(isset($reset)) {
            $this->setSettings($data);
        }
    }
    
    
    // if we need to call someting on settings save
    function callOnSave($values, $old_values) {
        return true;
    }
}
?>