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


namespace PublicSetting;

use SettingParserCommon;
use BaseModel;
use tplTemplatez;
use Registry;
use SettingData;


class SettingParser extends SettingParserCommon
{

    function parseIn($key, $value, &$values = array()) {
        $page_to_load_keys = array('page_to_load', 'page_to_load_mobile');

        if($key == 'contact_attachment_ext') {
            $value = str_replace(' ', '', $value);
            $value = str_replace(array(';','.'), ',', $value);

        } elseif(in_array($key, $page_to_load_keys) && empty($value)) {
            $value = 'Default';        
        }
        
        // header = 1 in intranet view 
        if($values['view_format'] == 'fixed' && empty($values['view_header'])) {
            $values['view_header'] = 1;
        }
        
        return $value;
    }


    function parseOut($key, $value) {
        
        if($key == 'subscribe_entry_day') {
            $value = (!in_array($value, range(1,31))) ? 1 : $value;
        }
        
        return $value;
    }
    
    
    function parseExtraOut($key, $value, $manager, $view, $id) {
        $extra['text'] = '';
        
        // if ($key == 'nav_extra') {
        //     $rules_count = 1;
        //     if ($value) {
        //         $rules_count += count(explode('||', trim($value)));
        //     }
        //
        //     // $extra['text'] = sprintf('%s: <span id="%s_count">%s</span>', $view->msg['items_added_msg'], $id, $rules_count);
        //     // $extra['text'] = ($rules_count) ? sprintf('[%d]', $rules_count) : '2';
        // }
        
        // if ($key == 'menu_main') {
        //     $rules_count = 0;
        //     if ($value) {
        //         $items = unserialize(html_entity_decode($value));
        //         $items = SettingData::getMainMenu($items);
        //         // $rules_count = count($items['active']) + count($items['inactive']);
        //         $rules_count = count($items['active']);
        //     }
        //
        //     $extra['text'] = sprintf('%s: <span id="%s_count">%s</span>', $view->msg['items_added_msg'], $id, $rules_count);
        // }
        
        if ($key == 'page_design') {
            $link = $view->getLink('setting', 'public_setting', 'page_design');
            $extra['click_handler'] = sprintf("PopupManager.create('%s', 'r', 'r', 1, '90%%', '600');", $link);
        }
        
        return $extra;
    }


    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        if($key == 'view_format') {
            $options = array('option_1', 'option_2');
            $values = array_diff_key($values, $options);

        } elseif($key == 'register_user_priv') {
            $options = $this->manager->getPrivSelectRange();
            $options[0] = $values['option_1'];
            unset($values['option_1']);
            unset($options[1]); // unset admin
            ksort($options);
            $values = &$options;

        } elseif($key == 'register_user_role') {
            $options = $this->manager->getRoleSelectRange();
            $i = 1;
            foreach($options as $k => $v) {
                if($i == 1) {
                    $values[0] = $values['option_1'];
                    unset($values['option_1']);
                }

                $values[$k] = $v;
                unset($options[$k]);
                $i++;
            }

        // remove "allowed for all" for subscription
        // to enable comment it and add value to the table 1,2,3,4
        } elseif($key == 'allow_subscribe_news'
              || $key == 'allow_subscribe_entry') {
            unset($values['option_2']);

        // remove "allowed for all" and "with priv only" for comment subscription
        } elseif($key == 'allow_subscribe_comment') {
            unset($values['option_2']);
            unset($values['option_4']);

        } elseif($key == 'subscribe_news_time' || $key == 'subscribe_entry_time') {

            $reg =& Registry::instance();
            $conf = $reg->getEntry('conf');
            $time_format = $conf['lang']['time_format'];

            foreach(range(0, 23) as $v) {
                $ts = mktime($v, 0, 0, 1, 1, 2014);
                $hour = sprintf('%02d', $v);
                $hours[$hour] = _strftime($time_format, $ts);
            }
            $values = $hours;
        
        } elseif($key == 'subscribe_entry_weekday') {
            
            foreach(range(1, 7) as $v) {
                $ts = mktime(1, 0, 0, 1, 2+$v, 2022);
                $weekday = date('N', $ts);
                $weekdays[$weekday] = _strftime('%A', $ts);
            }
            $values = $weekdays;
            
        } elseif($key == 'container_width') {
            foreach(array_keys($values) as $key) {
                $values[$key] = ($key == 1) ? '100%' : $key . 'px';
            }
        }

        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }


    // when display
    function skipSettingDisplay($key, $value = false) {
        $ret = false;

        $keys = array(
            'search_spell_pspell_dic',
            'search_spell_custom',
            'search_spell_bing_spell_check_key',
            'search_spell_bing_spell_check_url',
            'search_spell_bing_autosuggest_key',
            'search_spell_bing_autosuggest_url',
            'search_spell_enchant_provider',
            'search_spell_enchant_dic'
        );

        if(in_array($key, $keys)) {
            $ret = true;
        }
        
        return $ret;
    }


    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(
            8=>'2.2', 9=>'4.1', 10=>'4.6', 11=>'2.4', 12=>'4.8',
            14=>'4.4', 15=>'5.0', 16=>'4.6', 17=>'2.3', 18=>'4.5',19=>'2.1');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }


    // any special rule to parse form field
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {

        $setting_keys = array('view_template');
        if(in_array($setting_key, $setting_keys)) {

            if($val == 'check') {
                return true;
            }

            return SettingParseForm::$setting_key($this, $val, $field, $setting_msg);
        
        } elseif ($setting_key == 'captcha_type') {

            $js = "<script>
                $(document).ready(function() {
                    toggleCaptchaSettings($('#captcha_type').val(), 1);
                });
            
                function toggleCaptchaSettings(val, display) {
                    // show = (val == 2);
                    // console.log(val);
                    show = ($.inArray(parseInt(val), [2,3,4]) != -1) ? true : false;
                    var elements = $('#template_recaptcha_site_key').add('#template_recaptcha_site_secret');
                    toggleSettingsByElements(elements, show, display);
                }
            </script>";
        
            return $field . $js;
            
        }

        return false;
    }

}



