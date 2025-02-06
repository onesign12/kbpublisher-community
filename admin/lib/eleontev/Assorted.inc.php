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

// ASSORTED CLASSES AND FUNCTIONS INCLUDED ON EVERY REQUEST
class Registry
{
    var $_cache;

    function __construct() {
        $this->_cache = array();
    }

    function setEntry($key, &$item) {
        $this->_cache[$key] = &$item;
    }

    function &getEntry($key) {
        return $this->_cache[$key];
    }

    function isEntry($key) {
        return ($this->getEntry($key) !== null);
    }
    
    function destroyEntry($key) {
        return $this->_cache[$key] = null;
    }

    static function & instance() {
        static $registry;
        if (!$registry) { $registry = new Registry(); }
        return $registry;
    }
}


// to place all useful functions if not sure where to place
class ExtFunc
{

    // @example valueToArray($elem1, $elem2, $elem3, ...);
    // @return array('$elem1' => '$elem1', ...);
    static function valueToArray($elem) {
        $ar = func_get_args();
        foreach($ar as $v) {
            $new_ar[$v] = $v;
        }
        return $new_ar;
    }


    // recursive
    static function arrayToString(&$arr, $glue = ',') {
        foreach($arr as $k => $v) {
            if(is_array($v)) {
                $arr[$k] = ExtFunc::arrayToString($v, $glue);
            }
        }
        return (join($glue, $arr));
    }


    // convert multidimensional array flat array
    static function &multiArrayToOne($arr, $out = array()) {
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $out = ExtFunc::multiArrayToOne($arr[$k], $out);
            } else {
                $out[] = $arr[$k];
            }
        }

        return $out;
    }

    // just get array value by key
    static function arrayValue($arr, $key, $return = false) {
        return (isset($arr[$key])) ? $arr[$key] : $return;
    }
        
    // it does not work
    static function array_filter_recursive($arr, $callback = false) { 
        foreach (array_keys($arr) as $key) {
            if (is_array($arr[$key])) {
                if($callback) {
                    $arr[$key] = self::array_filter_recursive($arr[$key]);
                } else {
                    $arr[$key] = self::array_filter_recursive($arr[$key], $callback);
                }
            } 
        } 

        if($callback) {
            return array_filter($arr, $callback);
        } else {
            return array_filter($arr);
        }
    }
    
    
    // to insert to any possiton to array
    static function array_insert($array, $values, $position, $replace = false) {
        $offset = ($replace) ? $position : $position - 1;
        return array_slice($array, 0, $position-1, true) + $values + array_slice($array, $offset, NULL, true);  
    }
}


class WebUtil
{

    static function getIP() {

        $ip = false;
       
        #TODO use all possible $_SERVER variables for ip. Uteshev
        if    (!empty($_SERVER['HTTP_CLIENT_IP']))       { $ip = $_SERVER['HTTP_CLIENT_IP']; }
        elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        elseif(!empty($_SERVER['REMOTE_ADDR']))          { $ip = $_SERVER['REMOTE_ADDR']; }

        // fix for mysql 5.7 error - string error for INET_ATON('::1')
        $ip = ($ip == '::1') ? '127.0.0.1' :  $ip;
        
        if($ip && ip2long($ip) === false) {
            $ip = false;
        }
        
        $ip = (!$ip) ? 'UNKNOWN' :  $ip;

        return $ip;
    }

    
    // static function ipToTable($ip) {
    //     return sprintf('INET_ATON("%s")', (($ip && ip2long($ip) === false)) ? 0 : $ip);
    // }


    static function serialize_url($url) {
        return rawurlencode(serialize($url));
    }


    static function unserialize_url($url) {
        return unserialize(urldecode(stripslashes($url)));
    }


