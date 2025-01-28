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


class Auth extends AppModel
{

    var $sql;
    var $cookie_expire = '2 week';
    var $check_ip = true;
    var $run_session_regenerate_id = true;
    var $user_status = 1; // if false we do not check any status
    
    
    static function factory($auth) {
        $class = 'Auth' . $auth;
        return new $class;
    }
    
    
    function setTable($table) {
        $this->tbl->table = $table;
    }    
    
    
    function setSql($sql) {
        $this->sql = $sql;
    }

    
    function setCheckIp($check_ip) {
        $this->check_ip = $check_ip;
    }    
    
    
    function setUserStatus($status) {
        $this->user_status = $status;
    }
    
    
    function setSessionRegenerateId($value) {
        $this->run_session_regenerate_id = $value;
    }
    
    
    function setLastAuth($user_id, $date) {
        $sql = "UPDATE {$this->tbl->user} SET lastauth = '%d', 
        date_updated=date_updated WHERE id = '%d'";
        $sql = sprintf($sql, $date, $user_id);
        $this->db->Execute($sql) or die(db_error($sql));    
    }
    
    
    function isPasswordExpiered($user_id, $days) {
        $sql = "SELECT lastpass FROM {$this->tbl->user} 
        WHERE id = %d 
        AND DATEDIFF(NOW(), FROM_UNIXTIME(lastpass)) > %d";
        $sql = sprintf($sql, $user_id, $days);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('lastpass');
    }
    
    
    function _getAuth_old($username, $password, $md5 = true) {
        
        $auth = false;
        
        if (!empty($username) && !empty($password)) {

            $sql = "
            SELECT 
                u.id AS user_id, 
                u.username
            FROM 
                {$this->tbl->user} u
            WHERE 1
                AND u.username = '%s'
                AND u.password = '%s'
                AND u.active = 1";
            
            // addslashes added when registered, md5(addslashes($password)); 
            $password = addslashes(stripslashes($password)); 
            $password = ($md5) ? MD5($password) : $password;
            $username = addslashes(stripslashes($username));

            $sql = sprintf($sql, $username, $password);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
            if($result->RecordCount() == 1) { 
                $auth = $result->FetchRow();
            }
        }

        return $auth;
    }
    
    
    function getAuth($username, $password, $md5 = true) {

        $auth = false;

        $options = [];
        if(is_array($md5)) {
            $options = $md5;
            $md5 = (isset($md5['md5'])) ? $md5['md5'] : true;
        }
        
        if (!empty($username) && !empty($password)) {

            // this come from different sources and from database
            // so we need addslashes here, it always should work 
            // the problem coul be if \ (backslash) used in username
            $username_escaped = addslashes(stripslashes($username));

            $sql_login_email = '';
            if(!empty($options['auth_allow_email'])) {
                $sql_login_email = sprintf("OR email = '%s'", $username_escaped);
            }

            $sql = "
            SELECT 
                u.id AS user_id, 
                u.password AS hashed_password,
                u.username
            FROM 
                {$this->tbl->user} u
            WHERE 1
                AND u.username = '%s' {$sql_login_email}
                AND u.active = 1";

            // AND u.username = BINARY '%s' -- case sensetive

            // need it here because "hashed_password" was generated with slashes
            // the problem coul be if \ (backslash) used in password
            $password_escaped = addslashes(stripslashes($password));

            $sql = sprintf($sql, $username_escaped);
            $result = $this->db->Execute($sql) or die(db_error($sql));

            if($result->RecordCount() == 1) {
                $row = $result->FetchRow();

                // migrate password
                if(strlen($row['hashed_password']) == 32) {
                    $auth = $this->_getAuth_old($username, $password, $md5);
                    if($auth) {
                        $hash = HashPassword::getHash($password_escaped);
                        $this->updateUserPassword($row['user_id'], $hash);
                    }
                    
                    return $auth;
                }
                
                // need to encode, in prev implementaion it was md5
                if($md5) {
                    $ret = HashPassword::validate($password_escaped, $row['hashed_password']);
                
                // goes from db, encoded
                } else {
                    $ret = ($password_escaped == $row['hashed_password']);
                }
                
                if($ret) {
                    $auth = $row;
                }
            }
        }

        return $auth;
    }    
    
    
    function _isAuth($as_name, $check_ip = true) {
        $ret = self::_isAuthSession($as_name, $check_ip);  
        
        // auth is ok, check session id in table if required
        if($ret) {
            $s = $_SESSION[$as_name];
            if(isset($s['auth_concurrent']) && !$s['auth_concurrent']) {
                if(!$ret = $this->isSessionIdValid(AuthPriv::getUserId(), AuthPriv::getSessionId())) {
                    AuthPriv::logout();
                }
            }
        }
        
        return $ret;
    }
    
    
    static function _isAuthSession($as_name, $check_ip = true) {
        $ret = true;
        $ip = ($check_ip) ? Auth::getIP() : '';
        
        if(@$_SESSION[$as_name]['auth'] != md5(@$_SESSION[$as_name]['thua'] .
                                               @$_SESSION[$as_name]['user_id'] . 
                                               @$_SESSION[$as_name]['username'] .
                                               $ip) 
           || @!$_SESSION[$as_name]['auth'] || @!$_SESSION[$as_name]['thua']){
        
            $ret = false;
        }
        
        return $ret;
    }    
    
    
    function authToSession($name, $user_id, $username, $options = []) {
        $ip = ($this->check_ip) ? Auth::getIP() : '';
        $hash = !empty($options['hash']) ? $options['hash'] : false;
        
        // to prevent session fixation a t t a c k s
        if($hash === false) { 
            if($this->run_session_regenerate_id) {
                session_regenerate_id(true);
            }
            
            $hash = session_id();
        }
        
        $_SESSION[$name]['auth'] = md5($hash . $user_id . $username . $ip);
        $_SESSION[$name]['thua'] = $hash;
        $_SESSION[$name]['user_id'] = $user_id;
        $_SESSION[$name]['username'] = $username;
        $_SESSION[$name]['time_flag'] = time();
        $_SESSION[$name]['selector'] = WebUtil::generatePassword(5, 2); // 12 length
        $_SESSION[$name]['auth_concurrent'] = (int) $options['auth_concurrent'];
    
        return $_SESSION[$name]['auth'];
    }
    
    
    function authToSessionApi($user_id, $username, $hash) {
        $options = [
            'hash' => $hash,
            'auth_concurrent' => 1 // allow
        ];
        $this->authToSession($this->as_name, $user_id, $username, $options);
    }
    
    
    // TOKEN AUTH (remember) // 
    
