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

class FileEntryModel_s3 extends FileEntryModel_dir
{
    
    var $addtype = 3;
    
    
    function readDirectory($directory, $options = array()) {

        $files = array();

        $s3options = FileEntryUtil_s3::getS3OptionsFromDir($directory);
        if(!empty($options['add_dir']) || !empty($options['one_level'])) {
            $s3options['delimiter'] = '/';
        }
        
        $kbs3 = KBS3Client::instance($s3options);
        $list = $kbs3->listObjects($s3options);
        
        $allowed = $this->setting['file_allowed_extensions'];
        $denied = $this->setting['file_denied_extensions'];
        if(!empty($options['skip_file_setting'])) {
            $allowed = array();
            $denied = array();
        }
        
        $dir_remove = '/' . trim($directory, '/') . '/';
        
        $d = new MyDir;
        
        if($objects = $list->get('Contents')) {
            
            foreach($objects as $object) {
                if($object['Size'] == 0) { // could be dir
      
                } elseif($this->isExtensionAllowed(basename($object['Key']), $allowed, $denied)) {
                
                    $dir = $d->getFileDirectory($object['Key']);
                    $files['files'][] = '/' . $s3options['bucket'] . '/' . $object['Key'];
                    // $files['options']['directory'] = '/' . $s3options['bucket'] . '/';
                    $files['options']['directory'] = $dir_remove;
                    $files['options'][] = array(
                        'md5hash' => trim($object['ETag'], '"'),
                        'region' => $kbs3->options['region'],
                        'filesize' => $object['Size'],
                        'last_modified' => $object['LastModified']->format("Ymdhis")
                    );
                }
            }
        }
        
        if (!empty($options['add_dir'])) {
            if($prefixes = $list->get('CommonPrefixes')) {
                $files['dirs'] = ExtFunc::multiArrayToOne($prefixes);
                // $files['options']['directory'] = '/' . $s3options['bucket'] . '/';
                $files['options']['directory'] = $dir_remove;
            }
        }

        return $files;
    }
    
    
    function isDirectoryAllowed($directory, $options = array()) {
        $options = FileEntryUtil_s3::getS3OptionsFromDir($directory);
        $kbs3 = KBS3Client::instance($options);
        return $kbs3->doesBucketExist();
    }
    
    
    function getFileData($file, $foptions = array()) {

        $d = new MyDir;
        $data = array();
        
        $data['name'] = $d->getFilename($file);
        $data['type'] = _mime_content_type($file);
        $data['tmp_name'] = '';
        $data['extension'] = $d->getFileExtension($file);
        $data['size'] = $foptions['filesize'];
        $data['md5hash'] = $foptions['md5hash'];
        $data['to_read'] = '';
        $data['directory'] = $d->getFileDirectory($file);
        
        // add trailing slash
        $data['directory'] = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
        $data['directory'] = $data['directory'] . '/';
        
        $data['name_index'] = $this->getFilenameIndex($data['name']);
        $data['name_disk'] = $data['name'];
        
        // $data['sub_directory'] = serialize(array('region' => $foptions['region']));
        $data['sub_directory'] = json_encode(array('region' => $foptions['region']));
        $data['addtype'] = $this->addtype;
        
        return $data;
    }
    
    
    function getMD5File($file, $foptions = array()) {
        return $foptions['md5hash'];
    }
    
    
    function getFileTime($file, $foptions = array()) {
        return $foptions['last_modified'];
    }
    
    
    // not in use mauybe later, to try to get text from s3 file
    // add task to parse files
    function setS3ParseTask($entry_id, $entry_type) {
        $rule_id = 8;
        $sql = "INSERT IGNORE {$this->tbl->entry_task} SET 
            rule_id = {$rule_id}, 
            entry_id = {$entry_id},
            entry_type = {$entry_type}";
            
        $result = $this->db->_Execute($sql) or die(db_error($sql));
    }
    
    
    function isExtensionAllowed($filename, $allowed, $denied) {
        static $patern;
        if(!$patern) {
            $filetr_ext = ($allowed) ? $allowed : $denied;
            $filetr_ext = array_map( function($val) { return '\\.' . $val; }, $filetr_ext);
            $patern = implode('|', $filetr_ext);
        }
        
        $ret = true;
        if($allowed) {
            $ret = (preg_match("#({$patern})$#", $filename)) ? true : false;
        } elseif($denied) {
            $ret = (preg_match("#({$patern})$#", $filename)) ? false : true;
        }
        
        return $ret;
    }
    
    
    // for array map
    function filterByExtension($val) {
        $allowed = $this->setting['file_allowed_extensions'];
        $denied = $this->setting['file_denied_extensions'];
        return $this->isExtensionAllowed($val, $allowed, $denied);
    }
    
}
?>