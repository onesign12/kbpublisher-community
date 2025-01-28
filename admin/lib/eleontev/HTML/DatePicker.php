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
//
// $Id: date_picker.php,v 1.1.1.1 2004/09/15 16:57:44 root Exp $

/**
 * DatePicker is a class to generate "date_picker" easily.
 * It uses js to check and generate appropriate number of days for selected month,
 * considering for leap year.
 * It also remembers its values after "wrong submit", it means when user submit a form
 * but something is wrong, the values of "date picker" will be the same as before submit.
 * Ability to create your own, custom SELECT tag.
 *
 * After submitting a form date will be in POST or GET array
 * [date] => Array
 *   (
 *      [day] => 10
 *      [month] => 02
 *      [year] => 2003
 *   )
 *
 *
 * @version 1.0
 * @since 08/08/2003
 * @author Evgeny Leontev <eleontev@gmail.com>
 * @access public
 */

class DatePicker
{
    var $lang = 'en';                    // month will be generated in this lang
    var $form_name = 'my_form';            // form name where select with date will be
    var $form_method;

    var $select_name = 'date';            // name for SELECT tag

    var $day_name = 'day';                // form select name
    var $day_width = '45';                // form select width
    var $day_range = array();

    var $month_name = 'month';            // form select name
    var $month_width = '';            // form select width
    var $month_range = array();
    var $month_format = '%B';            // format for month value
                                        // %B - full month name
                                        // %b - abbreviated month name
                                        // %m - month as a decimal number (range 01 to 12)

    var $year_name = 'year';            // form select name
    var $year_width = '60';                // form select width
    var $year_range = array();

    var $hour_name = 'hour';            // form select name
    //var $hour_width = '110';            // form select width
    var $hour_range = array();


    var $minute_name = 'minute';        // form select name
    //var $year_width = '60';            // form select width
    var $minute_range = array();

    var $time_name = 'time';
    //var $time_width = '60';            // form select width
    var $time_range = array();
    var $time_format = '%H:%i';

    var $week_name = 'week';
    var $week_range = array();
    var $week_start = 0;                 // 0 - week starts from Sunday, 1 - Monday

    var $custom_name = 'custom';        // form select name
    var $custom_width = '';                // form select width
    var $custom_range = array();
    var $cur_custom = '';

    var $js_writed = false;
    var $css_class = '';
    var $error_pref    = '<font color="#FF0000">DATEPICKER</font>';     // error prefix


    var $cur_day;
    var $cur_month;
    var $cur_year;
    var $cur_hour;
    var $cur_minute;
    var $cur_week;
    var $cur_time;

    var $default_time = 'EMPTY'; // or CURRENT

    var $options = array();


    /**
     * Class constructor
     *
     * @access   public
     */
    function __construct() {
        $this->setFormMethod($_POST);     // default form method, to change call - setFormMethod($_GET);
        $this->setDate();                 // now
        //setlocale(LC_TIME, $this->lang);
    }


    function setOption($option) {
        $this->options[] = $option;
    }


   /**
    * FUNCTION: setFormName -- set form name
    *
    * @param    string    $name    form name where the "date_picker" will be
    *
    * @return   void
    * @access   public
    */
    function setFormName($name) {
        $this->form_name = $name;
    }


   /**
    * FUNCTION: setFormMethod -- set form method
    *
    * @param    string    $method    form method where the "date_picker" will be
    *
    * @return   void
    * @access   public
    */
    function setFormMethod($method) {
        $this->form_method =& $method;
    }


   /**
    * FUNCTION: setSelectName -- set name for SELECT tag
    *
    * @param    string    $name    form method where the "date_picker" will be
    *
    * @return   void
    * @access   public
    */
    function setSelectName($name) {
        $this->select_name = $name;
    }


   /**
    * FUNCTION: setLang -- set lang
    *
    * @param    string    $lang    for details see www.php.net/manual/en/function.setlocale.php
    *
    * @return   void
    * @access   public
    */
    function setLang($lang) {
        setlocale(LC_TIME, $lang);
    }


