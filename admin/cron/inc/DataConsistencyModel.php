<?php

class DataConsistencyModel extends AppModel
{
 
    static function &getEntryManager($user, $type) {
        
        // admin user
        if($user === 'admin') {
            $user = array(
                'user_id' => 0, 
                'priv_id' => 1, 
                'role_id' => array()
                );
        }
        
        if($type == 1 || $type == 11) {
            $emanager = new KBEntryModel($user, 'read');
        
        } elseif ($type == 3) {            
            $emanager = new NewsEntryModel($user, 'read');
            
        } else {            
            $emanager = new FileEntryModel($user, 'read');
        }
    
        return $emanager;
    } 
    
    
    static function &getDraftManager($user, $type) {
        
        // admin user
        if($user === 'admin') {
            $user = array(
                'user_id' => 0, 
                'priv_id' => 1, 
                'role_id' => array()
                );
        }
        
        if($type == 1) {
            $emanager = new KBDraftModel($user);
        
        } elseif ($type == 2) {   
            $emanager = new FileDraftModel($user);   
        }
    
        return $emanager;
    }
 
    
    static function &getCategoryManager($user, $type) {
        
        // admin user
        if($user === 'admin') {
            $user = array(
                'user_id' => 0, 
                'priv_id' => 1, 
                'role_id' => array()
                );
        }
        
        if($type == 11) {
            $manager = new KBCategoryModel($user);
        
        } elseif ($type == 12) {            
            $manager = new FileCategoryModel($user);   
        }
    
        return $manager;
    }
}

/*
-- -- find entries with missed categories in kbp_kb_entry_to_category
SELECT id FROM kbp_kb_entry e
LEFT JOIN kbp_kb_entry_to_category e_to_cat ON e.id = e_to_cat.entry_id
WHERE e_to_cat.category_id IS NULL;

-- find entries with missed categories in kbp_kb_entry.category_id
SELECT e.id FROM  kbp_kb_entry e
LEFT JOIN kbp_kb_category cat ON e.category_id = cat.id
WHERE cat.id IS NULL;

-- update entries by id with missed categories, set kbp_kb_entry.category_id
UPDATE kbp_kb_entry set category_id = 144, date_updated=date_updated
WHERE id IN (7,20);

-- update entries by id with missed categories, set kbp_kb_entry_to_category.category_id
UPDATE kbp_kb_entry_to_category SET category_id = 144 WHERE entry_id IN (7,20)
*/

?>