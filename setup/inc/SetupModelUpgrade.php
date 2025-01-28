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


class SetupModelUpgrade extends SetupModel
{
	
	static function factory($version) {
		$class = self::getClass($version);
		return new $class;
	}
	
	
	static function getClass($version) {
		if($version == 'skip') {
    		return 'SetupModelUpgrade_skip';	
		}
		
		// SetupModelUpgrade_20_to_301
		preg_match('#\d+_to_(\d+)#', $version, $matches);
		// echo '<pre>' . print_r($version, 1) . '</pre>';
		// echo '<pre>' . print_r($matches, 1) . '</pre>';
		// exit;
		$to_version = $matches[1];
		$to_file = 'inc/SetupModelUpgrade_' . $to_version . '.php';
		if(file_exists($to_file)) {
			require_once $to_file;
		}
		
		$class = 'SetupModelUpgrade_' . $version;
		return $class;
	}
	
	// upgrade setting_to_value table
	function setCommonSettings($values) {

		if(!empty($values['file_dir'])) {
			$ret = $this->setFileDirectory($values['file_dir']);
			if($ret !== true) {
				return $ret;
			}		
		}
		
		if(!empty($values['html_editor_upload_dir'])) {
			$ret = $this->setFckDirectory($values['html_editor_upload_dir']);
			if($ret !== true) {
				return $ret;
			}		
		}
		
		return true;
	}
}


class SetupModelUpgrade_skip extends SetupModelUpgrade
{
    function execute($values) {
        return true;
    }
}


// complete
class SetupModelUpgrade_20_to_301 extends SetupModelUpgrade
{
	
	var $tbl_pref_custom;
	var $tables = array('entry', 'category', 'entry_to_category', 'setting_to_value', 'comment');
	var $custom_tables = array('user','member');
	
	
	// some check implemented here 
	// this will be caled inside executeArray
	function checkSkipSql($key, $sql, $tbl_pref) {	
		
		// skip drop index in priv if no index "user"
		if($key == 0 && strpos($sql, 'DROP INDEX `user`') !== false) {
		
			$sql = "SHOW INDEX FROM {$tbl_pref}priv";			
			$result = $this->db->_Execute($sql);
			while($row = &$result->FetchRow()) {
				if($row['Key_name'] == 'user') {
					return false; // no need to skip
				}				
			}
			
			return true; // skip, not user index found
		}
		
		return false;
	}
	
	
	function execute($values) {
		
		$file = 'db/upgrade_2.0_to_3.0.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));
		
		$tbl_pref = $values['tbl_pref']; // we have valid prefix here
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array, $tbl_pref);

		if($ret !== true) {
			return $ret;
		}

		
		$this->setTables($tbl_pref, 'kb_');
		$ret = $this->upgradeEntries();
		if($ret !== true) {
			return $ret;
		}
		
		$this->setTables($tbl_pref, 'file_');
		$ret = $this->upgradeEntries();
		if($ret !== true) {
			return $ret;
		}

		$this->setTables($tbl_pref, 'kb_');
		$ret = $this->upgradeUsers(false);
		if($ret !== true) {
			return $ret;
		}
		
		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');
		
		if(!empty($values['file_dir'])) {
			$ret = $this->setFileDirectory($values['file_dir']);
			if($ret !== true) {
				return $ret;
			}		
		}
		
		if(!empty($values['html_editor_upload_dir'])) {
			$ret = $this->setFckDirectory($values['html_editor_upload_dir']);
			if($ret !== true) {
				return $ret;
			}		
		}
		
