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

class Validator
{

    var $msg = "Error(s) occur!";
    var $errors = array();
    var $values = array();
    var $is_error = false;
    var $display_all = true;
    var $required_set = false; // if required values should exits in values or not 

    var $js;
    var $use_jscript = false;



    function __construct(&$values, $display_all = true) {
        $this->setValues($values);
        $this->setDisplayMethod($display_all);

        $this->js = new ValidatorJscript();
    }

    
    function csrf($msg = 'csfr_msg', $val_key = 'atoken', $session = true) {
        $ret = false;
        if(isset($this->values[$val_key])) {
            $ret = Auth::validateCsrfToken($this->values[$val_key], $session);
        }
        
        if(!$ret) {
            $this->setError($msg, $val_key, 'csrf');
            $this->setDisplayMethod(false);
        }
    }


    function csrfCookie($msg = 'csfr_msg', $val_key = 'atoken') {
        $this->csrf($msg, $val_key, false);
    }


    function regex($msg, $rule, $val_key, $required = true) {
        if(!Validate::regex($rule, $this->values[$val_key], $required)) {
            $this->setError($msg, $val_key, $rule);
        }

        $this->js->regex($msg, $rule, $val_key, $required);
    }


    function between($msg, $val_key, $min, $max = false, $required = true) {
        $val = (float) $this->values[$val_key];
        if(!Validate::between($this->values[$val_key], $min, $max, $required)) {
            $this->setError($msg, $val_key, 'inRange');
        }

        $this->js->between($msg, $val_key, $min, $max, $required);
    }


    function length($msg, $val_key, $min, $max = false, $required = true) {
        $val = strlen($this->values[$val_key]);
        if(!Validate::between($val, $min, $max, $required)) {
            $this->setError($msg, $val_key, 'inRange');
        }

        $this->js->between($msg, $val_key, $min, $max, $required);
    }


    function compare($msg, $key_check_val, $with_val, $operator = '==') {
        if(!Validate::compare($this->values[$key_check_val], $this->values[$with_val], $operator)) {
            $this->setError($msg, $key_check_val, 'compare');
        }

        $this->js->compare($msg, $key_check_val, $with_val, $operator);
    }


    function required($msg, $val_key) {

        if(!is_array($val_key))  { $val_key = array($val_key); }

        $missed = array();
        foreach($val_key as $k => $v) {
            
            if(!$this->required_set ) {
                if(!Validate::required(@$this->values[$v])) {
                    $missed[] = $v;
                }
                
            // required value should exits in $this->values
            } elseif(isset($this->values[$v]) && !Validate::required($this->values[$v])) {
                $missed[] = $v;
            }
        }

        if($missed) {
            $this->setError($msg, $missed, 'required');
        }

        $this->js->required($msg, $missed);
    }


    function writeable($msg, $val_key) {
        if(!Validate::writeable($this->values[$val_key])) {
            $this->setError($msg, $val_key, 'writeable');
        }
    }


    function setError($msg, $field, $rule = false, $type = 'key') {

        // not to display all errors
        if($this->display_all != true && $this->is_error) { return; }

        $types = array('key', 'custom', 'formatted', 'parsed');
        if(!in_array($type, $types)) {
            trigger_error("Validator::setError - Wrong error type: <b>" . $type . "</b>");
        }

        $this->is_error = true;
        $this->errors[$type][] = array('msg'  => $msg,
                                       'field'=> $field,
                                       'rule' => $rule
                                        );
    }


    static function parseError($msg, $field, $rule = false, $type = 'key') {
        $error[$type][] = array(
            'msg'  => $msg,
            'field'=> $field,
            'rule' => $rule
        );
        
        return $error;
    }


    function setDisplayMethod($method) {
        $this->display_all = $method;
    }


    function setRequiredMethod($val) {
        $this->required_set = $val;
    }


    function setValues(&$values) {
        $this->values =& $values;
    }


    function &getErrors() {
        return $this->errors;
    }


    function getJscript() {
        return $this->js->getScript();
    }


    function getHtml() {

        $ret[] = sprintf("<b>%s</b><ul>", $this->msg);
        foreach($this->errors as $type => $error) {
            foreach($error as $k => $v) {
                $ret[] = sprintf("<li>%s</li>", $v['msg']);
            }
        }
        $ret[] = "</ul>";

        return implode("\n", $ret);
    }


    function display() {
        echo $this->getHtml();
    }
}


class ValidatorJscript
{

    var $script_box;
    var $script = array();
    var $use_script_box = true;


    function __construct() {
        $this->script_box = GetJscript::getScriptBox();
    }


    function regex($msg, $rule, $val_key, $required = true) {

        @$reg = GetJscript::getRegex($rule);

        if($rule == 'email') {
            $this->email($msg, $val_key, $required);

        } elseif(!$reg) {
            return;

        } else {
            $html[] = GetJscript::getElement('f', $val_key);
            $html[] = 're = new RegExp("' . $reg . '");';
            $html[] = 'r = re.test(f.value);';
            $html[] = ($required) ? 'if(!r)' : 'if(f.value && !r)';
            $html[] = GetJscript::getErrorRoutine('f', $msg);

            $this->setScript($html);
        }
    }


