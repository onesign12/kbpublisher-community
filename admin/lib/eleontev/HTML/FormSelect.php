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



/**
 * FormSelect is a class to generate "form_select" easily.
 * @author Evgeny Leontev
 */

class FormSelect
{
    var $form_name = 'my_form';            // form name where select with date will be
    var $form_method;

    var $select_name;                    // name for SELECT tag
    var $select_width;
    var $select_range = array();
    var $select_size;
    var $multiple;
    var $select_tag = true;
    var $on_change_submit = '';
    var $range = array();
    var $disabled = false;
    var $option_disabled = array();
    var $option_params = array();
    var $strip_values = true;

    //var $js_writed = false;
    var $css_class = '';
    var $error_pref    = '<font color="#FF0000">FORM_SELECT</font>';     // error prefix



    /**
     * Class constructor
     *
     * @access   public
     */
    function __construct() {
        $this->setFormMethod($_GET);     // default form method, to change call - setFormMethod($_GET);
    }


   /**
    * FUNCTION: setFormName -- set form name
    *
    * @param    string    $name    form name where the "form_select" will be
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
    * @param    string    $method    form method where the "form_select" will be
    *
    * @return   void
    * @access   public
    */
    function setFormMethod(&$method) {
        $this->form_method =& $method;
    }


   /**
    * FUNCTION: setSelectName -- set name for SELECT tag
    *
    * @param    string    $name    form method where the "form_select" will be
    *
    * @return   void
    * @access   public
    */
    function setSelectName($name) {
        $this->select_name = $name;
    }

    function setDisabled($var) {
        $this->disabled = ($var) ? ' disabled' : '';
    }

    function setOptionParam($val, $option) {
        $this->option_params[$val][] = $option;
    }

    function resetOptionParam($val = false, $option = false) {
        $this->option_params = array();
    }

    function setReadonly($var) {
        $this->readonly = ($var) ? ' readonly' : '';
    }

    function setSelectWidth($num) {
        $this->select_width = $num;
    }

    function setMultiple($size, $multiple = true) {
        if($multiple) { $this->multiple = ' multiple'; }
        else          { $this->multiple = false; }
        $this->select_size = $size;
    }


    function setOnChangeSubmit($action = true, $form_name = false) {
        $form_name = ($form_name) ? $form_name : $this->form_name ;
        if($action) {
            $this->on_change_submit = ' onChange="this.form.submit()"';
        } else {
            $this->on_change_submit = '';
        }
    }


   /**
    * FUNCTION: setRange -- generate range.
    *
    * @param    mixed     $sql_or_ar    sql query or array with data
    * @param    mixed     $extra_range  (optional) array($key => $value) or string
    * @param    mixed     $position     (optional) 'BEGIN' (by default) or 'END'
    *
    * @return   void
    * @access   public
    */
    function setRange($sql_or_ar, $extra_range = false, $position = 'BEGIN') {

        $range = false;
        if(is_array($sql_or_ar)) {
            $this->range = $sql_or_ar;

        } else {
            $this->_error("Nothing to set as range");
        }

        if($extra_range) {
            if(!is_array($extra_range)) {
                $extra_range = func_get_args();
                unset($extra_range[0]);
            }
            $this->range = ($position == 'BEGIN') ? $extra_range + $this->range : $this->range + $extra_range;
        }
    }


   /**
    * FUNCTION: select -- generate select tag.
    *
    * @param    boolean   $empty_tag    (optional) show or not empty OPTION tag
    * @param    string    $empty_value  (optional) value for empty OPTION tag
    * @param    string    $empty_option (optional) option(caption) for empty OPTION tag
    *
    * @return   string    generated SELECT tag
    * @access   public
    */
    function select($default_value = false, $empty_tag = false, $empty_value = '', $empty_option = '') {

        $arr_sign = ($this->multiple) ? '[]' : ''; // add array sign to name

        $select = '';
        if($this->select_tag) {
            // $select .= "\n" . '<select name="'.$this->select_name.$arr_sign.'" id="'.$this->select_name.'" class="'.$this->css_class.'" style="width: '.$this->select_width.'px; margin: 0px;"  size="'.$this->select_size.'"'.$this->multiple.$this->on_change_submit.$this->disabled.' data-role="none">'."\n";

            $size = ($this->select_size) ? ' size="'.$this->select_size.'"' : '';
            $select .= "\n" . '<select name="'.$this->select_name.$arr_sign.'" id="'.$this->select_name.'" class="'.$this->css_class.'" style="width: '.$this->select_width.'px; margin: 0px;"'.$size.''.$this->multiple.$this->on_change_submit.$this->disabled.' data-role="none">'."\n";
        }
        $select .= $this->_emptyOtionTag($empty_tag, $empty_value, $empty_option);
        $select .= $this->_optionTag($this->range, $default_value, $empty_tag);
        if($this->select_tag) { $select .= "\n</select>\n"; }

        return $select;
    }


    /*-----------------------------------------------------------------------------\
    |                           private functions                                  |
    \-----------------------------------------------------------------------------*/

   /**#@+5
    * @access private
    */
    function _optionTag($range, $default_value, $empty_tag) {

        if(!isset($this->form_method[$this->select_name])) { // no $_POST
            if(!is_array($default_value)) {
                $default_value = array($default_value);
            }
            $current = ($default_value) ? $default_value : array();
            $submit = false;

        } else {

            $submit = true;
            if(!is_array($this->form_method[$this->select_name])) {
                $current[$this->select_name] = $this->form_method[$this->select_name];
            } else {
                $current = $this->form_method[$this->select_name];
            }
        }

        $rules = RequestDataUtil::_getRules();
        $tag = array();

        foreach(array_keys($range) as $k) {

            $v = $range[$k];

            if($this->strip_values) {
                $v = str_replace($rules['search'], $rules['replace'], $v); // revert special chars to normal
                $v = str_replace('&amp;', '&', htmlspecialchars($v, ENT_QUOTES)); // 2016-11-16 changed &amp to &amp;
            }

            $options = '';
            if(isset($this->option_params[$k])) {
                foreach($this->option_params[$k] as $k1 => $param) {
                    $options .= ' ' . $param;
                }
            }

            //to correct parse 0 values
            if($k === 0) {
                if(!in_array('0', $current, true)) {
                    $k = 'zero';
                }
            }

            $selected = (in_array($k, $current)) ? ' selected' : '';
            $k = ($k === 'zero') ? 0 : $k; //to correct parse 0 values
            $tag[] = sprintf('<option value="%s"%s%s>%s</option>', $k, $options, $selected, $v);
        }

        $this->range = array();
        return implode("\n", $tag);
    }


    function _emptyOtionTag($empty_tag, $empty_value, $empty_option) {
        if($empty_tag) {
            return "\n" . '<option value="'.$empty_value.'">'.$empty_option.'</option>'."\n";
        }
    }

    function _error($msg) {
        trigger_error($this->error_pref.' '.$msg);
    }

} // <-- class end

?>