		return true;
	}
	
	
	// add category_id to entry, file
	function upgradeEntries() {
	
		$sql = "SELECT entry_id, category_id FROM {$this->tbl->entry_to_category} WHERE is_main = 1";
		$result =& $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}		
		
		
		$sql_str = "UPDATE {$this->tbl->entry} SET date_updated = date_updated, category_id = %d WHERE id = %d";
		while($row = $result->FetchRow()) {
			$sql = sprintf($sql_str, $row['category_id'], $row['entry_id']);
			$result1 = $this->db->_Execute($sql);
			if(!$result1) {
				return DbUtil::error($sql);
			}
		}
		
		return true;
	}
	
	
	// move members to users
	function upgradeUsers() {
	
		$sql = $this->getDublicateMembersByEmailSql();
		$result = $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}		
		
		
		$users = &$result->GetAssoc();
		if($users) {
			$users = array_chunk($users, 20);		
			
			foreach(array_keys($users) as $k) {
				$member_ids = (implode(',', $users[$k]));
				$sql = "DELETE FROM {$this->tbl->member} WHERE id IN ({$member_ids})";
				$result = $this->db->_Execute($sql);
				if(!$result) {
					return DbUtil::error($sql);
				}
			}		
		}

		
		$sql = $this->moveMembersQuickSql();
		$result =& $this->db->_Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		
		$sql = "UPDATE {$this->tbl->comment} SET user_id = NULL";
		$result =& $this->db->_Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}	
	
		return true;
	}
	
	
	function getDublicateMembersByEmailSql() {
		$sql = "SELECT u.id AS user_id, m.id AS member_id 
		FROM 
			{$this->tbl->member} m,
			{$this->tbl->user} u
		WHERE m.email = u.email";
		return $sql;
	}
	
		
	function moveMembersQuickSql() {
		$sql = "
		INSERT IGNORE INTO {$this->tbl->user} 
		(first_name, last_name, middle_name, email, username, password, phone, date_registered, active, user_comment, admin_comment)
		SELECT 
		 first_name, last_name, middle_name, email, username, password, phone, date_registered, active, member_comment, admin_comment 
		FROM {$this->tbl->member}";
		return $sql;
	}
	
	
	// it is better to reasign order in new created field sort_order
	// in kb_entry_to_category
	function reassignArticleOrder() {
			
	}
}


class SetupModelUpgrade_301_to_35 extends SetupModelUpgrade
{
	
	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	
	
	
	function execute($values) {
		
		$file = 'db/upgrade_3.0.1_to_3.5.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));
		
		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);	
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	

		
		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');
	
		// admin email
		$ret = $this->getSetting('from_email');
		if(empty($ret['error'])) {
			$ret = $this->setAdminEmail($ret['val']);
			if($ret !== true) {
				return $ret;
			}
			
		} else {
			return $ret['error'];
		}

		
		// xpdf
		$ret = $this->getSetting('file_extract');
		if(empty($ret['error'])) {
			if($ret) {
				$file = $this->getSetupData('old_config_file');
				if($file && file_exists($file)) {
					
					$old_admin_dir = str_replace('config.inc.php', '', $file);
					$file = $old_admin_dir . 'extra/file_extractors/config.inc.php';
					if(file_exists($file)) {
						require_once $file;
						if(isset($file_conf['extract_tool']['pdf'])) {
							
							// file extract pdf
							$ret = $this->setSettingById(141, $file_conf['extract_tool']['pdf']);
							if($ret !== true) {
								return $ret;
							}						
						}
					}			
				}			
			}		

		} else {
			return $ret['error'];
		}


		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
}


class SetupModelUpgrade_20_to_35 extends SetupModelUpgrade
{	
	
	function execute($values) {
		
		// 2.0 to 3.0.1
		$upgrade = new SetupModelUpgrade_20_to_301();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		
		// 3.0.1 to 3.5
		$upgrade = new SetupModelUpgrade_301_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		
		
		return true;
	}
}


// ======== 4.0 ======================= >

class SetupModelUpgrade_20_to_402 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 3.5
		$upgrade = new SetupModelUpgrade_20_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_402 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 3.5
		$upgrade = new SetupModelUpgrade_301_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_352_to_402 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		$file = 'db/upgrade_3.5.2_to_4.0.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);	
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


// ======== 4.5 ======================= >

// update to 453 does not any special sql

class SetupModelUpgrade_20_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 4.0.2
		$upgrade = new SetupModelUpgrade_20_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 4.0.2
		$upgrade = new SetupModelUpgrade_301_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_352_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_402_to_452 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		$file = 'db/upgrade_4.0.2_to_4.5.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_45_to_452 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5_to_4.5.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_451_to_452 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_4.5.1_to_4.5.2.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();

        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }   


        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_452_to_453 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();
        

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


// ======== 5.0 ======================= >

class SetupModelUpgrade_20_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 4.5.2
		$upgrade = new SetupModelUpgrade_20_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 4.5.2
		$upgrade = new SetupModelUpgrade_301_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_352_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_352_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
}


class SetupModelUpgrade_402_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}

