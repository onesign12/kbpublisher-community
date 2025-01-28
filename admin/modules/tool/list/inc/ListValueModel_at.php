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


class ListValueModel_at extends ListValueModel
{

    var $custom_tables = array('reftable' => 'kb_entry');
    
    
    function inUse($id) {
        $row = $this->getById($id);
        $list_value = $row['list_value'];
        
        $sql = "SELECT 1 AS field 
        FROM {$this->tbl->reftable} 
        WHERE entry_type = '{$list_value}' 
        LIMIT 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('field');        
    }
}
?>
