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
 * BackupUtil is a class to backup databases.
 *
 * @version 1.0
 * @since 03/01/2007
 * @author Evgeny Leontev
 * @access public
 *
 * EXAMPLE
 *
 *
 * CHANGELOG
 * 06/15/2004 - release
 */
class BackupUtil
{

    var $db_host;
    var $db_base;
    var $db_user;
    var $db_pass;
    var $db_path;

    var $backup_dir;
    var $filename = 'dump.sql';
    var $win;


    function __construct() {
        $this->win = $this->isWin();
    }

    function isWin() {
        return (substr(PHP_OS, 0, 3) == "WIN");
    }

    function setDB($data) {
        $this->db_host = $data['db_host'];
        $this->db_base = $data['db_base'];
        $this->db_user = $data['db_user'];
        $this->db_pass = $data['db_pass'];
    }

    function &factory($engine) {
        $class = 'Backup_' . $engine;
        return new $class;
    }

    function _getDBPath() {
        $db_path = $this->db_path;
        if($db_path) {
            $db_path = str_replace('\\', '/', $this->db_path) . '/';
            $db_path = str_replace('//', '/', $db_path);
        }

        return $db_path;
    }
}


class BackupUtil_mysql extends BackupUtil
{

    function getDumpCmd() {

        $date = date('Y_m_d_h_i_');
        $filename = $date . $this->backup_dir . $this->filename;
        $db_path = $this->_getDBPath();

        if($this->win) {
            $cmd_str = '%smysqldump --opt -h %s -u %s -p%s %s > %s';
            $cmd = sprintf($cmd_str, $db_path, $this->db_host, $this->db_user,
                                     $this->db_pass, $this->db_base, $filename);
        } else {
            $cmd_str = '%smysqldump --opt -h %s -u %s -p%s %s | gzip > %s.gz';
            $cmd = sprintf($cmd_str, $db_path, $this->db_host, $this->db_user,
                                     $this->db_pass, $this->db_base, $filename);
        }

        return $cmd;
    }

    function getRestoreCmd($filename) {

        $db_path = $this->_getDBPath();

        if(!$this->win) {
            $cmd_str = '%smysql -h %s -u %s -p%s %s < %s';
            $cmd = sprintf($cmd_str, $db_path, $this->db_host, $this->db_user,
                                     $this->db_pass, $this->db_base, $filename);
        } else {
            $cmd_str = '%smysql gunzip < %s | -h %s -u %s -p%s %s';
            $cmd = sprintf($cmd_str, $db_path, $filename, $this->db_host, $this->db_user,
                                     $this->db_pass, $this->db_base);
        }

        return $cmd;
    }

    function dump() {
        $str = '';
        //system
    }

    function restore() {

    }


}

/*
$conf['db_host']    = "localhost";
$conf['db_base']    = "dbbase";
$conf['db_user']    = "user";
$conf['db_pass']    = "pass";
$conf['db_path']      = "";

$backup = BackupUtil::factory('mysql');
$backup->setDB($conf);
$backup->setDB($conf);
//echo $backup->getDumpCmd();
echo $backup->getRestoreCmd('test');
*/


/*
C:\> C:/mysql/bin/mysqldump --opt -u root kb_25 > sql.dump

C:\> C:/mysql/bin/mysql sql.dump > -u root kb_25_1

C:\> C:/mysql/bin/mysql -u root kb_25_1 < C:/sql.dump


C:/mysql/bin/mysqldump --opt -u root kb_25 | gzip > sql.dump

mysqldump -h host -u user -ppassword $dbname | gzip > $backupFile

// restore unix
gunzip < custback.sql.gz | mysql -u sadmin -p pass21 Customers
*/
?>