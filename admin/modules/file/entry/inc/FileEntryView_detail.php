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


class FileEntryView_detail extends KBEntryView_detail
{
    
    var $template = 'form_detail.html';
    
    var $draft_view = false;
    
    var $module = 'file';
    var $page = 'file_entry';
    
    
    function execute(&$obj, &$manager, $draft_data = false) {

        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');        
        
        $template_dir = APP_MODULE_DIR . 'file/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        // tabs
        $prefix = ($this->controller->page == 'file_entry') ? 'Entry' : 'Draft';
        $class = sprintf('File%sView_common', $prefix);
        
        if ($draft_data) {
            list($draft_obj, $draft_manager) = $draft_data;
            $tpl->tplAssign('menu_block', FileDraftView_common::getEntryMenu($draft_obj, $draft_manager, $this, $manager));
            
            if ($draft_obj->get('entry_id')) {
                $tpl->tplSetNeeded('/entry_id2');
                $tpl->tplAssign('id2', $draft_obj->get('entry_id'));
            }
            
            $file_id_param = $draft_obj->get('id');
            
        } else {
            
            $tpl->tplSetNeededGlobal('entry_view');
            
            // tabs
            $tpl->tplAssign('menu_block', FileEntryView_common::getEntryMenu($obj, $manager, $this));
            
            // attached to
            $related_to_num = '';
            $related_to = $manager->getReferencedArticlesNum($obj->get('id'));
            if(!empty($related_to)) {
                $related_to_num = count($related_to);
                $more = array('filter[q]'=>'attachment:' . $obj->get('id'));
                $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                $tpl->tplAssign('attached_to_link', $link);
            }
            
            $tpl->tplAssign('attached_to_num', $related_to_num);
            
            // draft
            $draft_id = $manager->isEntryDrafted($obj->get('id'));
            if ($draft_id) {
                $tpl->tplSetNeeded('/draft');
                
                $more = array('id' => $draft_id);
                $link = $this->getLink('file', 'file_draft', false, 'detail', $more);
                $tpl->tplAssign('draft_link', $link);
            }
            
            $file_id_param = $obj->get('id');
        }
        
        CommonEntryView::parseInfoBlock($tpl, $obj, $this);
        
        
        $link = $this->getActionLink('file', $file_id_param);
        $tpl->tplAssign('download_link', $link);
        
        $link = $this->getActionLink('fopen', $file_id_param);
        $tpl->tplAssign('open_link', $link);
        
        $link = $this->controller->getPublicLink('file', $obj->get());
        $tpl->tplAssign('public_download_link', $link);
        
        $link = $this->controller->getPublicLink('file', $obj->get(), ['f'=>1]);
        $tpl->tplAssign('public_open_link', $link);
        
        
        $tpl->tplAssign('file_path', FileEntryUtil::getFilePath($obj->get(), false, true));
        $tpl->tplAssign('file_addtype', $manager->getAddTypeSelectRange()[$obj->get('addtype')]);
        
        $tpl->tplAssign('filesize_str', WebUtil::getFileSize($obj->get('filesize')));
        
        if ($obj->get('id')) {
            $date = $manager->getLastViewed($obj->get('id'));
            $tpl->tplAssign('last_viewed_formatted', $this->getFormatedDate($date, 'datetime'));
        }
                                       
        // categories
        $cat_records = $this->stripVars($manager->getCategoryRecords());
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        
        $category = array();
        foreach($obj->getCategory() as $category_id) {
            $category[] = $categories[$category_id];
        }
        $tpl->tplAssign('category', implode('<br>', $category));

        
        // tags
        $tpl->tplAssign('tags', implode(', ', $obj->getTag()));
        
        // custom
        if(AppPlugin::isPlugin('fields')) {
            $this->parseCustomBlock($tpl, $obj, $manager, $cat_records, $obj->getCategory());
        }
                                                                      
        // status
        $status = $obj->get('active'); 
        $status_range = $manager->getListSelectRange('file_status', true, $status);
        
        if (!$this->draft_view) {
            $tpl->tplSetNeeded('/status');
            $tpl->tplAssign('status', $status_range[$status]);
        }
        
        // private
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplsetNeeded('/block_private');
            $this->parsePrivateBlock($tpl, $obj, $manager);
        }

        // schedule
        $this->parseScheduleBlock($tpl, $obj, $status_range);
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        if ($this->draft_view) {
            $tpl->tplAssign('id', $draft_obj->get('id'));
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>