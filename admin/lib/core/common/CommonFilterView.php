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

class CommonFilterView
{

static $period_range = [
        "all_period",
        "never",
        "previous_24_hour",
        "this_day",
        "previous_day",
        "this_week",
        "previous_week",
        "this_month",
        "previous_month",
        "this_year",
        "previous_year",
        "custom_period" 
    ];

    static function getPeriodRange($set_range = array(), $skip_range = array()) {
        $msg_range = AppMsg::getMsgs('datetime_msg.ini', false, 'period_range');
        $msg_range['all_period'] = '__';
        
        $range = [];
        foreach(self::$period_range as $v) {
            $range[$v] = $msg_range[$v];
        }
        
        if($set_range) {
            $range = array_intersect_key($range, array_flip($set_range));
        }
        
        if($skip_range) {
            $range = array_diff_key($range, array_flip($skip_range));
        }
        
        return $range;
    } 
    

    // parse period input in filter 
    static function parsePeriodFilterInput(&$tpl, $values, $manager, 
        $set_range = array(), $skip_range = array()) {
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $range = CommonFilterView::getPeriodRange($set_range, $skip_range);
        $select->setRange($range);
        
        @$v = $values['p'];
        $tpl->tplAssign('custom_display', ($v == 'custom_period') ? 'block' : 'none');
        $tpl->tplAssign('period_select', $select->select($v));
        
        
        $start_date_timestamp = $manager->getStartDate();
        $start_date = date('m/d/Y', $start_date_timestamp);
        $tpl->tplAssign('min_date', $start_date);
        
        if (empty($v) || $v != 'custom_period') {
            $date_from = time();
            $date_to = time();
            
        } else {
            $date_from = strtotime(urldecode($values['date_from']));
            $date_to = strtotime(urldecode($values['date_to']));
            
            if (!$date_from && $date_to) {
                $date_from = $start_date_timestamp;
            }
            
            if ($date_from && !$date_to) {
                $date_to = time();
            }
            
            if (!$date_from && !$date_to) { // both dates are missing
                $date_from = time();
                $date_to = time();
            }
        }
        
        return array($date_from, $date_to);
    }
    
}
?>