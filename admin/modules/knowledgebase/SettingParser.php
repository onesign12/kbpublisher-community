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

namespace Knowledgebase;

use SettingParserCommon;


class SettingParser extends SettingParserCommon
{            
    
    function parseOut($key, $value) {
        
        // to num
        $to_num = array('toc_character_limit', 'toc_tag_limit', 'preview_article_limit');
        if(in_array($key, $to_num)) {
            $value = intval($value);
        }
        
        if($key == 'toc_tags') {
            $value = implode(',', array_unique(array_map('strtolower', array_map('trim', explode(',', $value)))));
        }
        
        return $value;
    }
        
    
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(
            16=>'4.0', 9=>'4.1', 10=>'4.6', 11=>'4.7', 12=>'4.8', 
            14=>'4.4', 14=>'4.9', 15=>'5.3', 13=>'5.4', 5=>'5.6');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        // remove "allowed for all" and "with priv only" for comment subscription
        if($key == 'allow_subscribe_comment') {
            unset($values['option_2']);
            unset($values['option_4']);
        }
        
        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }
        
    
    function parseDescription($key, $value) {
        if($key == 'show_author_format') {
            $tags = '[first_name], [last_name], [middle_name], 
            [short_first_name], [short_last_name], [short_middle_name], 
            [username], [email], [phone], [id], [company]';
            $value = str_replace('{tags}', $tags, $value);        
        
        } elseif($key == 'comments_author_format') {
            $tags = '[first_name], [last_name], [middle_name], 
            [short_first_name], [short_last_name], [short_middle_name],
            [username], [email], [phone], [user_id]';
            $value = str_replace('{tags}', $tags, $value);        
        }
        
        return $value;
    }
    
    
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {

        if ($setting_key == 'article_action_block_position') {
            $js = '<script>
                $(document).ready(function() {
                    toggleFloatPanel();
                });
                
                function toggleFloatPanel() {
                    var value = $(\'#article_action_block_position\').val();
                    
                    if (value != \'info\') {
                        $(\'#template_float_panel\').show();
                        
                    } else {
                        $(\'#template_float_panel\').hide();
                    }
                }
            </script>';
            
            return $field . $js;
        }

        return false;
    }

}
?>