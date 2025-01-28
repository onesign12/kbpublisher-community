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

class FileEntryView_bulk extends KBEntryView_bulk
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // tag
        if($manager->bulk_manager->isActionAllowed('tag')) {
            $link = $this->getLink('file', 'file_entry', false, 'tags');
            $this->parseTags($tpl, $xajax, $obj, $manager, $this, $link);
        }
        
        // private
        if($manager->bulk_manager->isActionAllowed('private')) {
            $tpl->tplSetNeeded('/private');
            $tpl->tplAssign('block_private_tmpl', 
                PrivatePlugin::getPrivateBulkBlock($obj, $manager, 'file', 'file_entry'));
            $xajax->registerFunction(array('loadRoles', $this, 'ajaxLoadRoles'));
        }
        
        // parse
        $range = array('filesize', 'filetext');
        if(!$manager->setting['file_extract']) {
            unset($range[1]);
        }
        
        $range_msg = AppMsg::getMsg('bulk_msg.ini', false, 'bulk_file_parse');
        foreach($range as $action) {
            $data['name'] = $action;
            $data['caption'] = $range_msg[$action];
            $tpl->tplParse($data, 'parse_row');
        }        
        
        
        // status
        $status_range = array();
        if($manager->bulk_manager->isActionAllowed('status')) {
            $range = $manager->getListSelectRange('file_status', true);
            $range = $this->priv->getPrivStatusSet($range, 'select');
            $status_range = &$range;
            
            if($range) {
                $select->setRange($range);
                $tpl->tplAssign('status_select', $select->select());                
            } else {
                $manager->bulk_manager->removeActionAllowed('status'); // no range, remove from actions
            }            
        }
        
        // schedule
        if($manager->bulk_manager->isActionAllowed('schedule') && $status_range) {
            $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range, true));
                        
            $items = array('set', 'remove');
            $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_schedule');
            $select->setRange($range);
            $tpl->tplAssign('schedule_action_select', $select->select());
        } else {
            $manager->bulk_manager->removeActionAllowed('schedule');
        }        
        
        // custom
        $filtered_cat = (isset($_GET['filter']['c'])) ? (int) $_GET['filter']['c'] : 0; 
        $tpl->tplAssign('filtered_cat', $filtered_cat);
        
        $xajax->registerFunction(array('parseCutomBulkAction', $this, 'ajaxParseCutomBulkAction'));
        $xajax->registerFunction(array('addTag', $this, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $this, 'ajaxGetTags'));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>