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


class SubscriptionModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'user_subscription',
                        'user_subscription',
                        'files' => 'file_entry',
                        'news' => 'news',
                        'category_files' => 'file_category',
                        'category_articles' => 'kb_category',
                        'articles' => 'kb_entry',
                        'category' => 'kb_category',
                        'entry_to_category' => 'kb_entry_to_category',
                        'entry_trash');


    var $types = array(
        '3'   => 'news',
        '1'   => 'articles',
        '11'  => 'articles_cat',
        '31'  => 'comments',
        '2'   => 'files',
        '12'  => 'files_cat'
    );
    
    // which also used in saved list (favorites) articles, files
    var $types_saved_list = array(1,2);
    var $types_comment_list = array(31);


    function getArticleManager() {
        return new KBEntryModel(false, 'read');
    }


    function getFileManager() {
        return new FileEntryModel(false, 'read');
    }


    function getRowsByIds($record_id) {
        $sql = "SELECT t.id AS id2, t.* FROM {$this->tbl->table} t
        WHERE id IN ($record_id)
        {$this->sql_params_order}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // changed 18 June, 2014 as entry could be in trash
    // but subscription not deleted
    function getRowsCount($user_id) {
        $sql = "SELECT t.entry_type, COUNT(*) AS num
        FROM {$this->tbl->table} t
        LEFT JOIN {$this->tbl->entry_trash} th
            ON th.entry_id = t.entry_id
            AND IF(t.entry_type = 31, th.entry_type=1, th.entry_type=t.entry_type)
        WHERE t.user_id = '{$user_id}'
        AND th.id IS NULL
        AND t.is_mail = 1
        GROUP BY t.entry_type";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    

    function getSubscription($entry_type, $user_id) {
        $sql = "SELECT entry_id, entry_id AS 'eid'
        FROM {$this->tbl->table}
        WHERE entry_type = '{$entry_type}' AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function parseCategories($cats, $type) {

        $m = ($type == 11) ? $this->getArticleManager()
                             : $this->getFileManager();

        $c = &$m->cat_manager->getChildCategories(false, $cats);

        $ret['remove'] = array();
        foreach($c as $parent_id => $v) {
            foreach($v as $child_id) {
                if(isset($c[$child_id])) {
                    $ret['remove'][$child_id] = $child_id;
                }
            }
        }

        $ret['add'] = array_diff($cats, $ret['remove']);

        return $ret;
    }


    function saveSubscription($values, $entry_type, $user_id, $replace = false, $is_mail = 1) {
        
        $values = (is_array($values)) ? $values : array($values);
        $entry_type = (is_array($entry_type)) ? $entry_type : array($entry_type);
        $user_id = (is_array($user_id)) ? $user_id : array($user_id);

        $values2 = array();
        foreach($entry_type as $type) {
            foreach($values as $entry_id) {
                foreach($user_id as $_user_id) {
                    $k_ = $entry_id . $type . $_user_id;
                    $values2[$k_] = array ($entry_id, $type, $_user_id, $is_mail);
                }
            }
        }

        if($values2) {
            $ins = new MultiInsert;
            $ins->setFields(
                array('entry_id', 'entry_type', 'user_id', 'is_mail'), 
                array('date_subscribed', 'date_lastsent')
            );
            $ins->setValues($values2, array('NOW()', 'NOW()'));
            
            $command = ($replace) ? 'REPLACE' : 'INSERT IGNORE';
            $sql = $ins->getSql($this->tbl->user_subscription, $command);

            //echo '<pre>', print_r($sql, 1), '</pre>';
            //exit;

            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }


    function updateDateLastsent($entry_id, $entry_type, $user_id) {
        $sql = "UPDATE {$this->tbl->table} SET date_lastsent = NOW()
        WHERE  entry_id IN ({$entry_id})
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteByUserId($user_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE user_id = '{$user_id}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteByEntryType($entry_type, $user_id) {
        $sql = "DELETE FROM {$this->tbl->user_subscription}
        WHERE entry_type IN ({$entry_type})
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSubscription($entry_id, $entry_type, $user_id) {
        $entry_id = (is_array($entry_id)) ? implode(',', $entry_id) : $entry_id;
        $sql = "DELETE FROM {$this->tbl->user_subscription}
        WHERE  entry_id IN ({$entry_id})
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // NEW // ------------------------
    // unsubscribe, stop get notifications
    // available for articles and files
    
    function saveSubscriptionEntry($entry_id, $entry_type, $user_id, $is_mail) {
        return $this->saveSubscription($entry_id, $entry_type, $user_id, false, $is_mail);
    }
    
    
    function setEmailNotification($entry_id, $entry_type, $user_id, $is_mail) {
        $entry_id = (is_array($entry_id)) ? implode(',', $entry_id) : $entry_id;
        $sql = "UPDATE {$this->tbl->table}
        SET is_mail = %d
        WHERE  entry_id IN (%s)
        AND entry_type = %d
        AND user_id = %d";
        $sql = sprintf($sql, $is_mail, $entry_id, $entry_type, $user_id);
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function subscribe($entry_id, $entry_type, $user_id) {
        $this->setEmailNotification($entry_id, $entry_type, $user_id, 1);
    }
    
    
    function unsubscribe($entry_id, $entry_type, $user_id) {
        $this->setEmailNotification($entry_id, $entry_type, $user_id, 0);
    }
    
    
    function unsubscribeByUserId($user_id, $include_comment_subs = true) {
        $this->_unsubscribeDeleteFromList($user_id, false, $include_comment_subs);
        $this->_unsubscribeEmailFromList($user_id, false, $include_comment_subs);
    }


    function unsubscribeByEntryType($user_id, $entry_type) {
        $this->_unsubscribeDeleteFromList($user_id, $entry_type);
        $this->_unsubscribeEmailFromList($user_id, $entry_type);
    }
    
    
    function _unsubscribeDeleteFromList($user_id, $entry_type = false, $include_comment_subs = true) {
        $list_types = implode(',', $this->types_saved_list);
        $entry_type_sql = ($entry_type) ? "entry_type IN ({$entry_type})" : 1;
        
        $comment_types = implode(',', $this->types_comment_list);
        $skip_comment_sql = (!$include_comment_subs) ? "entry_type NOT IN ({$comment_types})" : 1;
        
        $sql = "DELETE FROM {$this->tbl->table} 
            WHERE user_id IN ({$user_id})
            AND entry_type NOT IN ({$list_types})
            AND {$entry_type_sql} AND {$skip_comment_sql}";
            
        $this->db->Execute($sql) or die(db_error($sql));
    }
        
    
    function _unsubscribeEmailFromList($user_id, $entry_type = false) {
        $list_types = implode(',', $this->types_saved_list);
        $entry_type_sql = ($entry_type) ? "entry_type IN ({$entry_type})" : 1;
        
        $sql = "UPDATE {$this->tbl->table} SET is_mail = 0
            WHERE user_id IN ({$user_id})
            AND entry_type IN ({$list_types})
            AND {$entry_type_sql}";
            
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
}
?>