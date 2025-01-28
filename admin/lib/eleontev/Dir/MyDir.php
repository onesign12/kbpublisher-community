<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class MyDir
{
    
    var $one_level = false;  // true = all, dir, file, false = recursively
    var $full_path = false;
    
    var $skip_dirs = array();
    var $skip_files = array();
    
    var $allowed_extension = array();
    var $denied_extension = array();
    
    var $skip_regex = array();
    

   /**
    * Read $dirname, return multi array with files and dirs on success
    *
    * @param       string   $dirname    The directory to read
    * @return      bool     Returns true on success, false on failure
    */
    function &getFilesDirs($dirname = '.', $output = array()){
        
        if ($handle = opendir($dirname)) {
            while(false !== ($entry = readdir($handle))) {
            
                if($entry == '.' || $entry == '..') {
                    continue; 
                }
                
                if(in_array($entry, $this->skip_dirs)) {
                    continue; 
                }
                
                if(in_array($entry, $this->skip_files)) {
                    continue; 
                }
                
                foreach($this->skip_regex as $reg) {
                    if(preg_match($reg, $entry)) {
                        continue 2;
                    }
                }
                
                $dirname = preg_replace("#[/\\\]+$#", '', trim($dirname));
                $path = $dirname.'/'.$entry;
                //echo "<pre>"; print_r($path); echo "</pre>";
                //echo "<pre>"; print_r($entry); echo "</pre>";
                
                if(is_dir($path)) {
                    
                    if($this->one_level) {
                        continue;
                    }
                    
                    if(in_array($path, $this->skip_dirs)) {
                        continue; 
                    }
                    
                	if(in_array($path, $this->skip_files)) {
                    	continue;
                	}
                    
                    foreach($this->skip_regex as $reg) {
                        if(preg_match($reg, $entry)) {
                            continue 2;
                        }
                    }                                
                
                    $output[$entry] = $this->getFilesDirs($path); 
                
                } elseif(is_file($path)) {
                    
                    $extension = substr($path, strrpos($path, '.')+1);
                    
                    if(in_array($path, $this->skip_files)) { 
                        continue;
                    
                    // check for allowed extension
                    // if set and current file not in allowed
                    } elseif($this->allowed_extension && !in_array($extension, $this->allowed_extension)){
                        continue;
                    
                    // check for not denied extension
                    // if set and current file in denied and allowed is not set
                    } elseif(!$this->allowed_extension && 
                                $this->denied_extension && in_array($extension, $this->denied_extension)){
                        continue;
                    }
                    
                    foreach($this->skip_regex as $reg) {
                        if(preg_match($reg, $entry)) {
                            continue 2;
                        }
                    }                    
                    
                    if($this->full_path) { $output[] = $path; }
                    else                 { $output[] = $entry; }
                }
            }
            
            closedir($handle);
        }        
        
        return $output;
    }
        
    
   /**
    * Read $dirname, return multi array with dirs on success
    *
    * @param       string   $dirname    The directory to read
    * @return      bool     Returns true on success, false on failure
    */
    function &getDirs($dirname = '.', $output = array()){
        static $call = 0; $call++;
        
        if($this->one_level && $call > 1) {
            return $output;
        }                    
        
        
        if (@$handle = opendir($dirname)) {
            while(false !== ($entry = readdir($handle))) {
            
                if($entry == '.' || $entry == '..') {
                    continue; 
                }
                
                if(in_array($entry, $this->skip_dirs)) {
                    continue; 
                }
                
                foreach($this->skip_regex as $reg) {
                    if(preg_match($reg, $entry)) {
                        continue 2;
                    }
                }                
                
                $dirname = preg_replace("#[/\\\]+$#", '', trim($dirname));
                $path = $dirname.'/'.$entry;
                $short_path = basename($path); 
                
                if(is_dir($path)) {
                                        
                    if(in_array($path, $this->skip_dirs)) {
                        continue; 
                    }                    
                    
                    foreach($this->skip_regex as $reg) {
                        if(preg_match($reg, $path)) {
                            continue;
                        }
                    }
                    
                    if($this->one_level) {
                        $output[$short_path] = $path;
                    } else {
                        $output[$short_path] = array('path'=>$path);
                    }
                    
                    if($ret = $this->getDirs($path)) {
                        $output[$short_path]['subdir'] = $ret;
                    }
                    
                }
            }
            
            closedir($handle);
        }
        
        return $output;
    }
    
    
   /**
    * Delete a file, or a folder and its contents
    *
    * @param       string   $dirname    The directory to delete
    * @param       integer  $days       Delete only older that $days
    * @return      bool     Returns true on success, false on failure
    */
    function removeFilesDirs($dirname = '.', $days = 0) {
        
        if($days) {
            $days_old = floor((time() - filemtime($dirname)) / 86400);
            if($days_old <= $days) {
                return null;
            }
        }
        
        if (is_file($dirname)) {
            return unlink($dirname);
        }
        
        $dir = dir($dirname);
        while (false !== ($entry = $dir->read())) {
            
            if($entry == '.' || $entry == '..') {
                continue; 
            }            
            
            // deep delete directories  
            $dirname = preg_replace("#[/\\\]+$#", '', trim($dirname));
            $path = $dirname . '/' . $entry;
            
            if (is_dir($path)) { 
                $this->removeFilesDirs($path, 0); 
            } else {
                unlink($path); 
            }
        }
        
        $dir->close();
        return rmdir($dirname);
    }
    

   /**
    * Sets directories to skip (not parse)
    * Depends on var $full_path
    * it may work unexpetedly if you set var $full_path = false; in this case all files and dir
    * will have one word only and one skip dir or file can have many matches inside all the tree
    *
    * @param       mixed   $extension    directory name
    * @return      void     
    */
    function setSkipDirs($dirname) {
        if($dirname) {
            $dirname = (is_array($dirname)) ? $dirname : func_get_args();
            foreach($dirname as $k => $v) {
                $this->skip_dirs[] = $v;
            }            
        }
    }
    

   /**
    * Sets files to skip (not parse)
    * Depends on var $full_path
    * for filename use top_dir/sub_dir/filename.php 
    * it may work unexpetedly if you set var $full_path = false; in this case all files and dir
    * will have one word only and one skip dir or file can have many matches inside all the tree
    *
    * @param       mixed   $extension    filename
    * @return      void     
    */
    function setSkipFiles($filename) {
        if($filename) {
            $filename = (is_array($filename)) ? $filename : func_get_args();
            foreach($filename as $k => $v) {
                $this->skip_files[] = $v;
            }            
        }
    }

    
   /**
    * Define allowed extensions
    *
    * @param       mixed   $extension    allowed extesions
    * @return      void     
    */    
    function setAllowedExtension($extension) {
        if($extension) {
            $extension = (is_array($extension)) ? $extension : func_get_args();
            foreach($extension as $k => $v) {
                $this->allowed_extension[] = str_replace('.', '', $v);
            }            
        }
    }
    
    
    function setDeniedExtension($extension) {
        if($extension) {
            $extension = (is_array($extension)) ? $extension : func_get_args();
            foreach($extension as $k => $v) {
                $this->denied_extension[] = str_replace('.', '', $v);
            }            
        }
    }    
    

   /**
    * Define skip regex
    *
    * @param       mixed   $regex    full format regex
    * @return      void     
    */    
    function setSkipRegex($regex) {
        if($regex) {
            $regex = (is_array($regex)) ? $regex : func_get_args();
            foreach($regex as $k => $v) {
                $this->skip_regex[] = $v;
            }            
        }
    }

    
    static function getFileExtension($path) {
        return substr($path, strrpos($path, '.')+1);
    }
    
    function getFilename($path) {
        return basename($path);
    }
    
    function getFileDirectory($path) {
        return str_replace('\\', '/', dirname($path));
    }
    
    function _toArray($val) {
        if(!is_string($val[0])) { return $val[0]; }
        return $val;
    }
    
    // static function getMimeContentType($path, $force_own = false) {
    //     if (function_exists('mime_content_type') || $force_own) {
    //         // require_once 'eleontev/Dir/mime_content_type.php';
    //         echo __DIR__ . '/mime_content_type.php';
    //         echo dirname(__DIR__) . '/mime_content_type.php';
    //         exit;
    //         require_once __DIR__ . '/mime_content_type.php';
    //         return _mime_content_type($path);
    //     } else {
    //         return mime_content_type($path);
    //     }
    // }
    
}

