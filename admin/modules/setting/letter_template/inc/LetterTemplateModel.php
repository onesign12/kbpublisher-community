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


class LetterTemplateModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'letter_template');
    
    
    function getRecordsSql() {
        $sql = "SELECT * FROM {$this->tbl->table} 
        WHERE active = 1 
        AND {$this->sql_params}
        ORDER BY group_id, sort_order";
        return $sql;
    }
    
    
    function &getRecords() {
        $data = array();
        $sql = $this->getRecordsSql();
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            $data[$row['group_id']][] = $row;
        }

        return $data;
    }
    
    
    function getById($id) {
        $data1 = parent::getById($id);
        $data2 = AppMailParser::getTemplateMsg($data1['letter_key']);
        return $this->getDataMerged($data1, $data2);
    }
    
    
    function getDataMerged($data1, $data2) {
        
        $data = array();
        foreach($data1 as $k => $v) {
            $data[$k] = $v;
            if(empty($v) && isset($data2[$k])) {
                $data[$k] = $data2[$k];
            }
        }
        
        return $data;
    }
    
    
    function getDefaultRecords() {
        $sql = "SELECT id, from_email, from_name, to_email, to_name, 
        to_cc_email, to_cc_name, to_bcc_email, to_bcc_name, to_special, subject
        FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
}
?>