    static function getFileSize($file, $format = false){

        if(is_numeric($file)) {
            $file_size = $file+0;
        } else {
            $file_size = filesize($file);
        }

        // usefull in listing
        if($format) {
            if ($format == 'gb') {
                $file_size = number_format($file_size / 1073741824 * 100 / 100, 2, '.', ' ') ." gb";
            } elseif ($format == 'mb') {
                $file_size = number_format($file_size / 1048576 * 100 / 100, 2, '.', ' ') ." mb";
            } elseif ($format == 'kb') {
                $file_size = number_format($file_size / 1024 * 100 / 100, 2, '.', ' ') ." kb";
            } else {
                $file_size = number_format($file_size, 0, '.', ' ') . " b";
            }

        } else {

            if ($file_size >= 1073741824) {
                $file_size = round($file_size / 1073741824 * 100) / 100 ." gb";
            } elseif ($file_size >= 1048576) {
                $file_size = round($file_size / 1048576 * 100) / 100 ." mb";
            } elseif ($file_size >= 1024) {
                $file_size = round($file_size / 1024 * 100,-2) / 100 ." kb";
            } else {
                $file_size = round($file_size) . " b";
            }
        }

        return $file_size;
    }


	static function generatePassword($num_sign = 3, $num_int = 2, $num_special = 0) {
		mt_srand(time());
		$a[] = preg_replace_callback("/(.)/",
			function ($matches) {
				return chr(mt_rand(ord('m'),ord('z')));
			},str_repeat('.',$num_sign));

		$a[] = preg_replace_callback("/(.)/",
		function ($matches) {
				return chr(mt_rand(ord('A'),ord('Z')));
			}, str_repeat('.',$num_sign));

		$a[] = preg_replace_callback("/(.)/",
		function ($matches) {
			return chr(mt_rand(ord('0'),ord('9')));
		}, str_repeat('.',$num_int));

        if($num_special) {
    		$a[] = preg_replace_callback("/(.)/",
    		function ($matches) {
                $str = '!@#$%^&*()-_+.';
    			return $str[rand(0, strlen($str)-1)];;
    		}, str_repeat('.',$num_int));
        }

    	return str_shuffle(implode('', $a));      
    }


    static function sendFile($params, $filename = NULL, $attachment = true) {

        require_once 'HTTP/Download.php';
        PEAR::setErrorHandling(PEAR_ERROR_PRINT);

        session_write_close();
        ini_set('zlib.output_compression', 'Off');

        $http_download = ($attachment) ? HTTP_DOWNLOAD_ATTACHMENT : HTTP_DOWNLOAD_INLINE;
        $h = new HTTP_Download($params);
        $h->setContentDisposition($http_download, $filename);

        // if use inline it works ok but if user want/choose "save as"
        // the name for the file looks strange id number for Safary and  "O5dG__E9.html.part" for FF
        // the same ting with HTTP_DOWNLOAD_INLINE but with this user can save just by click
        // $h->setContentDisposition(HTTP_DOWNLOAD_INLINE, $data['filename']);

        return $h->send();
    }


    // function getMaxFileSize($size = 'system') {
        // return ($size == 'system') ? ini_get('upload_max_filesize') : $size;
    // }


    // return size in bytes for upload_max_filesize
    // and others identical
    static function getIniSize($key) {
        $value = ini_get($key);
        if(!is_numeric($value)) {
            $value = self::returnBytes($value);
        }

        return $value;
    }


    // return size in bytes, $value = '50M' or '21k';
    static function returnBytes($val) {

        $val = trim($val);
        $val_int = substr($val, 0, strlen($val) - 1);
        if (!$val_int) {
            return 0;
        }

        $last = strtolower($val[strlen($val)-1]);

        switch($last) {
            case 'g':
                $val_int *= 1024;
            case 'm':
                $val_int *= 1024;
            case 'k':
                $val_int *= 1024;
        }

        return $val_int;
    }


    // parse multilines ini file
    // it will skip all before defining first [block]
    static function parseMultiIni($file, $key = false) {
        $s_delim = '[%';
        $e_delim = '%]';

        $str = implode('',file($file));
        if($key && strpos($str, $s_delim . $key . $e_delim) === false) { return; }

        $str = explode($s_delim, $str);
        $num = count($str);

        for($i=1;$i<$num;$i++){
            $section = substr($str[$i], 0, strpos($str[$i], $e_delim));
            $arr[$section] = substr($str[$i], strpos($str[$i], $e_delim)+strlen($e_delim));
        }

        return ($key) ? @trim($arr[$key]) : array_map('trim', $arr);
    }
}


