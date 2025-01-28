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


class KBGlossaryModelBulk extends BulkModel
{

    var $actions = array('glossary_display', 'glossary_case', 'status', 'delete');
    

    function updateHighlight($val, $ids) {
        $bit = KBGlossaryModel::HIGHTLIGHT_BIT;
        $this->updateOptions($val, $ids, $bit);      
    }


    function updateCase($val, $ids) {
        $bit = KBGlossaryModel::CASE_BIT;
        $this->updateOptions($val, $ids, $bit);
    }
    
    
    function updateOptions($val, $ids, $bit) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->table} 
        SET display_once = (display_once - (display_once & {$bit})) | {$val} WHERE id IN ($ids)";
        // $sql = "UPDATE {$this->model->tbl->table} SET display_once = '{$val}' WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));       
    }

}
?>