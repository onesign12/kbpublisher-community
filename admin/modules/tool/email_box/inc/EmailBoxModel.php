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


class EmailBoxModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=> 'stuff_data', 'trigger');
    
    
    function isInUse($id) {
        $mailbox_str = 's:10:"mailbox_id";s:%d:"%s";';
        $mailbox_str = sprintf($mailbox_str, strlen($id), $id);
        
        $sql = "SELECT 1 FROM {$this->tbl->trigger} WHERE options LIKE '%{$mailbox_str}%'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }
    
    
    function getDefaultSqlSettingKey() {
        $key = 'default_sql_automation_email_box';
        return $key;
    }
    
    
    function runDefaultSql($sql, $msg) {
        $reg =& Registry::instance();
        $sql = str_replace('{prefix}', $reg->getEntry('tbl_pref'), $sql);
        
        $sql = str_replace('{mailbox_title}', $msg['test_mailbox_msg'], $sql);
        
        $this->db->Execute($sql) or die(db_error($sql));
        
        return $this->db->Insert_ID();
    }
    
}
?>