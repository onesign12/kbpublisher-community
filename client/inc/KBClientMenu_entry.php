<?php

class KBClientMenu_entry extends KBClientMenu
{

    var $parents = array();
    var $limit = 25;
    var $padding_step = 12;
    
    
    function getLevelStyle($max_level) {
        $str = '.cat_level_%d, .entry_level_%d { padding-left: %dpx; }';
        
        $style = [];
        for ($i=2; $i<=$max_level; $i++) {
            $style[] = sprintf($str, $i, $i, $this->padding_step*$i-$this->padding_step);
        }
    
        return implode("\n", $style);
    }


    function &getLeftMenu($manager, $parse_top_cat_menu = false) {

        $max_len = $this->entry_menu_max_len;

        $template_dir = $this->view->getTemplateDir('fixed', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('sidebar.html', $template_dir));

        $tpl->tplSetNeeded('/menu_title');
        $tpl->tplAssign('menu_title', $this->view->msg['menu_title_msg']);

        $view_id = $this->getViewIdIndex();
        $tpl->tplAssign('menu_title_link', $this->view->getLink($view_id));

        $scroll_id = 0;
        if($manager->getSetting('view_format') == 'fixed') {
            if($this->category_id || $this->entry_id) {
                $scroll_id = ($this->category_id) ? 'aitem_' . $this->category_id : 0;
                $scroll_id = ($this->entry_id) ? 'aitem_entry_' . $this->entry_id : $scroll_id;
            }
        }
        $tpl->tplAssign('scroll_id', $scroll_id);

        // top category menu
        $top_tree = ($manager->getSetting('view_menu_type') == 'top_tree');
        if($this->category_id && $top_tree) {
            $tpl->tplSetNeeded('/top_category_menu');

            // when in left menu view
            if($parse_top_cat_menu) {
                $block = $this->parseTopCategyJsMenu($manager->categories, false);
                $tpl->tplAssign('top_category_menu_block', $block);
            }
        }

        $this->setVars($manager);
        $level_style = ($this->tree) ? max($this->tree)+2 : 0;
        $tpl->tplAssign('level_style', $this->getLevelStyle($level_style));
        // echo '<pre>' .print_r($this->tree, 1) . '</pre>';

        $top_ids = array();
        foreach($this->tree as $id => $level) {
            if($level == 0) {
                $top_ids[] = $id;
            }
        }

        $parent_id = false;
        if($this->category_id && $manager->categories_parent) {
            $this->parents = array_keys($manager->categories_parent);
            $parent_id = $this->parents[0];
        }

        foreach($top_ids as $cat_id) {

            if($top_tree) {
                if (!empty($parent_id) && ($cat_id != $parent_id)) {
                    continue;
                }
            }

            $title = $manager->categories[$cat_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);

            $v = array();
            $v['id'] = $cat_id;
            $v['title'] = $this->stripVarStatic($title);
            $v['short_title'] = $this->stripVarStatic($short_title);
            $v['link'] = $this->view->getLink($this->view_id, $cat_id_params);
            $v['padding'] = 7;
            $v['padding_class'] = 'cat_level_1';
            $v['item_class'] = (($cat_id == $this->category_id) && !$this->entry_id) ? 'menu_item_selected' : '';

            $v['block_class'] = ($cat_id == $parent_id) ? 'category_loaded' : '';
            $v['icon'] = ($cat_id == $parent_id) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $private = $this->view->isPrivateEntry(false, $manager->categories[$cat_id]['private']);
            if ($private && !$manager->is_registered) {
                $v['icon_str'] = $this->getIcon('menu_category_collapsed_private');

            } else {
                $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
                $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
                $v['icon_str'] = $this->getIcon($v['icon'], $attributes);
            }

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            if ($cat_id == $parent_id) { // this top category is expanded
                $collapse = ($this->view_id != 'files');
                $this->parseSubtree($tpl, $manager, $cat_id, $collapse);
            }
        }

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);
        if($this->view->view_id == '404') {
            $xajax->setRequestURI('index');
        }
        $xajax->registerFunction(array('getCategoryChildren', $ajax, 'getCategoryChildren'));
        $xajax->registerFunction(array('loadEntries', $ajax, 'loadEntries'));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function setVars($manager) {
        
        $this->view_id = $this->getViewIdIndex();
        $this->tree = $manager->getTreeHelperArray($manager->categories);

        $this->tree2 = new TreeHelper();
        foreach($manager->categories as $k => $row) {
            $this->tree2->setTreeItem($row['id'], $row['parent_id']);
        }
    }


