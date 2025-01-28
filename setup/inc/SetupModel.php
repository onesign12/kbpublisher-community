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


class SetupModel extends BaseModel
{

    var $dryrun = false;


    var $tables = array(
        'user', 'priv', 'setting_to_value', 'setting',
        'kb_glossary', 'list_value', 'trigger');


    function __construct() {

    }


    function setTables($tbl_pref = '', $tbl_pref_custom = '') {
        $t1 = $this->_setTableNames($this->tables, $tbl_pref, $tbl_pref_custom);
        $t2 = $this->_setTableNames($this->custom_tables, $tbl_pref);

        $this->tbl =  (object) array_merge($t1, $t2);
    }


    function connect($conf, $force_connect = false) {

        $reg =& Registry::instance();
        if($reg->isEntry('db') && !$force_connect) {
            $db = $this->db = $reg->getEntry('db');

        } else {
            mysqli_report(MYSQLI_REPORT_OFF); //see Assorted,inc.php
            
            $db = ADONewConnection($conf['db_driver']);
            $result = @$db->Connect($conf['db_host'], $conf['db_user'], $conf['db_pass'], $conf['db_base']);
            $db->SetFetchMode(ADODB_FETCH_ASSOC);
            $db->ADODB_COUNTRECS = false;
        
            $this->db = &$db;
            $reg =& Registry::instance();
            $reg->setEntry('db', $db);
            
            if(!$result) {
                return DbUtil::error();
            }

            $sql = "SET sql_mode=''";
            $result = $db->_Execute($sql);
            if(!$result) {
                return DbUtil::error($sql);
            }
        }
        
        // $sql = "SET SESSION query_cache_type=0";
        // $result = $db->_Execute($sql);
        // if(!$result) {
        //     return DbUtil::error($sql);
        // }

        return true;
    }


    // MetaDatabases()
    // Returns a list of databases available on the server as an array.
    // You have to connect to the server first. Only available for ODBC, MySQL and ADO.

    // it assumed server was not started with the --skip-show-database option,
    // http://dev.mysql.com/doc/refman/5.0/en/show-databases.html
    function isDatabaseExists($db_name) {
        $sql = "SHOW DATABASES LIKE '{$db_name}'";
        $result = $this->db->Execute($sql);
        if($result) {
            return bool ($result->RecordCount());
        } else {
            return DbUtil::error($sql);
        }
    }


    function createDB($db_name, $charset, $collation) {
        $sql = $this->getCreateDbSql($db_name, $charset, $collation);
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        return true;
    }


    function getCreateDbSql($db_name, $charset, $collation) {
        $sql = 'CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET %s COLLATE %s';
        $sql = sprintf($sql, $db_name, $charset, $collation);
        return $sql;
    }


    function getCreateDbSqlMsg($values, $charset, $collation) {
        $sql = $this->getCreateDbSql($values['db_base'], $charset, $collation) . ';<br />';

        $str = "GRANT ALL ON %s TO '%s'@'%s'";
        $sql .= sprintf($str, $values['db_base'], $values['db_user'], $values['db_host']);

        if($values['db_pass']) {
            $sql .= sprintf(" IDENTIFIED BY '%s'", $values['db_pass']);
        }

        $sql .= ';';

        return $sql;
    }


    function checkSkipSql($key, $sql, $tbl_pref) {
        return false;
    }


    function executeString($sql, $prefix = array()) {
        $ret = true;
        $result = $this->db->_Execute($sql);
        if(!$result) {
            $ret = DbUtil::error($sql);
        }

        return $ret;
    }


    function executeArray($arr, $prefix = array()) {

        $ret = true;
        $error = false;
        $tables = array();

        foreach(array_keys($arr) as $k) {

            $sql = $arr[$k];

            if($this->checkSkipSql($k, $sql, $prefix)) {
                continue;
            }

            $result = $this->db->_Execute($sql);
            if(!$result) {
                $ret = DbUtil::error($sql);
                $error = true;
                break;
            }

            $tables[] = ParseSqlFile::getTable($sql);
        }

        //?
        if($error) {
            $tables = array_unique($tables);
            $sql = "DROP TABLE IF EXISTS %s";
            $sql = sprintf($sql, implode(', ', $tables));
            //$result = $this->db->_Execute($sql);
        }

        return $ret;
    }


