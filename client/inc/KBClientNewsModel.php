<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class KBClientNewsModel extends KBClientModel_common
{
    var $tbl_pref_custom = 'news_';
    
    var $custom_tables = array(
        'news', 
        'entry' => 'news',
        'list_value', 'entry_hits',
        'data_to_value'=>'data_to_user_value',
        'data_to_value_string'=>'data_to_user_value_string',
        'custom_field', 'custom_field_to_category', 'custom_field_range_value',
        'tag', 'tag_to_entry',
        'user', 'user_company', 'user_subscription'
    );
    
    
    // rules id in data to user rule
    var $role_entry_read_id = 107;    
    var $role_entry_write_id = 108;    
    
    var $entry_list_id = 3; // id in list statuses 
    var $entry_type = 3; // entry type in entry_hits, entry_schedule  
    
    var $session_view_name = 'kb_view_news_';
    
    
    function getNewsYears() {
        
        $private_sql = $this->getPrivateSql(false);
        
        $sql = "SELECT DISTINCT 
            YEAR(date_posted) as 'year', 
            YEAR(date_posted) as 'year2' 
        FROM 
            {$this->tbl->news} e 
            {$this->entry_role_sql_from}
        WHERE 1 
            AND {$private_sql}
            AND {$this->entry_role_sql_where}
            AND e.active IN ({$this->entry_published_status})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
    
    
    function getYearByEntryId($entry_id) {
        $sql = "SELECT YEAR(date_posted) as 'year' FROM {$this->tbl->news} WHERE id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('year');        
    }


    function getNewsSql($entry_id = false, $year = false, $date_to = false) {

        $stype = ($entry_id) ? 'id' : false;
        $private_sql = $this->getPrivateSql($stype);
        $entry_sql = ($entry_id) ? "e.id = '{$entry_id}'" : 1;
        
        $year_sql = 1;
        if($year) {
            $range = $this->getDateRange('year', $year);
            $year_sql = sprintf("e.date_posted BETWEEN '%s' AND '%s'", $range['min'], $range['max']);
        }
        
        $sql = "
        SELECT e.*,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated
        FROM 
            {$this->tbl->news} e 
            {$this->entry_role_sql_from}
        WHERE 1 
            AND {$private_sql}
            AND {$this->entry_role_sql_where}
            AND {$entry_sql}
            AND {$year_sql}
            AND e.active IN ({$this->entry_published_status})
        {$this->sql_params_group}
        ORDER BY date_posted DESC";
        
        return $sql;
    }
    
    
    function getNewsSqlCount($year = false, $date_to = false) {

        $private_sql = $this->getPrivateSql(false);
        
        $year_sql = 1;
        if($year) {
            $range = $this->getDateRange('year', $year);
            $year_sql = sprintf("e.date_posted BETWEEN '%s' AND '%s'", $range['min'], $range['max']);
        }
        
        $sql = "SELECT COUNT(*) AS num 
        FROM 
            {$this->tbl->news} e 
            {$this->entry_role_sql_from}
        WHERE 1 
            AND {$private_sql}
            AND {$this->entry_role_sql_where}
            AND {$year_sql}
            AND e.active IN ({$this->entry_published_status})
        {$this->sql_params_group}";
        
        return $sql;
    }
    
    
    function getNewsCount($year = false, $date_to = false) {
        $sql = $this->getNewsSqlCount($year, $date_to);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        //echo $this->getExplainQuery($this->db, $result->sql);
        return $result->Fields('num');
    }
    
    
    function getNewsList($limit, $offset, $year = false, $date_to = false) {
        $sql = $this->getNewsSql(false, $year, $date_to);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        //echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }
    
    
    function getNewsByYear($year) {
        $sql = $this->getNewsSql(false, $year);
        $result = $this->db->SelectLimit($sql, -1, 0) or die(db_error($sql));
        
        //echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }    
    
    
    function getNewsByDate($year) {
        $sql = $this->getNewsSql(false, $year);
        $result = $this->db->SelectLimit($sql, -1, 0) or die(db_error($sql));
        
        //echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }    
    
    
    function getNewsById($id) {
        $sql = $this->getNewsSql($id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getDateRange($range, $value_from, $value_to = false) {
        if($range == 'year') {
            $min = $value_from . '0101';
            $max = $value_from . '1231';
        } else {
            $min = $value_from;
            $max = $value_to;
        }
        
        return array('min' => $min, 'max'=> $max);
    }
    
    
    function isNewsAccessible($entry_id, $category_id = false) {
        return $this->getNewsById($entry_id);
    }
    
    
    // is entry exists in db and active
    function isNewsExistsAndActive($entry_id) {
        $sql = "SELECT e.id FROM {$this->tbl->news} e 
        WHERE e.id = '{$entry_id}' AND e.active = 1";
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    // reassign 
    
    function isPrivateCategory($category_id) {
        return false;
    }    
    
    
    function setCategories($sort = false, $type_param = 'all') {
        return array();
    }
    
    
    function getEntryPublishedStatus($list_id) {
        return 1;
    }
    
    
    // keep maximum the same as in KBClientModel 
    function getPrivateSql($category = true) {
        $sql = '1';
        if(AppPlugin::isPlugin('private')) {
            if($this->getSetting('private_policy') == 1 && !$this->is_registered) {
                $private = $this->private_rule['read'];
                $sql = "NOT e.private & {$private}";
            }
    
            if($category === 'count' || $category === false) {
                $unlisted = $this->private_rule['list'];
                $sql .= " AND NOT e.private & {$unlisted}";
            }
        }
    
        return $sql;
    }
    
    
    function isNewsUpdatableByUser($entry_id, $entry_private) {
        
        if(!$is_priv = $this->isUserPriv()) {
            return false;
        }
        
        if($this->isUserPrivIgnorePrivate()) {
            return true;
        }
        
        $auth = new AuthPriv;
        $auth->use_exit_screen = false;
        
        if($auth->check('update', 'news_entry')) {
            $is_priv = true;
        }
        
        // entry private write
        if($is_priv) {
            $is_priv = $this->isEntryInUserRoles($entry_id, $entry_private, 'entry', 'write');                        
        }
        
        return $is_priv;
    }
    
}
?>