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


class FileUtil
{    

   /**
    * read -- get data from file.
    *
    * @param    string    $filename    filename to read
    *
    * @return   string    data from file or false on failure
    * @access   public
    */
    static function read($filename) {

        $data = false;
        if($fp = @fopen($filename, "rb")) {
            $data = fread($fp, filesize($filename));
            fclose($fp);
        }
        return $data;
    }
    

   /**
    * read -- get data from file.
    *
    * @param    string    $filename    filename to read
    *
    * @return   string    data from file or false on failure
    * @access   public
    */
    static function readLine($filename) {

        $data = false;
        if($fp = @fopen($filename, "rb")) {
            $data = fgets($fp);
            fclose($fp);
        }
        return $data;
    }
    
    
   /**
    * write -- write data to file.
    *
    * @param    string    $filename    filename to write
    * @param    string    $data        data to write
    * @param    bool      true/false   truncate file or not
    * 
    * @return   boolean   true/false
    * @access   public
    */
    static function write($filename, $data, $truncate = true) {    
    
        $ret = false;
        if ($fp = @fopen($filename, 'ab')) {
            flock($fp, LOCK_EX);
            
            if ($truncate) {
                ftruncate($fp, 0);
            }
            
            fputs($fp, $data);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            $ret = true;
        }
        return $ret;
    }
    
} // <-- end
?>