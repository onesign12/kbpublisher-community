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

class CalendarUtil {
    
    var $lang = 'en';                    // month will be generated in this lang
    var $sek_in_day = '86400';
    var $day;
    var $month;
    var $year;
    var $week;
    var $week_start = 1;                // 0 - for Sunday, 1 - for Monday
    var $cur_day = array();
    var $show_other_days = 1;            // show or not days for not current month
    
    //var $weekday_name = Array(1 => 'Sun','Mon','Tue','Wed','Thu','Fri','Sat'); // for inside purpose 
    
    var $month_format = '%B';            // format for month value 
                                        // %B - full month name
                                        // %b - abbreviated month name 
                                        // %m - month as a decimal number (range 01 to 12) 
    
    var $week_day_format = '%A';        // %A - full weekday name according to the current locale 
                                        // %a - abbreviated weekday name according to the current locale 
                                        


    function __construct($lang = false) {

    }
    
    
    function setLang($lang) {
        setlocale(LC_TIME, $lang);
    }
    
    
   /**
    * setCalendar -- set all nesessary calendar values
    *
    * @param    mixed   $timestamp    (optional) one param (UNIXTEMISTAMP) or 3 param (day, month, year)
    *
    * @return   void
    * @access   public    
    */
    function setCalendar($timestamp = false) {
    
        $numargs = func_num_args();
        if($numargs > 1) { 
            $arg_list = func_get_args();
            $this->day = $arg_list[0];
            $this->month = $arg_list[1];
            $this->year = $arg_list[2];
        } else {
            if(!$timestamp) { $timestamp = time(); }

            $this->day = date('j', $timestamp);
            $this->month = date('n', $timestamp);
            $this->year = date('Y', $timestamp);
        }
        
        $this->setCalendarValues();
    }
    

