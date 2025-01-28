<?php

// for some special functions to get someting

class KBApiEntryModel
{   
    
    static function getCategory($manager, $category_id) {
        
        $private_sql = $manager->getPrivateSql();
        $role_skip_sql = $manager->getCategoryRolesSql();

        $sql = "SELECT c.* FROM {$manager->tbl->category} c
        WHERE c.id = '%d' 
            AND c.active = 1
            AND {$private_sql}
            AND {$role_skip_sql}";

        $sql = sprintf($sql, $category_id);
        $result = $manager->db->Execute($sql) or die(db_error($sql));
        // echo $sql;

        return $result->FetchRow();
    }
    
}
?>