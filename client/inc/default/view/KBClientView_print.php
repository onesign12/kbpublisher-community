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


class KBClientView_print extends KBClientView_common
{

    var $page_print = true;
    

    function execute($manager) {
        
        if($this->view_id == 'print-glossary') {
            return $this->getGlossary($manager);
        
        } elseif($this->view_id == 'print-cat') {
            return $this->getFaq($manager);
        
        } elseif($this->view_id == 'print-news') {
            return $this->getNews($manager);
            
        } else {
          
            if (!empty($_GET['id'])) { // from pool
                return $this->getArticleList($manager, $_GET['id']);
                
            } else {
                return $this->getArticle($manager);
            }
        }
    }
    
    
    function getArticle($manager, $row = false) {
        
        if (!$row) {
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
        }
        
        $row = $this->stripVars($row);
        if(empty($row)) { return; }
        
        $this->meta_title = $row['title'];
        $related = $manager->getEntryRelatedInline($this->entry_id);
        
        $tpl = new tplTemplatez($this->template_dir . 'article_print.html');
    
    
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }        
        
        if(DocumentParser::isLink($row['body'])) {
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                        $related, $row['id'], $this->controller);
        }

        if(DocumentParser::isCode($row['body'])) {
            if ($this->view_id == 'send') {
                DocumentParser::parseCode($row['body'], $manager, $this->controller);     
            } else {
                DocumentParser::parseCodePrint($row['body']);
            }  
        }
        
        if(DocumentParser::isCode2($row['body'])) {
            DocumentParser::parseCode2($row['body'], $this->controller);
        }
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows, true);

        $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
        $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
        $tpl->tplAssign('custom_tmpl_bottom_block', $this->parseCustomData($custom_data[3], 3));
        
        
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$row['category_id']];
        $tpl->tplAssign('category_title_full', $full_path);    
        $tpl->tplAssign('category_title', $row['category_name']);
		
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
        $tpl->tplAssign('entry_link', $this->getLink('entry', $this->category_id, $entry_id));        
        
		$tpl->tplAssign('formated_date', $this->getFormatedDate($row['ts_updated']));
        
        if(AppPlugin::isPlugin('history')) {
            $row['revision'] = $manager->getRevisionNum($this->entry_id);
            $tpl->tplSetNeeded('/revision');    
        }
        
        $tpl->tplParse(array_merge($row, $this->msg));
        return $tpl->tplPrint(1);
    }
    
    
    // articles from pool 
    function getArticleList($manager, $ids) {
        
        $ids = array_slice($ids, 0, 50); // set limit
        $ids = array_unique(array_map('intval', $ids));
        
        $manager->setting['private_policy'] = 1; // set Login policy not to display
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));        
        
        $manager->setSqlParams(sprintf("AND e.id IN (%s)", implode(',', $ids)));
        $rows = $manager->getEntryList(-1, -1);
        
        $data = array();
        foreach ($rows as $row) {
            $this->entry_id = $row['id'];
            $data[] = $this->getArticle($manager, $row);
        }
        
        return implode('<div style="page-break-after: always;"></div>', $data);
    }
    
    
    function getGlossary($manager) {
        
        $title = $manager->getSetting('header_title') . ' - ' . $this->msg['menu_glossary_msg'];
        $this->meta_title = $title;
        
        $tpl = new tplTemplatez($this->template_dir . 'glossary_print.html');
        
        if(isset($_GET['let'])) {
            $l = addslashes(urldecode($_GET['let']));
            $manager->setSqlParams("AND phrase LIKE '$l%'");
        }
        
        $rows = $manager->getGlossary(30, $this->entry_id);
        $rows = $this->stripVars($rows, array('definition'));
            
        foreach($rows as $k => $v) {
            DocumentParser::parseCurlyBracesSimple($v['definition']);
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('title', $title);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getFaq($manager) {
    
        $title = $this->stripVars($manager->getCategoryTitle($this->category_id));
        $this->meta_title = $title;
    
        $manager->setSqlParams("AND cat.id = '{$this->category_id}'");
        $manager->setSqlParamsOrder('ORDER BY e.sort_order');
        $rows = $manager->getEntryList(-1, 0, 'category');
        $rows = $this->stripVars($rows);
        
        if(empty($rows)) { return; }
    
        $ids = $manager->getValuesString($rows, 'id');
        $related = $manager->getEntryRelatedInline($ids);
    
        $tpl = new tplTemplatez($this->template_dir . 'article_print_faq.html');   
        
        foreach(array_keys($rows) as $k) {
            
            if(DocumentParser::isLink($rows[$k]['body'])) {
                DocumentParser::parseLink($rows[$k]['body'], array($this, 'getLink'), $manager, 
                                            $related, $rows[$k]['id'], $this->controller);
            }
            
            if(DocumentParser::isTemplate($rows[$k]['body'])) {
                DocumentParser::parseTemplate($rows[$k]['body'], array($manager, 'getTemplate'));
            }            
            
            DocumentParser::parseCurlyBraces($rows[$k]['body']);
            
            $tpl->tplParse($rows[$k], 'row');
        }
        
                
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$this->category_id];
        $tpl->tplAssign('category_title_full', $full_path);
        $tpl->tplAssign('entry_link', $this->getLink('index', $this->category_id, $this->entry_id));
        $tpl->tplAssign('category_title', $title);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getNews($manager) {
        
        $row = $manager->getNewsById($this->entry_id);
        $row = $this->stripVars($row);
        if(empty($row)) { return; }
        
        $this->meta_title = $row['title'];
        
        $tpl = new tplTemplatez($this->template_dir . 'news_entry_print.html');
        
        $row['date_formatted'] = $this->getFormatedDate($row['date_posted']);
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom    
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows);

        $row['custom_tmpl_top'] = $this->parseCustomData($custom_data[1], 1);
        $row['custom_tmpl_bottom'] = $this->parseCustomData($custom_data[2], 2);
        
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title']);
        $tpl->tplAssign('entry_link', $this->getLink('news', false, $entry_id));
        
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }

}
?>