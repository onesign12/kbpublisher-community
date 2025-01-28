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


class FileEntryModel_dir extends FileEntryModel
{

    var $addtype = 2;


    function sendFileDownload($data, $attachment = true) {
        FileEntryUtil::sendFileDownload($data, $this->setting['file_dir'], $attachment);
    }
    
    
    function getFileDir($data) {
        return FileEntryUtil::getFileDir($data, $this->setting['file_dir']);
    }        
    

    // ACTIONS // ---------------------
    // we just delete old if update and file
    // file already saved by upload method in dir implementation
    function saveFileData($bin_data, $id = null) {
        
        if($id) { 
            $fdata = $this->getFilesdata($id);
            $this->deleteFileData($fdata); 
        }
        
        return $id;
    }
    
    
    function getFileContent($filename) {
        return true;
    }
    
    
    function upload($rename_file = true, $file = false, $dir = false) {
    
        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();
        $upload->setAllowedExtension($this->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($this->setting['file_denied_extensions']);
        $upload->setMaxSize($this->setting['file_max_filesize']);
        
        if (!$dir) {
            $dir = $this->setting['file_dir'];    
        }
        $upload->setUploadedDir($dir);
        
        if($rename_file) {
            $upload->setRenameValues('date');
        } else {
            $upload->setRenameValues(false);
        }
        
        if ($file) {
            $f = $upload->upload(array('file_1' => $file));
        } else {
            $f = $upload->upload($_FILES);
        }

        if(isset($f['bad'])) {
            $f['error_msg'] = $f['bad'];
        } else {
            
            $data['filename'] = $f['good'][1]['name_orig'];
            $data['filename_disk'] = $f['good'][1]['name'];
            $data['directory'] = $dir;
            $data['sub_directory'] = '';
            $f['good'][1]['to_read'] = $this->getFileDir($data, $dir);
            $f['good'][1]['directory'] = $dir;
        
            // 2015-07-23 revrite to be compatible, when added filename_disk
            $f['good'][1]['name'] = $data['filename']; // it will be filename
            $f['good'][1]['name_disk'] = $data['filename_disk']; // it will be filename_disk
            $f['good'][1]['addtype'] = 1; // 1 for uploaded
        }
        
        return $f;
    }

    
    // $options = array(
    //     'one_level' => true,
    //     'add_dir' => false
    // )
    function readDirectory($dirname, $options = array()) {

        $d = new MyDir;
        $d->one_level = (isset($options['one_level'])) ? $options['one_level'] : true;
        $d->full_path = true;

        $d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS', 'etc');
        $d->setSkipFiles('.DS_Store');
        //$d->setSkipFiles('robots.txt');
        $d->setSkipRegex('#^\.ht.*#i');
        $d->setSkipRegex('#^config.*\.php#i');
        
        if(empty($options['skip_file_setting'])) {
            $d->setAllowedExtension($this->setting['file_allowed_extensions']);
            $d->setDeniedExtension($this->setting['file_denied_extensions']);
        }

        $dirname = str_replace('\\', '/', realpath($dirname));
        $directory = preg_replace("#[/\\\]+$#", '', trim($dirname)) . '/'; // remove/add trailing slash
        
        $files = array();
        if($f = &$d->getFilesDirs($dirname)) {
            $files['files'] = ExtFunc::multiArrayToOne($f);
            $files['options']['directory'] = $directory;
        }

        // add dirs to output
        if (!empty($options['add_dir'])) {
            $files['dirs'] = $d->getDirs($dirname);
            $files['options']['directory'] = $directory;
        }

        return $files;
    }
    
    
    function isDirectoryAllowed($directory, $options = array()) {
        
        $ret = false;
        if(!is_dir($directory) || !is_readable($directory)) {
            return $ret;
        }
        
        $directory = str_replace('\\', '/', $directory);
        $directory = preg_replace("#[/\\\]+$#", '', trim($directory)) . '/'; // remove/add trailing slash
        
        $allowed_dirs = SettingModel::getQuick(1, 'file_local_allowed_directories');
        $allowed_dirs = explode('||', $allowed_dirs);
        
        foreach($allowed_dirs as $v) {
            if($v == '*') {
                $ret = true;
                break;
            }
            
            if(strpos($directory, $v) !== false) {
                $ret = true;
                break;
            }
        }
        
        return $ret;
    }
    
    
    function getFileData($file) {
        
        $d = new MyDir;
        $data = array();
        
        $data['name'] = $d->getFilename($file);
        $data['type'] = mime_content_type($file);
        // $data['type'] = $d->getMimeContentType($file);
        $data['tmp_name'] = '';
        $data['extension'] = $d->getFileExtension($file);
        $data['size'] = filesize($file);
        $data['md5hash'] = md5_file($file);
        $data['to_read'] = $file;
        $data['directory'] = $d->getFileDirectory($file);
        
        // add trailing slash
        $data['directory'] = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
        $data['directory'] = $data['directory'] . '/';
        
        $data['name_index'] = $this->getFilenameIndex($data['name']);
        $data['name_disk'] = $data['name'];
        
        $data['addtype'] = $this->addtype;
        
        return $data;
    }
    
    
    function extractFileText($file, $options) {
        $ext = @$options['extension'];
        $ext = $ext ?: MyDir::getFileExtension($file);
        
        $extractor = new FileTextExctractor($ext, $options['extract_tool']);
        //$extractor->setDecode('windows-1251', 'UTF-8'); // example
        $extractor->setTool($options['extract_tool']);
        $extractor->setExtractDir($options['extract_save_dir']);
        
        return $extractor->getText($file);
    }
    
    
    function getMD5File($file, $foptions = array()) {
        return md5_file($file);
    }
    
    
    function getFileTime($file, $foptions = array()) {
        return filemtime($file);
    }
    
    
    function getFilenameIndex($str) {
        return str_replace('_', ' ', _substr($str, 0, _strrpos($str, '.')));
    }
    
    
    function addRecord($obj, $sort_values = array()) {
        
        $id = $this->add($obj);
        
        // for cron, error could be in filename, text, etc
        if($id === false) {
            return false;
        }
        
        $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);
        $this->saveSchedule($obj->getSchedule(), $id);
        
        if($obj->get('private')) {
            $this->saveRoleToEntryObj($obj, $id);
        }
        
        $this->tag_manager->saveTagToEntry($obj->getTag(), $id);
        $this->cf_manager->save($obj->getCustom(), $id);  
    
        return $id;
    }
    
    
    