class DBUtil
{

    static function error($sql = null, $real_error = false, $db = false) {

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        if(!$db) {
            $db = &$reg->getEntry('db');
        }

        $format = false;
        if($conf['debug_db_error'] || $real_error) {
            $format = ($conf['debug_db_error'] == 2 || $real_error) ? 'full' : 'short';
        }

        if($conf['debug_db_error'] === 'api') {
            $err = array(
                'num' => $db->ErrorNo(),
                'msg' => $db->ErrorMsg(),
                'sql' => $sql
                );
            register_shutdown_function(array('KBApiError','shutdownDbError'), $err);
            return;

        }  elseif($conf['debug_db_error'] === 'cron') {
            return DBUtil::getErrorShortString($db->ErrorMsg(), $db->ErrorNo());

        }  elseif($conf['debug_db_error'] === 'cloud') {
            return DBUtil::getErrorShortString($db->ErrorMsg(), $db->ErrorNo());
        }

        return DBUtil::getError($db->ErrorMsg(), $db->ErrorNo(), $sql, $format);
    }


    static function getErrorBody($error_msg, $error_num, $sql, $format = 'short') {

        $html = false;
        if($format == 'full') {
            $html =  '<div style="font-size: 12px;">';
            $html .= '<b>SQL ERROR:</b> ' . $error_msg . '<br />';
            $html .= '<b>CODE:</b> ' . $error_num;
            $html .= ($sql) ? '<br /><b>SQL: </b><pre>' . print_r($sql, 1) . '</pre>' : '';
            $html .= '</div>';
        } elseif($format == 'short') {
            $html = sprintf("%s: %s", $error_num, $error_msg);
        }

        return $html;
    }


    static function getError($error_msg, $error_num, $sql, $format = 'short') {

        
        $msgs = AppMsg::getMsgs('error_msg.ini', false, 'db_error', 1);

        if($format) {
            $msgs['body'] = DBUtil::getErrorBody($error_msg, $error_num, $sql, $format);
        }

        $options = array('page' => 1);
        return BoxMsg::factory('error', $msgs, array(), $options);
    }


    static function getErrorShortString($error_msg, $error_num) {
        return sprintf("%s: %s", $error_num, $error_msg);
    }


    static function &connect($conf, $error_die = true) {

        // by defualkt it was MYSQLI_REPORT_OFF in php 8.1 default is MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
        // to avoid any incompabilty
        mysqli_report(MYSQLI_REPORT_OFF);
        
        $db = ADONewConnection($conf['db_driver']);
        @$ret = $db->Connect($conf['db_host'], $conf['db_user'], $conf['db_pass'], $conf['db_base'], true);

        if(!$ret) {
            if($error_die) {
                 die (DBUtil::error(false, true, $db));
            } else {
                $ret = false;
                return $ret;
            }
        }

        $db->SetFetchMode(ADODB_FETCH_ASSOC);
        // $db->ADODB_COUNTRECS = false; // 29-01-2025 this one ig GLOBAL now  and it woked without it
        $db->debug = (!empty($conf['debug_db_sql']) && !isset($_GET['ajax'])) ? 1 : 0;

        // set connection names, could be required for some situations
        if(!empty($conf['db_names'])) {
            $sql = sprintf("SET NAMES '%s'", $conf['db_names']);
            $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        }

        // sql_mode, remove ONLY_FULL_GROUP_BY, no call in sphinx
        if(empty($conf['sphinx'])) {
            $sql = "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))";
            $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        } else {
            // $sql = "SET sql_mode=''";
            // $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
        }

        //echo "<pre>"; print_r($db); echo "</pre>";
        //$db->fnExecute = 'CountExecs';
        //$db->fnCacheExecute = 'CountCachedExecs';

        return $db;
    }


