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


class ToolView_default extends AppView
{
    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . 'page.html');
        
        $nav = new AppNavigation;
        
        $nav->setEqualAttrib('GET', 'page');
        $nav->setSubEqualAttrib('GET', 'sub_page', true);
        $nav->setGetParams(sprintf('%s=%s', 'module', 'tool'));

        $menu_msg = AppMsg::getMenuMsgs('tool');
        $nav->setMenuMsg($menu_msg);

        $nav->setMenu($this->controller->module);
        unset($nav->menu_array['default'][0]);
        
        $emodules = AppPlugin::getModules(3);
        foreach($emodules as $k => $v) {
            if(!AppPlugin::isPlugin($k)) {
                if(isset($v['pages'])) {
                    foreach($v['pages'] as $p) {
                        $nav->unsetMenuItem($p);
                    }
                }
            }
        }
        
        $desc_msg = AppMsg::getMenuMsgs('item_description');
        
        foreach ($nav->menu_array['default'] as $item) {
            $v = $item;
            
            $key = array_search($v['menu_item'], $menu_msg);
            $key .= '_desc';
            $v['description'] = $desc_msg[$key];
            
            $tpl->tplAssign($this->getViewListVarsRow()); 
            $tpl->tplParse($v, 'row');
        }

        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
 
}
?>