// 451, 452, 453 to 502 
class SetupModelUpgrade_452_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5.2_to_5.0.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

        // set language 
        if(!empty($values['lang'])) {
    		$ret = $this->setLanguage($values['lang']);
    		if($ret !== true) {
    			return $ret;
    		}   
        }

		return true;
	}	
	 
}

// 4.5, 4.5.1, 4.5.2, 4.5.3 to 502
class SetupModelUpgrade_45_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5_to_4.5.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		
		
		// 4.5 to 4.5.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion45($tbl_pref);
        if(is_bool($ret) === false) {
            return $ret;
        }
		
		if($ret) {
		    $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}
		
		
        // 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
		
	
	// to find out if db version 4.5 or above
	function isVersion45($tbl_pref) {
        $sql = "SHOW COLUMNS FROM `{$tbl_pref}news` WHERE Field = 'hits'";
		$result = $this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->FetchRow()) ? false : true;
	}
	 
}

// 50, 501 to 502
class SetupModelUpgrade_50_to_502 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_5.0_to_5.0.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


		// 5.0 to 5.0.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion50($tbl_pref);
		if(is_bool($ret) === false) {
            return $ret;
		}
		
		if($ret) {
    		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}
		

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
    
    
	// to find out if db version 5.0 or below
	function isVersion50($tbl_pref) {
        $sql = "SELECT id FROM `{$tbl_pref}setting` WHERE id = '280'";
		$result = $this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->Fields('id')) ? false : true;
	}
}


// ======== 5.5 ======================= >

class SetupModelUpgrade_20_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 5.0.2
		$upgrade = new SetupModelUpgrade_20_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_301_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 5.0.2
		$upgrade = new SetupModelUpgrade_301_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_352_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_352_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
		
		return true;
	}
}


class SetupModelUpgrade_402_to_551 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_402_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_45_to_551 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');
	

	function execute($values) {

		// 4.5 - 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_45_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


// 50, 501, 502 to 551
class SetupModelUpgrade_50_to_551 extends SetupModelUpgrade
{
	var $tbl_pref_custom;
	//var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting', 'trigger');	


	function execute($values) {

		$file = 'db/upgrade_5.0.2_to_5.5.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }    

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }
        
        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        // set default sql
        $ret = $this->setDefaultSql($values, false);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


// the same as SetupModelUpgrade_50_to_551
// added for compability view
class SetupModelUpgrade_502_to_551 extends SetupModelUpgrade
{

	function execute($values) {

        // 5.0 to 5.5.1
        $upgrade = new SetupModelUpgrade_50_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_55_to_551 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_5.5_to_5.5.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        return true;
    }
}




// ======== 6.0 ======================= >

class SetupModelUpgrade_20_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 5.5.1
		$upgrade = new SetupModelUpgrade_20_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_301_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 5.5.1
		$upgrade = new SetupModelUpgrade_301_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_352_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 5.5.1
		$upgrade = new SetupModelUpgrade_352_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
		
		return true;
	}
}


class SetupModelUpgrade_402_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 5.5.1
		$upgrade = new SetupModelUpgrade_402_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_45_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.5.* to 5.5.1
		$upgrade = new SetupModelUpgrade_45_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_50_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 5.0.* to 5.5.1
		$upgrade = new SetupModelUpgrade_50_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_551_to_602 extends SetupModelUpgrade
{
	var $tbl_pref_custom;
    var $tables = array('entry_draft_workflow', 'entry_draft_workflow_to_assignee');
	var $custom_tables = array('setting_to_value', 'setting', 'trigger');	


	function execute($values) {

		$file = 'db/upgrade_5.5.1_to_6.0.1.sql'; // no 602 updates 
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }    

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

        
        // upgrade draft assignee
		$ret = $this->upgradeDraftAssignee();
		if($ret !== true) {
			return $ret;
		}

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
         return $ret;
        }
        
        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

		return true;
	}
    
    
    function upgradeDraftAssignee() {
        
        
        
        // get assignees
        $sql = "SELECT draft_id, id AS draft_workflow_id, assignee 
            FROM {$this->tbl->entry_draft_workflow} 
            WHERE assignee != ''";
		$result =& $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}
	
        // add assignee to workflow_to_assignee 
        $data = array();
		while($row = $result->FetchRow()) {
            $assignees = explode(',', $row['assignee']);
            foreach($assignees as $assignee_id) {
                $data[] = array($row['draft_id'], $row['draft_workflow_id'], $assignee_id);
            }
		}
        
        if($data) {
            $chunks = array_chunk($data, 30);
            foreach($chunks as $v) {
                    
                $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->entry_draft_workflow_to_assignee} (draft_id, draft_workflow_id, assignee_id) VALUES ?", $v);
        
    			$result1 = $this->db->_Execute($sql);
    			if(!$result1) {
    				return DbUtil::error($sql);
    			}
            }
        }
        
        // drop assignee field
        $sql = "ALTER TABLE {$this->tbl->entry_draft_workflow} DROP assignee";
		$result1 = $this->db->_Execute($sql);
		if(!$result1) {
            
            // empty workflow_to_assignee if error
            $sql2 = "TRUNCATE TABLE {$this->tbl->entry_draft_workflow_to_assignee}";
    		$result1 = $this->db->_Execute($sql2);
            
			return DbUtil::error($sql);
		}
	
		return true;
    }
}


