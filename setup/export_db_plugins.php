<?php
require_once '../admin/config.inc.php';
require_once '../admin/config_more.inc.php';

require_once 'autoload/AppLoader.php';
AppLoader::register(['app', 'setup']);


$file = '../plugins_all/%s/tables.sql';
$prefix = array('s_'=> 'kbp_');

$dump = FileUtil::read('../../_sql/dump.sql');
$dump = ParseSqlFile::parseDumpString($dump);


$pluginable = AppPlugin::getPluginsFiltered('tables');
// echo print_r($pluginable, 1), "\n";
// exit;

$install_file = 'db/install.sql';
$install_dump = $ret = FileUtil::read($install_file);
if(!$install_dump) {
    echo "Unable read file $install_file \n";
    exit;
}

// empty means all
foreach($pluginable as $plugin => $v) {
        
    $tables['skip'] = array();
    $tables['parse'] = $v['tables'];
    $tables['no_data'] = $v['tables'];

    $sql = array();
    $sql['before'] = array("SET sql_mode = '';");
    
    $sql['tables'] = ParseSqlFile::parseSqlArray($dump, $prefix, $tables);
    $sql['tables'] = str_replace('\"', '"', $sql['tables']);
    $install_dump = str_replace($sql['tables'], '', $install_dump);
    
    $sql = array_merge($sql['before'], $sql['tables']);

    $file_write = sprintf($file, $plugin);
    $content = implode("\n--\n", $sql);
    $ret = FileUtil::write($file_write, $content);
    if(!$ret) {
        echo "Unable write to file $file_write \n";
        exit;
    }
}

// remove empty blocks
$install_dump = preg_replace("#^--\n{2,}#m", '', $install_dump);

$ret = FileUtil::write($install_file, $install_dump);
if(!$ret) {
    echo "Unable write to file $install_file \n";
    exit;
}

?>