    function getManager() {
        static $manager;
        
        if(!$manager) {
            $manager = new UserModelExtra();
        }
    
        return $manager;
    }
    
    
    function isValidRememberAuth($selector, $validator) {
        
        $ret = false;
    
        $manager = $this->getManager();
        $row = $manager->getRememberAuth($selector);
        
        if($row && HashPassword::validate($validator, $row['token'])) {
            $ret = $row;
        }
    
        return $ret;
    }
    
    
    function setRememberAuth($user_id, $remote_token = false, $auth_id = false) {
        
        $selector = WebUtil::generatePassword(5, 2); // 12 length
        $token = HashPassword::getHash($selector);

        $days = 14;
        $timestamp = strtotime("+ {$days} days");
        $expired = date('Y-m-d', $timestamp);

        $remote_token = ($remote_token) ?: $this->user_ldap_token;
        $auth_id = ($auth_id) ? (int) $auth_id : 'NULL';
        
        $manager = $this->getManager();
        $id = $manager->saveRememberAuth($auth_id, $user_id, $token, $remote_token, $expired);
        $cookie = sprintf('%s:%s',$id, $selector);

        $this->authToCookie($timestamp, $cookie);
        
        $ret = ['rmid' => $id, 'days' => $days, 'expired' => $expired];
        
        return $ret;
    }

    // Save auth to table -----------------
    
