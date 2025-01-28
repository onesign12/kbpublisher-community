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



class StuffEntryModel extends AppModel
{
    var $tbl_pref_custom = 'stuff_';
    var $tables = array('table'=>'entry', 'entry', 'category');
    var $custom_tables =  array('user');
    
    var $update_diff = 60; // seconds, to display updated if difference more than 
    
    
    function __construct($user = array()) {
        parent::__construct();
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
    }
    
    
    function getRecordsSql() {
        $sql = "
        SELECT 
            *,
            {$this->sql_params_select}
            
        FROM 
            {$this->tbl->table}
            
        WHERE 1
            AND {$this->sql_params}
        {$this->sql_params_order}";

        return $sql;
    }    
    
    
    function getCategories() { 
        $sql = "SELECT id, title FROM {$this->tbl->category}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
        
    
    // DELETE RELATED // ---------------------     
    function delete($record_id) {
        $record_id = $this->idToString($record_id);
        $sql = "DELETE FROM {$this->tbl->entry} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function sendFileInline($id) {
        
        $data = &$this->getById($id); 
        $params['data'] = $data['filedata'];
        $params['contenttype'] = $data['filetype'];
        $params['gzip'] = false;        

        return WebUtil::sendFile($params, $data['filename'], false);
    }  


    // ACTIONS // ---------------------    
    
    function upload() {
            
        
    
        $upload = new Uploader;
        $upload->store_in_db = true;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();        
        $upload->setRenameValues('date');
        $upload->setAllowedExtension($this->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($this->setting['file_denied_extensions']);
        $upload->setMaxSize($this->setting['file_max_filesize']);
        $upload->setUploadedDir($this->setting['file_dir']);
        
        $f = $upload->upload($_FILES);
        
        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        } else{
            $f['good'][1]['to_read'] = $f['good'][1]['tmp_name'];
            $f['good'][1]['directory'] = '';
        } 

        return $f;
    }
    
    
    function getFileContent($filename) {
        return Uploader::getFileContent($filename);
    }
    
        
    function &getUser($user_id, $one_user = true) {
        $sql = "SELECT id, username, first_name, last_name, email, phone 
        FROM {$this->tbl->user} WHERE id IN($user_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $ret = ($one_user) ? $result->FetchRow() : $result->GetAssoc();
        if(!$ret) { $ret = array(); }
        return $ret;
    }
    
    
    function setFileSetting(&$values) {
        
        $this->setting = &$values;
        $this->setting['file_denied_extensions'] = ($this->setting['file_denied_extensions']) 
                                                ? explode(',', $this->setting['file_denied_extensions'])
                                                : array();
                                                
        $this->setting['file_allowed_extensions'] = ($this->setting['file_allowed_extensions']) 
                                                ? explode(',', $this->setting['file_allowed_extensions'])
                                                : array();
    }
    
    
    function sendFileDownload($data) {
        $params['data'] = $data['filedata'];
        $params['gzip'] = false;
        $params['contenttype'] = $data['filetype'];

        return WebUtil::sendFile($params, $data['filename']);
    } 
    
}
?>