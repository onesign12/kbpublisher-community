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



class ListBuilderModel extends AppModel
{
    var $tables = array();
    
    
    function __construct($user = array()) {
        parent::__construct();
        
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->sm_manager = new SettingModelUser($this->user_id);
    }
    
    
    function getColumns($page) {
        $setting_key = 'columns_' . $page;
        $setting_id = $this->sm_manager->getSettingIdByKey($setting_key);
        
        $sql = $this->sm_manager->getDefaultValuesSql(false);
        $sql = sprintf($sql, 0);
        $sql .= ' AND id = ' . $setting_id;
        
        $result = $this->db->Execute($sql) or die(db_error($sql));  
        $data = $result->FetchRow();
        
        $columns = explode(',', $data['setting_value']);
        return $columns;
    }
    
}
?>