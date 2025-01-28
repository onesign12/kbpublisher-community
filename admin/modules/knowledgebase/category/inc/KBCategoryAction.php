<?php

class KBCategoryAction extends AppAction
{

    function setCategoryParams($obj, $manager, $delim = '->', $field_name = 'name') {
        
        RequestDataUtil::stripVars($this->rq->category_name, array());
        $category_name = $this->rq->category_name;
        
        $index = _strrpos($this->rq->category_name, $delim);
        if ($index !== false) {
            if (!empty($manager->role_write_id)) {
                $manager->setSqlParams($manager->getPrivateParams());
            }
            
            $categories = $manager->getSelectRecords();
        
            $category_to_find = substr($this->rq->category_name, 0, $index);
            $parent_id = TreeHelperUtil::getIdByString($categories, $category_to_find, $delim, $field_name);
            if (!empty($parent_id)) {
                $obj->set('parent_id', $parent_id);
                
                $category_name = trim(substr($this->rq->category_name, $index + 2));
            }
            
        }
        
        $obj->set($field_name, $category_name);
    }
    
    
    function cloneTree($obj, $manager, $controller) {
        
        $data = $manager->getById($this->rq->id);
        $parent_ids = array($data['parent_id']);
        
        $cat_ids = array($this->rq->id);
    
        $categories = $manager->getSelectRecords();
        $top_parent_id = TreeHelperUtil::getTopParent($categories, $data['parent_id']);
        
        $children = $manager->getChildCategories($categories, $this->rq->id);
        $cat_ids = array_merge($cat_ids, $children);
        $cat_ids_str = implode(',', $cat_ids);
        
        $manager->setSqlParams(sprintf('AND id IN(%s)', $cat_ids_str));
        $manager->setSqlParamsOrder('ORDER BY sort_order');
        $rows = $manager->getRecords();
        $rows = RequestDataUtil::stripVars($rows, array(), 'addslashes');
        
        
        $rows[$this->rq->id]['name'] = '[COPY] ' . $rows[$this->rq->id]['name'];
        
        $tree_helper = &$manager->getTreeHelperArray($rows, $data['parent_id']);
        
        $admin_users = $manager->getAdminUserById($cat_ids_str, true);
        $role_read = $manager->getRoleReadById($cat_ids_str, true);
        $role_write = $manager->getRoleWriteById($cat_ids_str, true);
        
        $class_name = get_class($obj);
        
        foreach ($tree_helper as $cat_id => $level) {
            $obj = new $class_name;
            
            $obj->set($rows[$cat_id]);
            $obj->set('id', null);
            $obj->set('parent_id', $parent_ids[$level]);
            $obj->set('sort_order', 'sort_end');
            
            if (!empty($admin_users[$cat_id])) {
                $obj->setAdminUser(array_keys($admin_users[$cat_id]));
            }
            
            if (!empty($role_read[$cat_id])) {
                $obj->setRoleRead($role_read[$cat_id]);
            }
            
            if (!empty($role_write[$cat_id])) {
                $obj->setRoleWrite($role_write[$cat_id]);
            }
            
            $id = $manager->save($obj, 'insert');
            
            $parent_ids[$level + 1] = $id;
        }
        
        $more = array('filter[c]' => ($parent_ids[0] == 0) ? $parent_ids[1] : $top_parent_id);
        $link = $controller->getLink('this', 'this', 'this', false, $more);
        $controller->setCustomPageToReturn($link, false);
        
        $controller->go();
    }
    
}

?>