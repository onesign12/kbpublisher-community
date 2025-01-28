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

/**
 * Uploader is a class used to manage uploading.
 *
 * @since 20/05/2003
 * @author Evgeny Leontev <eleontev@gmail.com>
 * @access public
 */

/*
UPLOAD_ERR_OK
Value: 0; There is no error, the file uploaded with success.

UPLOAD_ERR_INI_SIZE
Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.

UPLOAD_ERR_FORM_SIZE
Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.

UPLOAD_ERR_PARTIAL
Value: 3; The uploaded file was only partially uploaded.

UPLOAD_ERR_NO_FILE
Value: 4; No file was uploaded.
*/

class Uploader
{

    var $allowed_type       = array();
    var $denied_type        = array();
    var $allowed_extension  = array();
    var $denied_extension   = array();

    var $max_file_size      = array(500);        // max file size in kb (1kb = 1024b)
    var $not_uploaded       = array();
    var $dest_dir;
    var $suffix;
    var $error = false;
    var $store_in_db = false; // db
    var $uploaded_chmode = 0644; // 0600            // set permisson for uploaded files
    var $safe_name = false;
    var $safe_name_extensions = array('gif', 'jpg', 'jpeg', 'png'); // we always use nameToSafe with these extensions

    var $rename_rule;
    var $rename_value;

    var $save_dir_rule;
    var $save_dir_value;


    // set permitted_ext for upload
    function __construct() {
        $this->setRenameValues('suffix', 3);
    }

    // suffix, date or custom
    // if $rename = su, then use $rename_value
    // if $rename = custom, then use $rename_value
    function setRenameValues($rule, $value = false) {

        $this->rename_rule = $rule;

        if($this->rename_rule == 'date') {
            $this->rename_value = (!$value) ? '_' . date('ymdHis') : '_' . date($value);

        } elseif($this->rename_rule == 'custom') {
            $this->rename_value = $value;

        } elseif($this->rename_rule == 'suffix') {
            $value = (!$value) ? 3 : $value;
            $this->setSuffix($value);
            $this->rename_value = $this->suffix;
        }
    }


    // set suffix for reason unique
	function setSuffix($num) {
		mt_srand($this->_make_seed());
		$this->suffix = '_' . preg_replace_callback("/(.)/",
		function ($matches) {
			return chr(mt_rand(ord('0'),ord('9')));
		}, str_repeat('.',$num));
	}


    // seed with microseconds
    function _make_seed() {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }


    // set allowed type for upload example: 'application/pdf', 'application/msword'
    function setAllowedType($type) {
        $type = (is_array($type)) ? $type : func_get_args();
        foreach($type  as $k => $v) {
            $this->allowed_type[] = $v;
        }
        // remove all allowed from denied
        $this->denied_type = array_diff($this->denied_type, $this->allowed_type);
    }


    function setDeniedType($type) {
        $type = (is_array($type)) ? $type : func_get_args();
        foreach($type  as $k => $v) {
            $this->denied_type[] = $v;
        }
        // remove all allowed from denied
        $this->denied_type = array_diff($this->denied_type, $this->allowed_type);
    }


    function setAllowedExtension($extension) {
        $extension = (is_array($extension)) ? $extension : func_get_args();
        foreach($extension as $k => $v) {
            $this->allowed_extension[] = $v;
        }
        // remove all allowed from denied
        $this->denied_extension = array_diff($this->denied_extension, $this->allowed_extension);
    }


    function setDeniedExtension($extension) {
        $extension = (is_array($extension)) ? $extension : func_get_args();
        foreach($extension as $k => $v) {
            $this->denied_extension[] = $v;
        }
        // remove all allowed from denied
        $this->denied_extension = array_diff($this->denied_extension, $this->allowed_extension);
    }


    // set permitted size for upload
    // can specified different sizes for diiferent types
    function setMaxSize($size, $ext = 0) {
        $this->max_file_size[$ext] = $size;
    }


    // return true if file size not bigger
    function maxFileSizeCheck($size, $type = false, $error = false) {

        if($type && isset($this->max_file_size[$type])) {
            $allowed_size = $this->max_file_size[$type] * 1024;

        } else {
            $allowed_size = $this->max_file_size[0] * 1024;
        }

        //1 - The uploaded file exceeds the upload_max_filesize directive in php.ini.
        //2 - The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
        if($error == 1 || $error == 2) {
            return false;

        } elseif ($size > $allowed_size) {
            return false;
        }

        return true;
    }


    function setUploadedDir($dir, $perm = 0755) {
        if(!is_dir($dir)) {
            $oldumask = umask(0);
            mkdir ($dir, $perm); 
            umask($oldumask);
        }

        $this->dest_dir = $dir;
    }


