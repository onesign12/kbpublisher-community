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



class SimpleFilter extends FormSelect
{

    var $tpl;
    var $chapter;
    //var $all_values_msg;
    var $sql_field;             // sql field to select for example "rc.cat_id"
    var $ses_name = 'filter';
    var $sql = '1=1';
    var $sql2 = '1=1';
    var $id = '';

    var $form_name = 'simple_form';
    var $select_name = 'filter';
    var $css_class = 'color';

    function __construct($form_method = false) {
        $form_method = ($form_method) ? $form_method : $_GET;
        $this->setFormMethod($form_method);
    }


    function setVars($sql_field) {
        $this->sql_field = $sql_field;

        $ret = $this->form_method[$this->select_name];
        $ret = ($ret == 'F_NULL') ? 0 : $ret;

        if(isset($this->form_method[$this->select_name]) && $this->form_method[$this->select_name] != 'all') {
            $this->sql = "$sql_field = '$ret'";
        }
    }

    function generateSelect($default = false) {

        $html = '<form name="'.$this->form_name.'">';

        //unset($this->form_method[$this->select_name]);// skip $_GET['filter']
        $html .= http_build_hidden($arr, true);

        $this->setOnChangeSubmit(true, $this->form_name);
        $this->setSelectWidth(200);
        $this->setSelectName($this->select_name);

        $html .= $this->select($default);
        $html .= '</form>';

        return $html;
    }
}
?>