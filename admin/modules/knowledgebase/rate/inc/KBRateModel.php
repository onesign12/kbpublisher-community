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


class KBRateModel extends AppModel
{
    
    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'rating_feedback', 'rating_feedback', 'entry');
    var $custom_tables = array('user');
    
    var $entry_type = 32;
    
    
    function getById($record_id) {
        $this->setSqlParams(sprintf("AND c.id = %d", $record_id));
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    

    function getRecordsSql() {
        
        $sql = "
        SELECT 
            c.*,
            c.date_posted as c_date_posted, 
            e.title,
            u.username,
            u.first_name, u.last_name, u.middle_name,
            u.email AS r_email,
            UNIX_TIMESTAMP(c.date_posted) AS ts,
            {$this->sql_params_select}
            
        FROM 
            ({$this->tbl->table} c, 
            {$this->tbl->entry} e)
            LEFT JOIN {$this->tbl->user} u ON c.user_id = u.id
        
        WHERE 1
            AND e.id = c.entry_id  
            AND {$this->sql_params}
            {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }        
    
    
    function getStatRecords() {
        
        $sql = "
        SELECT 
            c.active, COUNT(*) AS num
        FROM 
            {$this->tbl->table} c,
            {$this->tbl->entry} e
        WHERE 1
            AND e.id = c.entry_id  
            AND {$this->sql_params}
            GROUP BY c.active";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function  getCountCommentsPerEntry($entry_ids) {
        $sql = "SELECT c.entry_id, COUNT(*) FROM {$this->tbl->table} c 
        WHERE c.entry_id IN($entry_ids) GROUP BY c.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
    
    
    function getArticleData($entry_id) {
        $sql = "SELECT id AS entry_id, title FROM {$this->tbl->entry} WHERE id = '{$entry_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();        
    }
    
    
    // STATUS, TYPE // ---------------------------------    
    
    function getListSelectRange($active_only = true, $updated_entry_value = false) {
        return ListValueModel::getListSelectRange('rate_status', $active_only, $updated_entry_value);
    }        

    
    function getEntryStatusData() {
        foreach(ListValueModel::getListData('rate_status') as $list_value => $v) {
            $data[$v['list_value']] = array('title' => $v['title'],
                                            'color' => $v['custom_1']
                                            );
        }
        
        return $data;
    }
    
    
    function getEntryStatusPublished() {
        $data = array();
        foreach(ListValueModel::getListData('rate_status') as $list_value => $v) {
            if($v['custom_3'] == 1) {
                $data[$v['list_value']] = $v['list_value'];
            }
        }
        
        return $data;
    }    
    
    
    // ACTIONS // ---------------------
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {
        
        $priv->setCustomAction('entry', 'select');
        $priv->setCustomAction('delete_entry', 'delete');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new KBRateModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }        
        
        $priv->check($action);
        
        // for update, delete, status
        $sql = "SELECT DISTINCT(1) FROM {$this->tbl->table} c, {$this->tbl->entry} e 
        WHERE c.id = '{$record_id}' AND c.entry_id = e.id AND e.author_id = '{$priv->user_id}'";
        $priv->setOwnSql($sql);
    
        $priv->check($action);
    
        // set sql to select own records
        $priv->setOwnParam($this->getOwnParams($priv));
        $this->setSqlParams('AND ' . $priv->getOwnParam());
    }    
    
    
    function getOwnParams($priv) {
        return sprintf("e.author_id=%d", $priv->user_id);
    }    
    
    
    function updateCommentDateForEntry($entry_id, $date = false) {}
    
    // DELETE RELATED // ---------------------
    
    function deleteByEntryId($entry_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>