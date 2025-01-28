<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class SpecialSearch
{
    
    static $search_rules = array(
        'id' => array(
            // 'search' => '#^entry_id:([\d,\s?]+)$#',
            'search' => '#^(?:id:|ids:)?(\d+(?:,\s?\d+)*)$#',
            'prompt' => 'id:{entry_id}[,{entry_id2},...]',
            'insert' => 'id:',
            'filter' => 'ids'),
            
        'tag' => array(
            'search' => '#^tags?:(.*?)$#',
            'prompt' => 'tag:{tag1}[,{tag2},...]'),       
             
        'author' => array(
            'search' => '#^author(?:_id)?:(\d+(?:,\s?\d+)*)$#',
            'prompt' => 'author:{user_id}[,{user_id2},...]',
            'filter' => 'ids'),
            
        'updater' => array(
            'search' => '#^updater(?:_id)?:(\d+(?:,\s?\d+)*)$#',
            'prompt' => 'updater:{user_id}[,{user_id2},...]',
            'filter' => 'ids'),
            
        'private' => array(
            // 'search' => '#^private(?:-all|-entry|-cat)?:(\w+)?$#',
            'search' => '#^private(?:-all|-entry|-cat)?:(read|write|unlisted|none)?$#',
            'prompt' => 'private[-all | -entry | -cat]:[read | write | unlisted | none]',
            'insert' => 'private:'),
            
        'mustread' => array(
            'search' => "#^mustread:(\s*-forced(\s+[yn])?)?$#",
            'prompt' => 'mustread:[-forced [{y|n}]]',
            'insert' => 'mustread:'),
            
        'scheduled' => array(
            'search' => '#^scheduled:(\w+)?$#',
            'prompt' => 'scheduled:'),
            
        'custom_id' => array(
            'search' => '#^custom_id:(\d+(?:,\s?\d+)*)$#',
            'prompt' => 'custom_id:{custom_field_id}[,{custom_field_id2},...]',
            'filter' => 'ids'),
            
        'user_id' => array(
            'search' => '#^user(?:_id)?:(\d+)$#',
            'prompt' => 'user_id:{user_id}'),
                            
        'username' => array(
            'search' => '#^username:(\w+)$#',
            'prompt' => 'username:{username}'),
            
        'ip' => array(
            'search' => '#^(?:ip:)([\-\.\d]+)$#',
            'prompt' => 'ip:{ip adress}'),
            
        'ip_range' => array(
            'search' => '#^(?:ip:)([\- \.\d]+)$#',
            'prompt' => 'ip_range:{ip adress}-{ip adress2}'),

        'fname' => array(
            'search' => '#^fname:(.*?)$#',
            'prompt' => 'fname:{filename}'),
        );
    
    
    static function getSpecialSearch() {
        
        $pluginable = AppPlugin::getPluginsFiltered('ssearch');
        foreach($pluginable as $plugin => $v) {
            if(!AppPlugin::isPlugin($plugin)) {
                unset(self::$search_rules[$v['ssearch']]);
            }
        }
        
        foreach(self::$search_rules as $k => $v) {
            $search['search'][$k] = $v['search'];
            $search['prompt'][$k] = $v['prompt'];

            if(!empty($v['insert'])) {
                $search['insert'][$k] = $v['insert'];
            } else {
                $search['insert'][$k] = substr($v['prompt'], 0, strpos($v['prompt'], ':')+1);
            }
            
            if(!empty($v['filter'])) {
                $search['filter'][$k] = $v['filter'];
            }
        }
        
        return $search;
    }
    
    
    static function parseSpecialSearchStr($str, $search, $filter = array()) {

        $str = urldecode($str);
        $str = trim($str);
        
        foreach ($search as $k => $v) {
            preg_match($v, $str, $match);

            if(!empty($match[0])) {
                
                // to be able to highlight special search
                if(!defined('IS_SPECIAL_SEARCH')) {
                    define('IS_SPECIAL_SEARCH', true);
                }
                
                $ret['rule'] = $k;
                $ret['val'] = (isset($match[1])) ? $match[1] : false;

                if(!empty($filter[$ret['rule']])) {
                    $f = $filter[$ret['rule']];
                    if($f == 'ids') {
                        $ret['val'] = self::filterIds($ret['val']);
                    }
                }
                
                return $ret;
            }
        }

        return false;
    }
    
    
    static function getSpecialSearchHelpMsg($module_search) {       
              
        $custom_search = self::parseCustomSearch($module_search);

        $rules = self::getSpecialSearch();
        $search = array_intersect_key($rules['search'], array_flip($module_search));
        $search += $custom_search['search'];
        
        $help = $rules['prompt'];
        $help += $custom_search['prompt'];    
            
        $insert = $rules['insert'];
        $insert += $custom_search['insert'];
        
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'search_help');
        $data = array();
        $data[] = sprintf('<b>%s</b>:', $msg['title']);
        $data[] = '<ul>';
        
        $data_li = '';
        $li_str = '<li style="margin-top: 2px; cursor:pointer;" onclick="insertSpecialSearch(\'%s\')">%s</li>';
        
        foreach ($search as $k => $v) {
            if(!empty($help[$k])) {
                $li = str_replace(array('{', '}'), array('&#123;', '&#125;'), $help[$k]);
                $data_li .= sprintf($li_str, $insert[$k], $li);
            }        
        }
        
        $data[] = $data_li . '</ul>';
        
        // echo '<pre>', print_r($data,1), '<pre>';
        // exit;
        
        $img = '<img src="../client/images/icons/help.svg" alt="help" 
            class="searchTooltipImage _tooltip_right" title="%s" />';
        $ret = ($data_li) ? sprintf($img, htmlentities(implode('', $data))) : '';
        
        return $ret;
    }
    
    
    static function parseCustomSearch(&$module_search) {
        $module_custom = array('search' => array(), 'prompt' => array(), 'insert' => array());
        foreach($module_search as $k => $v) {
            if(is_array($v)) {
                $module_custom['search'][$k] = $v['search'];
                $module_custom['prompt'][$k] = $v['prompt'];
                
                if(!empty($v['insert'])) {
                    $module_custom['insert'][$k] = $v['insert'];
                } else {
                    $module_custom['insert'][$k] = substr($v['prompt'], 0, strpos($v['prompt'], ':')+1);
                }
                
                if(!empty($v['filter'])) {
                    $module_custom['filter'][$k] = $v['filter'];
                }
                
                unset($module_search[$k]);
            }
        }
        
        return $module_custom;
    }
    
    
    static function filterIds($ids) {
        return implode(',', array_filter(explode(',', $ids), 'intval'));
    }
    
}

?>