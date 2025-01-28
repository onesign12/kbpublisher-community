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


class PageDesignView_list extends AppView
{
    
    var $tmpl = 'list.html';
    var $group_id = 1;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('page_design_msg.ini');
        $this->addMsg('common_msg.ini', 'public_setting');
        
        $this->template_dir = APP_MODULE_DIR . 'setting/page_design/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $rows = $manager->sm->getRecords();
        
        foreach($rows[$this->group_id] as $k => $v) {
            
            // actions/links
            $links = array();
            $links['update_link'] = $this->getActionLink('update', false, array('key' => $k));
            $actions = $this->getListActions($links);
            
            $page_key = substr($k, 12);
            $v['title'] = $this->msg['pages'][$page_key];
            
            $tpl->tplAssign($this->getViewListVarsJs($v['name'], null, true, $actions));
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign($this->msg);
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($links) {
        $actions = array(
            'update' => array(
                'link' => $links['update_link']
            )
        );
        
        return $actions;
    }
    
}
?>