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


class BanModel extends BaseModel
{

    var $tables = array('user_ban');

    var $types = array(
        //'login'        => 1,
    );


    var $rules_to_ban_insert = array(
        'ip'        => 'INET_ATON(%d)'
    );
    

    var $rules_to_ban_where = array(
        'ip'        => 'INET_NTOA(%d)'
    );

    
    function factory($type) {
        $class = 'BanModel_' . $type;
        $file = $class . '.php';
        
        // require_once $file;
        $obj = new $class;
        return $obj;
    }


  /**
    * FUNCTION: ban -- ban, add record to table.
    *
    * @param string    $rule_str       ban rule, user_id, user_ip, etc.
    * @param mixed     $val            ban value
    * @param int       $second         period in secconds to ban
    * @param string    $admin_reason   admin notes
    * @param string    $user_reason    user notes
    * @return true/false
    * @access public
    */
    function ban($rule_str, $value, $second, $admin_reason = false, $user_reason = false) {
        $type = $this->types[$this->type];
        $rule = $this->rules[$rule_str];
        
        $value = sprintf("'%s'", $value);
        if(isset($this->rules_to_ban_insert[$rule])) {
            $value = sprintf($this->rules_to_ban_insert[$rule], $value);
        }
        
        $sql = "INSERT {$this->tbl->user_ban} SET 
            ban_type = '$type', 
            ban_rule = '$rule',
            ban_value = $val,
            date_start = NOW(),
            date_end = DATE_ADD(NOW(), INTERVAL $second SECOND),
            admin_reason = '{$admin_reason}',
            user_reason = '{$user_reason}',";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;        
    }


  /**
    * FUNCTION: isBan -- check if user id banned
    *
    * @param array     $rule_to_val_arr  ban rule to value array, example: array('user_id'=>12);
    * @return date_end/false             if banned returns datetime when ban ended or false otherwise
    * @access public
    */
    function isBan($rule_to_val_arr) {
        
        $sql_where = array();
        $sql_str = "(ban_type = %d AND ban_rule = %d AND ban_value = %s AND (date_end > NOW() OR date_end IS NULL))";
        
        $type = $this->types[$this->type];

        foreach($rule_to_val_arr as $rule_str => $value) {
            $rule = $this->rules[$rule_str];
            
            $value = sprintf("'%s'", $value);
            if(isset($this->rules_to_ban_where[$rule])) {
                $value = sprintf($this->rules_to_ban_where[$rule], $value);
            }
            
            $sql_where[] = sprintf($sql_str, $type, $rule, $value);
        }

        $sql_where = implode(' OR ', $sql_where);


        $sql = "SELECT date_end, user_reason FROM {$this->tbl->user_ban} WHERE {$sql_where}";
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $ret = $result->FetchRow();
        return ($ret) ? $ret : false;
    }
}


class BanModel_login extends BanModel
{

    var $custom_tables = array('log_login');
    var $type = 'login';
    
    var $rules = array(
        'user_id'    => 1, 
        'username'    => 2, 
        'ip'        => 3
    );    
    
    var $rules_to_log_field = array(
        'username'    => 'username', 
        'ip'        => 'user_ip'
    );

    var $rules_to_log_where = array(
        'username'    => "'%s'", 
        'ip'        => 'INET_NTOA(%d)'
    );


/*
    var $rules_to_value = array(
        'username'    => null, 
        'ip'        => null        
    );

    var $rules_to_tries = array(
        'username'    => 5,
        'ip'        => 5
    );
    
    
    
    function setValue($rule, $value) {
        $this->rules_to_value[$rule] = $value;
    }
    
    
    function setAllowedTries($rule, $value) {
        $this->rules_to_tries[$rule] = $value;
    }*/

    
    
  /**
    * FUNCTION: getBadTries -- get bad login attempts 
    *
    * @param string    $rule_str       ban rule, user_id, user_ip, etc.
    * @param int       $val            ban value
    * @param int       $tries          allowed number for bad logins
    * @param int       $period         period in secconds to check for continued bad tries
    * @return int      
    * @access public
    */    
    function getBadTries($rule_str, $val, $tries, $period) {
    
        $field = $this->rules_to_log_field[$rule_str];
        $str_where = $this->rules_to_log_where[$rule_str];
        $sql_where = sprintf($str_where, $field, $val);

        $sql = "SELECT COUNT(date_login) as num
        FROM {$this->tbl->log_login}
        WHERE {$sql_where}
        AND date_login > DATE_SUB(NOW(), INTERVAL %d SECOND)
        AND exitcode = 2
        AND active = 1";

        $sql = sprintf($sql, $id, $period);
        $result = $this->db->SelectLimit($sql, $tries, 0) or die(db_error($sql));
        return $result->Fields('num');  
    }
    
    
  /**
    * FUNCTION: resetBadTries - reset bad user login tries, not to count it next time
    *
    * @param array          $rule_to_val_arr  ban rule to value array, example: array('user_id'=>12);
    * @return true/false
    * @access public
    */    
    function resetBadTries($rule_to_val_arr) {
        
        $where_sql = array();
        foreach($rule_to_val_arr as $rule_str => $val) {
            $field = $this->rules_to_log_field[$rule_str];
            $str_where = $this->rules_to_log_where[$rule_str];
            $sql_where = sprintf($str_where, $field, $val);
        }
        
        $where_sql = implode(' OR ', $where_sql);
        
        
        $sql = "UPDATE {$this->tbl->log_login} SET active = 0
        WHERE ($where_sql)
        -- AND date_login > DATE_SUB(NOW(), INTERVAL %d SECOND)
        AND exitcode = 2
        AND active = 1";

        $sql = sprintf($sql, $id, $period);
        return $this->db->SelectLimit($sql, $tries, 0) or die(db_error($sql));
    }
}

?>