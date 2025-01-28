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



class KBCategoryModelBulk extends BulkModel
{

    var $actions = array(
        'private', 'public',
        'admin', 'type', 'sort',
        'commentable', 'ratingable',
        'status', /*'delete'*/ );
        
    var $actions_immidiate = array('delete');
    var $msg_key = 'bulk_kbcategory';
    
    var $apply_child = true;


    function setActionsAllowed($manager, $priv, $allowed = array()) {

        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);

        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;
    }


    function getChildIds($ids) {
        $child_ids = $this->model->getChildCategories(false, $ids);
        foreach(array_keys($child_ids) as $id) {
            $ids = array_merge($ids, $child_ids[$id]);
        }

        $ids = array_unique($ids);
        return $ids;
    }


    function parseIds($ids) {
        if($this->apply_child) {
            $ids = $this->getChildIds($ids);
        }

        return $ids;
    }


    function setPrivate($values, $private, $ids) {
        $ids = $this->parseIds($ids);
        $str_ids = $this->model->idToString($ids);

        $private = PrivatePlugin::getPrivateValue($private);
        $this->updateEntryPrivate($private, $str_ids);
        
        $this->model->deleteRoleToCategory($str_ids);
        
        $role_read = (!empty($values['role_read'])) ? $values['role_read'] : array();
        $role_write = (!empty($values['role_write'])) ? $values['role_write'] : array();
        $this->model->saveRoleToCategory($private, $role_read, $role_write, $ids);
    }


    function setPublic($ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $this->updateEntryPrivate(0, $ids);
        $this->model->deleteRoleToCategory($ids);
    }


    function updateEntryPrivate($val, $ids) {
        $sql = "UPDATE {$this->model->tbl->category} SET private = '{$val}' WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
    }


    function setAdmin($values, $ids) {
        $ids = $this->parseIds($ids);
        $str_ids = $this->model->idToString($ids);
        $this->model->deleteAdminUserToCategory($str_ids);

        $empty = array_search(0, $values);
        if($empty !== false) {
            unset($values[$empty]);
        }

        if($values) {
            $this->model->saveAdminUserToCategory($values, $ids);
        }
    }


    function setEntryType($values, $ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->category} SET category_type = '$values' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }


    function setSortPublic($values, $ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->category} SET sort_public = '$values' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }


    function setCommentable($values, $ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->category} SET commentable = '$values' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }


    function setRatingable($values, $ids) {
        $ids = $this->parseIds($ids);
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->category} SET ratingable = '$values' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }


    function setSortOrder($val_sort, $ids) {

        foreach($ids as $cat_id) {
            $sort = $val_sort[$cat_id];

            $sql = "UPDATE {$this->model->tbl->category}
            SET sort_order = '{$sort}'
            WHERE id = '{$cat_id}'";
            $this->model->db->Execute($sql) or die(db_error($sql));
        }
    }


    function statusCategory($value, $ids) {
        $this->apply_child = ($value) ? $this->apply_child : true; // always true if non active
        $child_ids = $this->parseIds($ids);
        $this->model->status($value, $ids);
        
        $child_ids = array_diff($child_ids, $ids);
        if($child_ids) {
            $this->model->statusChild($value, 1, $child_ids);
        }
    }
    
}
?>