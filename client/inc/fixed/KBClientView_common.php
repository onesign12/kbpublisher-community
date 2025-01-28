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


class KBClientView_common extends KBClientView
{

    var $own_format = 'none';
    var $default_format = 'default';
    var $view_template = array('page_in.html', 'block_menu_top.html');
    

    function parseCustomPageIn(&$tpl, $manager) {
        
        if(isset($_COOKIE['kb_sidebar_width_'])) {
            $width = (int) $_COOKIE['kb_sidebar_width_'];
            $tpl->tplAssign('sidebar_width', sprintf('style="width: %spx;"', $width));
        }

        // we have search field on every page
        $sp = $this->_getSearchFormParams();
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        
        // top category menu
        if($this->category_id && $manager->getSetting('view_menu_type') == 'top_tree') {
            $block = $this->getTopCategyJsMenu($manager);
            $tpl->tplAssign('top_category_menu_block', $block);
        }
    }


    function getTopCategyJsMenu($manager) {
        $menu = new KBClientMenu($this);
        return $menu->parseTopCategyJsMenu($manager->categories, false);
    }


    function getLeftMenu($manager) {
        $menu = new KBClientMenu_entry($this);
        return $menu->getLeftMenu($manager);
    } 

}
?>