    // $files should be like $_FILES or maybe $_FILES['some_name']
    // in form field should be specified as simple name 'image' not as 'image[]'
    function upload($files) {

        $already_uploaded = array();

        foreach($files as $k => $v) {

            $v['extension'] = strtolower(substr($v['name'], strrpos($v['name'], ".")+1));

            //check for allowed type
            if(!is_writable($this->dest_dir) && $this->store_in_db == false) {
                $uploaded['bad']['not_writable_dir'] = $this->dest_dir;
                break;

            // if file uploaded already if multiple upload
            } elseif(!$v['name'] || in_array($v['name'], $already_uploaded)) {
                continue;

            //check for allowed type
            } elseif($this->allowed_type && !in_array($v['type'], $this->allowed_type)){
                $uploaded['bad']['wrong_type'][$v['name']] = $v['type'];
                continue;

            //check for not denied type
            } elseif($this->denied_type && in_array($v['type'], $this->denied_type)){
                $uploaded['bad']['wrong_type'][$v['name']] = $v['type'];
                continue;

            //check for allowed extension
            } elseif($this->allowed_extension && !in_array($v['extension'], $this->allowed_extension)){
                $uploaded['bad']['wrong_extension'][$v['name']] = $v['extension'];
                continue;

            //check for not denied extension
            } elseif($this->denied_extension && in_array($v['extension'], $this->denied_extension)){
                $uploaded['bad']['wrong_extension'][$v['name']] = $v['extension'];
                continue;

            // too big image size
            } elseif(!$this->maxFileSizeCheck($v['size'], $v['type'], $v['error'])) {
                $uploaded['bad']['big_size'][$v['name']] = $this->getFileSize($v['size']);
                continue;

            // too big image size
            } elseif($v['error'] == 1) {
                $uploaded['bad']['big_size'][$v['name']] = $this->getFileSize($v['size']);
                continue;

            // The uploaded file was only partially uploaded.
            } elseif($v['error'] == 3) {
                $uploaded['bad']['partially'][$v['name']] = $v['size'];
                continue;

            // No file was uploaded.
            } elseif($v['error'] == 4) {
                $uploaded['bad']['not_known'][$v['name']] = $v['none'];
                continue;

            } else {

                if($this->safe_name || in_array($v['extension'], $this->safe_name_extensions)) {
                    $v['name'] = $this->nameToSafe($v['name']);
                } else {
                    $v['name'] = stripslashes($v['name']);
                }

                 // 27-06-2024 eleontev, added trim
                 // it failed on update without file, on draft 
                $v['name'] = trim($v['name']);
                
                $v['name_orig'] = $v['name'];

                if($this->store_in_db == false) {
                    $this->_upload($uploaded, $k, $v);
                } else {
                    $this->_return($uploaded, $k, $v);
                }

                $already_uploaded[] = $v['name']; // not to upload twice
            }
        }

        return $uploaded;
    }


    function validate($files, $options = array()) {

        $total_size = 0;
        $ret = array();

        // check for dir for public we may need another msg than for admin 
        if(!empty($options['check_dir']) && !is_writable($this->dest_dir)) {
            $ret['not_writable_dir'] = $this->dest_dir;
            return $ret;
        }
            
        //check for allowed type
        foreach($files as $v) {
            $extension = strtolower(substr($v['name'], strrpos($v['name'], '.') + 1));

            if($this->allowed_extension && !in_array($extension, $this->allowed_extension)) {
                $ret['wrong_extension'][$v['name']] = $extension;
                continue;

            } elseif($this->denied_extension && in_array($extension, $this->denied_extension)) {
                $ret['wrong_extension'][$v['name']] = $extension;
                continue;

            } elseif(!$this->maxFileSizeCheck($v['size'])) {
                $ret['big_size'][$v['name']] = $this->getFileSize($v['size']);
                continue;
            }

            $total_size += $v['size'];
		}

        $post_max_size = Uploader::getIniValue('post_max_size');
        if($total_size > $post_max_size) {
            $ret['big_total_size'] = $this->getFileSize($total_size);
        }

        return $ret;
    }


    // $options = ['filename_to', 'touch']
    function move($filename, $dir_from, $options = [], $copy = false) {

        $dir_from = preg_replace("#[/\\\]+$#", '', trim($dir_from));
        $dir_to = preg_replace("#[/\\\]+$#", '', trim($this->dest_dir));
        
        $file_from = $dir_from .'/'. $filename;
        @$filename_to = ($options['filename_to']) ?: $filename;
        $file_to = $dir_to .'/'.  $filename_to;
        
        $new_filename = $filename_to;
        if(file_exists($file_to) && $this->rename_rule) {
            $new_filename = $this->getSuffixedName($filename_to);
            $file_to = $dir_to .'/'. $new_filename;
        }

        $func = ($copy) ? 'copy' : 'rename';
        $status = @$func($file_from, $file_to);
        
        // to change filemtime
        if($status && !empty($options['touch'])) {
            touch($file_to);
        }
        
        $ret = array(
            'status' => $status, 
            'new_filename' => $new_filename, 
            'new_file' => $file_to
        );
            
        return $ret;
    }


