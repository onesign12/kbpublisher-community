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


class ParseSqlFile
{
    
    static $dryrun = false;
    
    
    static function getPrefix($prefix) {
        if($prefix && substr($prefix, -1) != '_') {
            $prefix .= '_';
        }
        
        return $prefix;
    }
    

    static function getCommandAndTable($str) {
        preg_match('#^([A-Z ]+) `?(\w+)`?(?:.*SELECT.*FROM `?(\w+)`?)?#s', $str, $match);
        $tables = (!empty($match[3])) ? array($match[2], $match[3]) : array($match[2]);
        
		// ALTER TABLE `kbp_entry_draft` RENAME `kbp_entry_autosave`
        if(strpos($str, 'RENAME') !== false) {
        	preg_match('#(RENAME) `?(\w+)`?#s', $str, $match2);
        	if(!empty($match2[2])) {
        		$tables[] = $match2[2];
        	}
        }
        
        // DELETE l, lv FROM `kbp_list` l, `kbp_list_value` lv WHERE l.id = 8 AND l.id = lv.list_id;
        if(strpos($str, 'DELETE') !== false) {
        	preg_match('#(DELETE.*FROM) `?(\w+)`?.*`?(\w+)`?#s', $str, $match2);
        	if(!empty($match2[2])) {
        		$tables[] = $match2[2];
        	}
        	if(!empty($match2[3])) {
        		$tables[] = $match2[3];
        	}

        }
		
        return array($match[1], $match[2], $tables);
    }
    
    
    static function getTable($str) {
        $arr = ParseSqlFile::getCommandAndTable($str);
        return $arr[1];
    }
    
    
    static function parseDumpArray($data) {
    
        foreach(array_keys($data) as $k) {
            
            $data[$k] = ltrim($data[$k]);
            if(!preg_match("#ALTER|CREATE|INSERT|DROP|UPDATE|REPLACE#", $data[$k])) {
                unset($data[$k]);
            }
        }
        
        $data = array_merge($data, array());
        return $data;
    }
    
    
    static function parseDumpString($data) {
        $data = preg_replace("#AUTO_INCREMENT\s*=\s*\d+#i", "", $data);
        $data = preg_replace("#DEFAULT CHARSET\s*=\s*\w+#i", "", $data);
        $data = preg_replace("#(DROP TABLE IF EXISTS `?\w+`?;)#", "$1\n--", $data);
        $data = preg_replace("#\/\*!.*?\*\/;#", "", $data); // comments
        // $data = str_replace("TYPE=MyISAM   ;", "TYPE=MyISAM;", $data);
        $data = preg_replace("#(TYPE|ENGINE=MyISAM)\s+;#", "$1;", $data);
        
        // ALTER TABLE `kbp_entry_index` ADD FULLTEXT KEY `title` (`title`);
        $data = preg_replace("#`\);\s#", "`);\n--\n", $data);
        $data = preg_split("#^--\s+#m", $data, -1, PREG_SPLIT_NO_EMPTY);
        
        return ParseSqlFile::parseDumpArray($data);
    }
    
    
    static function parseUpgradeArray($data) {
    
        foreach(array_keys($data) as $k) {
            
            $data[$k] = ltrim($data[$k]);
            if(!preg_match("#ALTER|INSERT|UPDATE|DELETE|DROP|TRUNCATE#", $data[$k])) {
                unset($data[$k]);
                continue;
            }        
        }
        
        $data = array_merge($data, array());
        return $data;
    }
    
    
    static function parseUpgradeString($data) {
        $data = preg_replace("#AUTO_INCREMENT\s*=\s*\d+#i", "", $data);
        $data = preg_replace("#DEFAULT CHARSET\s*=\s*\w+#i", "", $data);
        $data = preg_split("#^--\s+#m", $data, -1, PREG_SPLIT_NO_EMPTY);
        return ParseSqlFile::parseUpgradeArray($data);
    }    
    
    
    // prepare array
    // function parseSqlArray($data, $prefix, $parse = array(), $data = array(), $skip = array(), 
                                // $install = true, $default = false) {
    static function parseSqlArray($data, $prefix, $tables = array(), $install = true) {

        $skip = (isset($tables['skip'])) ? $tables['skip'] : array();
        $parse = (isset($tables['parse'])) ? $tables['parse'] : array();
        $with_data = (isset($tables['data'])) ? $tables['data'] : array();
        $only_data = (isset($tables['only_data'])) ? $tables['only_data'] : array();
        $no_data = (isset($tables['no_data'])) ? $tables['no_data'] : array();
        
    
        $_data = array();
        foreach($data as $k => $v) {
            
            list($command, $table, $table_all) = ParseSqlFile::getCommandAndTable($v);
            
            $table_no_pref = $table;
            if ($prefix) {
                $prefix_pattern = sprintf("#^%s#", preg_quote(key($prefix)));
                $table_no_pref = preg_replace($prefix_pattern, '', $table);
            }
    
            // to skip
            if(in_array($table_no_pref, $skip)) {
                continue;
            }    
            
            // not to parse
            if($parse && !in_array($table_no_pref, $parse)) {
                continue;
            }
    
            // with data
            if($with_data && !in_array($table_no_pref, $with_data)) {
                if($command == 'INSERT INTO' || $command == 'UPDATE') {
                    continue;
                }
            }
            
            // data only
            if($only_data && in_array($table_no_pref, $only_data)) {
                if($command == 'CREATE TABLE IF NOT EXISTS' || $command == 'CREATE TABLE') {
                    continue;
                }
            }
            
            // no data
            if($no_data && in_array($table_no_pref, $no_data)) {
                if($command == 'INSERT INTO' || $command == 'UPDATE') {
                    continue;
                }
            }
            
            if($install && $command == 'DROP TABLE IF EXISTS') {
                continue;
            }    
        
            if($prefix ) {
                $new_prefix = current($prefix);
                foreach($table_all as $t) {
                    $new_table = preg_replace($prefix_pattern, $new_prefix, $t);
                    $v = str_replace($t, $new_table, $v);
                }
            }
            
            $_data[] = trim($v);
        }
        
        return $_data;
    }
    
    
    // with prepared
    static function parsePreparedArray($data, $prefix = false, $remove_end = true) {
    
        foreach($data as $k => $v) {
            
            $v = trim($v);
            list($command, $table, $table_all) = ParseSqlFile::getCommandAndTable($v);
            
            if($prefix) {
                
                $new_table = array();
                foreach($table_all as $t) {
                    $new_table[$t] = strtr($t, $prefix);
                }
             
                $v = strtr($v, $new_table);
            }
            
            // for dryrun
            if(self::$dryrun) {
                
                 // skip where double reference to the same databade
                 // INSERT table ... SELECT ...
                if(preg_match("#(INSERT|UPDATE).*SELECT[\n\r]*#s", $v)) {
                    unset($data[$k]);
                    continue;
                }
                
                $search = array('CREATE TABLE', 'DROP TABLE');
                $replace = array('CREATE TEMPORARY TABLE', 'DROP TEMPORARY TABLE');
                $v = str_replace($search, $replace, $v);
            }
            
            if($remove_end && strpos($v, -1) == ';') {
                $v = substr($v, -1);
            }    
            
            $data[$k] = trim($v);
        }
        
        return $data;
    }
    
    
    // $mysql_version renamed to $options 28-09-2023
    // static function parsePreparedString($data, $prefix, $mysql_version = false) {
    static function parsePreparedString($data, $prefix, $options = []) {
        
        $mysql_version = (!empty($options['mysqlv'])) ? $options['mysqlv'] : $options;
        $charset = (!empty($options['charset'])) ? $options['charset'] : 'utf8';
        $collation = (!empty($options['collation'])) ? $options['collation'] : 'utf8_general_ci';
        
        if($mysql_version) {
            if($mysql_version >= 5) { // replace TYPE to ENGINE
                $data = ParseSqlFile::replaceTypeSyntax($data, true);
            } else { // replace ENGINE to TYPE
                $data = ParseSqlFile::replaceTypeSyntax($data, false);
            }
        }
        
        //charset, may need if change full text field
        $data = preg_replace("#(CHARACTER SET) \w+#", "$1 $charset", $data);
        $data = preg_replace("#(COLLATE) \w+#", "$1 $collation", $data);
        
        $data = explode('--', $data);
		$data = array_filter($data);
        return ParseSqlFile::parsePreparedArray($data, $prefix);
    }
    
    
    static function replaceTypeSyntax($str, $type_to_engine = 1) {
        $replace = [
            0 => ['ENGINE=MyISAM' => 'TYPE=MyISAM'],
            1 => ['TYPE=MyISAM'   => 'ENGINE=MyISAM'],
        ];
        
        return strtr($str, $replace[intval($type_to_engine)]);
    }
}
?>