    function saveSessionId($user_id, $session_id) {
        $model = new UserModelExtra();
        
        $values = [
            'rule_id' => UserModelExtra::$temp_rules['auth_id'],
            'value2' => $session_id,
            'value_timestamp' => 'NOW()'
        ];
        
        if($model->getTempRuleById($user_id, $values['rule_id'])) {
            $model->updateTemp($values, $user_id);
        } else {
            $model->addTemp($values, $user_id);
        }
    }
    
    
    function isSessionIdValid($user_id, $session_id) {
        $rule_id = UserModelExtra::$temp_rules['auth_id'];
        
        $sql = "SELECT 1 FROM {$this->tbl->user_temp} 
            WHERE rule_id = %d AND user_id = %d 
            AND value2 = '%s' AND active = 1";
        $sql = sprintf($sql, $rule_id, $user_id, $session_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        return (bool) ($result->Fields(1));
    }
    
    
    // CSRF token -------------------
    
    static function getCsrfToken() {
        $selector = $_SESSION['auth_']['selector'];
        $token = HashPassword::getHash($selector);
        return $token;
    }
    
    
    static function validateCsrfToken($validator, $session = true) {
        @$selector = ($session) ? $_SESSION['auth_']['selector'] : $_COOKIE['kb_selector_'];
        return ($selector) ? HashPassword::validate($selector, $validator) : false;
    }
    
    
    static function getCsfrTokenCookie() {
        // self::removeCookieByName('kb_selector_');
        
        if(isset($_COOKIE['kb_selector_'])) {
            $selector = $_COOKIE['kb_selector_'];
            
        } else {
            $selector = WebUtil::generatePassword(5, 2); // 12 length            
            $secure = self::getCookieSecure();
            $expired = '1 day';
            
            self::authToCookieByName('kb_selector_', $expired, $selector, '/', "", $secure, true);
        }

        $token = HashPassword::getHash($selector);
        return $token;
    }
    
    
    // COOKIE //-------------------------
    
    static function authToCookieByName($name, $period = false, $data = false, $path = '/', $domain = "" , $secure = false , $httponly = false) {
        //setcookie ( string $name , string $value = "" , int $expires = 0 , string $path = "" , string $domain = "" , bool $secure = false , bool $httponly = false )
        setcookie($name, $data, self::getCookieTime($period), $path, $domain, $secure, $httponly);
    }
        
    
    static function removeCookieByName($name) {
        self::authToCookieByName($name);
        unset($_COOKIE[$name]);
    }    
    
    
    // period - 1 hour, 5 days, 8 months, 2 years ...
    static function getCookieTime($period = false, $sign = '+') {
        $ts = time();
        if($period) {
            $ts = (is_numeric($period)) ? $period : strtotime($sign . $period);
        }
    
        return $ts;
    }

    
    function setCookieExpire($period = false) {
        $this->cookie_expire = self::getCookieTime($period);
    }    
    
    
    static function getCookieSecure() {
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        return ($conf['ssl_admin'] && $conf['ssl_client']);
    }
    
    
    // USER // ------------------------
    
    function _getUserByValue($values) {
        $where_sql = ModifySql::_getWhereSql($values, array_keys($values));
        $sql = "SELECT * FROM {$this->tbl->user} WHERE {$where_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        if($result->RecordCount() == 1) {
            return $result->FetchRow();
        }
        
        return false;
    }
    

    function getUserByValue($values) {
        return $this->_getUserByValue($values);
    }
    

    
    function doAuthByValue($values, $md5 = false) {
        $user = $this->_getUserByValue($values);        
        if($user) {
            return $this->doAuth($user['username'], $user['password'], $md5);
        }    
        
        return false;
    }
    
    
    function getAuthByValue($values, $md5 = false) {
        $user = $this->_getUserByValue($values);
        if($user) {
            return $this->getAuth($user['username'], $user['password'], $md5);
        }    
        
        return false;        
    }
    
    
    function updateUserPassword($user_id, $password) {
        $sql = "UPDATE {$this->tbl->user} SET password = '%s', date_updated = date_updated WHERE id = '%d'";
        $sql = sprintf($sql, $password, $user_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // ROLES //-------------------------
    
    function _getUserRole($user_id) {
        $sql = "SELECT role_id FROM {$this->tbl->user_to_role} WHERE user_id = '%d'";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
    
    
    function roleToSession($name, $data) {
        foreach($data as $k => $v) {
            $_SESSION[$name]['role_id'][] = $v['role_id'];
        } 
    }
    
    
    // OTHER // ------------------------------
    
    static function getIP() {
        return WebUtil::getIP();
    }
    
}
?>