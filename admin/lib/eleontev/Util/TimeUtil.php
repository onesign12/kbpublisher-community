<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
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


class TimeUtil
{

    // return array with hours in format 00
    static function setHours($min = 0, $max = 24) {
        for($i=$min;$i<=$max;$i++){
            $val = ($i<10) ? '0'.$i : $i;
            $ar[$val] = $i;
        }
        return $ar;
    }
    
    
    // return array with minutes in format 00
    static function setMinutes($min, $max, $gap) {
        for($i=$min;$i<=$max;$i+=$gap){
            
            $val = ($i<10) ? '0'.$i : $i;
            $ar[$val] = $val;
        }
        return $ar;
    }
    
    // return whole hours from minutes
    static function getHours($minutes) {
        $hour = (int) ($minutes / 60);
        $hour = ($hour < 10) ? '0'.$hour : $hour;
        
        return $hour;
    }
    
    // return minutes after whole hours 
    static function getMinutes($minutes) {
        $minutes = (int) ($minutes % 60);
        $minutes = ($minutes < 10) ? '0'.$minutes : $minutes;
        
        return $minutes;
    }
    
    
    static function toMinites($hours, $minutes) {
        $hour = (int) ($hours * 60);
        $value = (int) $hour + $minutes;
        
        return $value;
    }


    static function prefixZero($value) {
        return ($value < 10) ? '0'.$value : $value;
    }


    static function toStampFromSqlDate($date) {
        
        $date = str_replace('-', '', $date);
        
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6);
        
