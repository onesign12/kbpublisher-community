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


class KBClientView_index_book extends KBClientView_index
{    

    var $padding = 25;
    
    
    function getCategoryList($rows, $title, $manager, $is_articles = true) {
        return;
    }
    
    
    function &getEntryList(&$manager) {
        
        $ret = false;
        $rows = &$manager->categories;
        
        if(!$rows) {
            return $ret;
        }
        
        $category_id = ($this->category_id) ? $this->category_id : 0;
        $tree_helper = &$manager->getTreeHelperArray($rows, $category_id);
        $data = $this->_getEntryListCategory($rows, $tree_helper, $manager, $category_id);
    
        return $data;
    }
    
    
    function _getEntryListCategory($rows, $tree_helper, $manager, $category_id) {
        
        $sort = $manager->getSortOrder($category_id);
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        
        // entry_type
        $type = ListValueModel::getListRange('article_type', false);
        
        
        $tpl = new tplTemplatez($this->template_dir . 'article_map.html');
        
        // list option block
        $tpl->tplAssign('block_list_option_tmpl', 
            $this->getBlockListOption($tpl, $manager, array('pdf', 'rss', 'subscribe')));
                
        
        $entries = $this->stripVars($manager->getCategoryEntries($category_id, 0));        
        foreach(array_keys($entries) as $k1) {
            $v1 = $entries[$k1];
                
            $entry_id = $this->controller->getEntryLinkParams($v1['entry_id'], $v1['title'], $v1['url_title']);
            $v1['category_link'] = $this->getLink('entry', $category_id, $entry_id);
            
            // entry prefix
            $v1['entry_prefix'] = $this->getEntryPrefix($v1['entry_id'], $v1['entry_type'], $type, $manager);
            
            $private = $this->isPrivateEntry($v1['private'], $manager->categories[$category_id]['private']);
            $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'list');
            
            $v1['padding'] = 0;
            $v1['padding_category'] = 0;
            $v1['a_class'] = 'articleLinkOther';
            $v1['img_class'] = 'mapTreeArticleImageTop';

            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse($v1, 'category_row');
        }
        
        
        foreach($tree_helper as $cat_id => $level) {
            
            $cid = $this->controller->getEntryLinkParams($rows[$cat_id]['id'], $rows[$cat_id]['name']);
            $v['category_link'] = $this->getLink('index', $cid);

            $v['title'] = $rows[$cat_id]['name'];
            $v['padding'] = $this->padding*$level;
            $v['padding_category'] = 5;
            $v['a_class'] = 'catLink';
            $v['img_class'] = 'mapTreeCategoryImage';

            $private = $this->isPrivateEntry(false, $rows[$cat_id]['private']);
            $v['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);
            
            $entries = $this->stripVars($manager->getCategoryEntries($cat_id, 0));
            foreach(array_keys($entries) as $k1) {
                $v1 = $entries[$k1];
                $entry_id = $this->controller->getEntryLinkParams($v1['entry_id'], $v1['title'], $v1['url_title']);
                $v1['entry_link'] = $this->getLink('entry', $cat_id, $entry_id);
                
                $v1['entry_prefix'] = $this->getEntryPrefix($v1['entry_id'], $v1['entry_type'], $type, $manager);
                
                $private = $this->isPrivateEntry($v1['private'], $manager->categories[$cat_id]['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'list');

                $v1['category_id'] = $cat_id;
                $tpl->tplParse($v1, 'category_row/entry_row');
            }
            
            $tpl->tplSetNested('category_row/entry_row');
            $tpl->tplParse(array_merge($v, $rows[$cat_id]), 'category_row');
        }        
        
        
        if(!$entries && !$tree_helper) {
            $tpl->tplAssign('msg', $this->getActionMsg('success', 'no_category_articles'));
        }
        
        $tpl->tplAssign('list_title', $this->meta_title);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);    
    }
}
?>