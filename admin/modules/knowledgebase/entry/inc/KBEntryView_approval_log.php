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


class KBEntryView_approval_log extends AppView 
{
    
    var $template = 'list_approval_log.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $view = new KBDraftView_approval_history;
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // tabs
        $prefix = ($this->controller->module == 'knowledgebase') ? 'KB' : 'File';
        $class = $prefix . 'EntryView_common';
        $tpl->tplAssign('menu_block', $class::getEntryMenu($obj, $manager, $this));
        
        $approval_log = $manager->getEntryApprovalLog($obj->get('id'));
        
        foreach ($approval_log as $draft_id => $events) {
            
            $template_dir = APP_PLUGIN_DIR . 'draft/kb_draft/template/';
            $tpl2 = new tplTemplatez($template_dir . 'list_approval.html');

            $tpl2->tplSetNeeded('/title');
            
            $title_field = ($this->controller->module == 'knowledgebase') ? 'title' : 'filename';
            $title_date = (isset($events[0])) ? $this->getFormatedDate($events[0]['date_posted']) : '--';
            $tpl2->tplAssign('title', $title_date);
            
            foreach ($events as $event) {
                
                $event = array_merge($event, $view->getEventVars($event['active'], $event['step_num'], $this->msg));
                $event['date_formatted'] = $this->getFormatedDate($event['date_posted'], 'datetime');
                
                if ($event['step_title']) {
                    $tpl2->tplSetNeeded('row/step_title');
                }
                
                if ($event['comment']) {
                    $tpl2->tplSetNeeded('row/comment');
                }
                
                $tpl2->tplParse($event, 'row');
            }
            
            $tpl2->tplParse();
            $draft_block = $tpl2->tplPrint(1);
            
            $tpl->tplAssign('draft_block', $draft_block);
            
            $row['draft_id'] = $draft_id;
            $tpl->tplParse(array_merge($row, $this->msg), 'draft_row');
        }
        
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>