   /**
    * FUNCTION: setDate -- set defaultd date for "date picker"
    *
    * Set date which appear on "date picker", default - current date
    * Can be used when you edit some record, and it has some date
    *
    * @param    int timestamp    $timestamp    UNIX timestamp
    *
    * @return   void
    * @access   public
    */
    function setDate($timestamp = false) {

        $this->cur_hour = 'empty';
        if(empty($timestamp)) {
            $timestamp = time();
        } else {
            $this->cur_hour = date('H', $timestamp);
            $this->cur_minute = date('i', $timestamp);
        }

        $this->cur_time = _strftime('%H:%M', $timestamp);

        $start_format = ($this->week_start == 0) ? '%U' : '%W';
        $this->cur_week = _strftime($start_format, $timestamp);

        $this->cur_day = date('d', $timestamp);
        $this->cur_month = date('m', $timestamp);
        $this->cur_year = date('Y', $timestamp);

        //echo '<pre>', print_r($timestamp, 1), '</pre>';
        //echo '<pre>', print_r($this, 1), '</pre>';
    }


   /**
    * FUNCTION: day -- generate select for days.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function & day($empty_tag = false, $empty_value = '', $empty_option = '') {

        if(!$this->day_range) {
            $this->setDayRange(1, 31);
        }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->day_name.']'.'" id="'.$this->select_name.'_'.$this->day_name.'" class="'.$this->css_class.'" style="width: '.$this->day_width.'px" ' .$options. '>'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('day_name', 'day_range', $this->cur_day, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


   /**
    * FUNCTION: setDayRange -- generate day range.
    *
    * Call it if you want special day range, by default - all days, use numbers for param 1,5,6
    * if one param, then it should be an array, setDayRange(array(1,5,9, ...));
    * if two param, range will be from $start till $end, setDayRange(5, 11);
    *
    * @param    mixed    $start    'array' with values(1,2, ...) or 'int' start point
    * @param    int      $end      (optional) end point
    *
    * @return   void
    * @access   public
    */
    function setDayRange($start, $end = false) {

        if($end) {
            for ($i = $start; $i <= $end; $i++) {
                if($i<10) { $value = 0 . $i; }
                else       { $value = $i; }
                $this->day_range[$value] = $i;
            }
        } else {
            if(!is_array($start)) {
                $this->_error("If setDayRange has one param, it should be an array");
            }

            foreach($start as $v) {
                if($v<10) { $value = 0 . $v; }
                else       { $value = $v; }
                $this->day_range[$value] = $v;
            }
        }
    }



   /**
    * FUNCTION: month -- generate select for month.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function & month($empty_tag = false, $empty_value = false, $empty_option = false) {

        if(!$this->month_range) { $this->setMonthRange(1, 12); }

        $s_js = ''; $e_js = '';
        if($this->js_writed) {
            $s_js = 'onchange="dayToMonth(\''.$this->select_name.'\')"';
            $e_js = '<script> dayToMonth(\''.$this->select_name.'\'); </script>'."\n";
        }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->month_name.']" id="'.$this->select_name.'_'.$this->month_name.'" class="'.$this->css_class.'" style="width: '.$this->month_width.'px" '.$s_js.' ' .$options. '>'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('month_name', 'month_range', $this->cur_month, $empty_tag);
        $select .= '</select>'."\n";
        $select .= $e_js;
        return $select;
    }


   /**
    * FUNCTION: setMonthRange -- generate month range.
    *
    * Call it if you want special month range, by default - all month, use numbers for param 1,5,6
    * if one param, then it should be an array, setMonthRange(array(1,5,9, ...));
    * if two param, range will be from $start till $end, setMonthRange(5, 11);
    *
    * @param    mixed    $start    'array' with values(1,2, ...) or 'int' start point
    * @param    int      $end      (optional) end point
    *
    * @return   void
    * @access   public
    */
    function setMonthRange($start, $end = false) {

        $j = 1; // pogreshnost
        if($end) {
            for ($i = $start; $i <= $end; $i++) {
                if($i<10) { $value = 0 . $i; }
                else      { $value = $i; }
                $this->month_range[$value] = _strftime($this->month_format, mktime(0,0,0,$i+$j,0,0));
            }
        } else {
            if(!is_array($start)) {
                $this->_error("If setMonthRange has one param, it should be an array");
            }

            foreach($start as $v) {
                if($v<10) { $value = 0 . ($v); }
                else      { $value = $v; }
                $this->month_range[$value] = _strftime($this->month_format, mktime(0,0,0,$v+$j,0,0));
            }
        }
    }


