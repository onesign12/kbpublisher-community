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

// to keep login logs in database and navigate in UI

class LoggerModel extends BaseModel
{

    var $tables = array('log_login');
    var $_log = array();


    // auth types str to number
    static function getAuthTypes() {
        
        $types = array(
            'local'  => 1,
            'remote' => 2,
            'user'   => 3, // as user
            'api'    => 5,
            'auto'   => 6, // local auto (cookie)
            'ldap'   => 7,
            'saml'   => 8,
            'social' => 9
        );
        
        return $types;
    }

    
    function addLogin($user_id, $username, $type, $exitcode, $user_ip = false) {

        $_log = &$this->getLoginLogInstance();
        $output = $_log->getBuffer('_login');

        $types = self::getAuthTypes();

        if(!is_numeric($type)) {
            $type = isset($types[$type]) ? $types[$type] : 1;
        }

        // set to 0 and not count for wrong tries for real user 
        // if login as user from admin area
        $active = ($type == 3) ? 0 : 1;

        // for remote, ldap and saml write last login to file to debug 
        // 4 = remote auto not in use 2016-12-14
        // 7 = ldap 2016-12-14
        if(in_array($type, array(2,7,8,9))) {
            $this->writeLoginLogFile($output);
        }

        if($user_ip === false) {
            $user_ip = WebUtil::getIP();
            $user_ip = ($user_ip == 'UNKNOWN') ? 0 :  $user_ip;
        }
        
        // $username = (_strlen($username) > 50) ? _substr($username, 0, 50) : $username;
        $username = RequestDataUtil::substrVars($username, 50);
        $output = addslashes($output);
        $sql = "INSERT {$this->tbl->log_login}
               SET user_id = '{$user_id}', 
                   user_ip = IFNULL(INET_ATON('{$user_ip}'), 0), 
                   username = '{$username}',
                   login_type = '{$type}',
                   output = '{$output}',
                   exitcode = '{$exitcode}',
                   active = '{$active}'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }


    function writeLoginLogFile($data = false) {
        
        if($data === false) {
            $_log = &$this->getLoginLogInstance();
            $data = $_log->getBuffer('_login');
        }
        
        $filename = APP_CACHE_DIR . 'last_remote_login.log';
        return FileUtil::write($filename, $data, true);
    }


    function &getLoginLogInstance() {
        if(empty($this->_log['_login'])) {
            $a['_login'] = array('type' => 'buffer');
            $this->_log['_login'] = new LogUtil($a);
        }
        
        return $this->_log['_login'];
    }


   /**
    * @param string $msg Message text (may be template for sprintf())
    * @param string $session Possible session identifier (printed within each log record).
    */
    function putLogin($msg, $session= NULL) {
        $_log = &$this->getLoginLogInstance();
        $_log->put('_login', $msg, $session);
    }


    function saveLoginLogData() {
        $_SESSION['log_data_']['login_'] = serialize($this->_log['_login']);
    }

    
    function setLoginLogData() {
        if(isset($_SESSION['log_data_']['login_'])) {
            $this->_log['_login'] = unserialize($_SESSION['log_data_']['login_']);
        }
    }
    
}

?>