    function copy($filename, $dir_from, $options = []) {
        return $this->move($filename, $dir_from, $options, true);
    }


    function getSuffixedName($name) {
        $ext = substr($name, strrpos($name,"."));
        $name = substr($name, 0, strpos($name,"."));
        return $name . $this->rename_value . $ext;
    }


    function _upload(&$uploaded, $k, $v) {

        $file = $this->dest_dir . $v['name'];
        if(file_exists($file) && $this->rename_rule) {
            $v['name'] = $this->getSuffixedName($v['name']);
            $file = $this->dest_dir . $v['name'];
        }

        $move = move_uploaded_file($v['tmp_name'], $file);

        if(@!$move) {
            $this->error = true;
            $uploaded['bad']['not_known'][$v['name']] = 'none'; // not uploaded img array
        } else {
            @chmod($file, $this->uploaded_chmode);  // octal; correct value of mode
            $key = substr($k, strrpos($k, "_")+1);
            $uploaded['good'][$key] = $v;
        }
    }


    function _return(&$uploaded, $k, $v) {
        $key = substr($k, strrpos($k, "_")+1);
        $uploaded['good'][$key] = $v;
    }


    // just get real filename whithout suffix which created in uploading
    function stripSuffix($name, $num) {
        $search = "/(_\d{$num}\.)/";
        $replace = '';
        return preg_replace($search, $replace, $name);
    }


    static function &getFileContent($filename) {

        $content = false;

        @$fp = fopen($filename, 'rb');
        if($fp) {
            $content = fread($fp, filesize($filename));
            fclose($fp);
        }

        return $content;
    }


    static function errorBox($errors, $msg = array()) {

        $msg = ($msg) ? $msg : AppMsg::getErrorMsgs();

        if(isset($errors['not_writable_dir'])) {

            $msg_error = array('title' => $msg['error_title_msg'],
                               'body'  => $msg['not_writable_dir_msg']);

            return BoxMsg::factory('error', $msg_error);
        }


        $html = array();
        $html[] = '<table class="sTable tableCs2">';

        $html[] = '<tr>';
        $html[] = '<td><b>' . $msg['file_name_msg'] . '</b></td>';
        $html[] = '<td><b>' . $msg['error_type_msg'] . '</b></td>';
        $html[] = '<td><b>' . $msg['value_msg'] . '</b></td>';
        $html[] = '</tr>';

        foreach($errors as $k => $v) {
            foreach($v as $k1 => $v1) {

                // added for files with same name
                if (is_array($v1)) {
                    foreach($v1 as $k2 => $v2) {
                        $html[] = '<tr><td colspan="3" bgcolor="#808080" style="height: 1px;padding: 0;"></td></tr>';
                        $html[] = '<tr>';
                        $html[] = '<td>'.$k1.'</td>';
                        $html[] = '<td>'.$msg[$k.'_msg'].'</td>';
                        $html[] = '<td>'.$v2.'</td>';
                        $html[] = '</tr>';
                    }

                } else {
                    $html[] = '<tr><td colspan="3" bgcolor="#808080" style="height: 1px;padding: 0;"></td></tr>';
                    $html[] = '<tr>';
                    $html[] = '<td>'.$k1.'</td>';
                    $html[] = '<td>'.$msg[$k.'_msg'].'</td>';
                    $html[] = '<td>'.$v1.'</td>';
                    $html[] = '</tr>';
                }
            }

        }

        //$html[] = '<tr><td colspan="3" bgcolor="#808080"></td></tr>';
        $html[] = '</table>';

        $msg_error = array(
            'title'  => $msg['error_title_msg'],
            'title' => $msg['uploaded_error_msg'],
            'body'   => implode("\n", $html)
        );

        return BoxMsg::factory('error', $msg_error);
    }


    static function getErrorText($errors, $msg = array()) {
        
        $msg = ($msg) ? $msg : AppMsg::getErrorMsgs();

        $error_msg = array();
        foreach($errors as $msg_key => $v) {
            if(is_array($v)) {
                $str = '<b> %s </b> <br/> %s (%s)';
                foreach ($v as $filename => $value) {
                    $error_msg[] = sprintf($str, $filename, $msg[$msg_key . '_msg'], $value);
                }
            } else {
                $str = '%s (%s)';
                $error_msg[] = sprintf($str, $msg[$msg_key . '_msg'], $v);
            }
        }

        return $error_msg;
    }


   /**
    * Format a file name to be safe
    * not permitted chars are replaced with "_"
    *
    * @param    string $file   The string file name
    * @param    int    $maxlen Maximun permited string lenght
    * @return   string Formatted file name
    */
    function nameToSafe($name, $maxlen = 250) {
        $name = substr($name, 0, $maxlen);
        return preg_replace('#[^a-zA-Z0-9,._\+\()\-]#', '_', $name);
    }


    function getFileSize($file){
        return WebUtil::getFileSize($file);
    }


    static function getIniValue($key) {
        return WebUtil::getIniSize($key);
    }
}
?>