    function setSetupData($data) {
        if(!isset($_SESSION['setup_'])) {
            $_SESSION['setup_'] = array();
        }

        foreach($data as $k => $v) {
            $_SESSION['setup_'][$k] = $v;
        }
    }


    function setStepSession() {
        $step = (isset($_GET['step'])) ? $_GET['step'] : 1;
        $_SESSION['setup_']['step'] = $step;
    }


    function &getSetupData($key = false) {
        if(!isset($_SESSION['setup_'])) {
            $_SESSION['setup_'] = array();
        }

        if($key) {
            $r = (isset($_SESSION['setup_'][$key])) ? $_SESSION['setup_'][$key] : false;
            return $r;
        } else {
            return $_SESSION['setup_'];
        }
    }


    function isUpgrade() {
        return ($this->getSetupData('setup_type') == 'upgrade');
    }


    function isUpgradeWithConfig() {
        if($this->getSetupData('setup_type') == 'upgrade' &&
           $this->getSetupData('old_config_file') &&
           !$this->getSetupData('old_config_file_skip') &&
            strpos($this->getSetupData('setup_upgrade'), '20_to_') === false) {

            return true;
        }

        return false;
    }


    function getMySQLVersion($return = 'float') {
        $sql = "show variables like 'version'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $row = $result->FetchRow();
        $version = $row['Value'];

        if($return == 'full') {

        } elseif($return == 'float') {
            $version = preg_replace("#[^\d.]#", '', $version);
            $version = (float) $version;
        } else {
            $version = preg_replace("#[^\d]#", '', $version);
            $version = (int) substr($version, 0, $return);
        }

        return $version;
    }


    function getMySQLCharset($db_name) {
        $sql = "SELECT default_character_set_name as 'charset',
            default_collation_name as 'collation'
            FROM information_schema.SCHEMATA 
            WHERE schema_name = '$db_name'";
        $result = $this->db->Execute($sql);
        
        $row = [];
        if($result) {
            $row = $result->FetchRow();
        }
        
        return $row;
    }


    function getMySQLOptions($db_name) {
        $options = $this->getMySQLCharset($db_name);
        $options['mysqlv'] = $this->getMySQLVersion();
        
        return $options;
    }


    function getLangSelectRange() {

        $d = new MyDir();
        $d->full_path = true;
        $d->one_level = false;
        $d->setAllowedExtension('php');
        $d->setSkipDirs('.svn', 'CVS', 'tmpl');
        $dirs = $d->getFilesDirs(APP_ADMIN_DIR . 'lang');

        $range = array();
        foreach($dirs as $k => $v) {
            if(!is_numeric($k) && isset($v[0])) {
                require $v[0];
                $range[$k] = sprintf('%s - (%s)', $conf['lang']['name'], $k);
                continue;
            }
        }

        if(!$range) {
            $range = array('en' => 'English');
        }

        return $range;
    }


    function setUser($data) {

        $first_name = addslashes(stripslashes($data['first_name']));
        $last_name = addslashes(stripslashes($data['last_name']));
        $email = addslashes(stripslashes($data['email']));
        $username = addslashes(stripslashes($data['username']));

        // addslashes(stripslashes just in case, as on validate pass the same code used
        $password = addslashes(stripslashes($data['password']));
        $password = HashPassword::getHash($password);

        $sql = "INSERT {$this->tbl->user} SET
        first_name = '$first_name',
        last_name = '$last_name',
        email = '$email',
        username = '$username',
        password = '$password',
        date_registered = NOW(),
        active = 1";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        $user_id = $this->db->Insert_ID();

        $sql = "INSERT {$this->tbl->priv} SET
        user_id = $user_id,
        priv_name_id = 1,
        grantor = $user_id,
        timestamp = NOW()";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        return true;
    }


    function setSupportEmail($email) {
        return $this->setSettingById(41, $email);
    }


    function setAdminEmail($email) {
        return $this->setSettingById(160, $email);
    }


    function setFileDirectory($dir) {
        return $this->setSettingById(20, $dir);
    }


    function setFckDirectory($dir) {
        return $this->setSettingById(128, $dir);
    }


