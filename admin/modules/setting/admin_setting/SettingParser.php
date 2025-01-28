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

namespace AdminSetting;

use SettingParserCommon;
use CompareLang;
use DateTimeZone;
use DateTime;
use AppController;
use KBEntryModel;
use FileEntryModel;
use WebUtil;
use ListValueModel;
use AppMsg;
use IntlDateFormatter;


class SettingParser extends SettingParserCommon
{
    
    function parseIn($key, $value, &$values = array()) {
        
        $dirs = array('html_editor_upload_dir', 'cache_dir', 'file_dir');
        $tools = array('file_extract_pdf', 'file_extract_doc', 'file_extract_doc2');
        
        if(in_array($key, $dirs) && !empty($value)) {
            $value = $this->parseDirectoryValue($value);
        
        } elseif(in_array($key, $tools)) {
            if(!empty($value) && strtolower($value) != 'off') {
                $value = $this->parseDirectoryValue($value);
            }
        
        } elseif(in_array($key, array('file_denied_extensions', 'file_allowed_extensions'))) {
            $value = str_replace(' ', '', $value);
            $value = str_replace(array(';','.'), ',', $value);
                
        // set max size no more than upload_max_filesize
        } elseif($key == 'file_max_filesize') {
        
            // upload_max_filesize "2M" PHP_INI_SYSTEM|PHP_INI_PERDIR 
            $size = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
            if(strtolower($value) == 'system') {
                $value = $size;
            }
            
            $value = ($value > $size) ? $size : $value;
            $value = (int) $value;
            
        } elseif(in_array($key, array('entry_autosave', 'auth_expired'))) {
            $value = (int) $value;
        
        } elseif($key == 'entry_history_max') {
            $value = strtolower($value);
            $value = ($value == 'all') ? $value : (int) $value;
            
        } elseif(strpos($key, 'notification_') === 0) {
            $_value = (!empty($value[0])) ? 1 : 0;
            if (!empty($value[1])) {
                $_value = ($_value) ? 3 : 2;
            }
            
            $value = $_value;
        
        } elseif($key == 'aws_secret_key' && $value) {
            // $value = \EncryptedPassword::encode($value);
        
        }  elseif($key == 'lang') {
            require_once APP_MSG_DIR . 'CompareLang.php';
            $valid_values = CompareLang::getLangSelectRange(APP_MSG_DIR);            
            $value = (isset($valid_values[$value])) ? $value : 'en';
        }
        
        return $value;
    }
    
    
    function parseOut($key, $value) {
    
        // hide in demo mode
        if(APP_DEMO_MODE && $key == 'file_dir') {
            $value = '- - - - - - - - - - -';
        
         // set max size nor more than upload_max_filesize
        } elseif($key == 'file_max_filesize') {

            // upload_max_filesize "2M" PHP_INI_SYSTEM|PHP_INI_PERDIR 
            $size = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
            if(strtolower($value) == 'system') {
                $value = $size;
            }

            $value = ($value > $size) ? $size : $value;
        
        } elseif($key == 'aws_secret_key' && $value) {
            // $value = \EncryptedPassword::decode($value);
        }
    
        return $value;
    }
            
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        if($key == 'directory_missed_file_policy') {
            
            $status =  ListValueModel::getListSelectRange('file_status', false, false);
            $file = AppMsg::getCommonMsgFile('common_msg.ini');
            $msg = AppMsg::parseMsgs($file, false, false);
            
            $options = array();
            $options['none'] = $values['option_1'];
            $options['delete'] = $values['option_2'];
            
            foreach($status as $k => $v) {
                $options['status_' . $k] = $msg['set_status2_msg'] . ': ' . $v;
            }

            $values = $options;
        
        // lang
        } elseif($key == 'lang') {
            require_once APP_MSG_DIR . 'CompareLang.php';
            $values = CompareLang::getLangSelectRange(APP_MSG_DIR);
            
        // timezone
        } elseif($key == 'timezone') {
            $values = SettingParser::getTimezones();

        } elseif($key == 'date_format') {
            $values = SettingParser::getDateFormatRange(APP_LANG);
            
        // } elseif($key == 'time_format') {
        //     $values = SettingParser::getTimeFormatRange(APP_LANG);
            
        } elseif($key == 'article_default_category') {
            $values = $this->getDefaultCategorySelectRange($values, 'article', false);
            
        } elseif($key == 'file_default_category') {
            $values = $this->getDefaultCategorySelectRange($values, 'file', false);
        }
        
        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }    
    
    
    function parseDescription($key, $value) {
        if($key == 'cron_mail_critical') {
            $email = $this->manager->getSettings('134', 'admin_email');
            $str = '<a href="%s">%s</a>';
            $str = sprintf($str, AppController::getRefLink('setting', 'email_setting'), $email);
            $value = str_replace('{email}', $str, $value);
        }
        
        return $value;
    }
    
    
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(12 => '1.1', 13 => '2.2', 14 => '8.1', 15 => '1.2', 18 => '11.1', 19 => '2.1');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    function parseForm($setting_key, $val, $field = false, $setting_msg = false) {
        
        $setting_keys = array('lang');
        
        if(in_array($setting_key, $setting_keys)) {
            
            $js = '<script>
                function populateDateFormatSelect(options) {
                    var opt = jQuery.parseJSON(options);
                    console.log(opt);
                    var $el = $("#date_format");
                    // var $selected = $el.val();
                    $el.empty(); // remove old options
                    $.each(opt, function(key,value) {
                      $el.append($("<option></option>")
                         .attr("value", key).text(value));
                    });
                    // $el.val($selected);
                }
                $(document).ready(function() {
                    $("#lang").change(function() {
                        console.log(this.value);
                        xajax_populateDateFormatSelect(this.value);
                    });
                });
            </script>';
            
            return $field . $js;
        }

        return false;
    }
    
    
    static function getTimezones() {
        
        $result = array();
        $timezones = array();
        
        $stimezone = ini_get('date.timezone') ?: 'UTC';
        $offset = timezone_offset_get(new DateTimeZone($stimezone), new DateTime());
        $offset_str = sprintf('%s%02d:%02d', ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600));
       
        $result['system'] = sprintf('System - (UTC %s) %s', $offset_str, $stimezone);
        
        $search = '~^(?:A(?:frica|merica|ntarctica|rctic|tlantic|sia|ustralia)|Europe|Indian|Pacific)/~';
        $tlist  = preg_grep($search, timezone_identifiers_list());

        // only process geographical timezones
        foreach ($tlist as $timezone) {
            $timezone = new DateTimeZone($timezone);
            $id = array();

            // get only the two most distant transitions
            foreach (array_slice($timezone->getTransitions($_SERVER['REQUEST_TIME']), -2) as $transition) {
                // dark magic
                $id[] = sprintf('%b|%+d|%u', $transition['isdst'], $transition['offset'], $transition['ts']);
            }

            // sort by %b (isdst = 0) first, so that we always get the raw offset
            sort($id, SORT_NUMERIC);

            $timezones[implode('|', $id)][] = $timezone->getName();
        }


        if (count($timezones) > 0) {
            uksort($timezones, function($a, $b) // sort offsets by -, 0, +
            {
                foreach (array('a', 'b') as $key) {
                    $$key = explode('|', $$key);
                }

                return intval($a[1]) - intval($b[1]);
            });

            foreach ($timezones as $key => $value) {
                $zone = reset($value); // first timezone ID is our internal timezone
                $result[$zone] = preg_replace(array('~^.*/([^/]+)$~', '~_~'), array('$1', ' '), $value); // "humanize" city names

                // "humanize" the offset 
                if (array_key_exists(1, $offset = explode('|', $key)) === true) {
                    $offset = str_replace(' +00:00', '', sprintf('(UTC %+03d:%02u)', $offset[1] / 3600, abs($offset[1]) % 3600 / 60));
                }

                 // sort city names
                if (asort($result[$zone]) === true) {
                    $result[$zone] = trim(sprintf('%s %s', $offset, implode(', ', $result[$zone])));
                }
            }
        }

        return $result;
    }
    
    
    static function getDateFormatRange($lang) {
        
        $fnone = IntlDateFormatter::NONE;
        $fshort = IntlDateFormatter::SHORT;
        $fmedium = IntlDateFormatter::MEDIUM;
        
        $range_ = [
            ['date' => $fshort,  'time' => $fnone, 'pattern' => null],
            ['date' => $fmedium, 'time' => $fnone, 'pattern' => null],
            ['date' => $fnone,   'time' => $fnone, 'pattern' => 'd MMM, y'],
            ['date' => $fnone,   'time' => $fnone, 'pattern' => 'd MMM y'],
        ];
        
        $range = [];
        $ts = mktime(12,30,15,1,1,date('Y'));
        foreach($range_ as $k => $v) {
            $fm = new IntlDateFormatter($lang, $v['date'], $v['time'], null, null, $v['pattern']);
            $key = $fm->getPattern();
            $range[$key] = $fm->format($ts);
        }
        
        return $range;
    }
    
    static function getTimeFormatRange($lang) {
        
        $fnone = IntlDateFormatter::NONE;
        $fshort = IntlDateFormatter::SHORT;
        $fmedium = IntlDateFormatter::MEDIUM;
        
        $range_ = [
            ['date' => $fnone, 'time' => $fmedium, 'pattern' => null],
            ['date' => $fnone, 'time' => $fnone,   'pattern' => 'h:mm:ss'],
            ['date' => $fnone, 'time' => $fnone,   'pattern' => 'hh.mm.ss'],
        ];
        
        $range = [];
        $ts = mktime(12,30,15,1,1,date('Y'));
        foreach($range_ as $k => $v) {
            $fm = new IntlDateFormatter($lang, $v['date'], $v['time'], null, null, $v['pattern']);
            $key = $fm->getPattern();
            $range[$key] = $fm->format($ts);
        }
        
        return $range;
    }
}
?>