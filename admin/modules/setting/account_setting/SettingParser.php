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

namespace AccountSetting;

use SettingParserCommon;
use AppMsg;


class SettingParser extends SettingParserCommon
{

    function getSettingMsg($module_name) {
        $admin_setting_msg = AppMsg::getMsg('setting_msg.ini', 'admin_setting', 0, 1, 1);
        
        $msg = array();
        $msg['group_title'] = array(
            1 => $admin_setting_msg['group_title']
        );
        
        unset($admin_setting_msg['group_title']);
        
        return array_merge($msg, $admin_setting_msg);
    }
    
    
    function parseIn($key, $value, &$values = array()) {
            
        if($key == 'entry_autosave') {
            $value = (int) $value;
            
        } elseif(strpos($key, 'notification_') === 0) {
            $_value = (!empty($value[0])) ? 1 : 0;
            if (!empty($value[1])) {
                $_value = ($_value) ? 3 : 2;
            }
            
            $value = $_value;
        }
        
        return $value;
    }
    
    
    function parseSelectOptions($key, $values, $range = array()) {
            
        if($key == 'article_default_category') {
            $values = $this->getDefaultCategorySelectRange($values, 'article', true);
            
        } elseif($key == 'file_default_category') {
            $values = $this->getDefaultCategorySelectRange($values, 'file', true);
        }
        
        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }
}
?>