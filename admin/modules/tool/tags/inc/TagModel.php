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


class TagModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'tag', 'tag', 'tag_to_entry',
        'tag_to_entry_update', 'entry_task');
    
    var $entry_type = 22;
    
 
    function isInUse($ids) {
        $sql = "SELECT 1 FROM {$this->tbl->tag_to_entry} WHERE tag_id IN ($ids)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
     }
    
    
    function getReferencedEntriesNum($ids) {
        $sql = "SELECT tag_id, entry_type, COUNT(*) as num
            FROM {$this->tbl->tag_to_entry} 
            WHERE tag_id IN ($ids) 
            GROUP BY entry_type, tag_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $data = array();
        while($row = $result->FetchRow()) {  
            $data[$row['tag_id']][$row['entry_type']] = $row['num'];
        }

        return $data;
    }
    
    
    function isTagExists($title, $id = false) {
        $sql = "SELECT id FROM {$this->tbl->table} WHERE title = '{$title}'";
        $sql .= ($id) ? sprintf(' AND id != %d', $id) : '';
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }    
        
        
    // ACTION LOG // --------------------

    function addTagSyncTask($tags, $action) {
        $data = array();
        $tags = (is_array($tags)) ? $tags : array($tags);
        $rule_id = array_search('sync_meta_keywords', $this->entry_task_rules);
        foreach($tags as $tag_id) {
            $data[$tag_id] = array($rule_id, $tag_id, $action);
        }
    
        if($data) {  
            $sql = "REPLACE {$this->tbl->entry_task} (rule_id, entry_id, value1) VALUES ?";      
            $sql = MultiInsert::get($sql, $data);
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    // DELETE // --------------------- 
    
    function deleteTag($record_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function delete($record_id) {
        $record_id = $this->idToString($record_id);
        $this->deleteTag($record_id);
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);
    }
}
?>