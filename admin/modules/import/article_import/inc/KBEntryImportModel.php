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

class KBEntryImportModel extends ImportModel
{    
    
    var $imported_cat_name = 'Imported';
    
    
    function getMoreFields($fields, $data, $filename) {
            
        $set = array();
        
        if(array_search('date_posted', $fields) === false) {
            $set[] = 'date_posted = ' . date('Y-m-d H:i:s');
        }
        
        if(array_search('category_id', $fields) === false) {
            $set[] = 'category_id = ' . $data['category_id'];
        }
        
        if(array_search('author_id', $fields) === false) {
            $set[] = 'author_id = ' . $this->model->user_id;
        }
        
        if(array_search('updater_id', $fields) === false) {
            $set[] = 'updater_id = ' . $this->model->user_id;
        }
        
        // echo '<pre>', print_r($set, 1), '</pre>';
        return $set;
    }
    
    
    function isCategory() {
        $sql = "SELECT id FROM {$this->model->tbl->category} 
        WHERE name = '{$this->imported_cat_name}'";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function createCategory() {
        $obj = new KBCategory();
        $obj->set('id', NULL);
        $obj->set('name', $this->imported_cat_name);
        $obj->set('parent_id', 0);
        $obj->set('active', 0);
        $obj->set('sort_order', 'sort_end');
        
        return $this->model->cat_manager->save($obj, 'insert');
    }
    
        
    function setEnryToCategory($category_id) {
        $sql = "INSERT IGNORE {$this->model->tbl->entry_to_category} (entry_id, category_id, is_main, sort_order)
        SELECT id, category_id, 1, 1
        FROM {$this->model->tbl->entry}
        WHERE category_id = '{$category_id}'";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
/*
    function setEntryCategory($category_id) {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET category_id = '{$category_id}', body_index = body, 
        date_updated = date_updated
        WHERE category_id = 0";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function setEntryAuthor($author_id) {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET author_id = '{$author_id}', date_updated = date_updated
        WHERE author_id = 0";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));    
    }
    
    
    function setEntryUpdater($updater_id) {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET updater_id = '{$updater_id}', date_updated = date_updated
        WHERE updater_id = 0";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function setEntryDatePosted() {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET date_posted = NOW(), date_updated = date_updated
        WHERE date_posted IS NULL";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
*/


    function setEntryHits($category_id) {
        $sql = "INSERT IGNORE {$this->model->tbl->entry_hits} (entry_id, entry_type, hits)
        SELECT id, 1, hits
        FROM {$this->model->tbl->entry}
        WHERE category_id = '{$category_id}'";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    

    // fill body_index from body field
    function setBodyIndexTask() {
        $rule_id = 1;
        $sql = "INSERT IGNORE {$this->model->tbl->entry_task} (rule_id, entry_id, entry_type)
        SELECT {$rule_id}, id, 1
        FROM {$this->model->tbl->entry}
        WHERE body_index = ''";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    // create tags from keywords
    function setMetaKeywordsTask() {
        $rule_id = 2;
        $sql = "INSERT IGNORE {$this->model->tbl->entry_task} (rule_id, entry_id, entry_type, value1)
        SELECT {$rule_id}, id, 1, meta_keywords
        FROM {$this->model->tbl->entry}
        WHERE body_index = ''
        AND meta_keywords != ''";
        $result = $this->model->db->_Execute($sql) or die(db_error($sql));
    }
    
}
?>