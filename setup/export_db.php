<?php
// require_once 'inc/ParseSqlFile.php';
require_once '../admin/config.inc.php';
require_once '../admin/config_more.inc.php';

require_once 'autoload/AppLoader.php';
AppLoader::register(['app', 'setup']);


/*
Do not forget letter templares default
/admin/modules/setting/letter_template/PageController.php

To genereate dump.sql
use phpmysdmin export with options
Structure
    Add DROP TABLE
    Add IF NOT EXISTS (less efficient as indexes will be generated during table creation) 5.7
    Remove AUTO_INCREMENT value
    Enclose table and field names with backquotes

Data
    Syntax to use when inserting data: both of the above
    Maximal length of created query: = empty    

or run 
./_dev/mysqldump_setup.sh    
    
=======================================================================
DO NOT FORGET DEFAULTS FOR 
    automations, workflows, letter templates!!!    
=======================================================================
*/

$prefix = array('s_'=> 'kbp_');


// -- INSTALL -----------------------

$install = FileUtil::read('../../_sql/dump.sql');
$install = ParseSqlFile::parseDumpString($install);


$file = 'db/install.sql';

// empty means all
$tables['skip'] = array();

$tables['parse'] = array();

$tables['data'] = array(
    'priv_module', 'priv_name', 'priv_rule',
    'letter_template', 'article_template',
    'user_role',
    'list_value', 'list', 'list_country',
    'setting'
);

$sql = array();
$sql['before'] = array("SET sql_mode = '';");
$sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables);
$sql['tables'] = str_replace('\"', '"', $sql['tables']);
$sql = array_merge($sql['before'], $sql['tables']);

$ret = FileUtil::write($file, implode("\n--\n", $sql));
if(!$ret) {
    echo "Unable write to file $file \n";
}

exit;

// -- UPGRADE ---------------------

$upgrade = FileUtil::read('../../_sql/db_upgrade_7.5_to_8.0.sql');
$upgrade = ParseSqlFile::parseUpgradeString($upgrade);

$file = 'db/upgrade_7.5_to_8.0.sql';

// empty means all
$tables['skip'] = array();

$tables['parse'] = array(
    'priv_module', 'setting', 
    'file_entry_history'
);

$tables['data'] = array(
    'priv_module', 'setting'
);

$sql = array();
$sql['before'] = array("SET sql_mode = '';");
$sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables, false);
$sql['tables'] = str_replace('\"', '"', $sql['tables']);
$sql['command'] = ParseSqlFile::parseSqlArray($upgrade, $prefix, array(), false);
$sql = array_merge($sql['before'], $sql['tables'], $sql['command']);

$ret = FileUtil::write($file, implode("\n--\n", $sql));
if(!$ret) {
    echo "Unable write to file $file \n";
}




// -- UPGRADE 2 ---------------------

// $upgrade = FileUtil::read('../../_sql/db_upgrade_7.0_to_7.0.2.sql');
// $upgrade = ParseSqlFile::parseUpgradeString($upgrade);
//
// $file = 'db/upgrade_7.0_to_7.0.2.sql';
//
// // empty means all
// $tables['skip'] = array();
// $tables['parse'] = array('fake_table'); // if not any table required
// $tables['data'] = array('fake_table');  // if not any table required
//
// $sql = array();
// $sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables, false);
// $sql['command'] = ParseSqlFile::parseSqlArray($upgrade, $prefix, array(), array(), array(), false);
// $sql = array_merge($sql['tables'], $sql['command']);
//
// $ret = FileUtil::write($file, implode("\n--\n", $sql));
// if(!$ret) {
//     echo "Unable write to file $file \n";
// }


// -- DEFAULT SQL -----------------------

// $default = FileUtil::read('../../_sql/default.sql');
// $default = ParseSqlFile::parseDumpString($default);
// // echo '<pre>', print_r($install, 1), '</pre>';
// $file = 'db/default.sql';
// 
// $tables['skip'] = array();
// $tables['parse'] = array('trigger');
// $tables['data'] = array('trigger');
// $tables['only_data'] = array('trigger');
// 
// $sql = ParseSqlFile::parseSqlArray($install, $prefix, $tables);
// $sql = ParseSqlFile::parseSqlArray($default, $prefix);
// // echo '<pre>', print_r($sql, 1), '</pre>';
// 
// $ret = FileUtil::write($file, implode("--\n", $sql));
// if(!$ret) {
//     echo "Unable write to file $file \n";
// }

?>