   /**
    * FUNCTION: year -- generate select for year.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function & year($empty_tag = false, $empty_value = false, $empty_option = false) {

        if(!$this->year_range) { // ten years from now till -10
            $this->setYearRange($this->cur_year, $this->cur_year-10);
        }

        $s_js = ''; $e_js = '';
        if($this->js_writed) {
            $s_js = 'onchange="dayToYear(\''.$this->select_name.'\')"';
            $e_js = '<script> dayToYear(\''.$this->select_name.'\'); </script>'."\n";
        }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->year_name.']" id="'.$this->select_name.'_'.$this->year_name.'" class="'.$this->css_class.'" style="width: '.$this->year_width.'px" '.$s_js.' ' .$options. '>'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('year_name', 'year_range', $this->cur_year, $empty_tag);
        $select .= '</select>'."\n";
        $select .= $e_js;
        return $select;
    }


   /**
    * FUNCTION: setYearRange -- generate year range.
    *
    * Call it if you want special year range, by default - 10 years from current year
    * if one param, then it should be an array, setYearRange(array(2000, 2010, ...));
    * if two param, range will be from $start till $end, setYearRange(1989, 2003);
    *
    * @param    mixed     $start    'array' with values or 'int' start point
    * @param    string    $end      (optional) end point
    *
    * @return   void
    * @access   public
    */
    function setYearRange($start, $end = false) {

        if($end) {
            if($start > $end) {
                $range =  $start - $end;
                for ($i = 0; $i <= $range; $i++) {
                    $this->year_range[$start-$i] = $start-$i;
                }
            } else {
                $range =  $end - $start;
                for ($i = 0; $i <= $range; $i++) {
                    $this->year_range[$start+$i] = $start+$i;
                }
            }

        } else {
            if(!is_array($start)) {
                $this->_error("If setYearRange has one param, it should be an array");
            }

            foreach($start as $v) {
                $this->year_range[$v] = $v;
            }
        }
    }



