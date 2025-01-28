<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class AppMailModel extends BaseModel
{

    var $tbl_pref_custom = '';
    var $tables = array('letter_template', 'user', 'priv', 'kb_entry', 'file_entry', 'news', 'email_pool');

    
    function getTemplate($id) {
        $sql = "SELECT body FROM {$this->tbl->letter_template} WHERE id = '$id' OR letter_key = '$id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('body');        
    }
    
    
    function getTemplateVars($id) {
        $sql = "SELECT * FROM {$this->tbl->letter_template} WHERE id = '$id' OR letter_key = '$id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();        
    }
    
    
    function &getSettings() {
        $s = new SettingModel();
        $s->setModuleId('email_setting');
        return $s->getSettings(false, false, true); // last parameter to ignore concrete parser    
    }
    
    
    function getUser($user_id, $use_die = true) {
        $user = $this->_getUser($user_id, $use_die);
        if($user === false) {
            return false;
        }
        
        return (isset($user[$user_id])) ? $user[$user_id] : array();
    }
    
    
    function getCategoryAdminUser($category_id, $rule, $use_die = true) {
        
        $admins = array();
        $admin_ids = array();
        
        if($rule == 'kb_category_to_user_admin') {
            
            
            $model = new KBCategoryModel();
            $admin_ids = $model->getSupervisorsByCatId($category_id);
            
        } elseif($rule == 'file_category_to_user_admin') {
            
            
            $model = new FileCategoryModel();
            $admin_ids = $model->getSupervisorsByCatId($category_id);
        
        } elseif($rule == 'feedback_user_admin') {
        
            
            $model = new DataToValueModel();
            $rule_id = $model->getRuleId($rule);
            $admin_ids = $model->getDataIds($rule_id, $category_id);
            $admin_ids = (isset($admin_ids[$category_id])) ? $admin_ids[$category_id] : array();
        }
        
        if($admin_ids) {
            $admins = $this->_getUser(implode(',', $admin_ids), $use_die);
        }
        
        return $admins;
    }
    
    
    function _getUser($user_ids, $use_die = true) {
        
        // $active_status = $this->getEntryPublishedStatus(4);
        // $active_sql = (1) ? "active IN ({$active_status})" : 1;
        $active_sql = 1;
        
        $sql = "SELECT id, id AS user_id, email, phone, username, 
            first_name, last_name, middle_name
        FROM {$this->tbl->user} 
        WHERE id IN($user_ids)
        AND {$active_sql}";   

        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $result->GetAssoc();
        } else {
            trigger_error('Cannot get user data. ' . $this->db->ErrorMsg());
            if($use_die) {
                die(db_error($sql));
            }
        }
        
        return $result;
    }
    
    
    function getUsersByPrivilege($priv_id) {
        $sql = "SELECT u.*
            FROM ({$this->tbl->user} u,
                {$this->tbl->priv} p)
            WHERE u.id = p.user_id 
                AND p.priv_name_id = '$priv_id'";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
        
    
    function getListValue($list_key, $list_value) {
        // 
        return ListValueModel::getListTitle($list_key, $list_value); 
    }
    
    
    function getListValueArray($list_keys) {
        
        $m = new ListValueModel();
        return $m->getListValuesByKeyArray($list_keys);
    }
    
    
    function getStatusTitleByEntryType($list_value, $record_type) {
        $st[1] = 'article_status';
        $st[2] = 'file_status';
        
        if ($record_type == 3) {
            $msg = AppMsg::getMsg('common_msg.ini', false, false);
            $value = ($list_value) ? $msg['status_published_msg'] : $msg['status_not_published_msg'];
        } else {
            $value = $this->getListValue($st[$record_type], $list_value);
        }
    
        return $value;
    }
    
    
    function getEntryTypeTitleByEntryType($record_type) {
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        return $msg[$this->record_type[$record_type]];
    }    
    
    
    // function getAdminFilterLinkByEntryType($entry_id, $entry_type) {
    //
    //     $link = false;
    //     $more_filter = array('filter[q]'=>$entry_id);
    //
    //     if($entry_type == 1) {
    //         $link = AppController::getRefLink('knowledgebase', 'kb_entry', false, false, $more_filter);
    //
    //     } elseif($entry_type == 2) {
    //         $link = AppController::getRefLink('file', 'file_entry', false, false, $more_filter);
    //
    //     } elseif($entry_type == 3) {
    //         $link = AppController::getRefLink('news', 'news_entry', false, false, $more_filter);
    //     }
    //
    //     return $link;
    // }
    

    // for scheduled items
    function getEntryDataByEntryType($entry_id, $entry_type) {

        $t = $this->record_type_to_table;
        if (!isset($t[$entry_type])) {
            trigger_error("Unknown entry_type: {$entry_type} (entry_id = {$entry_id}).");
            return false;
        }
        
        $table = $t[$entry_type];
        $table = $this->tbl->$table;

        $sql = "SELECT * FROM {$table} WHERE id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = ($row = $result->FetchRow()) ? $row : array();
        } else {
            trigger_error('Cannot get entry data. ' . $this->db->ErrorMsg());
        }
        
        return $result;
    }


    function getUserByEmail($email, $use_die = true) {
        $sql = "SELECT * FROM {$this->tbl->user} WHERE email = %s";
        $sql = sprintf($sql, $this->db->Quote($email));
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = ($row = $result->FetchRow()) ? $row : array();
        } else {
            trigger_error('Cannot get user data. ' . $this->db->ErrorMsg());
            if($use_die) {
                die(db_error($sql));
            }
        }
        
        return $result;
    }
    
    
    function getUserIdByEmail($email, $use_die = true) {
        $user_id = 0;
        $user = $this->getUserByEmail($email, $use_die);
        if($user) {
            $user_id = $user['id'];
        }
        
        return $user_id;
    }
    

    function &getPoolRecordsResult($limit, $force_order = false) {
        
        $order_by = 'date_created ASC';
        
        // to be ablle to process some letter types first, 
        // for instance 21 for workflows
        if($force_order) {
            // ORDER BY FIELD(letter_type, 21) DESC, date_created ASC
            $forder = implode(',', $force_order);
            $order_by = sprintf('FIELD(letter_type,%s) DESC, %s', $forder, $order_by);
        }
        
        $sql = "SELECT * FROM {$this->tbl->email_pool}
            WHERE status = 0
            ORDER BY {$order_by}";
        $result = $this->db->SelectLimit($sql, $limit, 0);
        if (!$result) { 
            trigger_error('Cannot get messages from pool. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return $result;
    }
        

    function markSentPool($msg_id) {
        $sql = "UPDATE {$this->tbl->email_pool}
            SET date_created = date_created, status = 1, 
            date_sent = NOW(), failed_message = ''
            WHERE id = %d";
        $sql = sprintf($sql, $msg_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error('Cannot mark email as sent. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }


    function markFailedPool($msg_id, $error = '', $status = 0, $add_failed_try = true) {
        
        $add_failed_sql = ($add_failed_try) ? ', failed = failed + 1' : '';
        
        $sql = "UPDATE {$this->tbl->email_pool}
            SET date_created = date_created, status = %d, 
            failed_message = %s, date_sent = NOW() {$add_failed_sql}
            WHERE id = %d";
        $sql = sprintf($sql, $status, $this->db->Quote($error), $msg_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error('Cannot mark email as failed. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }


    // function insertIntoPool($letter_type, $message) {
    //     $sql = "INSERT INTO {$this->tbl->email_pool}
    //         (letter_type, message, date_created) VALUES (%d, %s, NOW())";
    //     $sql = sprintf($sql, $letter_type, $this->db->Quote($message));
    //     $result = $this->db->Execute($sql);
    //     if (!$result) {
    //         trigger_error('Cannot insert email into global DB-pool. ' . $this->db->ErrorMsg());
    //         return false;
    //     }
    //     
    //     return $this->db->Insert_ID();
    // }
    
    
    function insertIntoPool($letter_type, $message) {
        
                
        $sql = "INSERT INTO {$this->tbl->email_pool}
            (message, letter_type, date_created) VALUES ?";
        
        $message = (!is_array($message)) ? array($message) : $message;
        $message = RequestDataUtil::addslashes($message);
        
        $data = array();
        foreach (array_keys($message) as $k) {
            $data[] = array($message[$k]);
        }
        
        $sql = MultiInsert::get($sql, $data, array($letter_type, 'NOW()'));
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error('Cannot insert email into global DB-pool. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return $this->db->Insert_ID();
    }


    function deletePoolById($pool_id) {
        $sql = "DELETE FROM {$this->tbl->email_pool} WHERE id = %d";
        $sql = sprintf($sql, $pool_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error('Cannot delete pool. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }


    function freshPool($status, $days) {
        $status = preg_replace(array("#[^,\d]#", "#,{2,}#"), '', $status);
        $status = ($status) ? $status : '9999';

        $sql = "DELETE FROM {$this->tbl->email_pool}
            WHERE status IN(%s) AND DATEDIFF(NOW(), date_sent) > %d";
        $sql = sprintf($sql, $status, $days);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error('Cannot delete email. ' . $this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }
}
?>