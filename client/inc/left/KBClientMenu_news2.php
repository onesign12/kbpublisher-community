<?php

class KBClientMenu_news2  extends KBClientMenu2
{

    function &getTreeMenu($manager) {

        $view_id = 'news';

        $year_range = $manager->getNewsYears();
        rsort($year_range);
        $year_range = array_slice($year_range, 0, 100);


        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('tree_menu.html', $template_dir));


        $str[] = "var img_path  = '".$this->view->controller->client_path . "jscript/dtree/';";
        $str[] = "var icon_path = '".$this->view->controller->client_path . "images/icons/';";
        $str[] = "var basehref = '".$this->view->controller->kb_path."';";

        $str[] = "d = new dTree('d');";
        $str[] = "d.config.useCookies = false;";
        //$str[] = "d.config.closeSameLevel = true;";
        $str[] = "d.config.inOrder = true;"; //If parent nodes are always added before children, setting this to true speeds up the tree


        // set kb_path to '' for generation shorter js, restore it later
        $link_path = $this->view->controller->link_path;
        $this->view->controller->link_path = '';

        $str1 = "d.add(0,-1,' <b>%s</b>', basehref+'%s');";
        $str[] = sprintf($str1, addslashes($this->view->msg['menu_news_msg']), $this->view->getLink($view_id));

        foreach($year_range as $k => $year) {

            $entry_link = $this->view->getLink($view_id, $year);
            $title = $year;
            $category_link = $this->view->getLink('news', $year);
            $category_link = $this->view->parseCategoryLink($category_link);

            // return just image name
            $item_img = $this->view->_getItemImg($manager->is_registered, false, true, false, true, '');

            $str1 = "d.add(%d, %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $year, 0, $title, $category_link, $title, $item_img, $item_img);

            if($this->entry_id) {
                $year2 = $manager->getYearByEntryId($this->entry_id);
                if($year2 == $year) {
                    $str[] = $this->_getTreeEntriesItemsNews($manager, $year);
                }
            }
        }

        $str[] = "document.write(d);";
        $str = implode("\n", $str);

        //if($view->category_id) {
        //    $str .= "d.openTo($view->category_id, false);";
        //}

        if(!$this->entry_id && $this->category_id) {
            $search = "#(d\.add\($this->category_id, 0), '(.*?)',.*?#";
            $str = preg_replace($search, "$1, '<span class=\"treeNodeSelected\">$2</span>', ", $str, 1);

        } elseif($this->entry_id) {
            if(!isset($year2)) {
                $year2 = $manager->getYearByEntryId($this->entry_id);
            }
            $str .= "d.openTo({$year2}, false);";

            $search = "#(d\.add\('e{$this->entry_id}', $year2), '(.*?)',.*?#";
            $str = preg_replace($search, "$1, '<span class=\"treeNodeSelected\">$2</span>', ", $str, 1);
        }

        // assign back kb_path
        $this->view->controller->link_path = $link_path;

        $tpl->tplAssign('js_tree', $str);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function &getTopTreeMenu(&$manager) {

        $max_len = $this->entry_menu_max_len;
        $view_id = $this->getViewIdIndex();
        
        $year_range = $manager->getNewsYears();
        rsort($year_range);
        $year_range = array_slice($year_range, 0, 100);

        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('tree_top_menu.html', $template_dir));

        $tpl->tplAssign('menu_title_top', $this->stripVarStatic($this->view->msg['news_title_msg']));
        $tpl->tplAssign('menu_link_top', $this->view->getLink($view_id));

        // top category menu
        if($this->category_id) {
            $block = $this->parseTopCategyJsMenu($year_range);
            $tpl->tplAssign('top_category_menu_block', $block);
        }

        if($this->category_id) {
            $tpl->tplAssign('js_tree', $this->getTreeMenu($manager));

        } else {

            foreach($year_range as $k => $year) {

                $title = $year;
                $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
                $category_link = $this->view->getLink($view_id, $year);
                $category_link = $this->view->parseCategoryLink($category_link);

                // stripVarStatic enough here, dispalying category listing, no js
                $v['title'] = $this->stripVarStatic($title);
                $v['short_title'] = $this->stripVarStatic($short_title);
                $v['menu_link'] = $category_link;

                $v['item_img'] = $this->view->_getItemImg($manager->is_registered, false, true);

                $tpl->tplParse($v, 'row');
            }

            $tpl->tplSetNeeded('/top_category');
        }

