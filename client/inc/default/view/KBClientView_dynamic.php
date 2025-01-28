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


class KBClientView_dynamic extends KBClientView_index
{
    
    var $dynamic_limit = 25;
    var $dynamic_reload_limit = 100;
    var $dynamic_sname = 'kb_dynamic_loaded_%s_';
    // var $load_button = true;
    
    
    function &execute(&$manager) {

        $limit = $this->dynamic_limit;
        $sname = sprintf($this->dynamic_sname, $this->dynamic_type);
        
        if (!empty($_COOKIE[$sname])) {
            $limit = $_COOKIE[$sname];
            if ($limit > $this->dynamic_reload_limit) {
                $limit = $this->dynamic_reload_limit;
            }
        }
        
        list($rows, $title) = $this->getRows($manager, $limit);
        
        if (count($rows) <= $limit) {
            // $this->load_button = false;
            $this->dynamic_limit = false;
            
        } else {
            array_pop($rows);
        }
        
        $this->home_link = true;
        $this->nav_title = $title;
        
        $this->meta_title = $title;
        // $this->meta_keywords = $manager->getSetting('site_keywords');
        // $this->meta_description = $manager->getSetting('site_description');
        
        $data = $this->parseArticleList($manager, $this->stripVars($rows), $title);
                
        return $data;
    }
    
    
    function getRows($manager, $limit, $offset = 0) {
        
        $rows = array();
        
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        switch ($this->dynamic_type) {
        case 'recent':
            $title = $this->msg['recently_posted_entries_title_msg'];
            $this->setRecentlyPostedSqlParams($manager);
            $rows =  $manager->getEntryList($limit + 1, $offset, 'index', 'FORCE INDEX (date_updated)');
            break;
            
        case 'popular':
            $title = $this->msg['most_viewed_entries_title_msg'];
            $this->setMostViewedSqlParams($manager);
            $rows = $manager->getEntryList($limit + 1, $offset, 'index', 'FORCE INDEX (hits)'); 
        	break;
            
        case 'featured':
        	$title = $this->msg['featured_entries_title_msg'];
            
            if ($this->category_id) {
                $rows = $manager->getFeaturedInCategory($limit + 1, $offset, $this->category_id);
            } else {
                $this->setFeaturedSqlParams($manager);
                $rows = $manager->getEntryList($limit + 1, $offset, 'index');
            }
            break;
        }
        
        return array($rows, $title);  
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {
        $a = '';
        return $a;
    }
    
    
    static function parseDinamicBlock(&$tpl, $manager, $view) {
        
        // $tpl->tplSetNeeded('/loader');
        $tpl->tplSetNeeded('/load_button');
        
        //xajax
        $ajax = &$view->getAjax('entry');
        $ajax->view = &$view;

        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('loadNextEntries', $ajax, 'loadNextEntries'));

        $tpl->tplSetNeeded('/dynamic_entries_scroll_loader');
        $tpl->tplAssign('dynamic_limit', $view->dynamic_limit);

        $context = ($manager->getSetting('view_format') == 'fixed') ? '#content' : 'false';
        $tpl->tplAssign('context', $context);

        $sname = sprintf($view->dynamic_sname, $view->dynamic_type);
        if (!empty($_COOKIE[$sname])) {
            $dynamic_offset = $_COOKIE[$sname];
            if ($dynamic_offset > $view->dynamic_reload_limit) {
                $dynamic_offset = $view->dynamic_reload_limit;
            }

        } else {
            $dynamic_offset = $view->dynamic_limit;
        }
        
        $tpl->tplAssign('dynamic_offset', $dynamic_offset);
    }
    
}
?>