    function setLanguage($value) {
        return $this->setSettingById(235, $value);
    }
	

    function setVersion($value) {
        return $this->setSettingById(377, $value);
    }


    function setSettingById($setting_id, $value) {

        $value = addslashes(stripslashes($value));

        $sql = "REPLACE {$this->tbl->setting_to_value} SET
        setting_id = %d,
        setting_value = '%s'";
        $sql = sprintf($sql, $setting_id, $value);
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        return true;
    }


    function setSettingByIdArray($settings) {

        $data = array();
        foreach($settings as $id => $value) {
            $value = addslashes(stripslashes($value));
            $data[] = array($id, $value);
        }

        $sql = "REPLACE {$this->tbl->setting_to_value} 
            (setting_id, setting_value) VALUES ?";
        
        $sql = MultiInsert::get($sql, $data);
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        return true;
    }


    function setSettingByKey($setting_key, $value) {

        $sql = "SELECT id FROM {$this->tbl->setting} WHERE setting_key = '{$setting_key}'";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        $setting_id = $result->Fields('id');
        return $this->setSettingById($setting_id, $value);
    }
    
    
    function getSetting($setting_key) {

        $ret['error'] = false;

        // $sql = "SELECT sv.setting_value  as value
        // FROM {$this->tbl->setting} s, {$this->tbl->setting_to_value} sv
        // WHERE s.setting_key = '{$setting_key}'
        // AND s.id = sv.setting_id";
        
        // 2018-09-18 changed to use IFNULL(sv.setting_value, s.default_value) as value
        $sql = "SELECT IFNULL(sv.setting_value, s.default_value) as value
        FROM {$this->tbl->setting} s
        LEFT JOIN {$this->tbl->setting_to_value} sv ON s.id = sv.setting_id
        WHERE s.setting_key = '{$setting_key}'";
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            $ret['error'] = DbUtil::error($sql);
            return $ret;
        }

