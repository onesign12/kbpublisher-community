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



class FileCategoryModelBulk extends KBCategoryModelBulk
{

    var $actions = array('private', 'public', 
                         'admin',
                         'attachable',
                        //'delete'
                         'status');
    
    var $apply_child = true;
    
    
    function setAttachable($values, $ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->category} SET attachable = '$values' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }    
}
?>