    static function beginTrans($db) {
        $db->_Execute($sql = 'SET AUTOCOMMIT=0') or die(db_error($sql));
        $db->_Execute($sql = 'START TRANSACTION') or die(db_error($sql));
    }


    static function commitTrans($db) {
        $db->_Execute($sql = 'COMMIT') or die(db_error($sql));
        $db->_Execute($sql = 'SET AUTOCOMMIT=1') or die(db_error($sql));
    }


    static function setTimezone($db, $timezone) {
        $sql = sprintf("SET time_zone = '%s'", $timezone);
        $db->_Execute($sql) or die (DBUtil::error(false, true, $db));
    }
}



class DebugUtil
{

    static function getDebugBlock($arr) {
        $str_pre = '<div><pre>%s</pre></div>';
        $str_br = '<span>%s</span><br/>';
        
        $html = array();
        $html[] = '<div style="margin-top: 5px; font-size: 12px; width: 100%;">';
        $html[] = '<div style="text-align: center; margin: 0 auto;">';

        foreach($arr as $k => $v) {
            $str = is_array($v) ? $str_pre : $str_br;
            $msgs = array();
            if(isset($v['kb_recent_setting_'])) {
                $v['kb_recent_setting_'] = 'UNSETTED';
            }
            
            $msgs['body']  = ($v) ? sprintf($str, htmlspecialchars(print_r($v, 1))) : false;

            $html[] = BoxMsg::factory('hint', $msgs);
        }

        $html[] = '</div></div>';

        return implode('', $html);
    }


    static function getDebugGlobal() {
        $arr = array('GET'=>$_GET, 'POST'=>$_POST, 'SESSION'=>$_SESSION, 'COOKIE'=>$_COOKIE, 'FILES'=>$_FILES);
        return self::getDebugBlock($arr);
    }


    static function getDebug() {
        $reg =& Registry::instance();
        $arr = $reg->getEntry('debug');

        $ret = array();
        if($arr) {
            foreach(array_keys($arr) as $num) {
                $ret[] = self::getDebugBlock($arr[$num]);
            }
        }

        return ($ret) ? implode('', $ret) : '';
    }


    static function getDebugInfo () {

        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');

        $kbp_debug_info = self::getDebug();

        if($conf['debug_info']) {
            $kbp_debug_info .= self::getDebugGlobal();
        }
        if($conf['debug_speed']) {
            $kbp_debug_info .= timeprint("%min %max graf");
        }

        return $kbp_debug_info;
    }
    
    
    static function _isDebugSpeed() {
        static $debug_speed;
        if($debug_speed === NULL) {
            $reg =& Registry::instance();
            $debug_speed = $reg->getEntry('conf')['debug_speed'];
        }
        
        return $debug_speed;
    }
    
    
    static function debug_backtrace() {
        foreach(debug_backtrace() as $k => $v) {
            echo $v['file'], ' - ', $v['line'], '<br>';
        }
    }
    
    
    static function timestart($key) {
        if(self::_isDebugSpeed()) {
            timestart($key);
        }
    }


    static function timestop($key) {
        if(self::_isDebugSpeed()) {
            timestop($key);
        }
    }
}



// SOME FUNCTIONS
function db_error($sql = null, $real_error = false) {
    return DBUtil::error($sql, $real_error);
}

function &db_connect($conf) {
    return DBUtil::connect($conf);
}


// to make siglton from any class
// $reg =& Singleton('ClassName');
function & Singleton($class) {
    static $instances = array();

    // if (!is_array($instances)) {
         // $instances = array();
    // }

    if (!isset($instances[$class])) {
        $instances[$class] = new $class;
    }

    return $instances[$class];
}


/**
 * Collect debug info to display bottom of page.
 *
 * @param mixed $vars
 * @param string  $title
 * @return void
 */
function debug($vars, $title = false) {
    static $num = 0;
    static $debug = array();

    $debug[$num++][$title] = $vars;
    $reg =& Registry::instance();
    $reg->setEntry('debug', $debug);
}

