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
    
    function factory($version) {
        $class = 'SetupModelUpgrade_' . $version;
        return new $class;
    }    
}


class SetupModelInstall extends SetupModel
{
    
    
    function execute($tbl_pref) {
        
        $file = 'db/install.sql';
        $data = FileUtil::read($file);
        
        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref));        
        $ret = $manager->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }
        
        return true;
    }
}
?>