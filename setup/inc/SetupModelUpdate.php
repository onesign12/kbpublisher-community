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

// all updates which required before upgrading will be here
// 1. update email, make email an uniquie index we need to show all diplicates and update it


class SetupModelUpdate extends SetupModel
{
    
	function factory($item, $manager = false) {
		$class = 'SetupModelUpdate_' . $item;
        
		$item = new $class;
        $item->db = $manager->db;
        $item->tbl = $manager->tbl;
        
        return $item;
    }
    
    
    function isDuplicatedEmailResult() {
		$sql = "SELECT email, COUNT(*) AS num FROM {$this->tbl->user} GROUP BY email HAVING num >= 2";
		$result =& $this->db->Execute($sql);
		return $result;
    }
    
    
    function isDuplicatedEmail() {
		$result = $this->isDuplicatedEmailResult() or die(DbUtil::error($sql));
        return ($result->FetchRow());
    }
}
    
    
class SetupModelUpdate_email extends SetupModelUpdate
{   
    
    
    function getDuplicatedEmail() {
        
		$result = $this->isDuplicatedEmailResult() or die(DbUtil::error($sql));
        
        $data = array();
        if($rows = $result->GetAssoc()) {
    		$sql = "SELECT * FROM {$this->tbl->user} WHERE email IN ('%s')";
    		$sql = sprintf($sql, implode("','", array_keys($rows)));
    		$result =& $this->db->Execute($sql) or die(DbUtil::error($sql));
            while($row = $result->FetchRow()) {
                $data[$row['email']][] = $row;
            }
        }
        
        return $data;
    }
    

    function isEmailExist($email, $user_ids) {
        $sql = "SELECT 1 FROM {$this->tbl->user} WHERE email = '%s' AND id NOT IN (%s)";
        $sql = sprintf($sql, $email, $user_ids);
        $result = $this->db->Execute($sql) or die(DbUtil::error($sql));
        
        return ($result->Fields(1));
    }


    function updateEmail($email, $user_id) {
        $sql = "UPDATE {$this->tbl->user} SET email = '%s' WHERE id = %d";
        $sql = sprintf($sql, $email, $user_id);
        $result = $this->db->Execute($sql) or die(DbUtil::error($sql));
        
        return true;
    }
    
}

?>