   /**
    * FUNCTION: week -- generate select for week.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function & week($empty_tag = false, $empty_value = false, $empty_option = false) {

        if(!$this->week_range) { // ten years from now till -10
            foreach(range(1, 53) as $k => $v) {
                $this->week_range[sprintf('%02d', $v)] = $v;
            }
        }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->week_name.']" id="'.$this->select_name.'_'.$this->week_name.'" class="'.$this->css_class.' ' .$options. '">'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('week_name', 'week_range', $this->cur_week, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


    function hour($empty_tag = false, $empty_value = '', $empty_option = '') {

        if(!$this->hour_range) { $this->setHourRange(1, 24); }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->hour_name.']'.'" id="'.$this->select_name.'_'.$this->hour_name.'" class="'.$this->css_class.' ' .$options. '">'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('hour_name', 'hour_range', $this->cur_hour, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


    function minute($empty_tag = false, $empty_value = '', $empty_option = '') {

        if(!$this->minute_range) { $this->setMinuteRange(0, 55, 5); }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->minute_name.']'.'" id="'.$this->select_name.'_'.$this->minute_name.'" class="'.$this->css_class.' ' .$options. '">'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('minute_name', 'minute_range', $this->cur_minute, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


    function setHourRange($min = 0, $max = 24, $gap = 1) {

        if($this->default_time == 'EMPTY') {
            $this->hour_range['empty'] = '_';
        }

        if(is_array($min)) {
             foreach($min as $v) {
                $val = ($v<10) ? '0'.$v : $v;
                $this->hour_range[$val] = $v;
            }
        } else {
            for($i=$min;$i<=$max;$i+=$gap){
                $val = ($i<10) ? '0'.$i : $i;
                $this->hour_range[$val] = $i;
            }
        }

        if(isset($this->hour_range[24])) {
            unset($this->hour_range[24]);
            $this->hour_range['00'] = '00';
        }
    }


    function setMinuteRange($min = 5, $max = 55, $gap = 5) {

        if(is_array($min)) {
             foreach($min as $v) {
                $val = ($v<10) ? '0'.$v : $v;
                $this->minute_range[$val] = $val;
            }
        } else {
            for($i=$min;$i<=$max;$i+=$gap){
                $val = ($i<10) ? '0'.$i : $i;
                $this->minute_range[$val] = $val;
            }
        }
    }


    function time($empty_tag = false, $empty_value = '', $empty_option = '') {

        if(!$this->time_range) { $this->setTimeRange(); }

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->time_name.']'.'" id="'.$this->select_name.'_'.$this->time_name.'" class="'.$this->css_class.' ' .$options. '">'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('time_name', 'time_range', $this->cur_time, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


    function setTimeRange($start = 8, $hours = 24, $gap_min = 30) {

        if(is_array($start)) {
            foreach($start as $v) {
                $key = _strftime('%H:%M', mktime($v['hour'],$v['min'],1,1,1,2008));
                $val = _strftime($this->time_format, mktime($v['hour'],$v['min'],1,1,1,2008));
                $this->time_range[$key] =    $val;
            }

        } else {
            $hours--;
            $end = $start+$hours;
            for($i = $start; $i <= $end; $i++){
                $hour = $i;

                for($j = 0; $j <= 60; $j+=$gap_min){
                    $min = $j;
                    $key = _strftime('%H:%M', mktime($hour,$min,1,1,1,2008));
                    $val = _strftime($this->time_format, mktime($hour,$min,1,1,1,2008));

                    $this->time_range[$key] =    $val;
                }
            }
        }
    }


    function getHidden($val, $value) {
        $str = '<input type="hidden" name="%s" value="%s">' . "\n";
        $name = $this->select_name.'['.$val.']';

        return sprintf($str, $name, $value);
    }


   /**
    * FUNCTION: js -- generate JavaScript to manage day values
    *
    * This js checks for selected month and year and prepare appropriate day values to select.
    * Can check for: number of days in month (30 or 31 or 28) considering leap year
    * for example when february is selected, day SELECT has only 28 day.
    *
    * @return   string    javascript
    * @access   public
    */
    function js() {
        ob_start();
        ?>
        <script>
        <!--
        month_numbDay=new Array();
        month_numbDay['01']=31;
        month_numbDay['02']=28;
        month_numbDay['03']=31;
        month_numbDay['04']=30;
        month_numbDay['05']=31;
        month_numbDay['06']=30;
        month_numbDay['07']=31;
        month_numbDay['08']=31;
        month_numbDay['09']=30;
        month_numbDay['10']=31;
        month_numbDay['11']=30;
        month_numbDay['12']=31;
        setDate=new Date();
        currentDay=setDate.getDate();
        currentMonth=setDate.getMonth();
        currentYear=setDate.getYear();
        if(((currentYear-2000)/4)==parseInt((currentYear-2000)/4)){
            month_numbDay['02']=29;
        }

        function dayToMonth(name){ //forms[upload].elements[date].
            flag1=true;
            flag2=true;
            day = eval ('document.forms["<?php echo $this->form_name; ?>"].elements["'+name+'[<?php echo $this->day_name; ?>]"]');
            month = eval ('document.forms["<?php echo $this->form_name; ?>"].elements["'+name+'[<?php echo $this->month_name; ?>]"]');
            while(flag1){
                if(day.options[day.options.length-1].value<month_numbDay[month.options[month.selectedIndex].value]){
                    op=new Option();
                    op.text=parseInt(day.options[day.options.length-1].value)+1;
                    op.value=op.text;
                    day.options[day.options.length]=op;
                } else{
                    flag1=false;
                }
            }
            while(flag2){
                if(day.options[day.options.length-1].value>month_numbDay[month.options[month.selectedIndex].value]){
                    if (day.selectedIndex==day.options.length-1){
                        day.selectedIndex=day.options.length-2;
                    }
                    day.options[day.options.length-1]=null;
                } else{
                    flag2=false;
                }
            }
        }


        function dayToYear(name){
            year = eval ('document.forms["<?php echo $this->form_name; ?>"].elements["'+name+'[<?php echo $this->year_name; ?>]"]');
            if( ((year.value-2000)/4)==parseInt((currentYear-2000)/4) ){
                month_numbDay['02']=29;
            } else {
                month_numbDay['02']=28;
            }
            dayToMonth(name);
        }

        //dayToYear(name);

        //-->
        </script>

        <?php

        $a = ob_get_contents();
        ob_end_clean();
        $this->js_writed = true;
        return $a;
    }