function debug_trace($title = false) {
    foreach(debug_backtrace() as $k => $v) {
        $vars[] = $v['file'] . ' - ' . $v['line'];
    }
    debug($vars, $title);
}


// build hidden fields from array of params
function http_build_hidden($formdata, $encode = false, $numeric_prefix = null) {

    // If $formdata is an object, convert it to an array
    if (is_object($formdata)) {
        $formdata = get_object_vars($formdata);
    }

    // Check we have an array to work with
    if (!is_array($formdata)) {
        trigger_error('http_build_hidden() Parameter 1 expected to be Array or Object. Incorrect value given.',
                       E_USER_WARNING);
        return false;
    }

    // If the array is empty, return null
    if (empty($formdata)) {
        return;
    }

    // Start building the query
    $tmp = array ();
    foreach ($formdata as $key => $val) {
        if (is_integer($key) && $numeric_prefix != null) {
            $key = $numeric_prefix . $key;
        }

        if (is_scalar($val)) {
            $str = '<input type="hidden" name="%s" id="%s" value="%s" />';
            if($encode) {
                $name = urlencode(urldecode($key));
                array_push($tmp, sprintf($str, $name, $name, urlencode(urldecode($val))));
            } else {
                array_push($tmp, sprintf($str, $key, $key, $val));
            }

            continue;
        }

        // If the value is an array, recursively parse it
        if (is_array($val)) {
            array_push($tmp, __http_build_hidden($val, $key, $encode));
            continue;
        }
    }

    return str_replace(array('%5B','%5D'), array('[',']'), implode("\n", $tmp));
}


 // Helper function
function __http_build_hidden ($array, $name, $encode) {

    $is_list = (array_values($array) === $array);

    $tmp = array ();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            array_push($tmp, __http_build_hidden($value, sprintf('%s[%s]', $name, $key), $encode));

        } elseif (is_scalar($value)) {
            $key = ($is_list) ? '' : $key;
            $str = '<input type="hidden" name="%s[%s]" value="%s" />';
            if($encode) {
                array_push($tmp, sprintf($str,
                    urlencode(urldecode($name)),
                    urlencode(urldecode($key)),
                    urlencode(urldecode($value))));
            } else {
                array_push($tmp, sprintf($str, $name, $key, $value));
            }


        } elseif (is_object($value)) {
            array_push($tmp, __http_build_hidden(get_object_vars($value), sprintf('%s[%s]', $name, $key), $encode));
        }
    }

    return implode("\n", $tmp);
}


if (!function_exists('array_diff_key')) {

    function array_diff_key($arr_reduced, $arr_needed) {
        $r = array();
        foreach($arr_needed as $v) {
            $r[$v] = $arr_reduced[$v];
        }

        return $r;
    }
}


if (!function_exists('array_intersect_key')) {

    function array_intersect_key() {

        $args = func_get_args();
        $array_count = count($args);
        if ($array_count < 2) {
            user_error('Wrong parameter count for array_intersect_key()', E_USER_WARNING);
            return;
        }

        // check arrays
        for ($i = $array_count; $i--;) {
            if (!is_array($args[$i])) {
                user_error('array_intersect_key() Argument #' . ($i + 1) . ' is not an array', E_USER_WARNING);
                return;
            }
        }

        // intersect keys
        $arg_keys = array_map('array_keys', $args);
        $result_keys = call_user_func_array('array_intersect', $arg_keys);

        // build return array
        $result = array();
        foreach($result_keys as $key) {
            $result[$key] = $args[0][$key];
        }

        return $result;
    }
}


function array_splice_assoc(&$input, $offset, $length, $replacement = array()) {
    $replacement = (array) $replacement;
    $key_indices = array_flip(array_keys($input));
    if (isset($input[$offset]) && is_string($offset)) {
            $offset = $key_indices[$offset];
    }
    if (isset($input[$length]) && is_string($length)) {
            $length = $key_indices[$length] - $offset;
    }

    $input = array_slice($input, 0, $offset, TRUE)
            + $replacement
            + array_slice($input, $offset + $length, NULL, TRUE);
}

?>