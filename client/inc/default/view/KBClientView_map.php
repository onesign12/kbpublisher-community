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


class KBClientView_map extends KBClientView_common
{
    
    var $padding = 15; // child's padding
    var $num_td = 3; // columns
    

    function &execute(&$manager) {
        
        $this->addMsg('common_msg.ini');
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_map_msg'];
        $this->nav_title = $this->msg['menu_map_msg'];
        
        $tpl = new tplTemplatez($this->getTemplate('map.html'));
        
        $tpl->tplAssign('list_title', $this->nav_title);
        
        $context = ($manager->getSetting('view_format') == 'fixed') ? '"#content"' : 'window';
        $tpl->tplAssign('context', $context);
        
        if ($manager->getSetting('view_format') == 'fixed') {
            $tpl->tplSetNeeded('/fixed');
        }
        
        $kb_menu = &$this->getTopMenuData($manager);
        
        $kb_menu_types = array();    
        foreach($kb_menu as $k => $v) {
            $kb_menu_types[] = $v['item_key'];
        }        
        
        // filter types
        $types = array(
            'article' => $this->msg['entry_title_msg'],
            'file' => $this->msg['file_title_msg'],
            'news' => $this->msg['news_title_msg'],
        );
        
        foreach(array_keys($types) as $_type) {
            if(!in_array($_type, $kb_menu_types)) {
                unset($types[$_type]);
            }
        }
        
        $type = false;
        if($this->page_id) {
            if(isset($types[$this->page_id])) {
                $type = $this->page_id;
            } else {
                $this->controller->goStatusHeader('404');
            }
        }
        
        
        if(count($types) > 1) {
            $tpl->tplSetNeeded('/type_switch');
            
            foreach ($types as $key => $title) {
                $v = array();
            
                $v['key'] = $key;
                $v['title'] = $title;
                $v['link'] = $this->getLink(array('map', $key), false, false, false);
                $v['class'] = ($type == $key) ? 'selected' : '';
            
                $tpl->tplParse($v, 'type_switch_row');
            }
        }
        // <-- filter types
        
        
        $this->js_hash = array();
        
        foreach (array_keys($kb_menu) as $k) {
            if ($kb_menu[$k]['item_key'] == 'map') {
                unset($kb_menu[$k]);
                continue;   
            }
            
            if ($kb_menu[$k]['item_key'] == 'article') {
                $item_key = $k;
                $article_item_menu = $kb_menu[$k];
            }
            
            $v = $kb_menu[$k];
            $v['id'] = $k;
            
            $tpl->tplParse($v, 'item_link');
        }
        
        // articles
        if (empty($type) || in_array($type, array('all', 'article'))) {
            $this->parseCategoryList($tpl, $manager, 'index', $item_key, $article_item_menu);
            unset($kb_menu[$item_key]);
        }
        
        foreach ($kb_menu as $k => $item) {
            if (!empty($type) && $type != 'all' && $item['item_key'] != $type) {
                continue;
            }
            
            switch ($item['item_key']) {
                case 'news':
                    $this->parseNews($tpl, $manager, $k);
                    break;

                case 'file':
                    $file_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'file');
                    $this->parseCategoryList($tpl, $file_manager, 'files', $k, $item);
                    break;
                
                default:
                    $this->parseItemLink($tpl, $manager, $k, $item);
                    break;
            }
        }
        
        if (!empty($this->js_hash)) {
            $js_hash = implode(",\n", $this->js_hash);         
            $tpl->tplAssign('list', $js_hash);
        }
                
