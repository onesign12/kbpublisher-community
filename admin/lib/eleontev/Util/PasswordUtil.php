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

class PasswordUtil
{

    var $table = 'user';
    var $id_field = 'id';
    var $pass_field = 'password';
    var $login_field = 'username';
    var $email_field = 'email';
    var $ext_sql;
    var $temp_table = 'user_temp';
    var $db;


    function __construct($manager) {
        $this->db = $manager->db;
        $this->table = $manager->tbl->user;
        $this->temp_table = $manager->tbl->user_temp;
        $this->manager = $manager;
    }


    function setExtSql($sql) {
        $this->ext_sql = $sql;
    }
    

    function getAllInfo($id) {
        $sql = "SELECT * FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    

    function getUsername($id) {
        $sql = "SELECT {$this->login_field} FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->login_field);
    }
    

    function getEmail($id) {
        $sql = "SELECT {$this->email_field} FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->email_field);
    }


    // set new password, temporary by default
    function setPassword($id, $password, $username = false) {

        $temp_sql_arr = array();
        if($username) {
            $temp_sql_arr[] = "{$this->login_field} = '{$username}'";
        }

        $temp_sql = ($temp_sql_arr) ? implode(',', $temp_sql_arr) . ',' : '';
        // -- lastpass = UNIX_TIMESTAMP(),  no need to change lastpass on password reset (forgot password)

        $sql = "UPDATE {$this->table} SET
        {$temp_sql}
        date_updated = date_updated,
        {$this->pass_field} = '{$password}'
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $password;
    }


	function generatePassword($num_sign = 3, $num_int = 2, $num_special = 0) {
        return WebUtil::generatePassword($num_sign, $num_int, $num_special);
	}


    // return id if email exists false otherwise
    function isEmailExists($email) {
        $sql = "SELECT {$this->id_field} FROM {$this->table}
        WHERE {$this->email_field} = '{$email}' {$this->ext_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }


    // return id if username exists false otherwise
    function isUsernameExists($login) {
        $sql = "SELECT {$this->id_field} FROM {$this->table}
        WHERE {$this->login_field} = '{$login}' {$this->ext_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }


    function setUserResetPassword($user_id, $resent_code, $reset_code, $user_ip = false) {

        $rule_id = 1; 
        if($user_ip === false) {
            $user_ip = WebUtil::getIP();
            $user_ip = ($user_ip == 'UNKNOWN') ? 0 :  $user_ip;
        }
        
        $sql = "DELETE FROM {$this->temp_table} WHERE rule_id = %d AND user_id = %d";
        $sql = sprintf($sql, $rule_id, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));

        $sql = "INSERT {$this->temp_table}
        SET rule_id = '{$rule_id}',
            user_id = '{$user_id}',
            user_ip = IFNULL(INET_ATON('{$user_ip}'), 0),
            value1 = '{$resent_code}',
            value2 = '{$reset_code}',
            active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function unsetUserResetPassword($user_id) {
        $sql = "UPDATE {$this->temp_table} SET active = 0 
            WHERE rule_id = %d AND user_id = %d";
        $sql = sprintf($sql, $rule_id, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function getUserByResetPasswordCode($code, $min_interval) {
        return $this->_getUserByPasswordCode($code, 'reset', $min_interval);
    }

    
    function getUserByResentPasswordCode($code, $min_interval) {
        return $this->_getUserByPasswordCode($code, 'resent', $min_interval);
    }


    private function _getUserByPasswordCode($code, $code_type, $min_interval) {
        $field = ($code_type == 'reset') ? 'value2' : 'value1';
        $sql = "SELECT ut.user_id AS 'user_id'
        FROM {$this->temp_table} ut
        WHERE ut.rule_id = 1
            AND ut.value_timestamp > DATE_SUB(NOW(), INTERVAL {$min_interval} MINUTE)
            AND ut.$field = '{$code}'
            AND ut.active = 1";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('user_id');
    }


    function getResetCodeByResentPasswordCode($code) {
        $sql = "SELECT ut.value2 AS 'reset_code'
        FROM {$this->temp_table} ut
        WHERE ut.rule_id = 1
            AND ut.value1 = '{$code}'
            AND ut.active = 1";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('reset_code');
    }


    static function parsePasswordStrengthRules($rule_str) {
        $rules = [];
        foreach(explode('|', $rule_str) as $v) {
            $rule = explode('=', $v);
            $rules[$rule[0]] = $rule[1]; 
        }
        
        if(empty($rules['error'])) {
            $rules['error'] = AppMsg::getMsgs('error_msg.ini')['pass_weak_msg'];
        }
        
        return $rules;
    }
    
    
    static function getSpecialChars() {
        return '!@#$%^&*()\-_+.';
    }
    
    // disabled
    static function isNotAllowedCharacters($password) {
        return false;
        // $rule = sprintf('/[^%s]/', 'a-zA-Z0-9' . self::getSpecialChars());
        // return preg_match($rule, $password);
    }
    
         
    // we do not check here for "wrong" charcters if rule matched
    static function isWeakPassword($password) {        
        
        //regex example
        //'/^(?=(.*[a-z]){1,})(?=(.*[A-Z]){1,})(?=(.*[0-9]){1,})(?=(.*[!@#$%^&*()\-__+.]){1,}).{8,}$/';    
                
        $parts = [
            'lcase'   => 'a-z',
            'ucase'   => 'A-Z',
            'number'  => '0-9',
            'special' => self::getSpecialChars()
        ];
        
        $ps = SettingModel::getQuick(1, 'password_strength');
        $rules = self::parsePasswordStrengthRules($ps);
        
        $regex_parts = [];
        $placeholder = '(?=(.*[%s]){%d,})';
        foreach($parts as $key => $rule) {
            if($rules[$key]) { 
                $regex_parts[] = sprintf($placeholder, $rule, $rules[$key]);
            }
        }
        
        $regex = sprintf('/^%s.{%d,}$/', implode('', $regex_parts), $rules['length']);
        $ret = (preg_match($regex, $password)) ? false : true;

        return $ret;
    }


    static function getWeakPasswordError() {    
        $ps = SettingModel::getQuick(1, 'password_strength');
        return self::parsePasswordStrengthRules($ps)['error'];
    }
    

    static function getNotAllowedCharactersPasswordError() {    
        $msg = AppMsg::getMsgs('error_msg.ini')['pass_character_msg'];
        $signs = stripslashes(self::getSpecialChars());
        return str_replace('{sign}', $signs, $msg);
    }

    
    static function isWeakPasswordOld($password) {        
        
        $ret = false;
        
        $length = 8;
        $upper = '#[A-Z]#';  //Uppercase
        $lower = '#[a-z]#';  //lowercase
        $number = '#[0-9]#';  //numbers
        // $special = '#~`!@\#%^&*()-_+={}[]|\;:<>,./?#';  // whatever you mean by 'special char'
        $special = '#!@#$%^&*()\-__+.#';  // whatever you mean by 'special char'
        
        if(!preg_match($upper, $password)) {
            $ret = true;
            
        } elseif(!preg_match($lower, $password)) {
            $ret = true;
        
        } elseif(!preg_match($number, $password)) {
            $ret = true;

        // } elseif(!preg_match($special, $password)) {
            // $ret = true;

        } elseif(strlen($password) < $length) {
            $ret = true;
        }

        return $ret;
    }
    
    
    // EXPIERED // -----------------------------
    
    function isPasswordExpiered($user_id, $days) {
        $sql = "SELECT lastpass FROM {$this->table} 
        WHERE id = %d AND DATEDIFF(NOW(), FROM_UNIXTIME(lastpass)) > %d";
        $sql = sprintf($sql, $user_id, $days);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('lastpass');
    }
    
    
    function saveExpieredPassword($user_id, $password) {
        // $m = new UserModel();
        $m = $this->manager;
        
        $values = [
            'rule_id' => UserModelExtra::$temp_rules['old_password'],
            'value2' => HashPassword::getHash($password)
        ];
        
        // if(!$m->extra_manager->getTempById($user_id, $values)) {
            $m->extra_manager->addTemp($values, $user_id);
        // }
    }
    
    
    function getSavedPasswords($user_id, $num) {
        $rule_id = UserModelExtra::$temp_rules['old_password'];
        $sql = "SELECT value_timestamp, value2 FROM {$this->temp_table} 
        WHERE user_id = %d AND rule_id = %d ORDER BY value_timestamp DESC";
        $sql = sprintf($sql, $user_id, $rule_id);
        
        $result = $this->db->SelectLimit($sql, $num, 0) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function refreshSavedPasswords($user_id, $num) {
        $rule_id = UserModelExtra::$temp_rules['old_password'];
        $sql = "SELECT value_timestamp AS ts FROM {$this->temp_table}
            WHERE user_id = %d AND rule_id = %d  AND active = 1
            ORDER BY value_timestamp DESC";
        $sql = sprintf($sql, $user_id, $rule_id);
        $result = $this->db->SelectLimit($sql, -1, $num) or die(db_error($sql));
        $ts = $result->Fields('ts');
        // echo '<pre>', print_r($ts,1), '</pre>';
        // echo '<pre>', print_r($sql,1), '</pre>';

        if($ts) {
            $sql = "UPDATE {$this->temp_table} SET active = 0
                WHERE user_id = %d AND rule_id = %d 
                AND active = 1 AND value_timestamp <= '{$ts}'";
            $sql = sprintf($sql, $user_id, $rule_id);
            // echo '<pre>', print_r($sql,1), '</pre>';
        }
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    static function setPassExpired($val) {
        AuthPriv::setPassExpired(1);
    }
    
    static function getPassExpired() {
        AuthPriv::getPassExpired();
    }
    
}
?>