class SettingParseForm
{

    static function view_template($obj, $values, $field, $setting_msg) {

        $format_values['1'] = 'default';
        $format_values['2'] = 'left';
        $format_values['3'] = 'fixed';

        $options_range = array('template', 'menu_type');


        $options_values['template']['1.1'] = 'default';
        // $options_values['template']['1.2'] = 'blocks';

        $options_values['template']['2.1'] = 'default';
        // $options_values['template']['2.2'] = 'full';

        $options_values['template']['3.1'] = 'default';

        $options_values['menu_type']['2.1'] = 'tree';
        $options_values['menu_type']['2.2'] = 'top_tree';

        $options_values['menu_type']['2.3'] = 'tree_55';
        $options_values['menu_type']['2.4'] = 'top_tree_55';
        $options_values['menu_type']['2.5'] = 'followon';
        
        $options_values['menu_type']['2.9'] = 'hide';

        // prev values for left view 2016-10-12 eleontev
        // $options_values['menu_type']['2.1'] = 'tree';
        // $options_values['menu_type']['2.2'] = 'followon';
        // $options_values['menu_type']['2.3'] = 'top_tree';

        $options_values['menu_type']['3.1'] = 'tree';
        $options_values['menu_type']['3.2'] = 'top_tree';


        foreach ($options_range as $option) {
            $json = array();

            $options_text = $setting_msg['view_' . $option];
            unset($options_text['title'], $options_text['descr']);
			
            $selected = $obj->manager->getSettings(2, 'view_' . $option);

            foreach($format_values as $format_key => $format_value) {
                $json_body = array();

                foreach($options_values[$option] as $k => $v) {
                    if($k[0] == $format_key) {

                        $val  = $options_values[$option][$k];
                        $text = $options_text['option_' . $k];

                        $s    = ($v == $selected) ? 'true' : 'false';
                        $json_body[] = sprintf('{"val": "%s", "text": "%s", "s": %s}', $val, $text, $s);
                    }
                }

                $json[] = sprintf("\"%s\": [\n%s\n]", $format_value, implode(",\n", $json_body));
            }

            $json2[] = sprintf("\"%s\": {\n%s\n}", $option, implode(",\n", $json));
        }

        $json = implode(",\n", $json2);

        // echo "<pre>"; print_r($json); echo "</pre>";;
        //echo "<pre>"; print_r($setting_msg); echo "</pre>";

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'setting/setting/template/form_view_template.js');

        $tpl->tplAssign('myOptionsJson', $json );
        $tpl->tplAssign('field', $field);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>