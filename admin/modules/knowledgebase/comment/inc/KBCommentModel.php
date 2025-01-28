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

class KBCommentModel extends AppModel
{
    
    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'comment', 'comment', 'entry');
    var $custom_tables = array('user', 'user_subscription');
    
    var $entry_type = 31;
    
    
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
            ({$this->tbl->comment} c, 
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
            c.active, COUNT(DISTINCT c.id) AS num
        FROM 
            {$this->tbl->comment} c,
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
        $sql = "SELECT c.entry_id, COUNT(*) FROM {$this->tbl->comment} c 
        WHERE c.entry_id IN($entry_ids) GROUP BY c.entry_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
    
    
    function getArticleData($entry_id) {
        $sql = "SELECT id AS entry_id, title FROM {$this->tbl->entry} WHERE id = '{$entry_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
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
            $bulk_manager = new KBCommentModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        
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
    

    function getEntryIdById($comment_id) {
        $sql = "SELECT entry_id FROM {$this->tbl->comment} WHERE id = %d";
        $sql = sprintf($sql, $comment_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('entry_id');
    }


    function getLatestCommentDateForEntry($entry_id) {
        $sql = "SELECT MAX(date_posted) as 'last_comment' FROM {$this->tbl->comment} 
        WHERE entry_id = %d AND active = 1";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return ($date = $result->Fields('last_comment')) ? $date : NULL;
    }
    
    
    function setCommentDateForEntry($entry_id, $date) {
        $sql = "UPDATE {$this->tbl->entry} SET 
        date_updated = date_updated, 
        date_commented = %s
        WHERE id = %d";
        $sql = sprintf($sql, ModifySql::_getQuoted($date), $entry_id);        
        $this->db->Execute($sql) or die(db_error($sql));
    }


    function updateCommentDateForEntry($entry_id, $date = false) {
        if($date === false) {
            $date = $this->getLatestCommentDateForEntry($entry_id);
        }
        
        $this->setCommentDateForEntry($entry_id, $date);
    }
    
    
    function isUserSubscribedToComments($record_id) {
        $user_id = AuthPriv::getUserId();        
        
        $sql = "SELECT 1 FROM {$this->tbl->user_subscription}
        WHERE entry_id IN ({$record_id}) 
        AND entry_type = 31
        AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    } 

    
    
    // DELETE RELATED // ---------------------
    
    function deleteByEntryId($entry_id) {
        $sql = "DELETE FROM {$this->tbl->comment} WHERE entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>