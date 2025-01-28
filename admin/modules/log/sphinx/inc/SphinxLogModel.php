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



class SphinxLogModel extends AppModel
{

    var $tables = array('table' => 'log_sphinx');
    

    function getRecordsSql() {
        $sql = "SELECT *,
            UNIX_TIMESTAMP(date_executed) AS date_executed_ts
            FROM {$this->tbl->table}
            WHERE entry_type = 0 
                AND {$this->sql_params}
            {$this->sql_params_order}";
        
        return $sql;
    }
    
    
    function getStartDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_executed)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getActionTypeSelectRange($msg) {
        $msg = AppMsg::getMsg('log_msg.ini', false, 'sphinx_log_type');
        
        $si_model = new SphinxIndexModel;
        foreach ($si_model->action_types as $id => $action_type) {
            $data[$id] = $msg[$action_type];
        }
        
        return $data;
    }
    
}
?>