    function setCalendarValues() {
    
        $this->month_name = _strftime($this->month_format, mktime(0,0,0,$this->month,1,$this->year));
        $this->cur_month_num_days = _strftime('%d', mktime(0,0,0,$this->month+1,0,$this->year));
        $this->prev_month_num_days = _strftime('%d', mktime(0,0,0,$this->month,0,$this->year));
        $this->next_month_num_days = _strftime('%d', mktime(0,0,0,$this->month+2,0,$this->year));
        //$this->prev_month = $this->getPrevNextMonth($this->month, 'prev');
        //$this->next_month = $this->getPrevNextMonth($this->month, 'next');        
        $this->prev_month = date('n', mktime(0,0,0,$this->month-1,1,$this->year));
        $this->next_month = date('n', mktime(0,0,0,$this->month+1,1,$this->year));
        $this->month_start_day = date('D', mktime(0,0,0,$this->month,1,$this->year));
        
        //$this->prev_year = $this->getPrevNextYear($this->month, $this->year, 'prev');
        //$this->next_year = $this->getPrevNextYear($this->month, $this->year, 'next');
        $this->prev_year = date('Y', mktime(0,0,0,$this->month,1,$this->year-1));
        $this->next_year = date('Y', mktime(0,0,0,$this->month,1,$this->year+1));
        
        $this->week_cur_day = date('D', mktime(0,0,0,$this->month,$this->day,$this->year));
        $this->week_days = $this->_setWeekDays($this->month, $this->year);
    }    
    
    
    // int mktime ( int hour, int minute, int second, int month, int day, int year [, int is_dst])
    // not sure is it right way, but it's working!
    function _setWeekDays() {
        for($i=0;$i<7;$i++){
            $week_days['week_day_'.$i] = ucfirst(_strftime($this->week_day_format, mktime(0,0,0,2,22+$this->week_start+$i,2004)));
        }
        return $week_days;
    }    
    
   
   /**
    * isToday -- check for today 
    *
    * @param    mixed   $timestamp    (optional) one param (UNIXTEMISTAMP) or 3 param (day, month, year)
    *
    * @return   void
    * @access   public    
    */
    function isToday($timestamp) {
        $today = false;
        if(date('Ymd') == date('Ymd', $timestamp)) {
            $today = true;
        }
        
        return $today;
    }

    
    function isCurrentMonth($timestamp) {
        if(date('m', $timestamp) == $this->month) { return true; }
        else                                      { return false; }
    }
    
    
    static function isSunday($timestamp) {
        if(date('w', $timestamp) == 0) {
            //echo '<pre>', print_r(date('w', $timestamp), 1), '</pre>';
            return true;
        }
        
        return false;
    } 
    
    
    // return day, month, year
    function fromTimestamp($timestamp) {
        $this->data['day'] = date('j', $timestamp);
        $this->data['month'] = date('n', $timestamp);
        $this->data['year'] = date('Y', $timestamp);
        
        return $this->data;
    }
    
    
    function _getOffset($start_day, $week_start = 0) {
        $timestamp = mktime(0,0,0,2,22+$week_start,2004);
        for($i=0;$i<7;$i++){
            $offset = date('D', $timestamp+86400*$i);
            $offsets[$offset] = $i;
        }
        
        return $offsets[$start_day];
    }
    
    
    // generate week values
    function setWeek() {
        $offset = $this->_getOffset($this->week_cur_day, $this->week_start);
        
		// $start = ($offset) ? $this->sek_in_day*$offset : 0; // what to minus (timestamp) or 0
        // $week_start_timestamp = mktime(1,0,0,$this->month,$this->day,$this->year) - $start; // week start timestamp
        $week_start_timestamp = mktime(1,0,0,$this->month,$this->day-$offset,$this->year); // week start timestamp
                
        $this->data['prev'] = $week_start_timestamp - ($this->sek_in_day*7);
        $this->data['next'] = $week_start_timestamp + ($this->sek_in_day*7);
        $this->data['start_day'] = $week_start_timestamp;
        $this->data['end_day'] = $week_start_timestamp + ($this->sek_in_day*6);
        
        return $this->data;
    }
    
    
    // week
    function getWeekNumber($timestamp) {
        if ($this->week_start == 0) {
            $timestamp = strtotime('+ 1 day', $timestamp);
        }
        
        $week_year = date('Y', $timestamp); 
        $week_number = date('W', $timestamp);
        
        if ((date('m', $timestamp) == '12') && ($week_number == 1)) {
            $week_year ++;
        }
        
        if ((date('m', $timestamp) == '01') && (in_array($week_number, array(52, 53)))) {
            $week_year --;
        }
        
        return $week_year . $week_number;
    }
    
    
    function getFirstDayByWeek($week_year, $week_number) {
        $week_str = sprintf('%sW%s', $week_year, $week_number);
        $timestamp = strtotime($week_str);
        
        if ($this->week_start == 0) {
            $timestamp -= $this->sek_in_day;
        }
        
        return $timestamp;
    }
     
    
    // generate month values for calendar views
	// in calendar view we have offset ti start from week start 
    function & setMonth() {
    
        $offset = $this->_getOffset($this->month_start_day, $this->week_start);
        
        if($offset > 0) {
            $day = $this->prev_month_num_days-$offset+1; // 25
            $month = $this->prev_month;
            $year = ($this->month == 1) ? $this->prev_year : $this->year;
        } else {
            $day = 1; $month = $this->month; $year = $this->year;
        }
        
        $this->data['start_day'] = mktime(0,0,0,$month,$day,$year);
        $this->data['prev'] = mktime(0,0,0,$this->prev_month,1,$year);
        
        
        $num_days = $this->cur_month_num_days + $offset; // days all
        if($num_days == 28)     { $outset = 0;              $this->data['num_weeks'] = 4; } 
        elseif($num_days > 35)  { $outset = 42 - $num_days; $this->data['num_weeks'] = 6; } 
        else                    { $outset = 35 - $num_days; $this->data['num_weeks'] = 5; }
        
        
        if($outset > 0) {
            $day = $outset;
            $month = $this->next_month;
            $year = ($this->month == 12) ? $this->next_year : $this->year;
        } else {
            $day = 1; $month = $this->month; $year = $this->year;
        }
        
        $this->data['end_day'] =  mktime(0,0,0,$month,$day,$year);
        $this->data['next'] = mktime(0,0,0,$this->next_month,1,$year);
		
        return $this->data;
    }
	
	
	function getTimestampValues() {
		
		// these real values without offset and outset
		$year = ($this->month == 1) ? $this->prev_year : $this->year;
        $data['prev_month_start'] =  mktime(0,0,0,$this->prev_month,1,$year);
        $data['prev_month_end'] =  mktime(0,0,0,$this->prev_month,$this->prev_month_num_days,$year);
		
		$year = $this->year;
        $data['cur_month_start'] =  mktime(0,0,0,$this->month,1,$year);
        $data['cur_month_end'] =  mktime(0,0,0,$this->month,$this->cur_month_num_days,$year);
		
		$year = ($this->month == 12) ? $this->next_year : $this->year;
        $data['next_month_start'] =  mktime(0,0,0,$this->next_month,1,$year);
        $data['next_month_end'] =  mktime(0,0,0,$this->next_month,$this->next_month_num_days,$year);
		
		return $data;
	}
	

}
?>