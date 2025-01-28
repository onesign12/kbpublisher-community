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



class FeedbackModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'feedback','user', 'custom_data' => 'feedback_custom_data');
    var $custom_tables = array('category'=>'kb_category', 'list', 'list_value', 'file_entry');
    
    var $entry_type = 20;
    
    
    
    function KBEntryModel($user = array(), $apply_private = 'write') {
        parent::__construct();
        $this->cf_manager = new CommonCustomFieldModel($this);
    }
    
    
    function getById($record_id) {
        $this->setSqlParams("AND e.id = '$record_id'");
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }

    
    function getRecordsSql() {
        
        $sql = "
        SELECT
            e.*,        
            u.username,
            u.first_name,
            u.last_name,
            #IFNULL(u.username, e.name) AS name,
            IFNULL(u.email, e.email) AS email,
            UNIX_TIMESTAMP(e.date_posted) AS ts,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->table} e
            {$this->sql_params_from})
        
        LEFT JOIN {$this->tbl->user} u ON e.user_id = u.id
        {$this->sql_params_join}
        
        WHERE {$this->sql_params}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    // ACTIONS // ---------------------
    
    function setEntryAnswered($record_id, $values) {
        
        $sql = "UPDATE {$this->tbl->table} SET 
        answer = '%s',
        answer_attachment = '%s',
        admin_id = %d,
        date_answered = NOW(),
        answered = 1 
        WHERE id = %d";
        
        $sql = sprintf($sql, $values['answer'], $values['answer_attachment'], $values['admin_id'], $record_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function sendContactAnswer($vars, $file) {
        
        
        
        $sender = new AppMailSender();
        return $sender->sendContactAnswer($vars, $file);
    }    
    
    
    function sendFileDownload($data, $type, $file_num, $attachment) {
        $key = ($type == 'question') ? 'attachment' : 'answer_attachment';
        
        $files = explode(';', $data[$key]);
        $file = $files[$file_num];
        $filename = basename($files[$file_num]);
        
        $params['file'] = $file;
        $params['gzip'] = false;
        $params['contenttype'] = mime_content_type($file);
                
        return WebUtil::sendFile($params, $filename, $attachment);
    }
    
  
    function upload() {
            
        
        
        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();
        $upload->setRenameValues('date');
        
        $attachment_max_filesize = WebUtil::getIniSize('upload_max_filesize')/1024;
        $upload->setMaxSize($attachment_max_filesize);
        
        $attachment_dir = $this->setting['file_dir'] . 'contact_attachment/';
        $upload->setUploadedDir($attachment_dir);
        
        $f = $upload->upload($_FILES);
        
        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        } 

        return $f;
    }
    
    
    function removeUploaded($file) {
        @unlink($file);
    }    
    
    
    // SUBJECT // --------------------------
    
    // range for form select
    function getSubjectSelectRange($active_only = false) {
        return ListValueModel::getListSelectRange('feedback_subj', $active_only);
    }
    
    
    
    // OTHER // ------------------------------
    
    // if check priv is different for model so reassign
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {
        
        $priv->setCustomAction('answer', 'update');
        $priv->setCustomAction('file', 'select');
        $priv->setCustomAction('file_download', 'select');
        $priv->setCustomAction('answer_status', 'status');
        $priv->setCustomAction('place_status', 'status');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new FeedbackModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }        
        
        $priv->check($action);
    }
    
    
    function isUnansweredFeedbacks() {
        $sql = "SELECT id FROM {$this->tbl->table} WHERE date_answered IS NULL"; 
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }    
    
    
    // DELETE RELATED // ---------------------
    
    function getAttachment($id) {
        $id = $this->idToString($id);
        $sql = "SELECT id, attachment, answer_attachment FROM {$this->tbl->table} WHERE id IN ({$id})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function deleteFilesFromDisk($attachment) {
        if($attachment) {
            foreach(array_keys($attachment) as $k) {
                $question_files = explode(';', $attachment[$k]['attachment']);
                $answer_files = explode(';', $attachment[$k]['answer_attachment']);
                
                $files = array_filter(array_merge($question_files, $answer_files));
                foreach($files as $k2 => $file) {
                    if (strpos($file, 'contact_attachment')) {
                        unlink($file);
                    }
                }   
            }
        }
    }
    
    
    function delete($id) {
        $attachment = $this->getAttachment($id);
        
        $this->deleteFilesFromDisk($attachment);
        parent::delete($id);
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $id, $this->entry_type);
    }
}
?>