        $str =  mktime (0,0,0,$month,$day,$year);
        return $str;    
    }
    
    
    // from that '15 March, 2004' to UNIXTIMESTAMP
    // if change something also do changes to getFormatedDate($timestamp)
    static function toStampFromJsDate($date) {
        $date = str_replace(',', '', $date);
        $date = explode(' ', $date);
        
        for($i=1;$i<=12;$i++){
            $monthes[_strftime('%B', mktime(0,0,0,$i,1,2004))] = $i;
        }
        
        $hour = date('H');
        $min = date('i');
        $sek = date('s');
        $month = $monthes[$date[1]];
        $date = mktime($hour,$min,$sek,$month,$date[0],$date[2]);
        
        return $date;
    }
    
    
    static function toFormatedFromStamp($timestamp) {
        $date = _strftime('%d %B, %Y', $timestamp);
        return $date;
    }
    
    
    static function toSqlFromStamp($timestamp) {
        $date = date('YmdHis', $timestamp);
        return $date;
    }
    
    
    // static function toDateFromString($date, $format = 'Ymd') {
    //     return date($format, strtotime($date));
    // }
    
    
    //Two usefull functions, if you want to built your own calendar.
    # Returns date of the Monday for given number of week(1..53) and  year.
    # Output is in date-format.
    static function _getMonday($week, $year=""){
        $first_date = strtotime("1 january ".($year ? $year : date("Y")));
        if(date("D", $first_date)=="Mon") {
            $monday = $first_date;
        } else {
            $monday = strtotime("next Monday", $first_date)-604800;
        }
        
        $plus_week = "+".($week-1)." week";
        return strtotime($plus_week, $monday);
    }
    
    // return timestamp for week start
    static function getWeekStart($week, $year, $week_start = 0){
        if($week_start == 0) {
            return TimeUtil::_getMonday($week, $year)-86400;
        } else {
            return TimeUtil::_getMonday($week, $year);
        }
    }
    
    // get days quantity of month
    static function getMonthDays($month, $year) {
        return date('j', mktime(0,0,0,$month+1,0,$year));
    }
    
    
    // takes two dates formatted as YYYY-MM-DD or in timestamp and creates an
    // inclusive array of the dates between the from and to dates.
    static function getDateRange($date_from, $date_to, $format = 'Y-m-d') {

        $range = array();
        
        if(!is_numeric($date_from)) {
            $date_from = strtotime($date_from);
            $date_to = strtotime($date_to);
        }
        
        if ($date_to >= $date_from) {
            $d = date($format, $date_from);
            $range[] = $d; // first entry
            
            while($date_from < $date_to) {
                $date_from += 86400; // add 24 hours
                $d = date($format, $date_from);
                $range[] = $d;
            }
        }
        
        return $range;
    }    
    
    
    // $today_format if we need to post something like Today 12:55 
    static function getInterval($timestamp, $today_format = false) {

        $now = time();
        $diff = $now - $timestamp;

        if($today_format) {
            $diff2 = date('Ymd', $timestamp) - date('Ymd', $now); //20081001
            if($diff2 === 0) {
                $r['i'] = _strftime($today_format, $timestamp);
                $r['t'] = 'today';
                return $r;
            } elseif($diff2 === -1) {
                $r['i'] = _strftime($today_format, $timestamp);
                $r['t'] = 'yesterday';                
                return $r;
            }
        }

        if($diff < 60) {
            $r['i'] = $diff;
            $r['t'] = 'second';

        } elseif($diff < 3600) { // seconds in hour
            $r['i'] = floor($diff/60);
            $r['t'] = 'minute';

        } elseif($diff < 86400) { // 3600*24 seconds in day
            $r['i'] = floor($diff/3600);
            $r['t'] = 'hour';

        // shaw day fisrt week only
        // } elseif($diff < 604800) { // 3600*24*7 seconds in week 

        // shaw day fisrt week and second week, skip 7 days (1 week)
        } elseif($diff < 604800 || ($diff > 691200 && $diff < 1209600)) { // 3600*24*7 seconds in week
            $r['i'] = floor($diff/86400);
            $r['t'] = 'day';

        } elseif($diff < 2635200) { // 3600*24*30.5 seconds in month
            $r['i'] = floor($diff/604800);
            $r['t'] = 'week';

         } elseif($diff < 31436000) { // 3600*24*365 // seconds in year
            $r['i'] = floor($diff/2635200);
            $r['t'] = 'month';

        } else {
            $total_months = floor($diff/2635200);
            $r['i']['year'] = floor($total_months/12);
            $r['i']['month'] = $total_months - $r['i']['year'] * 12;
            if($r['i']['month'] == 0) {
                unset($r['i']);
                $r['i'] = floor($total_months/12);
            }
            $r['t'] = 'year';
        }

        return $r;
    }
    
    
    static function getDateFormat() {
        
        //$patterns = array('#11\D21\D(1999|99)#', '#21\D11\D(1999|99)#', '#(1999|99)\D11\D21#');
        
        // for Japanese support, date_format = '%Y年%m月%d日';
        $patterns = array('#11\D21\D(1999|99)\D?#u', '#21\D11\D(1999|99)\D?#u', '#(1999|99)\D11\D21\D?#u');
        
        $replacements = array('mm/dd/yy', 'dd.mm.yy', 'yy/mm/dd');
        $timestamp = mktime(0,0,0,11,21,1999);
        
        return preg_replace($patterns, $replacements, _strftime('%x', $timestamp));
    }
    
    
    static function getPeriodData($period, $values, $week_start, $start_date_ts = false) {
                              
        $cal = new CalendarUtil();
        $cal->week_start = $week_start;
        $cal->setCalendar();
  
        $data = array();
        $data['start_day'] = NULL;
        $data['end_day'] = NULL;
  
  
        switch ($period) {
        case 'previous_24_hour': // ------------------------------
            $data['start_day'] = date('Y-m-d H:i:s', time() - 86400);
            $data['end_day'] = date('Y-m-d H:i:s');
            break;

        case 'this_day': // ------------------------------
            // $data['start_day'] = date('Y-m-d');
            // $data['end_day'] = date('Y-m-d', strtotime($data['start_day']) + 86400);
            
            $data['start_day'] = date('Y-m-d');
            $data['end_day'] = $data['start_day'];
            break;

        case 'previous_day': // ------------------------------
            // $data['end_day'] = date('Y-m-d');
            // $data['start_day'] = date('Y-m-d', strtotime($data['end_day']) - 86400);

            $data['end_day'] = date('Y-m-d', time() - 86400);
            $data['start_day'] = $data['end_day'];
            break;

        case 'this_week': // ------------------------------            
            $d = $cal->setWeek();
            $data['start_day'] = date('Y-m-d', $d['start_day']);
            $data['end_day'] = date('Y-m-d', $d['end_day']);
            break;

        case 'previous_week': // ------------------------------
            $d = $cal->setWeek();
            $data['start_day'] = date('Y-m-d', $d['prev']);
            $data['end_day'] = date('Y-m-d', $d['prev']+$cal->sek_in_day*6);
            break;

        case 'this_month': // ------------------------------
            $data['start_day'] = date('Y-m-01');
            $data['end_day'] = date('Y-m-' . $cal->cur_month_num_days);
            break;

        case 'previous_month': // ------------------------------            
			$m = $cal->getTimestampValues();
            $data['start_day'] = date('Y-m-d', $m['prev_month_start']);
            $data['end_day'] = date('Y-m-d', $m['prev_month_end']);
            break;
                 
        case 'this_year': // ------------------------------
            $data['start_day'] = sprintf('%s-%s-%s', $cal->year, '01', '01');
            $data['end_day'] = sprintf('%s-%s-%s', $cal->year, '12', '31'); 
            break;

        case 'previous_year': // ------------------------------
            $data['start_day'] = sprintf('%s-%s-%s', $cal->prev_year, '01', '01');
            $data['end_day'] = sprintf('%s-%s-%s', $cal->prev_year, '12', '31');  
            break;
                    
        case 'all_period': // ------------------------------
            $data['start_day'] = ($start_date_ts) ? date('Y-m-d', $start_date_ts) : '2009-01-01';
            $data['end_day'] = date('Y-12-31');
            break;
            
        case 'custom_period': // ------------------------------
        case 'custom_period2': // ------------------------------
            $data['start_day'] = date('Y-m-d', strtotime(urldecode($values['date_from'])));
            $data['end_day'] = date('Y-m-d', strtotime(urldecode($values['date_to'])));
            break;
        }

        // echo '<pre>', print_r($data, 1), '</pre>';
        return $data;
    }
}
?>