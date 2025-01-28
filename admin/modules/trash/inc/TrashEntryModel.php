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


class TrashEntryModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table' => 'entry_trash', 'entry_trash', 'user');
        
    var $entry_type = array(
        'article',
        'file', 
        'news',
        'user'
        );
        
    
    function getRecordsByEntryType() {
        $rows = $this->getRecords();
        
        $data = array();
        foreach ($rows as $row) {
            $data[$row['entry_type']][] = $row;
        }
        
        return $data;
    }
    
    
    function getEntryTypeSelectRange() {
        $data = array();    
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        foreach ($this->entry_type as $type) {
            $k = array_search($type, $this->record_type);
            $data[$k] = $msg[$type];
        }
                
        return $data;
    }
    
    
    function getEntryTypesInTrash() {
        $sql = "SELECT DISTINCT(entry_type), entry_type AS et FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getEntryData($id) {
        $sql = "SELECT * FROM {$this->tbl->entry_trash} WHERE id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getEntryTypes() {
        $sql = "SELECT DISTINCT(entry_type) AS et, entry_type   
        FROM {$this->tbl->entry_trash}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function truncate() {
        $sql = "DELETE FROM {$this->tbl->table} WHERE {$this->sql_params}";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>