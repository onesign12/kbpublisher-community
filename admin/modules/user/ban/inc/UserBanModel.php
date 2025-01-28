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



class UserBanModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'user_ban', 'user');
    var $ban_model;
    
    var $ban_reason = array(
        'spam' => 1,
        'harshness' => 2);
    
    
    function __construct() {
        parent::__construct();
        $this->ban_model = BanModel::factory('login');
    }    
    
    
    function getById($record_id) {

        $sql = "SELECT *, 
            IF(ban_rule = 3, INET_NTOA(ban_value), ban_value) as ban_value        
            FROM {$this->tbl->table}
            WHERE id = %d";
        
        $sql = sprintf($sql, $record_id);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getRuleSelectRange() {
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'ban_rule');
        foreach($this->ban_model->rules as $k => $v) {
            $range[$v] = $msg[$k];
        }
        
        return $range;
    }
    
    
    function getTypeSelectRange() {
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'ban_type');
        foreach($this->ban_model->types as $k => $v) {
            $range[$v] = $msg[$k];
        }
        
        return $range;
    }
    
    
    function getBanReasonSelectRange() {
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'ban_reason');
        foreach($this->ban_reason as $k => $v) {
            $range[$v] = $msg[$k];
        }

        return $range;
    }
    
    
    function save($obj) {
        
        if ($obj->get('id') && !$obj->get('date_end')) {
            $obj->set('date_end', NULL);
        }
        
        $id = parent::save($obj);
        if ($id) {
            $obj->set('id', $id);
        }
        
        if($obj->get('ban_rule') == 3) {
            $id = (int) $obj->get('id');
            $ban_value = $obj->get('ban_value');
            
            $sql = "UPDATE {$this->tbl->table} 
            SET ban_value = INET_ATON('{$ban_value}') WHERE id = '{$id}'";
            $this->db->Execute($sql) or die(db_error($sql));
        }
        
        return $id;
    }

}
?>