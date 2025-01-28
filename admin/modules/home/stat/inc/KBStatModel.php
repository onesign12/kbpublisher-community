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

class KBStatModel extends AppModel
{

    var $tables = array('user', 'priv', 'priv_name');
    
    var $active_portlets_ids = array();
    var $portlet_key_to_id = array('user' => 1,
                                   'article' => 2,
                                   'file' => 3,
                                   'comment' => 4,
                                   'article_feedback' => 5,
                                   'draft_article' => 6,
                                   'draft_file' => 7);
    
    
    function __construct() {
        parent::__construct();
        
        $setting = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, 'home_portlet_order');
        if ($setting == 'empty') {
            $col1 = '';
            $col2 = '';
            
        } else {
            list($col1, $col2) = explode('|', $setting);    
        }
        
        $column1 = (strlen($col1) != 0) ?  explode(',', $col1) : array();
        $column2 = (strlen($col2) != 0) ?  explode(',', $col2) : array();
        
        $this->active_portlets_ids = array($column1, $column2);
    }
    
    
    function getActivePortletsIds() {
        return $this->active_portlets_ids;
    }
    
    
    function getPortletKeyById($id) {
        return array_search($id, $this->portlet_key_to_id);
    }
    
    
    function getPortletSelectRange($msg) {
        $data = array();
        foreach($this->portlet_key_to_id as $k => $v) {
            $data[$v] = $msg[$k . '_stats_msg'];
        }
        
        return $data;
    }

}
?>
