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


class KBClientRSSModel extends KBClientBaseModel
{

    var $tbl_pref_custom = 'kb_';
    var $tables = array('entry', 'category', 'entry_to_category', 'comment');
    var $custom_tables = array('file_entry', 'list_value', 'news', 'user');
    
    
    static function factory($type) {
        $class = 'KBClientRSSModel_' . $type;
        return new $class;
    }
}


class KBClientRSSModel_article extends KBClientRSSModel
{

    var $entry_list_id = 1; // article
    
    
    function getEntriesSql() {
        $private = $this->private_rule['read'];
        
        $sql = "
        SELECT 
            e.id AS entry_id,
            e.title,
            e.url_title,
            e.body AS description,
            cat.name AS category,
            cat.id AS category_id,
            UNIX_TIMESTAMP(e.date_updated) AS pubDate

        FROM 
            ({$this->tbl->entry} e, 
            {$this->tbl->category} cat,
            {$this->tbl->entry_to_category} e_to_cat)                
        
        WHERE 1
            AND e_to_cat.entry_id = e.id 
            AND e_to_cat.category_id = cat.id    
            AND e.active IN ({$this->entry_published_status})
            AND cat.active = 1
            AND NOT e.private & {$private}
            AND NOT cat.private & {$private}
            AND {$this->sql_params}
        ORDER BY e.date_updated DESC";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function &getEntries($category_id, $cat, $controller, $limit = 15, $desc_signs = 250) {
        
        if($category_id) {
            $tree = new TreeHelper();
            foreach($cat as $k => $row) {
                $tree->setTreeItem($row['id'], $row['parent_id']);
            }
            
            $child_category_ids = $tree->getChildsById($category_id);
            $child_category_ids[$category_id] = $category_id;
            $child_category_ids = implode(',', $child_category_ids);
            
            $this->setSqlParams("AND e_to_cat.category_id IN($child_category_ids)");
        }
        
        $sql = $this->getEntriesSql($limit);
        $result = $this->db->SelectLimit($sql, $limit, 0) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']] = $row;
            
            $entry_id = $controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
            $data[$row['entry_id']]['link'] = $controller->getLink('entry', $row['category_id'], $entry_id);                
            
            $data[$row['entry_id']]['category'] = $this->getParentCategoriesTitles($cat, $row['category_id']);
            $data[$row['entry_id']]['description'] = DocumentParser::getSummary($row['description'], $desc_signs);
            //$data[$row['entry_id']]['content'] = $row['description'];
        }
        
        //echo "<pre>"; print_r($data); echo "</pre>";
        // echo "<pre>"; print_r($sql); echo "</pre>";
        
        return $data;
    }


    function getCategories() {
        $private = $this->private_rule['read'];
        $sql = "SELECT id as id1, id, parent_id, name 
        FROM {$this->tbl->category}
        WHERE active = 1
        AND NOT private & {$private}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getParentCategoriesTitles($categories, $category_id) {
        $categories = TreeHelperUtil::getParentsById($categories, $category_id, 'name');
        return implode('/', $categories);
    }
    
    
    function getCategoryData($category_id) {
        $sql = "SELECT name AS title, description FROM {$this->tbl->category} WHERE id = '{$category_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getChannelData($category_id, $categories, $controller, $setting) {
        
        $data = array();
        
        $data['title'] = $setting['rss_title'];
        $data['link'] = $controller->kb_path;
        $data['description'] = $setting['rss_description'];
        
        if($category_id) {
            $str = '%s (%s)';
            $category_data = $this->getCategoryData($category_id);
            
            $data['title'] = sprintf($str, $data['title'], $category_data['title']);
            if($category_data['description']) {
                $data['description'] = sprintf($str, $data['description'], $category_data['description']);
            }
            
            $data['link'] = $controller->getLink('index', $category_id);
        }
        
        return $data;
    }    
}


class KBClientRSSModel_news extends KBClientRSSModel
{


    function setEntryPublishedStatus() {}


    function getEntriesSql() {
        $private = $this->private_rule['read'];
        $sql = "
        SELECT
            e.id AS entry_id,
            e.title,
            e.body AS description,
            UNIX_TIMESTAMP(e.date_posted) AS pubDate
        FROM 
            {$this->tbl->news} e
        WHERE 1 
            AND NOT e.private & {$private}
            AND e.active = 1
        ORDER BY date_posted DESC";
        
        return $sql;
    }


