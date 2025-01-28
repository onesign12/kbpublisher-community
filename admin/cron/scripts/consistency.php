<?php

// set private attributes to child catefories,
// if child categories does not have at least one private attr from parents then it is incorrect 

// correct: 
// Top Category - private: Role1, Role2
// -- Child 1 - private: Role1
// -- Child 2 - private: Role2

// not correct: 
// Top Category - private: Role1, Role2
// -- Child 1 - private: Role1
// -- Child 2 - public - articles will be aviable to all, will be changed to parent private attr : Role1, Role2

// not correct: 
// Top Category - private: Role1, Role2
// -- Child 1 - private: Role1
// -- Child 2 - private: Role3 - no one will see it, notify user, we do not change anything


function inheritCategoryPrivateAttributes() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $updated = 0;
    $updated_cat = array();
    $notify_cat = array();
    $entry_types = array('11', '12'); // article, files
    
    $role_manager = new RoleModel;
    $roles = $role_manager->getSelectRecords();
    
    foreach ($entry_types as $v) {
        
        $emanager = DataConsistencyModel::getCategoryManager('admin', $v);
        $emanager->error_die = false;
        $categories = &$emanager->getSelectRecords();
        $full_categories = &$emanager->getSelectRangeFolow($categories);
        if(!$categories) {
            continue;
        }
        
        $tree = $emanager->getTreeHelperArray($categories);
        
        $ids = $emanager->getValuesString($categories, 'id');
        $roles_read = $emanager->getRoleReadById($ids, 'id_list');
        $roles_write = $emanager->getRoleWriteById($ids, 'id_list');
        
        $data = array();
        foreach ($tree as $cat_id => $level) {
            $data[$level][] = $cat_id;
        }
        
        $max_level = max(array_keys($data));
        
        for ($i = 1; $i <= $max_level; $i ++) {
            foreach ($data[$i] as $cat_id) {
                $cat_private = $categories[$cat_id]['private'];
                $parent_id = $categories[$cat_id]['parent_id'];
                $parent_private = $categories[$parent_id]['private'];
                
                $need_change = false;
                
                // check for read/write
                if ($parent_private != $cat_private && $parent_private != 0 && $cat_private != 1) {
                    
                    if ($parent_private == 1) {
                        $cat_private = 1;
                    }
                    
                    if ($parent_private == 2) {
                        $cat_private = ($cat_private == 3) ? 1 : 2;
                    }
                    
                    if ($parent_private == 3) {
                        $cat_private = ($cat_private == 2) ? 1 : 3;
                    }
                    
                    $need_change = true;
                }
                
                
                // handling roles
                $parent_roles_read = (!empty($roles_read[$parent_id])) ? $roles_read[$parent_id] : array();
                $cat_roles_read = (!empty($roles_read[$cat_id])) ? $roles_read[$cat_id] : array();
                $cat_roles_read_add = array();
                if ($parent_roles_read) { // check for parents' roles
                    
                    // no any role, assign all from parent
                    if(!$cat_roles_read) {
                        $cat_roles_read_add = $parent_roles_read;
                    
                    // has roles no one which exist in parent, notify user
                    } elseif (!array_intersect($parent_roles_read, $cat_roles_read)) {
                        if($categories[$cat_id]['active']) {
                            $notify_cat[$cat_id] = sprintf('ID: %d, Category: %s', $cat_id, $full_categories[$cat_id]);
                        }
                    }
                }
                
                
                $parent_roles_write = (!empty($roles_write[$parent_id])) ? $roles_write[$parent_id] : array();
                $cat_roles_write = (!empty($roles_write[$cat_id])) ? $roles_write[$cat_id] : array();
                $cat_roles_write_add = array();
                if ($parent_roles_write) { // check for parents' roles
                    
                    // no any role, assign all from parent
                    if(!$cat_roles_write) {
                        $cat_roles_write_add = $parent_roles_write;
                    
                    // has roles but no one which exist in parent, notify user
                    } elseif (!array_intersect($parent_roles_write, $cat_roles_write)) {
                        if($categories[$cat_id]['active']) {
                            $notify_cat[$cat_id] = sprintf('ID: %d, Category: %s', $cat_id, $full_categories[$cat_id]);
                        }
                    }
                }
                
                
                if ($need_change) { // update a category
                    $emanager->setPrivate($cat_private, $cat_id);
                    
                    $categories[$cat_id]['private'] = $cat_private;
                    $updated_cat[$cat_id] = $cat_id;
                }
                
                if($cat_roles_read_add || $cat_roles_write_add) {
                    // $emanager->deleteRoleToCategory($cat_id);
                    $emanager->saveRoleToCategory($cat_private, $cat_roles_read_add, $cat_roles_write_add, $cat_id);
                    
                    $roles_read[$cat_id] = $cat_roles_read + $cat_roles_read_add;
                    $roles_write[$cat_id] = $cat_roles_write_add;
                    $updated_cat[$cat_id] = $cat_id;
                }
            
            }
        }
    }
    
    if($notify_cat) {
        $msg = "Some child private categories have different roles than parents. You should manually inspect and change private attributes for following categories: \n";
        $msg .= implode("\n", $notify_cat);
        $cron->logInform($msg);
    }
    
    $updated = count($updated_cat);
    $notified = count($notify_cat);
    $cron->logNotify('%d category(ies) have been updated, %d notified.', $updated, $notified);
    
    return $exitcode;
}



// set not active to child catefories if parent not active
function inheritCategoryNotActiveStatus() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $updated = 0;
    $entry_types = array('11', '12'); // article, files
    
    foreach ($entry_types as $v) {
        
        $emanager = DataConsistencyModel::getCategoryManager('admin', $v);
        $emanager->error_die = false;
        $categories = &$emanager->getSelectRecords();
        if(!$categories) {
            continue;
        }
        
        $tree = $emanager->getTreeHelperArray($categories);
        
        $data = array();
        foreach ($tree as $cat_id => $level) {
            $data[$level][] = $cat_id;
        }
        
        $max_level = max(array_keys($data));
        
        for ($i = 1; $i <= $max_level; $i ++) {
            foreach ($data[$i] as $cat_id) {
                
                $child_active = $categories[$cat_id]['active'];
                $parent_id = $categories[$cat_id]['parent_id'];
                
                $parent_cat_id = $categories[$parent_id]['id'];
                $parent_active = $categories[$parent_id]['active'];
                                
                // parent not active but current active 
                if($parent_active == 0 && $child_active == 1) {
                    
                    // echo 'child_id: ', print_r($cat_id, 1), "\n";
                    // echo 'child_active: ', print_r($child_active, 1), "\n";
                    // echo '--------------------------', "\n";
                    //
                    // echo 'parent_cat_id: ', print_r($parent_cat_id, 1), "\n";
                    // echo 'parent_active: ', print_r($parent_active, 1), "\n";
                    // echo '===========================', "\n";

                    $ret = $emanager->statusChild(0, $parent_cat_id);
                    if ($ret === false) {
                        $exitcode = 0;
                    } else {
                        $updated += $ret;
                    }
                }
            }
        }
    }
    
    $cron->logNotify('%d category(ies) have been updated.', $updated);
    
    return $exitcode;
}

?>