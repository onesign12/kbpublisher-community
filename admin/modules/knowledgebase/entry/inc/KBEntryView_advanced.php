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


class KBEntryView_advanced extends AppView 
{
    
    var $tmpl = 'form_advanced.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('client_msg.ini', 'public');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // categories
        $cat_records = $manager->getCategoryRecords();
        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax(); 
        
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplSetNeeded('/block_private');
            $tpl->tplAssign('block_private_tmpl', 
            PrivatePlugin::getPrivateEntryBlock($xajax, $obj, $manager, $this, 'knowledgebase', 'kb_entry'));
        }
        
        // status
        $cur_status = ($this->controller->action == 'update') ? $obj->get('active') : false;
        $range = $manager->getListSelectRange('article_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;
        
        $published_status_ids = $manager->getEntryStatusPublished('article_status');
        $tpl->tplAssign('published_status_ids', implode(',', $published_status_ids));
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $select->resetOptionParam();
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
        // schedule
        $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range));
        
        // sort order
        $xajax->registerFunction(array('populateSortSelect', $this, 'ajaxPopulateSortSelect'));
        $xajax->registerFunction(array('getNextCategories', $this, 'ajaxGetNextCategories'));
        
        foreach($obj->getCategory() as $category_id) {
            $cat_title = $categories[$category_id];
            $a['sort_order_select'] = CommonEntryView::populateSortSelect($manager, $obj, $category_id, $cat_title);
            $tpl->tplParse($a, 'sort_order_row');
        }

        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getSortSelectRange($rows, $start_num, $entry_id = false, $show_more_top = false) {
        return CommonEntryView::getSortSelectRange($rows, $start_num, $entry_id, $show_more_top);
    }
    
    
    function getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax = false) {
        return CommonEntryView::getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax);
    }
    
    
    function ajaxGetNextCategories($mode, $val, $category_id) {
        return CommonEntryView::ajaxGetNextCategories($mode, $val, $category_id, $this->manager);  
    }
}
?>