        $ret['val'] = $result->Fields('value');
        return $ret;
    }
    
    
    // need this db upgraded but we need old value 
    function getSettingToValue($setting_id) {

        $ret['error'] = false;

        $sql = "SELECT sv.setting_value as value
        FROM {$this->tbl->setting_to_value} sv
        WHERE sv.setting_id = '{$setting_id}'";
        $result = $this->db->Execute($sql);
        if(!$result) {
            $ret['error'] = DbUtil::error($sql);
            return $ret;
        }

        $ret['val'] = $result->Fields('value');
        return $ret;
    }
    
    
    function getSettingArray($setting_key, $id_param = true) {

        $ret['error'] = false;
        $setting_key = is_array($setting_key) ? $setting_key : explode(",", $setting_key);
        $setting_key = "'" . implode("','", $setting_key) . "'";
        $key_param = ($id_param) ? 's.id' : 's.setting_key';

        $sql = "SELECT {$key_param}, IFNULL(sv.setting_value, s.default_value) as value
        FROM {$this->tbl->setting} s
        LEFT JOIN {$this->tbl->setting_to_value} sv ON s.id = sv.setting_id
        WHERE s.setting_key IN ({$setting_key})";
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            $ret['error'] = DbUtil::error($sql);
            return $ret;
        }

        $ret['val'] = $result->GetAssoc();
        return $ret;
    }
    

    function getDefaultSql() {
        $sql = "SELECT id, default_value FROM {$this->tbl->setting}
        WHERE setting_key LIKE 'default_sql%' AND default_value != ''";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return array('error' => DbUtil::error($sql));
        }

        return $result->GetAssoc();
    }


    // function setDefaultSqlArticleAutomation($sql, $list_data_arr, $install = true) {
    // for articles and files 04.06.2016 
    function setDefaultSqlAutomation($sql, $list_data_arr, $install = true) {

        // set correct value for outdated status
        // for upgrade only
        if(!$install) {

            // get status value
            $ret = $this->getListValue($list_data_arr[0], $list_data_arr[1]);
            if(!empty($ret['error'])) {
                return $ret['error'];
            }

            $list_value = $ret['val']; // actual
            $hardcoded_list_value = 4; // default.sql, after the installation

            $set_outdated_serialized_str = 'a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:%s:"%s";}}';
            $search_str = sprintf($set_outdated_serialized_str, strlen($hardcoded_list_value), $hardcoded_list_value);
            $replacement_str = sprintf($set_outdated_serialized_str, strlen($list_value), $list_value);

            $sql = str_replace($search_str, $replacement_str, $sql);
        }

        return $this->setDefaultSqlEntry($sql);
    }



    function setDefaultSqlEntry($str) {
        // $str = addslashes($str);
        $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $str); // in case in upgrade we have it
        $ret = $this->executeString($sql);
        if($ret !== true) {
            return $ret;
        }
        
        return true;
    }


    function setDefaultSql($values, $install = true) {

        $ret = $this->getDefaultSql();
        if(!empty($ret['error'])) {
            return $ret['error'];
        }

        $sql_array = $ret;
        $tbl_pref = $values['tbl_pref'];

        foreach($sql_array as $sql) {

            $sql = str_replace('{prefix}', $tbl_pref, $sql);

            // article automations
            if(strpos($sql, 'outdated_article') !== false) {
                $ret = $this->setDefaultSqlAutomation($sql, array(1, 'outdated'), $install);
                $ret = true;
            
            // file automations
            } elseif(strpos($sql, 'outdated_file') !== false) {
                $ret = $this->setDefaultSqlAutomation($sql, array(2, 'outdated'), $install);
                $ret = true;

            // email automation
            } elseif(strpos($sql, 'mailbox_') !== false) {
                // skip
                $ret = true;

            // workflow
            } else {
                $ret = $this->setDefaultSqlEntry($sql);
            }

    		if($ret !== true) {
    			return $ret;
    		}
        }

        return $ret;
    }


    function getListValue($list_id, $list_key) {
        $sql = "SELECT list_value FROM {$this->tbl->list_value}
        WHERE list_id = '{$list_id}' AND list_key = '{$list_key}';";
        $result = $this->db->Execute($sql);
        if(!$result) {
            $ret['error'] = DbUtil::error($sql);
            return $ret;
        }

        $ret['val'] = $result->Fields('list_value');
        return $ret;
    }


    function checkPrefixOnUpgrade() {

        $sql = "SELECT 1 FROM {$this->tbl->kb_glossary} LIMIT 1";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        return true;
    }


	function generatePassword($num_sign = 3, $num_int = 2) {
        return WebUtil::generatePassword($num_sign, $num_int);
	}
    
    
    // DRY RUN to test upgrade

    function isUserHasPriv($priv) {
        $sql = "SHOW GRANTS";
        $result = $this->db->_Execute($sql);
        if(!$result) {
            return array('error' => DbUtil::error($sql));
        }
        
        $rights = current($result->FetchRow());
        $ret = ((strpos($rights, 'ALL PRIVILEGES') !== false || strpos($rights, $priv) !== false));
        
        return $ret;
    }

    
    function getTables($tbl_pref) {
        $sql = "SHOW TABLE STATUS LIKE '%s%%'";
        $sql = sprintf($sql, $tbl_pref);
        $result = $this->db->_Execute($sql);
        if(!$result) {
            return array('error' => DbUtil::error($sql));
        }
    
        $tablses = array();
        while($row = $result->FetchRow()) {
            $tables[] = $row['Name'];
        }
    
        return $tables;
    }


    function dropTempTables($tables_arr) {
        $sql = "DROP TEMPORARY TABLE IF EXISTS %s";
        $sql = sprintf($sql, implode(", ", $tables_arr));
        $result = $this->db->_Execute($sql)  or die(db_error($sql));
        if(!$result) {
            return DbUtil::error($sql);
        }
        
        return true;            
    }


    function copyTable($table, $new_table, $with_data = false) {
        $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `%s` LIKE `%s`";
        $sql = sprintf($sql, $new_table, $table);
        $result = $this->db->_Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }

        // if($with_data) {
        //     $sql = "INSERT %s SELECT * FROM %s";
        //     $sql = sprintf($sql, $new_table, $table);
        //     echo '<pre>', print_r($sql,1), '<pre>';
        //     $result = $this->db->_Execute($sql);
        //     if(!$result) {
        //         return DbUtil::error($sql);
        //     }
        // }


        return true;
    }
    
}
?>