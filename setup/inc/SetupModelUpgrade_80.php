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


class SetupModelUpgrade_20_to_80 extends SetupModelUpgrade
{    

    function execute($values) {

        // 2.0 to 7.5
        $upgrade = new SetupModelUpgrade_20_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_301_to_80 extends SetupModelUpgrade
{    

    function execute($values) {
        
        // 3.0.1 to 7.5
        $upgrade = new SetupModelUpgrade_301_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_352_to_80 extends SetupModelUpgrade
{    

    function execute($values) {

        // 3.5.2 to_7.5
        $upgrade = new SetupModelUpgrade_352_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        return true;
    }
}


class SetupModelUpgrade_402_to_80 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 4.0.2 to_7.5
        $upgrade = new SetupModelUpgrade_402_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_45_to_80 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 4.5 to 7.5
        $upgrade = new SetupModelUpgrade_45_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_50_to_80 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {

        // 5.0 to 7.5
        $upgrade = new SetupModelUpgrade_50_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        // 7.0.2 to 7.5
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_55_to_80 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    


    function execute($values) {
        
        // 5.5 to 7.5
        $upgrade = new SetupModelUpgrade_55_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_60_to_80 extends SetupModelUpgrade
{
    
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    
    
    
    function execute($values) {
    
        // 6.0 to 7.5
        $upgrade = new SetupModelUpgrade_60_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }    
}

// 7.0, 7.0.1, 7.0.2 to 702
class SetupModelUpgrade_70_to_80 extends SetupModelUpgrade
{
    
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');    
    
    
    function execute($values) {
    
        // 7.0 to 7.5
        $upgrade = new SetupModelUpgrade_70_to_75();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
        
        // 7.5 to_8.0
        $upgrade = new SetupModelUpgrade_75_to_80();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }    
}


class SetupModelUpgrade_75_to_80 extends SetupModelUpgrade
{
    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_7.5_to_8.0.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);

        $options = $this->getMySQLOptions($values['db_base']);
        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $options);
        
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