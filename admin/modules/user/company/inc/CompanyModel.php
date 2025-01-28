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


class CompanyModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'user_company', 'user_company', 'user', 'country'=>'list_country');    
    
    
    function getRecordsSql() {
        $sql = "SELECT c.*, COUNT(u.company_id) as user_num
        FROM {$this->tbl->table} c
        LEFT JOIN {$this->tbl->user} u ON c.id = u.company_id     
        WHERE {$this->sql_params} 
        GROUP BY c.id
        {$this->sql_params_order}";

        return $sql;
    }   
    
    
    function getCountRecordsSql() {
        $sql = "SELECT COUNT(*) AS 'num'
        FROM {$this->tbl->table} c
        LEFT JOIN {$this->tbl->user} u ON c.id = u.company_id     
        WHERE {$this->sql_params} ";

        return $sql;
    }   
    
    
    function getSelectRange() {
        $sql = "SELECT id, title FROM {$this->tbl->table} ORDER BY title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getCountries() {
        $sql = "SELECT * FROM {$this->tbl->country} ORDER BY title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getCountrySelectRange() {
        $sql = "SELECT id, title FROM {$this->tbl->country} ORDER BY title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getStateSelectRange() {
        require 'eleontev/data_arrays/state.php';
        return $state;
    }    
    
    
    function getUsersNum($ids) {
        $sql = "SELECT company_id, COUNT(id) as user_num FROM {$this->tbl->user} 
        WHERE company_id IN ($ids) 
        GROUP BY company_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getByDomain($domain) {
        $sql = "SELECT id, title
            FROM {$this->tbl->table}
            WHERE url LIKE '%$domain'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // DELETE RELATED // ---------------------
    
    function isCompanyInUse($record_id) {
        $sql = "SELECT COUNT(*) AS num 
        FROM {$this->tbl->user} u 
        WHERE u.company_id = %d";
        
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
}
?>