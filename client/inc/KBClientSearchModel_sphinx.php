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


class KBClientSearchModel_sphinx extends KBClientSearchModel
{
    
    var $sphinx;
    var $smanager;
    
    function __construct(&$values, $manager) {
        
        // hack !!! to be able to set fulltext search params in 
        // /client/inc/KBClientSearchModel.php 
        // $values['in'] = ($values['in'] == 'all') ? 'article' : $values['in']; 
        // $_in = (is_array($values['in'])) ? $values['in'] : array($values['in']); 
        // $values['in'] = (in_array('all', $_in)) ? ['article'] : $_in; 
    
        $values['in'] = (is_array($values['in'])) ? $values['in'] : array($values['in']);
        $values['in'] = (in_array('all', $values['in'])) ? ['article'] : $values['in']; 
        
        parent::__construct($values, $manager);
        
        $this->smanager = new SphinxModel(true);
        $this->sphinx = $this->smanager->sphinx;
    }
    
    
    function _getSphinxSearchData($limit, $offset) {
        $rows = $this->smanager->getRecords($limit, $offset);
        
        if (empty($rows)) {
            return false;
        }
        
        $data = array();
        if (is_array($rows)) {
            foreach ($rows as $v) {
                $data[$v['entry_id']] = $v['score'];
    		}
        }
        
        $count = $this->smanager->getCountRecords();
        
        return array($count, $data);
    }
    
    
    function _getSearchData($method, $manager, $limit, $offset) {
        list($count, $sphinx_result) = $this->_getSphinxSearchData($limit, $offset);
        
        if (empty($sphinx_result)) {
            return array(0, array());
        }
        
        $rows = $this->_getSearchRows($sphinx_result, $method, $manager);
        
        return array($count, $rows);
    }
    
    
    function _getSearchRows($sphinx_result, $method, $manager) {
        $ids = array_keys($sphinx_result);
        $sql = $this->$method($ids, $manager);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $_rows = $result->GetAssoc();
        
        // echo '<pre>', print_r($sql,1), '<pre>';
        // echo '<pre>', print_r($ids,1), '<pre>';
        // echo '<pre>', print_r($_rows,1), '<pre>';
        
        $rows = array();
        foreach ($ids as $id) {
            if (!empty($_rows[$id])) {
                $_rows[$id]['score'] = $sphinx_result[$id];
                $rows[] = $_rows[$id];
            }
        }
        
        return $rows;
    }
    
    
    function _getEntryInfoSql($select, $manager) {
        $ids = $this->smanager->getRecordsIds(self::$filter_limit, 0);
        $ids = ($ids) ? implode(',', $ids) : 0;
    
        $sql = "SELECT {$select}
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
            GROUP BY e.id";
                
        return $sql;
    }
    
    
    // ARTICLE // ------------------
    
