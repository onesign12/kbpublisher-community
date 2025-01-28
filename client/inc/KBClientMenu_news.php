<?php

class KBClientMenu_news extends KBClientMenu
{

    function &getLeftMenu($manager, $parse_top_cat_menu = false) {

        $current_year = &$this->category_id;
        if($this->entry_id) {
            $current_year = $manager->getYearByEntryId($this->entry_id);
        }

        $year_range = $manager->getNewsYears();
        rsort($year_range);
        $year_range = array_slice($year_range, 0, 100);

        $template_dir = $this->view->getTemplateDir('fixed', 'default');
        $tpl = new tplTemplatez($this->view->getTemplate('sidebar.html', $template_dir));

        $tpl->tplSetNeeded('/menu_title');
        $tpl->tplAssign('menu_title', $this->view->msg['menu_news_msg']);
        $tpl->tplAssign('menu_title_link', $this->view->getLink('news'));

        // top category menu
        $top_tree = ($manager->getSetting('view_menu_type') == 'top_tree');
        if($this->category_id && $top_tree) {
            $tpl->tplSetNeeded('/top_category_menu');

            // when in left menu view
            if($parse_top_cat_menu) {
                $block = $this->parseTopCategyJsMenu($year_range, false);
                $tpl->tplAssign('top_category_menu_block', $block);
            }
        }

        foreach($year_range as $k => $year) {

            if($top_tree) {
                if (!empty($current_year) && ($year != $current_year)) {    
                    continue;
                }
            }

            $category_link = $this->view->getLink('news', $year);
            $category_link = $this->view->parseCategoryLink($category_link);

            $v = array();
            $v['id'] = $year;
            $v['title'] = $year;
            $v['short_title'] = $year;
            $v['link'] = $category_link;
            $v['padding'] = 7;
            $v['item_class'] = (($year == $current_year) && !$this->entry_id) ? 'menu_item_selected' : '';
            $v['block_class'] = ($year == $current_year) ? 'category_loaded' : '';
            $v['icon'] = ($year == $current_year) ? 'menu_category_expanded' : 'menu_category_collapsed';

            $attributes_str = 'style="cursor: pointer;" onclick="toggleCategory(%s, \'%s\');"';
            $attributes = sprintf($attributes_str, $v['id'], $this->kb_path);
            $v['icon_str'] = $this->getIcon($v['icon'], $attributes);

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($v, 'row');

            if ($year == $current_year) {
                $this->parseSubtree($tpl, $manager, $year);
            }
        }

        $ajax = &$this->view->getAjax('menu');
        $ajax->menu = &$this;
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getCategoryChildren', $ajax, 'getCategoryChildren'));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function parseSubtree($tpl, $manager, $root_id) {

        $max_len = $this->entry_menu_max_len;

        $str = array();
        $entries = $this->view->stripVars($manager->getNewsByYear($root_id));

        foreach(array_keys($entries) as $k) {
            $v = $entries[$k];

            $title = $v['title'];
            $short_title = ($max_len) ? $this->view->getSubstringSign($title, $max_len) : $title;
            $entry_id_param = $this->view->controller->getEntryLinkParams($v['id'], $title, false);

            $a = array();
            $a['id'] = $v['id'];
            $a['title'] = $this->stripVarStatic($title);
            $a['short_title'] = $this->stripVarStatic($short_title);
            $a['padding'] = 16;
            $a['link'] = $this->view->getLink('news', false, $entry_id_param);
            $a['item_class'] = ($v['id'] == $this->entry_id) ? 'menu_item_selected' : '';
            $a['base_href'] = $this->kb_path;
            $a['icon_str'] = $this->getIcon('menu_entry');

            $tpl->tplSetNeeded('row/link');
            $tpl->tplParse($a, 'row');
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
}

?>