        $this->callLeftMenuAjax($manager, 'tree_top');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _getTreeEntriesItemsNews($manager, $year) {

        $max_len = $this->entry_menu_max_len;

        $str = array();
        $entries = $this->view->stripVars($manager->getNewsByYear($year));

        foreach(array_keys($entries) as $k) {
            $v = $entries[$k];
            $entry_id = $entries[$k]['id'];

            $entry_id_param = $this->view->controller->getEntryLinkParams($entry_id, $v['title'], false);
            $entry_link = $this->view->getLink('news', false, $entry_id_param);

            $title = $v['title'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $title = addslashes($title);
            $short_title = addslashes($short_title);

            $private = $this->view->isPrivateEntry($v['private'], false);
            $item_img = $this->view->_getItemImg($manager->is_registered, $private, 'news', false, true, '');

            $str1 = "d.add('e%d', %d, '%s', basehref+'%s', '%s', '', icon_path+'%s', icon_path+'%s');";
            $str[] = sprintf($str1, $entry_id, $year, $short_title, $entry_link, $title, $item_img, $item_img);
        }

        return implode("\n", $str);
    }
    
    
    function parseTopCategyJsMenu($year_range, $parse_dropdown = true) {

        $tpl = new tplTemplatez($this->view->getTemplate('block_top_cat_menu.html'));

        if($parse_dropdown) {
            $tpl->tplSetNeeded('/dropdown');
        }

        foreach($year_range as $k => $year) {
            $v = array();
            $v['name'] = $year;
            $v['link'] = $this->view->getLink('news', $year);
            $v['link'] = $this->view->parseCategoryLink($v['link']);

            @$i++;
            if($i > 15) {
                $v['name'] = '...';
                $v['link'] = $this->view->getLink('index');
                $tpl->tplParse($v, 'row');
                break;
            }

            $tpl->tplParse($v, 'row');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    // not implemented !!! old menu we may not need it
    /*
    function &getFollowMenu(&$manager) {

        $max_len = $this->entry_menu_max_len;
        $limit = $this->tree_menu_limit;
        $rows = $manager->getNewsYears();
        rsort($year_range);
        
        
        // $year_range = array_slice($year_range, 0, 100);
        
        $view_id = 'news';
        // $view_id_enttry = $this->getViewIdEntry();

        $parents = array();
        $childs = array();

        $template_dir = $this->view->getTemplateDir('left', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('followup_menu.html', $template_dir));

        // set parent id
        $parent_id = $this->view->top_parent_id;
        if($this->category_id) {
            $parent_id = $manager->categories[$this->category_id]['parent_id']; // 1 behaviour
        }

        // top category menu
        if($this->category_id) {
            $block = $this->parseTopCategyJsMenu($manager->categories);
            $tpl->tplAssign('top_category_menu_block', $block);
        }

        foreach(array_keys($manager->categories) as $cat_id) {
            if($manager->categories[$cat_id]['parent_id'] == $this->category_id) {
                $childs[$cat_id] = $cat_id;
            }
        }

        if($this->category_id) {
            $parents = array_keys($manager->categories_parent);
            if(!$parents) {
                $parents = TreeHelperUtil::getParentsById($rows, $this->category_id);
            }
        }

        // echo '<pre>parents: ', print_r($parents, 1), '</pre>';
        // echo '<pre>childs: ', print_r($childs, 1), '</pre>';
        // echo '<pre>levels: ', print_r($levels, 1), '</pre>';
        // echo '<pre>categories: ', print_r($rows, 1), '</pre>';

        foreach(array_merge($parents, $childs) as $cat_id) {

            $a['entry_class'] = ($cat_id == $this->category_id) ? 'followMenuSelected' : 'followMenu';

            if(in_array($cat_id, $parents)) {
                $a['entry_class'] = 'followMenuUp';
            }

            if(in_array($cat_id, $childs)) {
                $icon = true;
            } else {
                $icon = ($this->category_id && $this->category_id != $cat_id) ? 'up' : true;
            }

            $private = $this->view->isPrivateEntry(false, $rows[$cat_id]['private']);
            $a['item_img'] = $this->view->_getItemImg($manager->is_registered, $private, $icon);

            $title = $rows[$cat_id]['name'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;

            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $title);
            $a['entry_link'] = $this->view->getLink($view_id, $cat_id_params);

            $a['title'] = $this->stripVarStatic($title);
            $a['short_title'] = $this->stripVarStatic($short_title);

            $tpl->tplParse($a, 'category_row');
        }


        $tpl->tplAssign('menu_title', $this->view->msg['menu_title_msg']);
        $tpl->tplAssign('menu_title_link', $this->view->getLink($view_id));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    */
}

?>