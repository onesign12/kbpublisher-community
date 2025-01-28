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

class ModifySql
{

    // generate sql for update, insert or replace
    static function getSql($action, $table, $fields, $more_fields = false, $keys = 'id') {
        
        $fields = ($more_fields) ? array_merge($fields, $more_fields) : $fields;
        $sql_fields = ModifySql::_getModifyFields($fields);
        
        if($action == 'UPDATE') {
            $where = ModifySql::_getWhereSql($fields, $keys);
            $sql = $action . ' ' . $table . ' SET ' . $sql_fields . ' WHERE ' . $where;            
        
        } elseif ($action == 'INSERT' || $action == 'INSERT IGNORE' || $action == 'REPLACE') {
            $sql = $action . ' ' . $table . ' SET ' . $sql_fields;
        } else {
            die('Wrong sql action ' . $action . 'in getModifySql()');
        }
        
        return $sql;
    }


    // generate where for updating
    static function getWhereSqlFromArr($vars) {
        return self::_getWhereSql($vars, array_keys($vars));
    }


    // generate where for updating
    static function _getWhereSql($vars, $keys) {
        $keys = (is_array($keys)) ? $keys : array($keys);
        $vars = (is_array($vars)) ? $vars : array($vars);
        foreach($keys as $k => $v) {
            $where[] = sprintf("%s=%s", $v, ModifySql::_getQuoted($vars[$v]));
        }
        return implode(' AND ', $where);
    }
    
    
    // return quoted or not 
    static function _getQuoted($val, $escape = false) {
        $special_mysql = array('NOW()');
        $special_str = array('INET_ATON');
        
        if($val === NULL) {
            return "NULL";
        } elseif(in_array($val, $special_mysql)) {
            return $val;        
        } elseif(self::substring_in_array($val, $special_str)) {
            return $val;
        } else {
            return ($escape) ? sprintf("'%s'", addslashes($val)) : sprintf("'%s'", $val);
        }
    }
    
    
    // generate fields in format id=15, ...
    static function _getModifyFields($fields) {
        foreach($fields as $k => $v) {
            $a[] = sprintf("%s=%s", $k, ModifySql::_getQuoted($v));
        }
        return implode(",\n", $a);
    }
    
    
    static function substring_in_array($val, $array) {
        foreach ($array as $needle) {
            if (strpos($val, $needle) !== false) {
                return true;
            }
        }
 
        return false;
    }

}


/*
function add_a($records) {
    $sql = "SELECT FROM {$this->tbl->table} WHERE id = -1";
    $result = $this->db->Execute($sql) or die(db_error($sql)); # Execute the query and get the empty recordset
    
    $sql = $this->db->GetInsertSQL($result, $records);
    $result = $this->db->Execute($sql) or die(db_error($sql));
    
    return  $this->db->Insert_ID();
}

function update_a($records, $id = false) {
    
    //$id = ($id !== false) ? $id : $records['id'];
    
    $sql = "SELECT FROM {$this->tbl->table} WHERE id = %d";
    $sql = sprintf($sql, $id);
    $result = $this->db->Execute($sql) or die(db_error($sql));
    
    $sql = $this->db->GetUpdateSQL($result, $records);
    if($sql) {
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
}
*/


/*
0 = ignore empty fields. All empty fields in array are ignored.
1 = force null. All empty, php null and string 'null' fields are changed to sql NULL values.
2 = force empty. All empty, php null and string 'null' fields are changed to sql empty '' or 0 values.
3 = force value. Value is left as it is. Php null and string 'null' are set to sql NULL values and 
    empty fields '' are set to empty '' sql values.

*/


/*
define('ADODB_FORCE_IGNORE',0);
define('ADODB_FORCE_NULL',1);
define('ADODB_FORCE_EMPTY',2);
define('ADODB_FORCE_VALUE',3);


$ADODB_FORCE_TYPE = 0;

$sql = "SELECT * FROM s_kb_user WHERE id = -1";  
$rs = $db->Execute($sql); # Execute the query and get the empty recordset 

$record = array(); # Initialize an array to hold the record data to insert 

# Set the values for the fields in the record 
$record["first_name"] = "Bob"; 
$record["last_name"] = "Smith"; 
$record["middle_name"] = "";

$insertSQL = $db->GetInsertSQL($rs, $record);
echo "<pre>"; print_r($insertSQL); echo "</pre>";
*/
?>