   /**
    * FUNCTION: setCustomName -- set name for SELECT tag.
    *
    * @param    string   $name    range_name
    *
    * @return   void
    * @access   public
    */
    function setCustomName($name) {
        $this->custom_name = $name;
    }


   /**
    * FUNCTION: setCustomRange -- generate custom range.
    *
    * Call it if you want any special range and can't create it by setMonthRange for example
    * For $range_name use 'day_range', 'month_range', 'year_range', by default - 'custom_range'.
    * For $array use array with key and values like this array('01' => 'January', '02' => 'February', ...);
    *
    * @param    array    $array         array with key and values
    * @param    string   $range_name    (optional)range_name
    *
    * @return   void
    * @access   public
    */
    function setCustomRange($array, $range_name = 'custom_range') {
        $this->$range_name = $array;
    }


   /**
    * FUNCTION: customSelect -- generate custom SELECT tag.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function customSelect($empty_tag = false, $empty_value = false, $empty_option = false) {

        $options = implode(' ', $this->options);

        $select = '';
        $select .= '<select name="'.$this->select_name.'['.$this->custom_name.']" class="'.$this->css_class.'" style="width: '.$this->custom_width.'px">'."\n";
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag('custom_name', 'custom_range', $this->cur_custom, $empty_tag);
        $select .= '</select>'."\n";
        return $select;
    }


   /**
    * FUNCTION: customTag -- generate custom INPUT tag.
    *
    * @param    string    $input_type   text or hidden
    * @param    string    $name         name for tag
    * @param    string    $value        (optional) value for tag
    * @param    int       $size         (optional) size for input text
    *
    * @return   string    generated INPUT tag
    * @access   public
    */
    function customTag($input_type, $name, $value = false, $size = 4) {

        @$submitted_value = $this->form_method[$this->select_name][$name];
        if(isset($submitted_value)) {
            $value = $submitted_value;
        }

        $tag = '';
        $tag .= '<input type="'.$input_type.'" name="'.$this->select_name.'['.$name.']" class="'.$this->css_class.'" value="'.$value.'" size='.$size.'>';
        return $tag;
    }


   /**
    * FUNCTION: sqlDate -- return formated date value.
    *
    * Return formated date value at format (YYYYMMDDHHMMSS) to insert to database
    * which suits for DATETIME, DATE, and TIMESTAMP types
    * call it like this - DatePicker::sqlDate();
    *
    * @param    string    $name     (optional) SELECT tag name, default - date
    * @param    string    $method   (optional) form method, default - $_POST
    * @param    string    $time     (optional) time value, default current time 'hhmmss'
    *
    * @return   string    date in format (YYYYMMDDHHMMSS)
    * @access   public
    */
    static function sqlDate($name = 'date', $method = false, $time = false) {

        $method = ($method) ? $method : $_POST;

        if($time !== false) {

            $hour = (isset($method[$name]['hour'])) ? $method[$name]['hour'] : '00';
            $minute = (isset($method[$name]['minute'])) ? $method[$name]['minute'] : '00';
            $second = (isset($method[$name]['second'])) ? $method[$name]['second'] : '00';

            $time = $hour . $minute . $second;
        }


        $str =  $method[$name]['year'] .
                $method[$name]['month'] .
                $method[$name]['day'] .
                $time;

        return $str;
    }


