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



class CronLogModel extends AppModel
{

    //var $tbl_pref_custom = '';
    var $tables = array('table'=>'log_cron', 'log_cron');
    var $custom_tables = array();
    
    
    function getById($id) {
        $this->setSqlParams("AND id='{$id}'");
        $sql = $this->getRecordsSql();    
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getSummaryRecord($magic) {
        $sql = "SELECT 
            *, 
            UNIX_TIMESTAMP(date_started) AS 'date_started_ts',
            UNIX_TIMESTAMP(date_finished) AS 'date_finished_ts' 
        FROM {$this->tbl->log_cron} 
        WHERE magic = '{$magic}' 
        AND date_finished IS NOT NULL 
        ORDER BY date_finished DESC";
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->FetchRow();
    }
    
    
    function getRecordsSql() {
        $sql = "SELECT 
            *, 
            UNIX_TIMESTAMP(date_started) AS 'date_started_ts',
            UNIX_TIMESTAMP(date_finished) AS 'date_finished_ts' 
        FROM {$this->tbl->log_cron} 
        WHERE {$this->sql_params}
        ORDER BY date_finished DESC";
        
        return $sql;
    }    
    
    
    function getCronMagic() {
        $c = new CronModel;
        unset($c->magic_to_number['_test_']);
        unset($c->magic_to_number['_run_']);
        
        return $c->magic_to_number;
    }
}
?>