    function required($msg, $val_key) {

        if(!is_array($val_key))  { $val_key = array($val_key); }

        $html = array();
        foreach($val_key as $k => $v) {
            $html[] = GetJscript::getElement('f', $v);
            $html[] = 'if(isBlank(f.value))';
            $html[] = GetJscript::getErrorRoutine('f', $msg);
        }

        $this->setScript($html);
    }


    function email($msg, $val_key, $required = true) {

        $html[] = GetJscript::getElement('f', $val_key);
        $html[] = ($required) ? 'if(!isValidEmailStrict(f.value))'
                              : 'if(f.value && !isValidEmailStrict(f.value))';
        $html[] = GetJscript::getErrorRoutine('f', $msg);

        $this->setScript($html);
    }


    function between($msg, $val_key, $min, $max = false, $required = true) {

        $html[] = GetJscript::getElement('f', $val_key);

        if($required) {
            $str = sprintf("if(!isBetween(f.value, '%s', '%s'))", $min, $max);
        } else {
            $str = sprintf("if(f.value && !isBetween(f.value, '%s', '%s'))", $min, $max);
        }

        $html[] = $str;
        $html[] = GetJscript::getErrorRoutine('f', $msg);

        $this->setScript($html);
    }


    function compare($msg, $key_check_val, $with_val, $operator = '==') {

         $html[] = GetJscript::getElement('f', $key_check_val);
         $html[] = GetJscript::getElement('f1', $with_val);

        if ('==' != $operator && '!=' != $operator) {

            $html[] = 'checked_value = parseFloat(f.value);';
            $html[] = 'checker_value = parseFloat(f1.value);';

        } else {

            $html[] = 'checked_value = f.value;';
            $html[] = 'checker_value = f1.value;';
        }

        $html[] = sprintf('r = (checked_value %s checker_value);', $operator);
        $html[] = 'if(r == false)';
        $html[] = GetJscript::getErrorRoutine('f1', $msg);

        $this->setScript($html);
    }


    function setScript($array) {
        $this->script[] = (is_array($array)) ? implode("\n", $array) : $array;
    }


    function getScript() {

        $script = implode("\n", $this->script);

        if($this->use_script_box) {
            $script = str_replace('{script}', $script, $this->script_box);
        }

        return $script;
    }
}


class GetJscript
{

    static function getScriptBox() {
        $html =
        '<script>
        <!--
            function __construct() {

                {script}

                return true;
            }
        //-->
        </script>';

        return $html;
    }


    static function getElement($var, $val) {
        $html[] = sprintf("%s = document.getElementById('%s');", $var, $val);
        $html[] = sprintf("if(!%s) { %s = form.%s };", $var, $var, $val);
        return implode("\n", $html);
    }


    static function getErrorRoutine($field, $msg) {
        $html = "
        {
            alert('%s');
            %s.focus();
            %s.select();
            return false;
        }";

        return sprintf($html, $msg, $field, $field);
    }


    static function getRegex($rule) {
        $regex = array('email'         => '^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$',
                       'lettersonly'   => '^[a-zA-Z]+$',
                       'alphanumeric'  => '^[a-zA-Z0-9]+$',

                       // does not work
                       //'numeric'       => '(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)',

                       // does not work - invaliid quantifier {
                       //'nopunctuation' => '^[^().\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$', // does not work

                       'nonzero'       => '^-?[1-9][0-9]*'
                       );

        return $regex[$rule];
    }


    static function getHighlightFunction() {
        $str =
        "function () {

        }

        ";
    }
}



//$js = new ValidatorJscript();
//$js->required('321321', 'first_name');
//echo "<pre>"; print_r($js->getScript()); echo "</pre>";
//echo "<pre>"; print_r($js); echo "</pre>";



// EXAMPLE

/*
$vars['first_name'] = 1;
$vars['last_name'] = 0;
$vars['email'] = 'info@ezactive';
$vars['password_1'] = 'wqewr';
$vars['password_2'] = 'dadssf';


$v = new Validator($vars);
$v->required('Required_msg', 'first_name');
$v->required('required2Array', array('first_name', 'last_name'));
//$v->regex('Email_msg', 'email', 'email');

//$v->compare('Compare_msg', 'password_1', 'password_2');
//$v->length('between_msg', 'password_1', 6);

$v->setError('custom_msg', 'password_1', 'custom');
$v->setError('custom_msg', 'password_2', 'custom', 'custom');
$v->setError('custom_msg', 'password_2', 'custom', 'formatted');


$v->display();
echo "<pre>"; print_r($v->getErrors()); echo "</pre>";
//echo "<pre>"; print_r($v->getScript()); echo "</pre>";
*/
?>