/*
require_once '/Volumes/dataHD_1/www/vhosts/localhost/kbp/kbp_dev/admin/lib/eleontev/Assorted.inc.php';
error_reporting(E_ALL);

$d = new MyDir;
$d->one_level = true;
$d->full_path = true;
$d->setSkipDirs('.svn', 'cvs','.SVN', 'CVS');
// $d->setAllowedExtension('php', 'ini');
$d->setAllowedExtension(null);
$d->setDeniedExtension('php', 'ini');
// $d->setSkipRegex('#^\.ht*#i');
// $d->setSkipRegex('#.*?adodb.*?#i');
// 
// $a = &$d->getFilesDirs('/Volumes/dataHD_1/www/vhosts/localhost/kbp/kbp_dev/admin/cron');
// // $a = &$d->multiArrayToOne($a);
// $a = ExtFunc::multiArrayToOne($a); 
$b = &$d->getDirs('/Volumes/dataHD_1/www/vhosts/localhost/kbp/kbp_dev/admin/lib');

// echo "<pre>"; print_r($d); echo "</pre>";
echo "<pre>"; print_r($b); echo "</pre>";
*/


/*
function multiArrayToString($arr, $glue=',') { 
    foreach($arr as $k => $v) {
        if(is_array($v)) { 
            $arr[$k] = multiArrayToString($v, $glue); 
        }
    }
    return (join($glue, $arr));
} 
*/

/*
function multiArrayToString($arr, $glue='/', $dir = 'sdd/jahsdshdk', $output = array()) {
    foreach($arr as $_dir => $v) {
        if(is_array($v)) {
            $d[$_dir] = $_dir;
            $output = multiArrayToString($v, $glue, $d, $output) + $output;
        } else {
            $dir = (is_array($dir)) ? implode('/', $dir) . '/' : '';
            $output[] = $dir . $v;
        } 
    }
    
    return $output;
}

echo "<pre>"; print_r(multiArrayToString($a)); echo "</pre>";
*/

// $d = array();
// $d['admin'] = array('/path/to/file');
// $d['admin'][0] = array('/path/to/file/2');
// 
// echo '<pre>', print_r($d, 1), '</pre>';
// 
// $preg = "#.adodb.#i";
// $preg = "#^\.ht*#i";
// $str = '.htaccess/zn,cmn,zmnxc';
// 
// preg_match($preg, $str, $match);
// echo '<pre>', print_r($match, 1), '</pre>';


?>