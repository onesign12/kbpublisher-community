<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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

class Logger
{

    static $subj = 'KBPublisher error log';
    static $filename = 'error.log';


    static function instance() {
        static $log;

        if ($log === null) {
            $_log = array();
            $_log['_run'] = array('type' => 'buffer');
            $log = new LogUtil($_log);
        }

        return $log;
    }

 
    static function log($msg) {
        self::put($msg);
        self::write();
    }
    

    static function put($msg) {
        $args = func_get_args();
        $msg = self::parseMsg($msg, $args);
        
        $bt = self::getBacktrace(debug_backtrace());
        $str = '%s in %s on line %s';
        $msg = sprintf($str, $msg, $bt['file'], $bt['line']);
        
        $log = self::instance();
        $log->put(array('_run'), $msg);
    }
    
    
    static function write() {
        $log = self::instance();
        if($msg = $log->getBuffer('_run')) {
            
            $pool = new MailPool(APP_MAIL_POOL_DIR);
            $dir = $pool->getPeriodDir('daily');
        
            if ($dir) {
                
                $fname = $dir . self::$filename;
                $to = SettingModel::getQuick(134, 'admin_email');
                
                /* append if exists */
                if (file_exists($fname)) {
                    if (!FileUtil::write($fname, $msg, false)) {
                        // need to undestand where to write ...
                        // trigger_error('Cannot append to the message file: '. $fname);
                    } else {
                        return true;
                    }
                    
                } else {
                    if (!$pool->createFile($fname, $to, self::$subj, $msg)) {
                        // trigger_error('Cannot create the message file: '. $fname);
                    } else {
                        return true;
                    }
                }
            }
        }
    }
    
    
    static function getBacktrace($backtrace) {
        foreach(['log', 'put'] as $v) {    
            $bt = array_filter($backtrace, function ($var) use ($v) {
                return ($var['function'] == $v);
            });
            
            if($bt) {
                $bt = current($bt);
                break;
            } else {
                $bt = $backtrace[count($backtrace)-1];
            }
        }
        
        return $bt;
    }
    
    
    static function parseMsg($msg, $args) {
        if(count($args) > 1) {
            $msg = call_user_func_array('sprintf', $args);
        }
    
        return $msg;
    }
    
}

?>