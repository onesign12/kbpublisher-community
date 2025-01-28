<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class AppSphinxModel extends SphinxModel
{
    
    
    function setOwnParams($manager, $priv, $priv_area = false) {
        if ($priv->isSelfPriv('select', $priv_area)) {
            $select_str = 'IF(source_id = %d, author_id = %d, 1) as _own%d';
            $select = sprintf($select_str, $manager->entry_type, $manager->user_id, $manager->entry_type);
            $this->setSqlParamsSelect($select);
            
            $where = sprintf('AND _own%d = 1', $manager->entry_type);
            $this->setSqlParams($where);
        }
    }
    
    
    function setEntryRolesParams($manager, $action) {
        
        if($manager->isUserPrivIgnorePrivate()) {
            return;
        }
        
        $user_role_ids = array(0); // no roles
        
        if($manager->user_role_id) {
            $user_role_ids_temp = $manager->role_manager->getChildRoles(false, $manager->user_role_id);
            foreach($user_role_ids_temp as $role_id => $role_ids) {
                $user_role_ids[] = $role_id;
                $user_role_ids = array_merge($user_role_ids, $role_ids);
            }
        }
        
        $where = sprintf("AND private_roles_%s IN (%s)", $action, implode(',', $user_role_ids));
        $this->setSqlParams($where);
    }  
    
    
    function setCategoryRolesParams($manager, $mode) {
        
        if($manager->role_skip_categories) {
            
            if ($mode == 'main') {
                $categories = $manager->getCategoryRecordsUser();
                $categories = implode(',', array_keys($categories));
                
                $select_str = 'IF(source_id = %d, IN(main_category, %s), 1) as _category_roles%s';
                $select = sprintf($select_str, $manager->entry_type, $categories, $manager->entry_type);
                
            } else {
                $skip_categories = implode(',', $manager->role_skip_categories);
                $select_str = 'IF(source_id = %d, NOT IN(category, %s), 1) as _category_roles%s';
                $select = sprintf($select_str, $manager->entry_type, $skip_categories, $manager->entry_type);
            }
            
            $this->setSqlParamsSelect($select);
            
            $where = sprintf('AND _category_roles%d = 1', $manager->entry_type);
            $this->setSqlParams($where);
        }
    }
    
    
    static function updateAttributes($attribute, $value, $entry_id, $entry_type) {
        
        if(SphinxModel::isSphinxOn()) {
            
            $entry_ids = is_array($entry_id) ? implode(',', $entry_id) : $entry_id;
            $sphinx = SphinxModel::connect();
            
            $idx = SphinxModel::setIndexNames();
            
            $sql = "UPDATE {$idx->all} SET %s = %s WHERE source_id = %d AND entry_id IN (%s)";
            $sql = sprintf($sql, $attribute, $value, $entry_type, $entry_ids);
            $result = $sphinx->Execute($sql) or die(DBUtil::error($sql, false, $sphinx));
        }
    }
}
?>