// 55, 551 to 602
class SetupModelUpgrade_55_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {
        
		// 5.5.* to 5.5.1 if required
		// if $ret is not bool then sql error, string with error
        // $this->connect($values);
        // $tbl_pref = $values['tbl_pref'];
        //
        // $ret = $this->isVersion55($tbl_pref);
        // if(is_bool($ret) === false) {
        //     return $ret;
        // }
        
        // we can't safely determine if version 5.0
        // so always run this upgrade, it is safe
        $ret = true;
        
        if($ret) {
            $upgrade = new SetupModelUpgrade_55_to_551();
            $ret = $upgrade->execute($values);
            if($ret !== true) {
                return $ret;
            }
        }

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


// 60, 601 to 602
class SetupModelUpgrade_60_to_602 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {
        
        $file = 'db/upgrade_6.0_to_6.0.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


		// 6.0 to 6.0.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion60($tbl_pref);
		if(is_bool($ret) === false) {
            return $ret;
		}
		
		if($ret) {
    		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        return true;
    }
    
    
	// to find out if db version 6.0 or below
	function isVersion60($tbl_pref) {
        $sql = "SELECT id FROM `{$tbl_pref}setting` WHERE id = '378'";
		$result = $this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->Fields('id')) ? false : true;
	}
}


// ======== 7.0 ======================= >

class SetupModelUpgrade_20_to_702 extends SetupModelUpgrade
{    