    function getArticlesSql($ids, $manager) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT e.id AS 'id_',
            e.id AS 'id',
            e.title,
            e.body,
            e.private,
            e.url_title,
            e.entry_type,
            e.hits,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,        
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords,
            e.date_posted,
            e.date_updated
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                
            GROUP BY e.id";
            
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;    
    }
    
    
    function getArticleSearchData($limit, $offset, $manager = false) {
        return $this->_getSearchData('getArticlesSql', $this, $limit, $offset);
    }
    
    
    // FILE // --------------------
    
    function getFilesSql($ids, $manager) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT e.id AS 'id_',
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
            e.meta_keywords,
            e.date_posted,
            e.date_updated
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                
            GROUP BY e.id";
            
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;    
    }
    
    
    function getFileSearchData($limit, $offset, $manager) {
        return $this->_getSearchData('getFilesSql', $manager, $limit, $offset);
    }
    
    
    // NEWS // --------------------------
    
    function getNewsSql($ids) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT 
            e.id AS 'id_',
            e.*,
            YEAR(e.date_posted) AS 'category_id',
            0 AS 'category_private',
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_posted) AS ts_updated,
            e.date_posted AS date_updated
        
        FROM 
            ({$this->tbl->news} e)
            
        WHERE e.id IN ({$ids})";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }
    
    
    function getNewsSearchData($limit, $offset, $manager) {
        return $this->_getSearchData('getNewsSql', $manager, $limit, $offset);
    }
    
    
    function _getNewsInfoSql($select, $manager) {
        $ids = $this->smanager->getRecordsIds(self::$filter_limit, 0);
        $ids = ($ids) ? implode(',', $ids) : 0;
    
        $sql = "SELECT id
        FROM {$this->tbl->news} e
        WHERE e.id IN ({$ids})";
                
        return $sql;
    }
    
    
    // ATTACHMENT // ------------------------------------
    
    function getAttachmentsSql($ids, $manager) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT f.id AS 'id_',
            e.id AS 'id',
            e.title,
            e.body,
            e.private,
            e.url_title,
            e.entry_type,
            e.hits,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,        
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords,
            e.date_posted,
            e.date_updated,
            f.title as file_title, 
            f.id as file_id,
            f.filename,  
            f.description as file_description
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat,
                {$manager->tbl->file_entry} f,
                {$manager->tbl->attachment_to_entry} a)
            
            WHERE f.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                AND e.id = a.entry_id 
                AND f.id = a.attachment_id
                
            GROUP BY e.id";
            
            // -- AND a.attachment_type IN(1,3)
        // echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;    
    }
    
    
    function getAttachmentSearchData($limit, $offset, $manager = false) {
        return $this->_getSearchData('getAttachmentsSql', $this, $limit, $offset);
    }
    
    
    // GENERATOR // ------------------------------------
    
    function setCategoryStatusParams($managers) {
        
        $single_source = !is_array($managers);
        
        if ($single_source) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(%sLENGTH(visible_category), 1, 0)';
        $select_no_cat_str = 'IF(source_id = %d, 1, 0)';
        
        foreach($managers as $manager) {
            if($manager->categories) {
                $source_param = ($single_source) ? '' : sprintf('source_id = %d AND ', $manager->entry_type);
                $select[] = sprintf($select_str, $source_param);
                
            } elseif (!$single_source) {
                $select[] = sprintf($select_no_cat_str, $manager->entry_type);                
            }
        }
        
        if (!empty($select)) {
            $select = implode(' + ', $select) . ' as _category_active';
            $this->smanager->setSqlParamsSelect($select);
            
            $where = 'AND _category_active = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function setStatusParams($managers) {
        
        $single_source = !is_array($managers);
        
        if ($single_source) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(%sIN(active, %s), 1, 0)';
        foreach($managers as $manager) {
            $source_param = ($single_source) ? '' : sprintf('source_id = %d AND ', $manager->entry_type);
            
            $statuses = $manager->getEntryPublishedStatusRaw($manager->entry_list_id);
            $statuses = (!empty($statuses)) ? array_values($statuses) : array(1);
            
            $select[] = sprintf($select_str, $source_param, implode(',', $statuses));
        }
        
        $select = implode(' + ', $select) . ' as _status';
        $this->smanager->setSqlParamsSelect($select);
        
        $where = 'AND _status  = 1';
        $this->smanager->setSqlParams($where);
        
        
        // for attachments
        if(isset($managers['attachment'])) {
            $statuses = $manager->getEntryPublishedStatusRaw(2); // files
            $statuses = (!empty($statuses)) ? array_values($statuses) : array(1);
            $statuses = implode(',', $statuses);
            
            $select = "IF(source_id = %d, IF(IN(file_active, %s), 1, 0), 1) as _file_status";
            $select = sprintf($select, 5, $statuses);
            $this->smanager->setSqlParamsSelect($select);

            $where = 'AND _file_status = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function filterById($ids) {
        $where = sprintf("AND entry_id IN (%s)", implode(',', $ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByTitle($str) {
        $this->smanager->setSqlParamsMatch("@title $str");
    }
    
    
    function filterByTag($tag_ids) {
        
        // find any tag 
        if(0) {
            $tag_ids = ($tag_ids) ? implode(',', $tag_ids) : 0;
            $where = sprintf("AND tag IN (%s)", $tag_ids);
        
        // find all tags 
        } else {
            foreach($tag_ids as $tid) {
                $w[] = sprintf("AND tag = %d", $tid);
            }
        
            $where = implode("\n", $w);
        }
        
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByAuthor($ids) {
        $where = sprintf("AND author_id IN (%s)", implode(',', $ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterArticleByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterFileByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterByFilename($str) {
        $this->smanager->setSqlParamsMatch("@title $str");
    }
    
    
    // this applied to file to hide it if by = in attacmnts only 
    function filterByArticleAttachments($str) {
        $this->smanager->setSqlParamsMatch("$str");
        $where = "AND source_id = 5";
        $this->smanager->setSqlParams($where);
    }
    
    
    // to skip attachments if files found
    function setSkipAttachmentsParams($file_ids) {
        $select = sprintf('IF(source_id = %d, entry_id, 0) as _file_id', 5);
        $this->smanager->setSqlParamsSelect($select);

        $where = sprintf('AND _file_id NOT IN(%s)', $file_ids);
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterNewsByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterEmpty() {
    }
    
    
    function filterByEntryType($c) {
        $where = sprintf("AND entry_type IN (%s)", implode(',', $c));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByCategory($c) {
        $where = sprintf("AND category IN (%s)", implode(',', $c));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByCustomDate() {
        
        if(isset($this->values['date_from'])) {
            $min = $this->values['date_from'];
            $min = strtotime($min);
        }
        
        if(isset($this->values['date_to'])) {
            $max = $this->values['date_to'];
            $max = strtotime($max);    
        }
        
        $attribute = $this->_getFilterDateAttribute();
        
        if(!empty($max) && !empty($min)) {
            $where = "AND $attribute BETWEEN {$min} AND {$max}";
        
        } elseif(!empty($min)) {
            $where = "AND $attribute >= {$min}";
        
        } elseif(!empty($max)) {
            $where = "AND $attribute <= {$max}";
        }
        
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByDate($match) {
        $attribute = $this->_getFilterDateAttribute();
        
        $min = strtotime(sprintf('-%s %s', $match[1], $match[2]));
        $where = "AND $attribute >= {$min}";
        $this->smanager->setSqlParams($where);
    }
    
    
    function _getFilterDateAttribute() {
        // added or updated
        @$attribute = ($this->values['pv'] == 'u') ? 'date_updated' : 'date_posted';
        return $attribute;
    }
    
    
    function setEntryRolesParams($manager) {
        
        if($manager->isUserPrivIgnorePrivate()) {
            return;
        }
        
        // display with lock sign
        if($manager->getSetting('private_policy') == 2 && !$manager->is_registered) {
            return;
        }
        
        $user_role_ids = array(0);
        if($manager->user_role_id) {
            $user_role_ids = array_merge($user_role_ids, $manager->getUserRolesIdsChildByUserId($manager->user_role_id));        
        }
        
        $where = sprintf("AND private_roles_read IN (%s)", implode(',', $user_role_ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function setPrivateParams($manager) {
        
        if(!AppPlugin::isPlugin('private')) {
            return;
        }
            
        if($manager->getSetting('private_policy') == 1 && !$manager->is_registered) {
            // $private = $manager->private_rule['read'];
            // $where = sprintf("AND NOT private && %d", $private);
            $private = $this->convertPrivateBitToIn($manager->private_rule, 'read');
            $where = sprintf("AND private NOT IN (%s)", $private);
            $this->smanager->setSqlParams($where);
            
            $where = 'AND category_readable = 1';
            $this->smanager->setSqlParams($where);
        }
        
        // $unlisted = $manager->private_rule['list'];
        // $where = sprintf("AND NOT private & %s", $unlisted);
        $unlisted = $this->convertPrivateBitToIn($manager->private_rule, 'list');
        $where = sprintf("AND private NOT IN (%s)", $unlisted);
        $this->smanager->setSqlParams($where);
        
        // echo '<pre>' . print_r($this->smanager->sql_params_ar, 1) . '</pre>';
    }
    
    
    function convertPrivateBitToIn($arr, $rule) {
        $ret = array();
        $ret[] = $arr[$rule];
        $ret[] = array_sum($arr);
        foreach($arr as $k => $v) {
            if($k != $rule) {
                $ret[] = $arr[$rule] | $arr[$k];
            }
        }
        
        sort($ret);
        return implode(',', $ret);
    }
    
    
    function setCategoryRolesParams($managers) {
        
        if (!is_array($managers)) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(source_id = %d AND IN(category, %s), 1, 0)';
        $select_no_cat_str = 'IF(source_id = %d, 1, 0)';
        
        $cats_to_skip = false;
        
        foreach($managers as $manager) {
            if($manager->role_skip_categories) {
                $allowed_categories = array_keys($manager->categories);
                $select[] = sprintf($select_str, $manager->entry_type, implode(',', $allowed_categories));
                
                $cats_to_skip = true;
            } else {
                $select[] = sprintf($select_no_cat_str, $manager->entry_type);
            }
        }
        
        if ($cats_to_skip) {
            $select = implode(' + ', $select) . ' as _category_roles';
            $this->smanager->setSqlParamsSelect($select);
            
            $where = 'AND _category_roles = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function setCustomFieldParams($manager) {
        if(!empty($this->values['custom'])) {
            $v = RequestDataUtil::stripVars($this->values['custom']);
            $custom_sql = $this->cf_manager->getCustomFieldSphinxQL($v);
            
            if ($custom_sql['where']) {
                $this->smanager->setSqlParamsSelect($custom_sql['select']);
                $this->smanager->setSqlParams('AND ' . $custom_sql['where']);
            }
            
            if ($custom_sql['match']) {
                $this->smanager->setSqlParamsMatch($custom_sql['match'], 'custom');
            }
        }
    }
    
    
    function setOrderParams($values, $entry_type) {
        
        $val = 'ORDER BY date_updated DESC';
        if($entry_type == 'news') {
            $val = 'ORDER BY date_posted DESC';
        }
        
        if(KBClientSearchHelper::isOrderByScore($values)) {
            $val = 'ORDER BY score DESC';
        }
        
        $this->smanager->setSqlParamsOrder($val);
    }
    
    
    // HIGHLIGHTING // ------------------
    
    function _escapeSnippet($match) {
        $from = array('\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', '<');
        return str_replace($from, '', $match);
    }
    
    
    function highlightTitle($str, $query, $keywords) {
        
        if(!$query) {
            return $str;
        }
        
        $_sql = $this->_getSnippetSql();
        $query = $this->smanager->sql_params_match;
        // $query = str_replace(["\'", '\"', '(', ')','~'], '', $query);
        // $query = $this->smanager->_escapeMatch($query);
        $query = $this->smanager->sql_params_match;
        $query = $this->_escapeSnippet($query);
        $query = addslashes($query);
        
        $sql = sprintf($_sql, $this->sphinx->addQ($str), $query, 1000); // no limit
        $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
        $snippet = $result->GetArray();
        if(!empty($snippet[0]['snippet'])) {
            $str = $snippet[0]['snippet'];
            
        } else {
            $str = DocumentParser::getTitleSearch($str, implode(' ', $keywords));
        }
        
        return $str;
    }
    
    
    function highlightBody($str, $query, $keywords, $limit) {
        
        if(!$limit ) {
            return;
        }

        if(!$query) {
            return $str;
        }
        
        DocumentParser::parseCurlyBracesSimple($str);
        $str = DocumentParser::stripHTML($str);
        
        $_sql = $this->_getSnippetSql();  
        $query = $this->smanager->sql_params_match;
        // $query = str_replace(["\'", '\"','(', ')','~'], '', $query);
        // $query = $this->smanager->_escapeMatch($query);
        $query = $this->smanager->sql_params_match;
        $query = $this->_escapeSnippet($query);
        $query = addslashes($query);        
        
        $sql = sprintf($_sql, $this->sphinx->addQ($str), $query, $limit);
        $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
        $snippet = $result->GetArray();
        
        if(!empty($snippet[0]['snippet'])) {
            $str = $snippet[0]['snippet'];
            
        } else {
            $str = RequestDataUtil::stripslashes($str);
            $str = DocumentParser::getSummarySearch($str, implode(' ', $keywords), $limit);
        }
        
        return $str;
    }
    
    
    function _getSnippetSql() {
        $idx = SphinxModel::setIndexNames();
        $sql = "CALL SNIPPETS('%s', '{$idx->kbpArticleIndex_main}', '%s',
            '<span class=\"highlightSearch\">' as before_match,
            '</span>' as after_match,
            %d as limit,
            1 as allow_empty)";
        
        return $sql;
    }
    
    
    function getKeywords() {
		$index_name = sprintf('%skbpBaseIndex', SphinxModel::getSphinxPrefix());
        $sql = "CALL KEYWORDS('%s', '%s')";
        $query = $this->smanager->sql_params_match;
        
        $sql = sprintf($sql, $query, $index_name);
        $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
		
        $rows = $result->GetArray();
        $keywords = array();
        foreach ($rows as $v) {
            $keywords[] = $v['tokenized'];
            $keywords[] = $v['normalized'];
        }
        
        $keywords = array_unique($keywords);
        return $keywords;
    }
    
    
    // GLOSSARY, TAGS // ------------------
    
    static function parseFilterSql($sphinx_sql, $options) {
        
        $bp = PageByPage::factory('form', $options['limit'], $_GET);
            
        $smanager = new SphinxModel;
        $smanager->setIndexParams($options['index']);
        
        $smanager->setSqlParamsMatch($sphinx_sql['match']);
        $smanager->setSqlParamsOrder($options['sort']);
        
        if (!empty($sphinx_sql['where'])) {
            $smanager->setSqlParams($sphinx_sql['where']);
        }
            
        $ids = $smanager->getRecordsIds($bp->limit, $bp->offset);
        
        if(!empty($ids)) {
            $arr['where'] = sprintf('AND id IN(%s)', implode(',', $ids));
            $arr['sort'] = sprintf('ORDER BY FIELD(id, %s)', implode(',', $ids));
            
        } else {
            $arr['where'] = 'AND 0';
            $arr['sort'] = 'ORDER BY id';
        }
        
        $arr['count'] = $smanager->getCountRecords();
        
        return $arr;
    }
}
?>