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


class SetConfiguration
{
    
    var $items = array();
    var $passed = 1;
    
    var $passed_value = 'image'; //or text 
    var $passed_color = array(1 => 'green', 2 => 'grey', 3 => 'red');
    var $passed_key = array(1 => 'pass', 2 => 'limit', 3 => 'failed');
    var $passed_img = '<img src="../admin/images/icons/check_%s.svg">';
    
    
    static function parseSetting($option) {
        if($option == 1)       { $option ='ON'; }
        elseif(empty($option)) { $option ='OFF'; }
        
        return $option;
    }
    
    
    function getPassedKey() {
        $key = ($this->passed) ? $this->passed : 3;
        return $this->passed_key[$key];
    }
    
    
    function setItem($values, $passed, $rule = 'required') {
        static $i = 0; $i++;
        
        $this->items[$i]['title'] = $values[0];
        $this->items[$i]['recommended'] = $this->parseSetting($values[1]);
        $this->items[$i]['current'] = $this->parseSetting($values[2]);
        
        $not_passed_value = ($rule == 'required') ? 3 : 2;
        $passed = ($passed) ? 1 : $not_passed_value;
        if($passed == 3) {
            $this->passed = false;
        }
        
        if($this->passed && $passed == 2) {
            $this->passed = 2;
        }
        
        if($this->passed_value == 'image') {
            $this->items[$i]['passed'] = sprintf($this->passed_img, $this->passed_color[$passed]);
        
        } elseif($this->passed_value == 'text') {
            $this->items[$i]['passed'] = $this->passed_text[$passed];
        
        } else {
            $this->items[$i]['passed'] = $passed;
        }
        
        
        return $this->items[$i];
    }
    
    
    function isPassed() {
        return $this->passed;
    }
    
    
    function getItems() {
        return $this->items;
    }
}


/*
$setting = new SetConfuguration();
$setting->setItem(array(1,2,3), 1);
$setting->setItem(array(1,2,3), 1);


echo "<pre>"; print_r($setting); echo "</pre>";
*/
?>