    function parseSubtree($tpl, $manager, $root_id, $collapse_entries = true) {

        $max_len = $this->entry_menu_max_len;
        $all_children = $this->tree2->getChildsById($root_id);
        $level = $this->tree[$root_id]+2;

        // as in 6.0
        if($this->display_parent_entry || ($this->view_id == 'files')) {

            // category's entries
            $padding = ($this->tree[$root_id] + 2) * 8;
            $this->parseEntries($tpl, $manager, $root_id, $padding, $all_children);
        
        // new in 7.0
        } else {
        
            // category's entries
            if (!$collapse_entries || $root_id == $this->category_id) {
                $padding = ($this->tree[$root_id] + 2) * 8;
                $this->parseEntries($tpl, $manager, $root_id, $padding, $all_children, $collapse_entries);
            
            // parent has entries
            } elseif($manager->getCategoryEntries($root_id, 0, 1)) {
                    
                $v = array();
                $v['id'] = '_more_' . $root_id;
                $v['cat_id'] = $root_id;
                $v['padding'] = ($this->tree[$root_id] + 2) * 8;
                $v['padding_class'] = sprintf('entry_level_%d', $level);
                $v['icon_str'] = $this->getIcon('menu_entry');
                $v['title'] = '...';
        
                // echo '<pre>' . _r($this->tree[$root_id], 1) . '</pre>';
                // echo '<pre>' . _r($this->tree[$root_id]+2, 1) . '</pre>';
                // echo '<pre>' . _r("===============", 1) . '</pre>';
        
                $tpl->tplSetNeeded('row/parent_entries_link');
                $tpl->tplParse($v, 'row');
            }   
        }
        

        $direct_children = array();

        foreach($all_children as $child_id) {
            if($manager->categories[$child_id]['parent_id'] == $root_id) {
                $direct_children[$child_id] = $child_id;
            }
        }

        foreach($direct_children as $child_id) {

            $title = $manager->categories[$child_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $cat_id_params = $this->view->controller->getEntryLinkParams($child_id, $title);

            $v = array();
            $v['id'] = $child_id;
            $v['title'] = $this->stripVarStatic($title);
            $v['short_title'] = $this->stripVarStatic($short_title);
            $v['link'] = $this->view->getLink($this->view_id, $cat_id_params);
            $v['padding'] = ($this->tree[$child_id] + 1) * 8;
            $v['padding_class'] = sprintf('entry_level_%d', $level);
            $v['item_class'] = (($child_id == $this->category_id) && !$this->entry_id) ? 'menu_item_selected' : '';
            $v['base_href'] = $this->kb_path;

            $v['block_class'] = (in_array($child_id, $this->parents)) ? 'category_loaded' : '';
            $v['icon'] = (in_array($child_id, $this->parents)) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $private = $this->view->isPrivateEntry(false, $manager->categories[$child_id]['private']);
            if ($private && !$manager->is_registered) {
                $v['icon_str'] = $this->getIcon('menu_category_collapsed_private');

            } else {
                $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
                $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
                $v['icon_str'] = $this->getIcon($v['icon'], $attributes);
            }

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            // entries
            if (in_array($child_id, $this->parents)) { // go deeper
                $this->parseSubtree($tpl, $manager, $child_id);
            }
        }
    }


    function parseEntries($tpl, $manager, $category_id, $padding, $children, $collapse_entries = true) {

        $max_len = $this->entry_menu_max_len;
        $level = $this->tree[$category_id]+2;

        if($this->view_id == 'index') {
            $sort = $manager->getSortOrder($category_id);
            $manager->setSqlParamsOrder('ORDER BY ' . $sort);

            $entries = $manager->getCategoryEntries($category_id, 0);
			
            $show_all_button_start = false;
            $show_all_button_end = false;
            if ($collapse_entries) {
                $pos = 0;
                
                if ($this->limit != -1 && count($entries) > $this->limit) {
                    foreach(array_keys($entries) as $key) {
                        if ($entries[$key]['id'] == $this->entry_id) {
                            $pos = $key;
                            break;
                        }
                    }
                    
                    $show_all_button = true;
                    $offset = $pos - ceil($this->limit / 2 * 0.2);
                    $offset = ($offset < 0) ? 0 : $offset;
                    
                    if ($offset > 0) {
                        $show_all_button_start = true;
                    }
                    
                    $entries_num = count($entries);
                    $entries = array_slice($entries, $offset, $this->limit, true);
                    
                    if (count($entries) + $offset != $entries_num) {
                        $show_all_button_end = true;
                    }
                }
            }

            if (empty($entries) && !$children) {

                $title = $this->view->msg['empty_category_msg'];
                $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

                $a = array();
                $a['title'] = $this->stripVarStatic($title);
                $a['short_title'] = $this->stripVarStatic($short_title);
                $a['id'] = 'msg';
                $a['padding'] = $padding;
                $a['padding_class'] = sprintf('entry_level_%d', $level);
                $a['base_href'] = $this->kb_path;
                $a['icon_str'] = $this->getIcon('menu_category_empty');

                $tpl->tplSetNeeded('row/message');
                $tpl->tplParse($a, 'row');

            } else {
                $num = count($entries);
                $i = 0;
                
                if ($show_all_button_start) {
                    $tpl->tplSetNeeded('row/show_all_start');
                    
                    $v = array();
                    $v['id'] = 'current_cat_start';
                    $v['category_id'] = $category_id;
                    $v['padding'] = $padding;
                    $v['padding_class'] = sprintf('entry_level_%d', $level);
                    $v['icon_str'] = $this->getIcon('menu_entry');
                    
                    $tpl->tplParse($v, 'row');
                }

                foreach ($entries as $entry) {
                    $i ++;
                    
                    $vars = $this->getEntryVars($entry, $category_id, $manager);
                    $vars['padding'] = $padding;
                    $vars['padding_class'] = sprintf('entry_level_%d', $level);
                    
                    $tpl->tplSetNeeded('row/link');
                    $tpl->tplParse($vars, 'row');
                }
                
                if ($show_all_button_end) {
                    $tpl->tplSetNeeded('row/show_all_end');
                    
                    $v = array();
                    $v['id'] = 'current_cat_end';
                    $v['category_id'] = $category_id;
                    $v['padding'] = $padding;
                    $v['padding_class'] = sprintf('entry_level_%d', $level);
                    $v['icon_str'] = $this->getIcon('menu_entry');
                    
                    $tpl->tplParse($v, 'row');
                }
            }

        } else { // files

            $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), null, true);
            $manager->setSqlParams(sprintf("AND cat.id = '%d'", $category_id));
            $count = $manager->getEntryCount();

            if ($count || !$children) {
                $a = array();
                $a['id'] = 'files_' . $category_id;

                if ($count) {
                    $title = sprintf('%s (%s)', $this->view->msg['file_title_msg'], $count);
                    $title = $this->stripVarStatic($title);
                    $a['link'] = $this->view->getLink($this->view_id, $category_id);
                    $a['style'] = 'font-size: 0.9em;color: #aaaaaa;';

                    $tpl->tplSetNeeded('row/link');

                } else {
                    $title = $this->view->msg['empty_category_msg'];
                    $tpl->tplSetNeeded('row/message');
                }

                $a['title'] = $title;
                $a['short_title'] = $title;
                $a['padding'] = $padding;
                $a['padding_class'] = sprintf('entry_level_%d', $level);
                $a['base_href'] = $this->kb_path;
                $a['icon_str'] = $this->getIcon('menu_entry');

                //$tpl->tplSetNeeded('row/message');
                $tpl->tplParse($a, 'row');
            }
        }
    }


    function getEntryVars($entry, $category_id, $manager) {
        $title = $entry['title'];
        $max_len = $this->entry_menu_max_len;
        $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
        $entry_id_param = $this->view->controller->getEntryLinkParams(
                            $entry['id'], $entry['title'], $entry['url_title']);

        $a = array();
        $a['title'] = $this->stripVarStatic($title);
        $a['short_title'] = $this->stripVarStatic($short_title);
        $a['id'] = 'entry_' . $entry['id'];
        $a['link'] = $this->view->getLink('entry', false, $entry_id_param);
        $a['category_id'] = $category_id;
        $a['category_id_cookie'] = $category_id;
        $a['item_class'] = ($entry['id'] == $this->entry_id && $this->category_id == $category_id) ? 'menu_item_selected' : '';
        $a['base_href'] = $this->kb_path;
        $a['block_class'] = '';

        $private = $this->view->isPrivateEntry($entry['private'], $manager->categories[$category_id]['private']);
        if ($private && !$manager->is_registered) {
            $a['icon_str'] = $this->getIcon('menu_entry_private');
        } else {
            $a['icon_str'] = $this->getIcon('menu_entry');
        }
        
        return $a;
    }
}

?>