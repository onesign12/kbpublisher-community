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

class GetMsg
{    

    var $file_type = 'php'; // also php, multi_ini
    var $types = array(
        'ini'=>'parseIni', 
        'multi_ini'=>'parseMultiIni', 
        'php'=>'parsePhp'
    );
    
    var $extensions = array(
        'ini'=>'ini', 
        'multi_ini'=>'ini', 
        'php'=>'php'
    );
    
    
    function __construct($dir = '', $type = 'php') {
        $this->setFileDir($dir);
        $this->setFileType($type);
    }
    
    
    function setFileType($type) {
        $this->file_type = $type;
    }
    
    
    function setFileDir($dir) {
        $this->file_dir = $dir;
    }
    
    
    function & get($file_key, $key_1 = false, $key_2 = false) {
        $func = $this->types[$this->file_type];
        $file = $this->_getFilePath($file_key);
        
        return GetMsgHelper::$func($file, $key_1, $key_2);
    }
        
    
    function _getFilePath($file_key) {
        return $this->file_dir . $file_key . '.' . $this->extensions[$this->file_type];
    }
}

?>