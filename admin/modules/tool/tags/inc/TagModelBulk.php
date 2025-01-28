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


class TagModelBulk extends BulkModel
{

    var $actions = array('status', 'delete');
	
	 
    function delete($ids) {
       return $this->model->delete($ids, true);
    }
    
    
    function getReferences($ids) {
              
        $ids_string = $this->model->idToString($ids);
        $not_delete_ids = array_keys($this->model->getReferencedEntriesNum($ids_string));
        if($not_delete_ids) {
            $ids = array_diff($ids, $not_delete_ids);
        }
        
        $ret = array(
            'free' => $ids,
            'taken' => $not_delete_ids
            );
    
        return $ret;
    }
        
}
?>