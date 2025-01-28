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


class KBClientView_news extends KBClientView_common
{

    // it will overwrite left menu
    function &getLeftMenu($manager) {
        
        $menu_type = $manager->getSetting('view_menu_type');
        
        // old menu
        if(strpos($menu_type, '55') !== false || $menu_type == 'followon') {
            
            $menu = new KBClientMenu_news2($this);
        
            if($menu_type == 'tree_55') {
                return $menu->getTreeMenu($manager);
            
            // not fully implemented we may not need this for such old menu
            } elseif($menu_type == 'top_tree_55') {
                return $menu->getTopTreeMenu($manager);
           
            // not implemented we may not need this for such old menu
            } else {
                return $menu->getTopTreeMenu($manager);
                // return $menu->getFollowMenu($manager);
            }
        
        // ajax menu
        } else {
            
            $menu = new KBClientMenu_news($this);
            return $menu->getLeftMenu($manager, true);
        }
    }
    
    
    function parseCategoryLink($link) {
        return preg_replace("#(news/)(\d{4})#", "$1c$2", $link);
    }    
        
    
    function &getListIndexPage($manager, $block_settings) {
        $limit = $block_settings['num_entries'];
        $title = false;
        
        $rows = $this->stripVars($manager->getNewsList($limit, 0));
        if(!$rows) { 
            $ret = false; 
            return $ret; 
        }
        
        return $this->_getList($manager, $rows, $title);        
    }
    
    
    function &getList(&$manager) {
        
        $limit = $manager->getSetting('num_entries_per_page');
        $bp = $this->pageByPage($limit, $manager->getNewsCount($this->category_id));
        
        $rows = $this->stripVars($manager->getNewsList($bp->limit, $bp->offset, $this->category_id));
        if(!$rows) {
            $msg = $this->getActionMsg('success', 'no_news'); 
            return $msg; 
        }
        
        $title =  $this->msg['news_title_msg'];
        
        return $this->_getList($manager, $rows, $title, $bp);
    }
    
    
    function &_getList(&$manager, $rows, $title, $by_page = false) {
    
        $tpl = new tplTemplatez($this->getTemplate('news_list.html'));            
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
        
            $private = $this->isPrivateEntry($row['private'], false);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);        
            $row['date_formatted'] = $this->getFormatedDate($row['date_posted']);
            
            $summary_limit = $this->getSummaryLimit($manager, $private);
            $row['body'] = DocumentParser::getSummary($row['body'], $summary_limit);
            
            $entry_id_param = $this->controller->getEntryLinkParams($row['id'], $row['title'], false);            
            $row['entry_link'] = $this->getLink('news', false, $entry_id_param);
            
            $tpl->tplParse($row, 'row');
        }
        
        // by page
        if($by_page && $by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
                
        if ($title) {
			$tpl->tplSetNeeded('/title');
			$tpl->tplAssign('list_title', $title);
		}
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);    
    }
    
    
    // rewrited from /client/inc/fixed/KBClientView_common.php
    
    function getTopCategyJsMenu($manager) {
        $menu = new KBClientMenu_news($this);
        return $menu->parseTopCategyJsMenu($manager->getNewsYears(), false);
    }
}
?>