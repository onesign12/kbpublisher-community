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

class FileEntryModelBulk extends KBEntryModelBulk
{

    var $actions = array('category_move', 'category_add',
                         'tag', 'private', 'public', 
                         'schedule', 'parse',
                         'custom',
                         'status', 'hits_reset', 'trash'
                        );
                            
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        if(!AuthPriv::isAdmin()) {
            unset($actions['hits_reset']);
        }
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }

    
    function delete($ids) {
        
        // to skip enties that  // inline and attached
        $related_ids = $this->model->getEntryToAttachment($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        if($ids) {
            $this->model->delete($ids, true, false); // false to skip sort updating  ???
        }
        
        return array_keys($related_ids);
    } 

    
    function trash($ids) {
        
        // to skip enties that  // inline and attached
        $related_ids = $this->model->getEntryToAttachment($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        if (!empty($ids)) {
            
            $objs = array();
            foreach ($ids as $id) {
                $data = $this->model->getById($id);
                $obj = new FileEntry;
                $obj->collect($id, $data, $this->model, 'save');
                $objs[] = $obj;
            }
            
            $this->model->trash($ids, $objs);
        }
        
        return array_keys($related_ids);
    } 

    
    function parse($values, $ids) {

        if(empty($values)) {
            return;
        }
        
        require_once 'eleontev/Dir/mime_content_type.php';        
        
        $ids_str = $this->model->idToString($ids);
        
        $this->model->setSqlParams("AND e.id IN($ids_str)");
        
        $limit = -1;
        $rows = &$this->model->getRecords($limit);
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            $file = $this->model->getFileDir($row);
            if(!$file) {
                continue;
            }
            
            $upload = $this->model->getFileData($file);
            
            $file_save = array();
            $file_save['id'] = $row['id'];
            $file_save['filetype'] = addslashes($upload['type']);
            $file_save['md5hash'] = $upload['md5hash'];            
            $file_save['date_updated'] = 'date_updated';
            $file_save['filename_index'] = addslashes($upload['name_index']);
                    
            if(in_array('filesize', $values)) {
                $file_save['filesize'] = $upload['size'];
            }
                        
            if(in_array('filetext', $values)) {
                $file_save['filetext'] = '';        

                if($this->model->setting['file_extract']) {
                    $file_save['filetext'] = addslashes($this->model->extractFileText($upload['to_read'], $this->model->setting));
                }
            }
            
            $this->updateFile($file_save);    
        }
    }
    
    
    function updateFile($val) {
        $sql = ModifySql::getSql('UPDATE', $this->model->tbl->entry, $val, false, 'id');
        $sql = str_replace("'date_updated'", 'date_updated', $sql);
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }    
    
    
    function getHitsFieldName() {
        return 'downloads';
    }
}
?>