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



class UserListModel extends AppModel
{

    var $tables = array('table' => 'user_subscription', 'kb_entry', 'file_entry');
    
    // var $types = array(
    //     '1' => 'articles',
    //     '2' => 'files'
    // );
    
    
    function __construct() {
        parent::__construct();
        $this->user_id = AuthPriv::getUserId();
        $this->s_manager = new SubscriptionModel;
    }
    
    
    function getRecordsSql() {
        $sql = "SELECT s.*,
            IFNULL (e.title, f.filename) as title
        FROM {$this->tbl->table} s
        
        LEFT JOIN {$this->tbl->kb_entry} e
        ON s.entry_id = e.id
            AND s.entry_type = 1
        
        LEFT JOIN {$this->tbl->file_entry} f
        ON s.entry_id = f.id
            AND s.entry_type = 2
        
        WHERE s.user_id = '{$this->user_id}'
            AND s.entry_type IN (1,2)
            AND (e.id OR f.id) 
            AND {$this->sql_params}
        {$this->sql_params_order}";
        
        return $sql;
    }
    
    
    function deleteEntry($entry_id, $entry_type) {
        $this->s_manager->deleteSubscription($entry_id, $entry_type, $this->user_id);
    }
    
    
    function emailStatus($entry_id, $entry_type, $status) {
        $this->s_manager->setEmailNotification($entry_id, $entry_type, $this->user_id, $status);
    }
     
}
?>