    function &getEntries($category_id, $cat, $controller, $limit = 15, $desc_signs = 250) {
        
        $file = AppMsg::getModuleMsgFile('public', 'client_msg.ini');    
        $msg = AppMsg::parseMsgs($file, false, false);
        
        $sql = $this->getEntriesSql();
        $result = $this->db->SelectLimit($sql, $limit, 0) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']] = $row;
            
            $entry_id = $controller->getEntryLinkParams($row['entry_id'], $row['title'], false);
            $data[$row['entry_id']]['link'] = $controller->getLink('news', false, $entry_id);                
            
            $data[$row['entry_id']]['category'] = $msg['menu_news_msg'];
            $data[$row['entry_id']]['description'] = DocumentParser::getSummary($row['description'], $desc_signs);
        }
        
        //echo "<pre>"; print_r($data); echo "</pre>";
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;
        
        return $data;
    }


    function getChannelData($category_id, $categories, $controller, $setting) {
        
        $file = AppMsg::getModuleMsgFile('public', 'client_msg.ini');    
        $msg = AppMsg::parseMsgs($file, false, false);
        //echo '<pre>', print_r($msg, 1), '</pre>';
        
        $data = array();
        
        $title = sprintf('%s (%s)', $setting['rss_title'], $msg['menu_news_msg']);  
        $data['title'] = $title;
        $data['link'] = $controller->getLink('news');
        $data['description'] = $setting['rss_description'];
        
        return $data;
    }
}


class KBClientRSSModel_comment extends KBClientRSSModel
{

    var $entry_data_flag = false;
    var $entry_data = array();


    function setEntryPublishedStatus() {}


    function &getEntryData($entry_id) {
        
        if(!$this->entry_data_flag) {
            $this->entry_data_flag = true;
            
            $manager = new KBClientRSSModel_article();
            $manager->setEntryPublishedStatus();
            $manager->setSqlParams("AND e.id = '{$entry_id}'");
            
            $sql = $manager->getEntriesSql();
            $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
            $this->entry_data = $result->FetchRow();            
        }
        
        return $this->entry_data;
    }


    function getEntriesSql($entry_id) {
        
        $sql = "
        SELECT 
            e.id AS entry_id,
            e.comment AS description,
            UNIX_TIMESTAMP(e.date_posted) AS pubDate,
            IFNULL(u.username, e.name) AS comment_name

        FROM 
            {$this->tbl->comment} e                
        LEFT JOIN {$this->tbl->user} u ON u.id = e.user_id            
        
        WHERE 1
            AND e.active = 1
            AND e.entry_id = '{$entry_id}'
        ORDER BY e.date_posted";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }


    function &getEntries($entry_id, $cat, $controller, $limit = 15, $desc_signs = 250) {
        
        $file = AppMsg::getModuleMsgFile('public', 'client_msg.ini');    
        $msg = AppMsg::parseMsgs($file, false, false);
        
        $data = array();
        $entry_data =& $this->getEntryData($entry_id);
        if(!$entry_data) {
            return $data;
        }
                
        $parser = KBCommentView_helper::getBBCodeObj();
        
        $sql = $this->getEntriesSql($entry_id);
        $result = $this->db->SelectLimit($sql, $limit, 0) or die(db_error($sql));
        
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']] = $row;
            
            $name = ($row['comment_name']) ? $row['comment_name'] : $msg['anonymous_user_msg'];
            $data[$row['entry_id']]['title'] = sprintf('%s', $name);
            
            $data[$row['entry_id']]['link'] = $controller->getLink('comment', false, $entry_id) . '#c' . $row['entry_id'];                
            $data[$row['entry_id']]['category'] = $msg['comment_msg'];
            //$data[$row['entry_id']]['description'] = nl2br($parser->qparse($row['description']));
            
            $row['description'] = RequestDataUtil::stripVarsXml($row['description']);
            $data[$row['entry_id']]['content'] = nl2br($parser->qparse($row['description']));
        }
        
        //echo "<pre>"; print_r($data); echo "</pre>";
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;
        
        return $data;
    }


    function getChannelData($entry_id, $categories, $controller, $setting) {
        
        $file = AppMsg::getModuleMsgFile('public', 'client_msg.ini');    
        $msg = AppMsg::parseMsgs($file, false, false);
        //echo '<pre>', print_r($msg, 1), '</pre>';
        
        $entry_data =& $this->getEntryData($entry_id);
        if(!$entry_data) {
            $entry_data['title'] = '';
        }
        
        $data = array();
        
        $title = sprintf('%s (%s: %s)', $setting['rss_title'], $msg['comments_for_msg'], $entry_data['title']);    
        $data['title'] = $title;
        $data['link'] = $controller->getLink('comment', false, $entry_id);
        $data['description'] = $setting['rss_description'];
        
        return $data;
    }
}

?>