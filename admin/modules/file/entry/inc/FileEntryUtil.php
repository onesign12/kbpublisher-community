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


class FileEntryUtil
{

    static function sendFileDownload($data, $file_dir, $attachment = true) {
        if(self::isS3($data)) {
            return FileEntryUtil_s3::sendFileDownload($data, $file_dir, $attachment);
        } else {
            return FileEntryUtil_local::sendFileDownload($data, $file_dir, $attachment);
        }
    }
    

    static function getFileDir($data, $file_dir) {
        if(self::isS3($data)) {
            return FileEntryUtil_s3::getFileDir($data, $file_dir);
        } else {
            return FileEntryUtil_local::getFileDir($data, $file_dir);
        }
    }


    static function getFilePath($data, $file_dir, $quick = false) {
        if(self::isS3($data)) {
            return FileEntryUtil_s3::getFilePath($data, $file_dir, $quick);
        } else {
            return FileEntryUtil_local::getFilePath($data, $file_dir, $quick);
        }
    }
    
    
    static private function isS3($data) {
        return (isset($data['addtype']) && $data['addtype'] == 3);
    }
    
}


class FileEntryUtil_local
{

    static function sendFileDownload($data, $file_dir, $attachment = true) {
        if($file_dir === 'quick') {
            $params['file'] = self::getFilePath($data, '', true);
        } elseif ($file_dir == 'file') {
            $params['file'] = $data['file'];
        } else {
            $params['file'] = self::getFileDir($data, $file_dir);
        }
        $params['gzip'] = false; //true;
        $params['contenttype'] = $data['filetype'];

        return WebUtil::sendFile($params, $data['filename'], $attachment);
    }
    

    static function getFileDir($data, $file_dir) {
        
        $files = array();
        
        $directory = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
        $filename = (!empty($data['filename_disk'])) ? $data['filename_disk'] : $data['filename'];
        
        $files[1] = $directory . '/' . $filename;
        $files[2] = $file_dir . $filename;
        
        foreach($files as $file) {
            if(file_exists($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    
    static function getFilePath($data, $file_dir, $quick = false) {
        $path = false;
        if(!$quick) { // by default search 2 places
            $path = self::getFileDir($data, $file_dir);
        }
        
        if(!$path) { // if not found just return what in db
            $directory = preg_replace("#[/\\\]+$#", '', trim($data['directory'])); // remove trailing slash
            $filename = (!empty($data['filename_disk'])) ? $data['filename_disk'] : $data['filename'];
            $path = $directory . '/' . $filename;
        }
                
        return $path;
    }
    
}


class FileEntryUtil_s3
{
    
    static function sendFileDownload($data, $file_dir, $attachment = true) {
                
        $options = self::getOptionsFromFileData($data);
        $kbs3 = KBS3Client::instance($options);
        
        if($attachment) {
            return $kbs3->getAttachment($data);
        } else {
            return $kbs3->getinline($data);
        }
    }
    
    
    static function getFileDir($data, $kbs3 = false) {
        
        $options = self::getOptionsFromFileData($data);
        $kbs3 = KBS3Client::instance($options);
        
        $ret = $kbs3->doesObjectExist();
        return ($ret) ? self::getFilePath($data, '') : false;
    }
    
    
    static function getFilePath($data, $file_dir, $quick = false) {
        $options = self::getOptionsFromFileData($data);
        return KBS3Client::getFileUrl($options);
    }
    
    
    static function getOptionsFromFileData($data) {
        $data['sub_directory'] = RequestDataUtil::stripVars($data['sub_directory'], array(), 'skipslashes');
        
        $region = json_decode($data['sub_directory'], true)['region']; // region here
        $dirs = explode('/', trim($data['directory'], '/'));
        $bucket = $dirs[0];
        unset($dirs[0]);
        
        $directory = ($dirs) ? implode('/', $dirs) . '/' : '';
        $keyname = $directory . $data['filename'];
        
        $options = array(
            'region' => $region, 
            'bucket' => $bucket,
            'keyname' => $keyname
        );
        
        return $options;
    }
    
    
    static function getS3OptionsFromDir($directory) {        
        $dirs = explode('/', trim($directory, '/'));
        $bucket = $dirs[0];
        unset($dirs[0]);
        
        $keyname = ($dirs) ? implode('/', $dirs) : '';        
        
        $options = array(
            'bucket' => $bucket,
            'keyname' => $keyname,
            // 'maxkeys' => 100
        );
        
        return $options;
    }
    
}

?>