    function execute($values) {

        // 2.0 to 6.0.2
        $upgrade = new SetupModelUpgrade_20_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_301_to_702 extends SetupModelUpgrade
{    

    function execute($values) {
        
        // 3.0.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_301_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_352_to_702 extends SetupModelUpgrade
{    

    function execute($values) {

        // 3.5.2 to 6.0.2
        $upgrade = new SetupModelUpgrade_352_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        return true;
    }
}


class SetupModelUpgrade_402_to_702 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 4.0.2 to 6.0.2
        $upgrade = new SetupModelUpgrade_402_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_45_to_702 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 4.5 to 6.0.2
        $upgrade = new SetupModelUpgrade_45_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_50_to_702 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 5.0 to 6.0.2
        $upgrade = new SetupModelUpgrade_50_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_55_to_702 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 5.5 to 6.0.2
        $upgrade = new SetupModelUpgrade_55_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


// 6.0, 6.0.1, 6.0.2 to 702
class SetupModelUpgrade_60_to_702 extends SetupModelUpgrade
{
    
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    
    
    
    function execute($values) {
    
        // 6.0 to 6.0.2
        $upgrade = new SetupModelUpgrade_60_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 6.0.2 to 7.0.2
        $upgrade = new SetupModelUpgrade_602_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }    
}


class SetupModelUpgrade_602_to_702 extends SetupModelUpgrade
{
    var $tbl_pref_custom;
    var $tables = array('stuff_data', 'user', 'user_to_sso');
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_6.0.2_to_7.0.2.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();

        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }
        
        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');
        
        // upgrade rewrite for cloud
        if(!empty($values['is_cloud'])) {
            $ret = $this->upgradeRewrite();
            if($ret !== true) {
                return $ret;
            }
        }
        
        // upgrade menu
        $ret = $this->upgradeMenuValues();
        if($ret !== true) {
            return $ret;
        }

        // upgrade passwords: email, mailbox, ldap
        $ret = $this->upgradePasswords();
        if($ret !== true) {
            return $ret;
        }

        // upgrade imported user id
        if(!$this->dryrun) {
            $ret = $this->upgradeImportedUserId();
            if($ret !== true) {
                return $ret;
            }
        }

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }
        
        return true;
    }


    // upgrade module_news, etc and extra_menu to main_menu
    function upgradeMenuValues() {
                      
        // normailze contact 
        $ret = $this->getSetting('allow_contact');
		if(!empty($ret['error'])) {
			return $ret['error'];   
		} else {
            $val = ($ret['val']) ? 1 : 0; 
            $ret = $this->setSettingById(390, $val);
    		if($ret !== true) {
    			return $ret;
    		}
		}
        
        
        // get modules 
        $module_keys = array();
        foreach(SettingData::$main_menu as $key => $v)  {
            if(!empty($v['setting'])) {
                $module_keys[] = $v['setting'];
            }
        }

        $ret = $this->getSettingArray($module_keys, false);
		if(!empty($ret['error'])) {
			return $ret['error'];   
		}
        
        $modules = $ret['val'];
        // echo '<pre>$modules: ', print_r($modules,1), '<pre>';
        
        // new menu
        $ret = $this->getSetting('menu_main');
		if(!empty($ret['error'])) {
			return $ret['error'];   
		}
        
        $new_menu = array();
        $menu = unserialize($ret['val']);
        // echo '<pre>', print_r($menu,1), '<pre>';
        
        foreach ($menu as $active => $v) {
            foreach ($v as $num => $item) {

                // move to inactive                
                if(!empty(SettingData::$main_menu[$item['id']]['setting'])) {
                    $module_key = SettingData::$main_menu[$item['id']]['setting'];
                    if(empty($modules[$module_key])) {
                        $new_menu['inactive'][] = $item;
                    } else {
                        $new_menu[$active][] = $item; // copy
                    }
                    
                // copy
                } else {
                    $new_menu[$active][] = $item;
                }
                
            }
        }
        

        // custom menu items
        $ret = $this->getSettingToValue(192); // menu_extra, removed in 7.0 
		if(!empty($ret['error'])) {
			return $ret['error'];   
		}
			
        $menu_extra = explode('||', $ret['val']);
        // echo '<pre>$menu_extra: ', print_r($menu_extra,1), '<pre>';
        
        for ($i = 0; $i < count($menu_extra); $i ++) {
            $item = explode('|', trim($menu_extra[$i]));

            if (count($item) < 2) { // this item is broken
                continue;
            }

            $options = (!empty($item[2])) ? trim($item[2]) : '';
            $dropdown = (!empty($item[3])) ? trim($item[3]) : '';
            $target = false;

            $pattern = '#target\s?=\s?[\"\']?_blank[\"\']?#i';
            if (preg_match($pattern, $options)) {
                $target = true;
                $options = trim(preg_replace($pattern, '', $options));
            }

            $new_menu['active'][] = array (
                'title' => trim($item[0]),
                'link' => trim($item[1]),
                'options' => $options,
                'dropdown' => $dropdown,
                'target' => $target,
            );
        }

        // echo '<pre>', print_r($new_menu,1), '<pre>';
        // exit;

        // set menu_main
        $new_menu = addslashes(serialize($new_menu));
        $ret = $this->setSettingById(387, $new_menu);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
    }
    
    
    // encrypt passwords 
    function upgradePasswords() {
        
        // email setting smtp pass, ldap pass
        $keys = array('smtp_pass', 'ldap_connect_password');
        $passwords = $this->getSettingArray($keys);
		if(!empty($ret['error'])) {
			return $ret['error'];   
		}
        
        $new_passwords = array();
        foreach($passwords['val'] as $sid => $sv) {
            $new_passwords[$sid] = EncryptedPassword::encode($sv);
        }
        
        if($new_passwords) {
            $ret = $this->setSettingByIdArray($new_passwords);
            if($ret !== true) {
                return $ret;
            }
        }
    
        // smtp password in automation email box 
        $sql = "SELECT * FROM {$this->tbl->stuff_data} WHERE data_key = 'iemail'";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }
        
        $mailboxes = $result->getArray();

        foreach ($mailboxes as $v) {
            $mailbox = unserialize($v['data_string']);
            $mailbox['password'] = EncryptedPassword::encode($mailbox['password']);
            $mailbox = addslashes(serialize($mailbox));
    
            $sql = "UPDATE {$this->tbl->stuff_data} SET data_string = '%s' WHERE id = %d";
            $sql = sprintf($sql, $mailbox, $v['id']);
            $result = $this->db->Execute($sql);
            if(!$result) {
                return DbUtil::error($sql);
            }
        }
    
        return true;
    }
    