    static function sqlDate2($val) {

        $hour = (isset($val['hour'])) ? $val['hour'] : '00';
        $minute = (isset($val['minute'])) ? $val['minute'] : '00';
        $second = (isset($val['second'])) ? $val['second'] : '00';

        $hour = (isset($val['time'])) ? substr($val['time'], 0, 2) : $hour;
        $minute = (isset($val['time'])) ? substr($val['time'], 3, 2) : $minute;

        $time = $hour . $minute . $second;

        $str =  $val['year'] .
                $val['month'] .
                $val['day'] .
                $time;

        return $str;
    }


   /**
    * FUNCTION: unixDate -- return formated date value.
    *
    * Return formated date value at UNIX timestamp format
    *
    * @param    string    $name     (optional) SELECT tag name, default - date
    * @param    string    $method   (optional) form method, default - $_POST
    * @param    string    $time     (optional) time value, default current time 'hhmmss'
    *
    * @return   string    date in format  UNIX timestamp
    * @access   public
    */
    static function unixDate($name = 'date', $method = false) {

        $method = (@$method) ? $method : $_POST;

        $hour = (isset($method[$name]['hour'])) ? $method[$name]['hour'] : 0;
        $minute = (isset($method[$name]['minute'])) ? $method[$name]['minute'] : 0;
        $second = (isset($method[$name]['second'])) ? $method[$name]['second'] : 0;

        $hour = (isset($method[$name]['time'])) ? substr($method[$name]['time'], 0, 2) : $hour;
        $minute = (isset($method[$name]['time'])) ? substr($method[$name]['time'], 3, 2) : $minute;

        $str =  mktime ($hour,
                        $minute,
                        $second,
                        $method[$name]['month'],
                        $method[$name]['day'],
                        $method[$name]['year']);

        return $str;
    }


    static function unixDate2($val) {

        $hour = (isset($val['hour'])) ? $val['hour'] : 0;
        $minute = (isset($val['minute'])) ? $val['minute'] : 0;
        $second = (isset($val['second'])) ? $val['second'] : 0;
        $day = (isset($val['day'])) ? $val['day'] : 1;

        $hour = (isset($val['time'])) ? substr($val['time'], 0, 2) : $hour;
        $minute = (isset($val['time'])) ? substr($val['time'], 3, 2) : $minute;

        $str =  mktime($hour, $minute, $second, $val['month'], $day, $val['year']);
        return $str;
    }


   /**
    * FUNCTION: toUnixDate -- return formated date value.
    *
    * Return formated date value at UNIX timestamp format
    *
    * @param    string    $date   string in format YYYYMMDDHHMM or YYYY-MM-DD HH:MM
    *
    * @return   string    date in format  UNIX timestamp
    * @access   public
    */
    static function toUnixDate($date) {

        $date = str_replace(array('-', ':', ' '), '', $date);

        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);

        $hour = ($h = substr($date, 8, 2)) ? $h : 0;
        $minute = ($m = substr($date, 10, 2)) ? $m : 0;

        $str =  mktime ($hour,$minute,0,$month,$day,$year);
        return $str;
    }



    /*-----------------------------------------------------------------------------\
    |                           private functions                                  |
    \-----------------------------------------------------------------------------*/

   /**#@+5
    * @access private
    */
    function _optionTag($name, $range, $value, $empty_tag) {

        @$select_name = $this->form_method[$this->select_name][$this->$name];

        if(!isset($select_name)) {
            $current = 'dsrwebhgf'; //
            if($value) {
                $current = $value;
            }
        } else {
            $current = $select_name;
        }

        $tag = '';
        foreach($this->$range as $k => $v) {
            $selected = ($current == $k) ? ' selected' : '';
            $tag .= '<option value='.$k.$selected.'>'.$v.'</option>'."\n";
        }

        //$this->$range = '';
        return $tag;
    }


    function _emptyOtionTag($empty_tag, $empty_value, $empty_option) {
        if($empty_tag) {
            return '<option value='.$empty_value.'>'.$empty_option.'</option>'."\n";
        }
    }

    function _error($msg) {
        trigger_error($this->error_pref.' '.$msg);
    }

} // <-- class end
?>
