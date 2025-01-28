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


class BoxMsgParser extends BoxMsg
{
    
    var $titles      = array('title', 'body', 'header');
    var $box_titles  = array('box', 'hint', 'error', 'success');     
    //var $box_classes = array('BoxMsg', 'HintMsg', 'ErrorMsg', 'SuccessMsg');
                                                          
    var $s_delim = '<';
    var $e_delim = '/>';
    var $parced = array();
    var $blocks = array();
    

    function &parse($str) {
        $box_titles = implode('|', $this->box_titles);
        preg_match_all('#'.preg_quote($this->s_delim).'('.$box_titles.'):(.+?)'.preg_quote($this->e_delim).'#is', $str, $match);
        
        foreach($match[1] as $k => $v) {
            $arr[$v] = $this->_setTitles($match[2][$k]); 
        }

        foreach($arr as $k => $v) {
            $s_delim = $this->s_delim . $k . ':';
            $e_delim = $v['body'] . $this->e_delim;
            
            $class = ucfirst(strtolower($k)). 'Msg';
            $box = new $class;
            $box->setMsgs($v);
            //$str = &$this->_substrReplace($str, $box->get(), $s_delim, $e_delim, false);
            $str = &Replacer::substrReplace($str, $box->get(), $s_delim, $e_delim, false);
        }
        
        return $str;
    }
    
    
    function _setTitles(&$arr) {
        $arr = explode('|',$arr);
        if(count($arr) == 1) {
            $arr1['body'] = $arr[0];
        } else {
            foreach($arr as $k => $v) {
                $arr1[$this->titles[$k]] = $v;
            }
        } 

        
        return $arr1;
    }
    
    
/*
    function &_substrReplace($string, $replace, $s_delim, $e_delim, $with_delims = true) { 
        // if not to check, can lead to unexpected result
        if (strpos($string, $s_delim) !== false && strpos($string, $e_delim) !== false) {
            $s_point = strpos($string, $s_delim); // number
            $e_point = strpos($string, $e_delim); // number
            
            if ($with_delims) {
                $string = substr_replace($string, $replace, $s_point + strlen($s_delim), $e_point - $s_point - strlen($s_delim)); // delims will stay in string         
            } else {
                $string = substr_replace($string, $replace, $s_point, ($e_point - $s_point + strlen($e_delim))); // delims will be replaced as well
            }
        } 
        return $string;
    } 
*/
}

$str = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Untitled</title>
</head>

<body>

Text before box:
<box:Example|Content />
Text after box:

</body>
</html>


$box = new BoxMsgParser;
echo $box->parse($str);


?>