        $tpl->tplAssign('index_page_link', $this->getLink('map'));
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function parseCategoryList(&$tpl, $manager, $view_key, $view_id, $item) {
        
        $title = $item['item_menu'];
        
        $rows = $manager->categories;
        $rows = $this->stripVars($rows);
        
        $tree = $manager->getTreeHelperArray($manager->categories);
        
        $top_ids = array();
        $top_children = array();
        
        $str = '{id: "%s", title: "%s", group: "%s"}';
        
        foreach($tree as $id => $level) {
            if($level == 0) {
                $top_ids[] = $id;
                $top_id = $id;
                
            } else {
                $top_children[$top_id][] = $id;
            }
            
            $this->js_hash[] = sprintf($str, $id, addslashes($manager->categories[$id]['name']), $item['item_key']);
        }
        
        $num = ($n = count($top_ids)) ? $n : 1;
        $rows = array_chunk($top_ids, $this->num_td);

        if($num < $this->num_td) {
            $grid_num = round(12 / $num);
            
        } else {
            $grid_num = round(12 / $this->num_td);
        }
        
        $tpl->tplSetNeeded('item/no_matches');

        foreach($top_ids as $k1 => $top_id) {
            
            $v1 = $manager->categories[$top_id];

            $v1['grid_num'] = $grid_num;
            $v1['link'] = $this->getLink($view_key, $top_id);
            
            $private = $this->isPrivateEntry(false, $v1['private']);
            $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);
            
            $v1['padding_bottom_children'] = '1';
            $v1['border_children'] = '1';
            
            if (!empty($top_children[$top_id])) {
                foreach ($top_children[$top_id] as $child_id) {
                    $v2 = $manager->categories[$child_id];
                    $v2['link'] = $this->getLink($view_key, $child_id);
                    
                    $private = $this->isPrivateEntry(false, $v2['private']);
                    $v2['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'list');
                    
                    $level = $tree[$child_id];
                    $v2['padding'] = ($level - 1) * $this->padding + 10;
                    
                    $tpl->tplParse($v2, 'item/top_category/child_category');
                }
                
                $v1['padding_bottom_children'] = '15';
            }
            
            $tpl->tplSetNested('item/top_category/child_category');
            $tpl->tplParse($v1, 'item/top_category'); // parse nested
        }

        $tpl->tplSetNeeded('item/top_category_toggle');
        
        $v = array();
        $v['id'] = $view_id;
        $v['key'] = $item['item_key'];
        $v['item_title'] = $title;
        $v['item_link'] = $this->getLink($view_key);
        $v['category_padding_bottom'] = 30;
        $v['extra_class'] = 'category_container';
        
        $tpl->tplSetNested('item/top_category');
        $tpl->tplParse($v, 'item');
    }

    
    function parseNews(&$tpl, $manager, $view_id) {
        $news_view = $this->controller->getView('news');
        
        $news_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'news');
        $year_range = $news_manager->getNewsYears();
        
        $v = array();
        $v['border_children'] = '0';
        
        $str = '{id: "%s", title: "%s", group: "news"}';
        
        $tpl->tplSetNeeded('item/no_matches');
        
        foreach($year_range as $year) {
            $v['name'] = $year;
            $v['id'] = $year;
            
            $category_link = $this->getLink('news', $year);
            $v['link'] = $news_view->parseCategoryLink($category_link);
            
            $v['item_img'] = $this->_getItemImg($manager->is_registered, false, true);
            
            $this->js_hash[] = sprintf($str, $year, $year);
            
            $tpl->tplSetNested('item/top_category/child_category');
            $tpl->tplParse($v, 'item/top_category'); // parse nested
        }
        
        $v = array();
        $v['id'] = $view_id;
        $v['key'] = 'news';
        $v['item_title'] = $this->msg['news_title_msg'];
        $v['item_link'] = $this->getLink('news');
        $v['category_padding_bottom'] = 30;
        $v['extra_class'] = 'category_container';
        
        $tpl->tplSetNested('item/top_category');
        $tpl->tplParse($v, 'item');
    }
    
    
    function parseItemLink(&$tpl, $manager, $view_id, $item) {
        $v = array();
        $v['id'] = $view_id;
        $v['key'] = $item['item_key'];
        $v['item_title'] = $item['item_menu'];
        $v['item_link'] = $item['item_link'];
        $v['category_padding_bottom'] = 10;
        
        $tpl->tplSetNested('item/top_category');
        $tpl->tplParse($v, 'item');
    }
}
?>