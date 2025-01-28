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

class KBClientSearchModel_mysql extends KBClientSearchModel
{    
        
    var $sql_params_group2;
    var $fmodel;
    
    
    // ARTICLE // ------------------
    
    function getArticleSqlSelect() {
        
        $select = "
            e.id AS 'id_',
            e.id AS 'id',
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords, -- fix for api  
            e.date_posted,
            e.date_updated,
            {$this->sql_params_select}";
        
        return $select;    
    }
    
    
    // in category select we need distinct or group by
    function getArticleSql() {
        $select = $this->getArticleSqlSelect();        
        return $this->_getEntriesSql($select, $this);
    }
        

    function getArticleCountSql() {
        return $this->_getEntryCountSql($this);
    }
    
    
    function getArticleDataByIds($ids) {
        $sql = "SELECT id, title, body, private, url_title, entry_type, hits
        FROM {$this->tbl->entry} WHERE id IN ({$ids})
        -- ORDER BY FIELD(id, {$ids})
        ";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function &getArticleList($limit, $offset) {
        $sql = $this->getArticleSql();
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        $rows = $result->GetAssoc();
        
        if($rows) {
            $ids = array_keys($rows);
            $ids_str = implode(',', $ids);
            $rows2 = $this->getArticleDataByIds($ids_str);
            foreach($ids as $id) {
                $rows[$id] = $rows2[$id] + $rows[$id];
            }
        }
        
        return $rows;
    }
    
    
    function getArticleCount() {
        $sql = $this->getArticleCountSql();                        
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $sql);
        return $result->fields['num'];
    }
    
    
    function getArticleSearchData($limit, $offset) {
        $count = $this->getArticleCount();
        $rows = $this->getArticleList($limit, $offset);
        
        return array($count, $rows);
    }
    
    
    // FILE // --------------------
    
    function getFilesSqlSelect() {
        
        $select = "
            e.id AS 'id_',
            e.id,
            e.title,
            e.description,
            e.filename,
            e.filesize,
            e.private,
            e.downloads,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords, -- fix for api
            e.date_posted,
            e.date_updated,
            {$this->sql_params_select}";
        
        return $select;    
    }
    

    function getFilesSql($manager) {
        $select = $this->getFilesSqlSelect();
        return $this->_getEntriesSql($select, $manager);
    }
    
    
    function getFileCountSql($manager) {
        return $this->_getEntryCountSql($manager);
    }
    
    
    function getFileList($limit, $offset, $manager) {
        $sql = $this->getFilesSql($manager);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
    
        //echo $this->getExplainQuery($this->db, $result->sql);    
        return $result->GetArray();
    }
    
    
    function getFileCount($manager) {
        $sql = $this->getFileCountSql($manager);
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $sql);
        return $result->fields['num'];
    }
    
    
    function getFileSearchData($limit, $offset, $manager) {
        $count = $this->getFileCount($manager);
        $rows = $this->getFileList($limit, $offset, $manager);
        
        return array($count, $rows);
    }
    
    
    // NEWS // --------------------------
    
    function getNewsSql($manager) {
        
        $sql = "
        SELECT 
            e.id AS 'id_',
            e.*,
            YEAR(e.date_posted) AS 'category_id',
            0 AS 'category_private',
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_posted) AS ts_updated,
            e.date_posted AS date_updated,
            {$this->sql_params_select}
        
        FROM 
            ({$this->tbl->news} e
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})
        {$this->sql_params_group}
        {$this->sql_params_order}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }


    function getNewsCountSql() {
        
        $sql = "SELECT COUNT(DISTINCT({$this->distinct_value})) AS num        
        FROM 
            ({$this->tbl->news} e
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }

    
    function getNewsList($limit, $offset, $manager) {
        $sql = $this->getNewsSql($manager);
        $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $result->sql);
        return $result->GetArray();
    }
    
    
    function &getNewsCount($manager) {
        $sql = $this->getNewsCountSql($manager);
        $result = $this->db->SelectLimit($sql, $this->count_limit) or die(db_error($sql));
        
        // echo $this->getExplainQuery($this->db, $sql);
        return $result->fields['num'];
    }
    
    
    function getNewsSearchData($limit, $offset, $manager) {
        $count = $this->getNewsCount($manager);
        $rows = $this->getNewsList($limit, $offset, $manager);
        
        return array($count, $rows);
    }
    
    
    function _getNewsInfoSql($select, $manager) {
        
        $sql = "SELECT id        
        FROM 
            ({$this->tbl->news} e
            {$this->sql_params_from})
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
            AND e.active IN ({$this->entry_published_status})";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }
    
    
    // PRIVATE // ------------------
        
    function _getEntriesSql($select, $manager) {

        $sql = "SELECT {$select}
        FROM 
            ({$manager->tbl->entry} e,
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
            
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        {$this->sql_params_group}
        {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function _getEntryCountSql($manager) {
        
        $sql = "SELECT COUNT(DISTINCT({$this->distinct_value})) AS 'num' 
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function _getEntryInfoSql($select, $manager) {
        
        $sql = "SELECT {$select}, {$this->sql_params_select}
        FROM 
            ({$manager->tbl->entry} e, 
            {$manager->tbl->category} cat,
            {$manager->tbl->entry_to_category} e_to_cat
            {$this->sql_params_from})
            
        {$this->entry_role_sql_from}
        {$this->sql_params_join}
        
        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1            
            AND {$this->entry_role_sql_where}
            AND {$this->sql_params}
        GROUP BY e.id";
        // {$this->sql_params_order}";
        
        // echo '<pre>' . print_r($sql, 1) . '</pre>';
        return $sql;
    }
    
    
    // GENERATOR // ------------------
    
    function filterById($ids) {
        $where = sprintf("AND e.id IN (%s)", implode(',', $ids));
        $this->setSqlParams($where);
    }
    
    
    function filterByTitle($str) {
        
        if($this->values['in'] == 'file') {
            
            $select = "MATCH (e.title, e.filename_index) AGAINST ('$str') AS score";
            $this->setSqlParamsSelect($select);
        
            $where = "AND MATCH (e.title, e.filename_index) AGAINST ('$str' IN BOOLEAN MODE)";
            $this->setSqlParams($where);
            
        } else {
            
            $select = "MATCH (e.title) AGAINST ('$str') AS score";
            $this->setSqlParamsSelect($select);
        
            $where = "AND MATCH (e.title) AGAINST ('$str' IN BOOLEAN MODE)";
            $this->setSqlParams($where);
        }
    }
    
    
    function filterByTag($tag_ids) {
            
        $w = array();

        // find any tag 
        if(0) {
            
            $w[] = "AND tag_to_e.entry_id = e.id";
            $w[] = "AND tag_to_e.entry_type = '{$this->entry_type}'";

            $tag_ids = ($tag_ids) ? implode(',', $tag_ids) : 0;
            $w[] = "AND tag_to_e.tag_id IN ({$tag_ids})";
            $from = sprintf(', %s %s', $this->tbl->tag_to_entry, 'tag_to_e');
        
        // find all tags 
        } else {
            
            $i = 1;
            $from = '';
            $tag_ids = ($tag_ids) ? $tag_ids : array(0);
            foreach($tag_ids as $tid) {
                $tbl = sprintf('tag_to_e%d', $i++);
            
                $w[] = sprintf("AND %s.entry_id = e.id", $tbl);
                $w[] = sprintf("AND %s.entry_type = '%d'", $tbl, $this->entry_type);
                $w[] = sprintf("AND %s.tag_id = '%d'", $tbl, $tid);
            
                $from .= sprintf(', %s %s', $this->tbl->tag_to_entry, $tbl);
            }
        }
          
        $where = implode("\n", $w);
        $this->setSqlParams($where);
        $this->setSqlParamsFrom($from);
    }
    
    
    function filterByAuthor($ids) {
        $where = sprintf("AND e.author_id IN (%s)", implode(',', $ids));
        $this->setSqlParams($where);
    }
    
    
    function filterArticleByAllFields($str) {
        $f = 'e.title, e.body_index, e.meta_keywords, e.meta_description';

        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);

        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);
    }
    
    
    function filterFileByAllFields($str) {
		$f = 'e.title, e.filename_index, e.meta_keywords, e.description, e.filetext';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);
    }
    
    
    function filterByFilename($str) {
        $str = str_replace('*', '%', $str);
        $where = "AND e.filename LIKE '$str'";
        $this->setSqlParams($where);
    }
	
	
    // this used manually to search in files which attached to articles 
    // function filterByArticleAttachmentsPlus($str) {
    function setSearchAttachmentParams($skip_ids, $search_type = 1) {
    
        $str = trim($this->values['qs']);
        $str = addslashes(stripslashes($str));
        
        $published_status = $this->getEntryPublishedStatus(2);
        $f = "f.title, f.filename_index, f.meta_keywords, f.description, f.filetext";
    
        $select = "f.id as file_id, f.title as file_title, f.filename, f.description as file_description, 
                    MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select, null, true);
    
        $from = sprintf(', %s %s', $this->tbl->file_entry, 'f');
        $from .= sprintf(', %s %s', $this->tbl->attachment_to_entry, 'a');
        $this->setSqlParamsFrom($from, null, true);
    
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $where .= " AND e.id = a.entry_id AND f.id = a.attachment_id";
        $where .= " AND f.active IN({$published_status})";
        // $where .= " AND a.attachment_type IN(1,3)"; // attachment only
        $this->setSqlParams($where, null, true);
        
        $this->setSqlParamsGroup('GROUP BY f.id', null, true);
        $this->distinct_value = 'f.id';
        
        if($skip_ids) {
            $this->setSqlParams("AND f.id NOT IN({$skip_ids})");
        }
    }
    
    
    // this applied to file to hide it if by = in attacmnts only 
    function filterByArticleAttachments($str) {
		$f = 'e.title, e.filename_index, e.meta_keywords, e.description, e.filetext';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND e.id = '-1'";
        $this->setSqlParams($where);
    }
    
    
    function filterNewsByAllFields($str) {
		$f = 'e.title, e.body_index, e.meta_keywords';
		
        $select = "MATCH ($f) AGAINST ('$str') AS score";
        $this->setSqlParamsSelect($select);
        
        $where = "AND MATCH ($f) AGAINST ('$str' IN BOOLEAN MODE)";
        $this->setSqlParams($where);    
    }
    
    
    function filterEmpty() {
        $select = "UNIX_TIMESTAMP(e.date_updated) AS score";        
        $this->setSqlParamsSelect($select);
    }

    
    function filterDate() {
        $select = "UNIX_TIMESTAMP(e.date_updated) AS date";        
        $this->setSqlParamsSelect($select);
    }
    
    
    function filterByEntryType($c) {
        $c = implode(',', $c);
        $where = "AND e.entry_type IN($c)";
        $this->setSqlParams($where);
    }
    
    
    function filterByCategory($c) {
        $csql = implode(',', $c);
        
        //$sql = "e_to_cat.category_id IN($c)";
        $where = "AND cat.id IN($csql)";
        $this->setSqlParams($where);
    }
    
    
    function filterByCustomDate() {
        
        if(isset($this->values['date_from'])) {
            $min = $this->values['date_from'];
            $min = (int) $min;
        }
        
        if(isset($this->values['date_to'])) {
            $max = $this->values['date_to'];
            $max = (int) $max;    
        }
        
        $field = $this->_getFilterDateField();
                                
        // we have date active
        if(!empty($max) && !empty($min)) {
            $where = "AND $field BETWEEN '{$min}' AND '{$max}'";
        
        } elseif(!empty($min)) {
            $where = "AND $field >= '{$min}'";
        
        } elseif(!empty($max)) {
            $where = "AND $field <= '{$max}'";
        }
        
        $this->setSqlParams($where);
    }
    
    
    function filterByDate($match) {
        $field = $this->_getFilterDateField();
        $period = strtoupper($match[2]);
        $sql = sprintf("AND %s >= DATE_SUB(CURDATE(), INTERVAL %s %s)", $field, $match[1], $period);
        $this->setSqlParams($sql);
    }
    
    
    function _getFilterDateField() {
        // added or updated
        @$field = ($this->values['pv'] == 'u') ? 'e.date_updated' : 'e.date_posted';        
        return $field;
    }


    function setCustomFieldParams($manager) {
        if(!empty($this->values['custom'])) {
            $v = RequestDataUtil::stripVars($this->values['custom']);
            $custom_sql = $this->cf_manager->getCustomFieldSql($v);
            
            $this->setSqlParams('AND ' . $custom_sql['where']);
            $this->setSqlParamsJoin($custom_sql['join']);
        }
    }
    
    
    function setOrderParams($values, $entry_type) {
    
        $val = 'ORDER BY e.date_updated DESC';
        if($entry_type == 'news') {
            $val = 'ORDER BY e.date_posted DESC';
        }
        
        if(KBClientSearchHelper::isOrderByScore($values)) {
            $val = 'ORDER BY score DESC';
        }
        
        $this->setSqlParamsOrder($val);
    }
    
    
    function setGroupParams($values) {
        $val = 'GROUP BY e.id';
        $this->setSqlParamsGroup($val);
        $this->distinct_value = 'e.id';
    }
    
    
    // HIGHLIGHTING // ------------------
    
    function highlightTitle($str, $query, $keywords) {
        return DocumentParser::getTitleSearch($str, $query);
    }
    
    
    function highlightBody($str, $query, $keywords, $limit) {
        return DocumentParser::getSummarySearch($str, $query, $limit);
    }
    

    function getKeywords() {
        return false;
    }
    
}
?>