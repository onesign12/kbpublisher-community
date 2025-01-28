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



class EmailParserEntryModel extends TriggerEntryModel
{
    var $tbl_pref_custom = '';
    var $tables = array('table' => 'trigger', 'trigger', 'stuff_data');
    
    
    function getRecordsSql() {
        $sql = "SELECT * FROM {$this->tbl->table} WHERE {$this->sql_params} {$this->sql_params_order}";
        return $sql;
    }
    
    
    function getMailboxSelectRange() {
        $sql = "SELECT * FROM {$this->tbl->stuff_data} WHERE data_key = 'iemail'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetAssoc();
        
        $data = array();
        foreach ($rows as $id => $row) {
            $options = unserialize($row['data_string']);
            $data[$id] = (!empty($options['title'])) ? $options['title'] : $options['host'];
        }
        
        natcasesort($data);
        return $data;
    }
    
    
    function getMailbox($id) {
        $sql = "SELECT * FROM {$this->tbl->stuff_data} WHERE data_key = 'iemail' AND id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
        
        $data = array();
        foreach ($rows as $id => $row) {
            $options = unserialize($row['data_string']);
            $data[$id] = (!empty($options['title'])) ? $options['title'] : $options['host'];
        }
        
        return $data;
    }
    
    
    // DELETE RELATED // ---------------------
    
    function deleteTriggerByMailbox($mailbox_id) {
        $mailbox_str = 's:10:"mailbox_id";s:%d:"%s";';
        $mailbox_str = sprintf($mailbox_str, strlen($mailbox_id), $mailbox_id);
        
        $sql = "DELETE FROM {$this->tbl->table}
        WHERE entry_type = '{$this->entry_type}'
            AND trigger_type = '{$this->trigger_type}'
            AND options LIKE '%{$mailbox_str}%'";
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // DUMP // ----------------------------
    
    function getDefaultSql($options = false) {
        $options = 'a:1:{s:10:"mailbox_id";s:{mailbox_id_length}:"{mailbox_id}";}';
        $sql = parent::getDefaultSql($options);
        
        return $sql;
    }
    
    function runDefaultSql($mailbox_id, $sql) {
        $reg =& Registry::instance();
        $sql = str_replace('{prefix}', $reg->getEntry('tbl_pref'), $sql);
        $sql = str_replace('{mailbox_id_length}', strlen($mailbox_id), $sql);
        $sql = str_replace('{mailbox_id}', $mailbox_id, $sql);
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function getDefaultSqlSettingKey() {
        $keys = array();
        
        $keys[2][30] = 'default_sql_automation_email';
        
        return $keys[$this->trigger_type][$this->entry_type];
    }
    
}
?>