    function upgradeImportedUserId() {

        $provider_id_to_setting_id = array(
            233 => 1, // remote, remote_auth_script
            232 => 2, // ldap, remote_auth
            347 => 3  // saml, saml_auth
        );

        $sql = "SELECT setting_id FROM {$this->tbl->setting_to_value} 
        WHERE setting_id IN (233,232,347) AND setting_value != 0";
        $result = $this->db->Execute($sql);
        if(!$result) {
            return DbUtil::error($sql);
        }
        
        if($remote_auth_setting_id = $result->Fields('setting_id')) {
            $provider_id = $provider_id_to_setting_id[$remote_auth_setting_id];
        
            $sql = "INSERT IGNORE {$this->tbl->user_to_sso} (user_id, sso_user_id, sso_provider_id)  
                SELECT id, imported_user_id, {$provider_id} FROM {$this->tbl->user} 
                WHERE imported_user_id != ''";
            $result = $this->db->Execute($sql);
            if(!$result) {
                return DbUtil::error($sql);
            }
        }
        
        return true;
    }
    
    
    // update rewrite setting for cloud as automaicj removed from .htaccess
    // mod_rewrite, 1 auto, 2 rewrite with numbers, 3 full rewrite    
    function upgradeRewrite() {             
        
        $ret = $this->getSetting('mod_rewrite'); // automatic then change to 2        
		if(!empty($ret['error'])) {
			return $ret['error'];   
		}
        
        if($ret['val'] == 1) { // automatic then change to 2
            $ret = $this->setSettingByKey('mod_rewrite', 2);
            if($ret !== true) {
                return $ret;
            }
        }
        
        return true;
    }

}


// 70, 701 to 702
class SetupModelUpgrade_70_to_702 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {
        
        $file = 'db/upgrade_7.0_to_7.0.2.sql';  // it has safe sql for 70 to 701
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);        
        $ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        return true;
    }
}


// ======== 7.5 ======================= >

class SetupModelUpgrade_20_to_75 extends SetupModelUpgrade
{    

    function execute($values) {

        // 2.0 to 7.0.2
        $upgrade = new SetupModelUpgrade_20_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_301_to_75 extends SetupModelUpgrade
{    

    function execute($values) {
        
        // 3.0.1 to 7.0.2
        $upgrade = new SetupModelUpgrade_301_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_352_to_75 extends SetupModelUpgrade
{    

    function execute($values) {

        // 3.5.2 to_7.0.2
        $upgrade = new SetupModelUpgrade_352_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        return true;
    }
}


class SetupModelUpgrade_402_to_75 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 4.0.2 to_7.0.2
        $upgrade = new SetupModelUpgrade_402_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_45_to_75 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 4.5 to 7.0.2
        $upgrade = new SetupModelUpgrade_45_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_50_to_75 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 5.0 to 7.0.2
        $upgrade = new SetupModelUpgrade_50_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to 7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_55_to_75 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 5.5 to 7.0.2
        $upgrade = new SetupModelUpgrade_55_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_60_to_75 extends SetupModelUpgrade
{
    
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    
    
    
    function execute($values) {
    
        // 6.0 to 7.0.2
        $upgrade = new SetupModelUpgrade_60_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }    
}

// 7.0, 7.0.1, 7.0.2 to 702
class SetupModelUpgrade_70_to_75 extends SetupModelUpgrade
{
    
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    
    
    
    function execute($values) {
    
        // 7.0 to 7.0.2
        $upgrade = new SetupModelUpgrade_70_to_702();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_702_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }    
}


class SetupModelUpgrade_702_to_75 extends SetupModelUpgrade
{
    var $tbl_pref_custom;
    var $tables = array('file_entry');
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_7.0.2_to_7.5.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();

        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }
        
        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');
        

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        // set kbp version, added in v7.5
        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');
        $ret = $this->setVersion($conf['product_version']);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();
        }
        
        return true;
    }
    
}
?>
