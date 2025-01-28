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

namespace SAuthSetting;

use SettingParserCommon;
use BaseModel;
use tplTemplatez;

class SettingParser extends SettingParserCommon
{
    
    
    function parseMsgKey($key) {
        $keys = array('google_debug', 'facebook_debug', 'twitter_debug', 'vk_debug', 'yandex_debug');
        if(in_array($key, $keys)) {
            $key = 'social_debug';
        }
            
        return $key;
    }

    
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {
        
        $setting_keys = array('google_auth');
        
        if(in_array($setting_key, $setting_keys)) {
            if($val == 'check') {
                return true;
            }
            
            $js = '<script>
                $(document).ready(function() {
                    $(\'input[id$="auth"]\').each(function() {
                        toggleSocialSettings($(this).attr(\'id\'), $(this).prop(\'checked\'), 1);
                    });
                });
                
                function toggleSocialSettings(id, checked, display) {
                    var provider = id.slice(0, -5);
                    var elements = $(\'#template_\' + provider + \'_client_id\').add(
                        \'#template_\' + provider + \'_client_secret\').add(
                        \'#template_\' + provider + \'_debug\'
                    );

                    toggleSettingsByElements(elements, checked, display);
                }
            </script>';
            
            return $field . $js;
        }

        return false;
    }
}
?>