    function save($obj, $action = 'insert', $is_file = false, $cron = false) {
                                      
        // sorting manipulations
        //$action = (!$obj->get('id')) ? 'insert' : 'update';
        $sort_values = $this->updateSortOrder($obj->get('id'), 
                                              $obj->getSortValues(), 
                                              $obj->getCategory(),
                                              $action);        
                                              
        // for sort order in main table now always 1
        $obj->set('sort_order', 1);                                              
        
                          
        if(in_array($action, array('insert', 'clone'))) {
                                
            $id = $this->addRecord($obj, $sort_values);
            $this->addHitRecord($id);
            
        } else {

            $id = (int) $obj->get('id');
        
            // we have old filetext and no new filetext
            if(!$is_file) {
                unset($obj->properties['filetext']);
            }
        
            $ret = $this->update($obj, $id);
            
            // for cron, error could be in filename, text, etc 
            if($ret === false) {
                return false;
            }
            
            
            $this->deleteEntryToCategory($id);
            $this->saveEntryToCategory($obj->getCategory(), $id, $sort_values);
            
            $this->deleteSchedule($id);
            $this->saveSchedule($obj->getSchedule(), $id);
                        
            $this->deleteRoleToEntry($id);
            $this->saveRoleToEntryObj($obj, $id);
            
            $this->tag_manager->deleteTagToEntry($id); 
            $this->tag_manager->saveTagToEntry($obj->getTag(), $id); 
            
            $this->cf_manager->delete($id);
            $this->cf_manager->save($obj->getCustom(), $id);
        }
        
        return $id;
    }
    
    
    function getSetting($key) {
        return $this->setting[$key];
    }
    
    
    function getFileDataById($id) {
        $sql = "SELECT id, directory, sub_directory, filename, filename_disk, addtype 
            FROM {$this->tbl->table} 
            WHERE id = '%d'";
        $sql = sprintf($sql, $id);
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // DELETE RELATED // --------------------- 

    function moveFile($file_from, $file_to, $copy = false) {
        $filename_from = basename($file_from);
        $dir_from = dirname($file_from);
        $filename_to = basename($file_to);
        $dir_to = dirname($file_to);
        
        if(!is_dir($dir_to)) {
            $oldumask = umask(0);
            @$r = mkdir($dir_to, 0777, true);
            umask($oldumask);
        
            if (!$r) {
                $f['status'] = false;
                $f['error_code'] = 1;
                $f['error_msg'] = "Unable to create directory {$dir_to}.";
                // throw new Exception($f['error_msg']);
                return $f;
            }
        }
            
        if(!is_writeable($dir_to)) {
            $f['status'] = false;
            $f['error_code'] = 2;
            $f['error_msg'] = "Destination directory {$dir_to} does not exists or it is not writable.";
            // throw new Exception($f['error_msg']);
            return $f;
        }
        
        if(!file_exists($file_from)) {
            $f['status'] = false;
            $f['error_code'] = 3;
            $f['error_msg'] = "File {$file_from} does not exists.";
            // throw new Exception($f['error_msg']);
            return $f;
        }

        $upload = new Uploader;
        $upload->safe_name_extensions = array();
        $upload->setUploadedDir($dir_to);
        $upload->setRenameValues('date');
        
        $func = ($copy) ? 'copy' : 'move';
        $options = [
            'filename_to' => $filename_to,
            'touch' => ($copy) ? false : true
        ];
        
        $f = $upload->$func($filename_from, $dir_from, $options);
        if(!$f['status']) {
            $action = ($copy) ? 'copy' : 'move';
            $f['error_code'] = 4;
            $f['error_msg'] = "Unable to {$action} file {$file_from} to {$file_to}.";
            // throw new Exception($f['error_msg']);
        }

        return $f;
    }
    
    
    function copyFile($file_from, $file_to) {
        return $this->moveFile($file_from, $file_to, true);
    }
        
    
    function getFilesdata($record_id) {
        
        $this->setSqlParams("AND e.id IN({$record_id})");
        $rows = $this->getRecords();
        
        $data = array();
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            $data[$row['id']] = $this->parseFilesData($row, $this->setting['file_dir']);
        }
        
        return $data;
    }
    
    
    function parseFilesData($row, $file_dir = false) {
        $data = array();
        if(!$file_dir) {
            $file_dir = $this->setting['file_dir'];
        }
        
        $data['filename'] = $row['filename'];
        $data['filename_disk'] = (!empty($row['filename_disk'])) ? $row['filename_disk'] : $row['filename'];
        
        if($row['addtype'] == 1) { // uploaded files
            $file = FileEntryUtil::getFileDir($row, $file_dir);
            $data['directory'] = str_replace('\\', '/', dirname($file)) . '/';
            $data['file'] = $file;
        } else {
            $data['directory'] = $row['directory'];
            $data['file'] = $data['directory'] .  $data['filename_disk'];
        }
        
        $data['addtype'] = $row['addtype'];
        
        return $data;
    }
    
    
    function deleteFileData($data) {
        
        $file_dir = $this->getSetting('file_dir');
        $not_moved = array();
        
        foreach($data as $entry_id => $v) {
            $ret = $this->deleteFileDataOne($v, $file_dir);
            if(!$ret) {
                $not_moved[$entry_id] = $ret;
            }
        }
        
        return $not_moved;
    }
    
    
    function deleteFileDataOne($data, $file_dir) {
        
        // echo '<pre>', print_r($data,1), '<pre>';
        // echo '<pre>', print_r($file_dir,1), '</pre>';
        // exit;
        
        $ret = true;

        // skip remote files, added as bulk files, linked to kb but not uploaded
        // addtype added in v7.5 so we still need next skip remote files block
        if($data['addtype'] != 1) {
            return $ret;
        } 
            
        // skip remote files, added as bulk files, linked to kb but not uploaded
        if(strpos($data['file'], $file_dir) === false) {
            return $ret;
        }
        
        $ret = false;
        $filename = FileEntryUtil::getFileDir($data, $file_dir);
        if($filename) {
            $filename_to = $file_dir . 'deleted/' . $data['filename'];
            $ret = $this->moveFile($filename, $filename_to);
            $ret = (empty($ret['status'])) ? false : true;
        }
        
        return $ret; 
    }
    
}
?>