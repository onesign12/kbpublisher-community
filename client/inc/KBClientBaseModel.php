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


class KBClientBaseModel extends BaseModel
{
    
    // entry status
    var $entry_published_status = array();
    
    
    static function getSettings($module_id, $setting_key = false) {
        $s = new SettingModel();
        return $s->getSettings($module_id, $setting_key);
    }
    
    
    function getSetting($setting_key) {
        return @$this->setting[$setting_key];
    }
        
    
    function isModule($module) {
        return self::_isModule($module, $this->setting);
    }
    
    
    static function isModuleByView($view_id, $settings) {
        $module = $view_id;
        
        // plugins by views params
        $pluginable = AppPlugin::getPluginsFiltered('views', true);
        if(isset($pluginable[$view_id])) {
            $module = $pluginable[$view_id];
        
        // settings
        } else {
            $settingable = SettingData::getMenuFiltered('setting');
            $views = SettingData::getMenuFiltered('views', true);
            if(isset($views[$view_id]) && isset($settingable[$views[$view_id]])) {
                $module = $views[$view_id];
            }
        }
        
        return self::_isModule($module, $settings);
    }    
    
    
    static private function _isModule($module, $settings) {
        $ret = true;
        
        // main menu settings
        $settingable = array_flip(SettingData::getMenuFiltered('setting', true));
        if(isset($settingable[$module])) {
            if(empty($settings[$settingable[$module]])) {
                $ret = false;
            }
        }
        
        // plugins modules
        if($ret) {
            $pluginable = array_keys(AppPlugin::getModules(2));
            if(in_array($module, $pluginable)) {
                if(!AppPlugin::isPlugin($module)) {
                    $ret = false;
                }
            }
        }
        
        return $ret;
    }
    
    
    function getEntryPublishedStatusRaw($list_id) {
        $status = ListValueModel::getEntryPublishedStatuses($list_id);
        return $status[$list_id];
    }
    
    
    function getEntryPublishedStatus($list_id) {
        $status = $this->getEntryPublishedStatusRaw($list_id);
        return ($status) ? implode(',', $status) : '987654321';
    }    
    
    
    function setEntryPublishedStatus() {
        $this->entry_published_status = $this->getEntryPublishedStatus($this->entry_list_id);
    } 
}
?>