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
    
    
    function &getLeftMenu($manager) {
        
        $menu_type = $manager->getSetting('view_menu_type');
		
		if (!empty($_COOKIE['kb_window_width_']) && $_COOKIE['kb_window_width_'] < 640) {
			$menu_type = 'tree';
		}
        
        // old menu
        if(strpos($menu_type, '55') !== false || $menu_type == 'followon') {
            
            $menu = new KBClientMenu_entry2($this);
            
            // change tree_entry_display for files view
            if(in_array($this->view_id, $this->files_views)) {
                $menu->tree_entry_display = 'entry';
            }

            if($menu_type == 'tree_55') {
                return $menu->getTreeMenu($manager);
        
            } elseif($menu_type == 'top_tree_55') {
                return $menu->getTopTreeMenu($manager);
        
            } else {
                return $menu->getFollowMenu($manager);
            }
        
        // ajax menu
        } else {
        
            $menu = new KBClientMenu_entry($this);
            return $menu->getLeftMenu($manager, true);    
        }
    }

    
    // REWRITED FROM PARENT
    
    // we do not need category select here
    function _getCategorySelect($manager, $top_category_id) {
        return array(array(), false);
    }    
}
?>