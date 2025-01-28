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
// $Id: BoxMsg.php,v 1.0 2004/10/15 16:57:44 root Exp $

/**
 * BoxMsg is a set of classes to display messages.
 *
 * @since 15/10/2004
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public

 * EXAMPLE:
 * echo HintMsg::quickGet('Test');
 * 
 * $hint = new SuccessMsg();
 * $hint->setMsg('title','SUCCESS {url}');
 * $hint->setMsg('body','If your browser does not support automatic redirection 
 *                       click this link <a h ref="{url}">{url}</a>');
 * $hint->assignVars('url', '555555');
 * echo $hint->get();
 */

class BoxMsg
{     

    var $error_pref = 'BoxMsg';
    var $vars = array();
    var $img_dir = '';
    var $img;    
    var $div_id;
    var $div_class = 'boxMsgDiv';
    
    var $msg   = array('title'    =>'',
                       'body'     =>'',
                       'header'   =>'');
                         
    var $options = array();
    var $replacer;
    
    var $close_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24">
            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"/>
        </svg>';
    
    
    function __construct () {
        $this->replacer = new Replacer();
    }    
    
    
    static function factory($class, $msgs = array(), $vars = array(), $options = array()) {
        
        $class = ucfirst($class) . 'Msg';
        $m = new $class;
        $m->setOptions($options);
        $m->div_id = $m->getOption('div_id');
        
        if($msgs) {
            if(!is_array($msgs)) {
                $args = func_get_args();
                unset($args[0]);
                $msgs = $m->getMsgsArrayFromArgs($args);
            }
            
            $m->assignVars($vars);
            $m->setMsgs($msgs);            
            $m = $m->get();
        }
        
        return $m;
    }
    
    
    function setMsg($msg_key, $msg) {
        $this->msg[$msg_key] = $msg;
    }    
        
    
    function setMsgs($msgs) {
        
        if(!is_array($msgs)) {     
            $msgs = $this->getMsgsArrayFromArgs(func_get_args());
        } 
        
        foreach($msgs as $k => $v) {
            $this->setMsg($k, $v);
        }
    }
    
    
    function getMsgsArrayFromArgs($arr) {
        $msg_keys = array_keys($this->msg);
        foreach($arr as $k => $v) {
            $ar[current($msg_keys)] = $v;
            next($msg_keys);
        }

        return $ar;
    }
    
    
    function setMsgsIni($file, $msg_file_key) {
        return $this->setMsgs(GetMsg::parseIni($file, $msg_file_key));
    }
    
    
    function get() {
        foreach($this->msg as $k => $v) {
            if(!$v) { continue; }
            $this->msg[$k] = $this->replacer->parse($v);
        }
        
        $msg = $this->getHtml();
        
        if($this->getOption('page')) {
            $charset = ($ch = $this->getOption('charset')) ? $ch : 'UTF-8';
            $msg = $this->getPageHtml($msg, $charset);
        }
        
        return $msg;
    }
    
    
    function assignVars($var, $value = false) {
        $this->replacer->assignVars($var, $value);
    }
    
    
    function setOptions($val) {
        $this->options = $val;
    }
    
    
    function getOption($key) {
        return @$this->options[$key];
    }
    
    
    function getPageHtml($msg, $charset = 'UTF-8') {
        $html = '<!DOCTYPE HTML>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=%s" />
                <meta name="robots" content="none" />
                <link rel="stylesheet" type="text/css" href="../client/skin/box.css">
                <link rel="stylesheet" type="text/css" href="client/skin/box.css">
            </head>
            <body style="padding: 15px;">
                %s
            </body>
            </html>';
        
        return sprintf($html, $charset, $msg);
    }
    

    function &getHtml() {
        
        if (!$this->div_id) { // generating an id
            $this->div_id = md5(time() . $this->msg['body']);
        }
        
        $div_id = ($this->div_id) ? 'id="'.$this->div_id.'"' : '';
        
        $classes = array($this->div_class, $this->class_name);
        
        $div_container = '<div %s class="%s">';
        $html = sprintf($div_container, $div_id, implode(' ', $classes)) . "\n";
        
        if ($this->getOption('close_btn')) {
            $str = '<div class="close"><a href="#" onclick="$(\'#%s\').fadeOut(1500); return false;">%s</a></div>';
            $html .= sprintf($str, $this->div_id, $this->close_icon);            
        }
        
        $title = $this->msg['title'];
        if($title) {
            $title_block = '<div class="title2">%s</div> ';
            $html .= sprintf($title_block, $title);
        }
        
        $html .= '';
        $html .= ($this->msg['header']) ?  '<b>'.$this->msg['header'].'</b><br />' : '';
        $html .= $this->msg['body'];
        
        $html .= '</div>' . "\n";
        
        if ($this->getOption('effect')) {
            $js = '<script>$(document).ready(function() {$("#%s").%s;});</script>';
            $html .= sprintf($js, $this->div_id, $this->getOption('effect'));
        }
        
        return $html;
    }
}


class HintMsg extends BoxMsg
{

    var $class_name = 'hint';
    
}



class ErrorMsg extends BoxMsg
{
    
    var $class_name = 'error';
    
}



class SuccessMsg extends BoxMsg
{
                         
    var $class_name = 'success';
                             
}


class InfoMsg extends BoxMsg
{
                         
    var $class_name = 'info';
                             
}



/*
$msg = BoxMsg::factory('hint');
$msg->setMsgs('title', 21321);
echo $msg->get();

$msg = BoxMsg::factory('error');
$msg->setMsgs('title', 21321);
echo $msg->get();

$msg = BoxMsg::factory('success');
$msg->setMsgs('title', 21321);
echo $msg->get();

$msg = new BoxMsg();
$msg->setMsgs('title', 21321);
echo $msg->get();
*/

/*
$hint = new SuccessMsg();
$hint->setMsg('title','SUCCESS {url}');
$hint->setMsg('body','If your browser does not support automatic redirection 
                      click this link <a href="{url}">{url}</a>');
$hint->assignVars('url', '555555');
echo $hint->get();
*/
?>