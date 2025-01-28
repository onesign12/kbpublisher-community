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

class KBFeaturedEntryModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array(
        'table' => 'entry_featured', 'entry_featured', 'kb_entry');
    
    
    function __construct() {
        parent::__construct();
        $this->emanager = new KBEntryModel;
        $this->entry_type = $this->emanager->entry_type;
    }
    
    
    function getRecordsSql() {
        
        $sql = "SELECT ef.*, e.title, e.hits
            FROM 
                ({$this->tbl->table} ef,
                {$this->tbl->kb_entry} e)
            WHERE ef.entry_id = e.id
                AND ef.entry_type = '{$this->entry_type}'
                AND {$this->sql_params}
            {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getRecords() {
        
        $args = func_get_args();
        $limit = (isset($args[0])) ? $args[0] : -1;
        $offset = (isset($args[1])) ? $args[1] : -1;
        
        $sql = $this->getRecordsSql();
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            if (empty($data[$row['entry_id']])) {
                $data[$row['entry_id']] = array(
                    'title' => $row['title'],
                    'hits' => $row['hits'],
                    'id' => $row['id'],
                    'category' => array($row['category_id'] => $row['sort_order'])
                    );
            } else {
                $data[$row['entry_id']]['category'][$row['category_id']] = $row['sort_order'];
            }
            
        }
        
        return $data;
    }
    
    
    function getCountRecordsSql() {
        $sql = "SELECT COUNT(DISTINCT ef.entry_id)
            FROM ({$this->tbl->table} ef,
                {$this->tbl->kb_entry} e)
            WHERE ef.entry_id = e.id
                AND ef.entry_type = '{$this->entry_type}'
                AND {$this->sql_params}";
 
        return $sql;        
    }
    
    
    function getByEntryId($entry_id) {
        $sql = "SELECT e.id, e.title, ef.category_id, ef.sort_order
            FROM {$this->tbl->kb_entry} e
            
            LEFT JOIN {$this->tbl->table} ef
            ON ef.entry_id = e.id
            AND ef.entry_type = '{$this->entry_type}'
            
            WHERE e.id = '{$entry_id}'
                AND {$this->sql_params}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            if (empty($data)) {
                $data = array(
                    'title' => $row['title'],
                    'id' => $row['id']);
                
                if (!is_null($row['category_id'])) {
                    $data['category'] = array($row['category_id'] => $row['sort_order']);
                } else {
                    $data['category'] = false;
                }
            } else {
                $data['category'][$row['category_id']] = $row['sort_order'];
            }
        }
        
        return $data;
    }
    
    
    function getEntryIds($record_id) {
        $record_id = $this->idToString($record_id);
        
        $sql = "SELECT entry_id
            FROM {$this->tbl->table}
            WHERE id IN (%s)";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['entry_id'];
        }
        
        return $data;
    }
    
    
    function getUsedCategories() {
        $sql = "SELECT DISTINCT category_id
            FROM {$this->tbl->table}
            WHERE category_id != 0
                AND entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['category_id'];
        }
        return $data;
    }
    
    
    function getEntryCount($category_id) {
        $sql = "SELECT COUNT(*) as num
            FROM {$this->tbl->table}
            WHERE category_id = '{$category_id}'
                AND entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function saveSortOrder($ids, $sort_order) {
        foreach ($ids as $id) {
            $this->updateSortOrder($id, $sort_order);
            $sort_order ++;
        }
    }
    
    
    function updateSortOrder($id, $sort_order) {
        $sql = "UPDATE {$this->tbl->table} SET sort_order = %d WHERE id = %d";
        $sql = sprintf($sql, $sort_order, $id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteByEntryId($entry_id) {
        $entry_id = $this->idToString($entry_id);
        $sql = "DELETE FROM {$this->tbl->table} 
        WHERE entry_id IN (%s) AND entry_type = %d";
        $sql = sprintf($sql, $entry_id, $this->entry_type);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
}
?>