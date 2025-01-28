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

class FileEntryModel_db extends FileEntryModel
{
    
    /*
         *          <b>one of:</b>
         *                  o 'file'                => path to file for download
         *                  o 'data'                => raw data for download
         *                  o 'resource'            => resource handle for download
         * <br/>
         *          <b>and any of:</b>
         *                  o 'cache'               => whether to allow cs caching
         *                  o 'gzip'                => whether to gzip the download
         *                  o 'lastmodified'        => unix timestamp
         *                  o 'contenttype'         => content type of download
         *                  o 'contentdisposition'  => content disposition
         *                  o 'buffersize'          => amount of bytes to buffer
         *                  o 'throttledelay'       => amount of secs to sleep
         *                  o 'cachecontrol'        => cache privacy and validity
    */
    function sendFileDownload($id) {
        
        $data = &$this->getById($id);
        
        $params['data'] = $this->getFileData($data);
        $params['gzip'] = false;
        $params['contenttype'] = $data['filetype'];
        
        return WebUtil::sendFile($params, $data['filename'], $attachment);
    }    
    
    
    function &getFileData($file_id) {
        $sql = "SELECT bin_data FROM {$this->tbl->data} WHERE id = %d";
        $sql = sprintf($sql, $file_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('bin_data');
    }
    

    // ACTIONS // ---------------------    
    
    function saveFileData($bin_data, $id) {
    
        $bin_data = addslashes($bin_data);
    
        if($id) {
            $sql = "REPLACE {$this->tbl->data} SET id = '{$id}', bin_data = '{$bin_data}'";
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
        } else {
            $sql = "INSERT {$this->tbl->data} SET bin_data = '{$bin_data}'";
            $result = $this->db->Execute($sql) or die(db_error($sql));
            $id = $this->db->Insert_ID();
        }
    
        return $id;
    }
    
    function upload() {
    
        $upload = new Uploader;
        $upload->store_in_db = true;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();        
        //$upload->setRenameValues('date');
        $upload->setAllowedExtension($this->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($this->setting['file_denied_extensions']);
        $upload->setMaxSize($this->setting['file_max_filesize']);
        //$upload->setUploadedDir($this->setting['file_dir']);
        
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
    
    
    function save($obj, $action, $is_file) {
        
        $id = (int) $obj->get('id');
        
        if($action == 'insert') {
            $this->add($obj);
            $this->saveEntryToCategory($obj, $id);
            
        } else {
            
            // we have old filetext and no new filetext
            if(!$is_file) {
                unset($obj->properties['filetext']);
            } 
        
            $this->update($obj, $id);
            $this->deleteEntryToCategory($id);
            $this->saveEntryToCategory($obj, $id);
        }
        
        return $id;
    }
    
    
    // DELETE RELATED // --------------------- 
    
    function getFilesdata($record_id) {
        return $record_id;
    }
    
    
    function deleteFileData($record_id) {
        $sql = "DELETE FROM {$this->tbl->data} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>