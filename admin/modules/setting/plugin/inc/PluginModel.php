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


class PluginModel extends AppModel
{

    var $tbl_pref_custom = '';
    // var $tables = array('table'=>'letter_template');
    
    
    function __construct() {
        parent::__construct();
        
        $this->s_manager = new SettingModel();
    }
    
    
    function &getRecords() {
        return $this->s_manager->getSettings(0, 'plugins', true);
    }
    
    
    function getPlugins() {
        $rows = $this->getRecords();
        return($rows) ? unserialize($rows) : [];
    }
    
    
    function save($data) {
        return $this->s_manager->setSettings([485 => $data]);
    }
    
    
    function savePlugins($plugins) {
        return $this->save(serialize($plugins));
    }
    
    
    function parsePlugins($plugins) {
        // add some filtering here to remove disabled plugins if any
        // validate sanitaze\
        
        return serialize($plugins);
    }
    
    
    // function getDefaultRecords() {
    //     $sql = "SELECT id, from_email, from_name, to_email, to_name, 
    //     to_cc_email, to_cc_name, to_bcc_email, to_bcc_name, to_special, subject
    //     FROM {$this->tbl->table}";
    //     $result = $this->db->Execute($sql) or die(db_error($sql));
    //     return $result->GetArray();
    // }
}
?>