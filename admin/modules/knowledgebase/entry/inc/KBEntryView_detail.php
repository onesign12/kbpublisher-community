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


class KBEntryView_detail extends AppView
{

    var $template = 'form_detail.html';

    var $draft_view = false;

    var $module = 'knowledgebase';
    var $page = 'kb_entry';


    function execute(&$obj, &$manager, $draft_data = false) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        $template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';

        $tpl = new tplTemplatez($template_dir . $this->template);

        if ($draft_data) {

            if (isset($draft_data['dkey'])) { // autosave

                $tpl->tplSetNeededGlobal('autosave_view');
                $tpl->tplAssign('date_formatted', $this->getTimeInterval($draft_data['date_saved']));

                // breadcrumb
                $nav = $this->getBreadcrumb('knowledgebase', 'kb_entry', 0, $this->controller->page);
                $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));

                // preview
                $link = $this->getActionLink('preview', false, array('dkey' => $draft_data['id_key']));
                $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
                $tpl->tplAssign('preview_link', $link);

                // links
                $links = $this->getDraftLinks($this->module, $this->page, $this->controller->page, $draft_data['dkey']);
                $tpl->tplAssign($links);

            } else {

                list($draft_obj, $draft_manager) = $draft_data;
                $tpl->tplAssign('menu_block', KBDraftView_common::getEntryMenu($draft_obj, $draft_manager, $this, $manager));


                $obj->set('id', $draft_obj->get('id'));
                CommonEntryView::parseInfoBlock($tpl, $obj, $this);

                // preview
                $link = $this->getActionLink('preview', $draft_obj->get('id'));
                $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
                $tpl->tplAssign('preview_link', $link);

                if ($draft_obj->get('entry_id')) {
                    $tpl->tplSetNeeded('/entry_id2');
                    $tpl->tplAssign('id2', $draft_obj->get('entry_id'));
                }
            }

        } else {

            // tabs
            $tpl->tplAssign('menu_block', KBEntryView_common::getEntryMenu($obj, $manager, $this));

            $tpl->tplSetNeededGlobal('entry_view');
            CommonEntryView::parseInfoBlock($tpl, $obj, $this);

            // preview
            $link = $this->getActionLink('preview', $obj->get('id'));
            $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $tpl->tplAssign('preview_link', $link);
            
            // public
            $link = $this->controller->getPublicLink('entry', $obj->get());
            $tpl->tplAssign('public_link', $link);
        
            $cats = $manager->getCategoryByIds($obj->get('id'))[$obj->get('id')];
            $publish_status_ids = $manager->getEntryStatusPublished('article_status');
            $published = CommonEntryView::isEntryPublished($obj->get(), $cats, $publish_status_ids);
            if($published) {
                $tpl->tplSetNeeded('/public_link');
            }


            $date = $manager->getLastViewed($obj->get('id'));
            $tpl->tplAssign('last_viewed_formatted', $this->getFormatedDate($date, 'datetime'));

            // statistics
            $str = '%s &nbsp;|&nbsp; <a href="%s">%s</a>';

            // comments
            $comment_num = $manager->getCommentsNum($obj->get('id'));
            $comment_num = (isset($comment_num[$obj->get('id')])) ? $comment_num[$obj->get('id')] : 0;

            if ($comment_num) {
                $link = $this->getActionLink('kb_comment', $obj->get('id'));
                $comment_num = sprintf($str, $comment_num, $link, $this->msg['view_msg']);
            }

            $tpl->tplAssign('comment_num', $comment_num);


            // rating comments
            $comment_num = $manager->getRatingCommentsNum($obj->get('id'));
            $comment_num = (isset($comment_num[$obj->get('id')])) ? $comment_num[$obj->get('id')] : 0;

            if ($comment_num) {
                $link = $link = $this->getActionLink('kb_rate', $obj->get('id'));
                $comment_num = sprintf($str, $comment_num, $link, $this->msg['view_msg']);
            }

            $tpl->tplAssign('rating_comment_num', $comment_num);

            // rating
            $rating = $manager->getRating($obj->get('id'));
            $tpl->tplAssign('rating_img', $this->getRatingImg($rating['rating'], $rating['votes']));
            $tpl->tplAssign('votes', ($rating['votes']) ? $rating['votes'] : 0);

            // related to
            $related_to_num = '';
            $related_to = $manager->getEntryToRelated($obj->get('id'));
            if(!empty($related_to)) {
                $related_to_num = count($related_to);
                $more = array('filter[q]'=>'related:' . $obj->get('id'));
                $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                $tpl->tplAssign('related_to_link', $link);
            }

            $tpl->tplAssign('related_to_num', $related_to_num);

            // draft
            $draft_id = $manager->isEntryDrafted($obj->get('id'));
            if ($draft_id) {
                $tpl->tplSetNeeded('/draft');

                $more = array('id' => $draft_id);
                $link = $this->getLink('knowledgebase', 'kb_draft', false, 'detail', $more);
                $tpl->tplAssign('draft_link', $link);
            }
         }


        // type
        $type_range = $manager->getListSelectRange('article_type', true, false);
        $type = $obj->get('entry_type');
        $tpl->tplAssign('type', ($type) ? $type_range[$type] : '');


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
        $status_range = $manager->getListSelectRange('article_status', true, $status);

        if (!$this->draft_view) {
            $tpl->tplSetNeeded('/status');
            $tpl->tplAssign('status', $status_range[$status]);
        }

        // related
        foreach($obj->getRelated() as $id => $data) {
            $more = array('id' => $id);
            $data['related_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
            $data['title'] = $this->getSubstring($data['title'], 100);
            $tpl->tplParse($data, 'related_row');
        }


        // attachment
        foreach($obj->getAttachment() as $id => $filename) {
            $more = array('id' => $id);
            $data['attachment_link'] = $this->getLink('file', 'file_entry', false, 'update', $more);
            $data['filename'] = $filename;
            $tpl->tplParse($data, 'attachment_row');
        }

        // private
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplsetNeeded('/block_private');
            $this->parsePrivateBlock($tpl, $obj, $manager);
        }
        
        // schedule
        $this->parseScheduleBlock($tpl, $obj, $status_range);
        
        // mustread
        if(AppPlugin::isPlugin('mustread')) {
            $tpl->tplsetNeeded('/block_mustread');
            if ($mustread = $obj->getMustread()) {
                $mustread = CommonEntryView::getMustreadToList($mustread, $this, $manager, false);
                $tpl->tplAssign('mustread', $mustread['mustread_title']);
            }
        }
        

        $vars = $this->setCommonFormVars($obj);

        $client_link = array('entry', false, $obj->get('id'));
        if($vars2 = $this->setRefererFormVars(@$_GET['referer'], $client_link)) {
            $vars['update_link'] = $vars2['cancel_link'];
        }

        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getRatingImg($rate, $votes) {
        $str = '<img src="images/rating/rate_star/rate_%s.gif" alt="" />';
        $rate = round($rate);
        return ($votes) ? sprintf($str, $rate) : '';
    }


    function getDraftLinks($module, $page, $draft_page, $dkey) {

        $more = array('dkey' => $dkey);

        $link = array();
        $link['draft_update_link'] = $this->getLink($module, $page, false, 'insert', $more);
        $link['draft_cancel_link'] = $this->getLink($module, $draft_page, false, false);
        $link['draft_delete_link'] = $this->getLink($module, $draft_page, false, 'delete', $more);

        return $link;
    }


    function getBreadcrumb($module, $page, $entry_id, $draft_view = false) {

        $menu_msg = AppMsg::getMenuMsgs($module);
        $nav = array();

        $link = $this->getLink($module, $page);
        $nav[1] = array('link' => $link, 'item' => $menu_msg[$page]);

        if($draft_view) {
            $link = $this->getLink($module, $draft_view);
            $nav[2] = array('link' => $link, 'item' => $this->msg['autosaved_draft_msg']);
            $nav[3]['item'] =  $this->msg['detail_msg'];

        } else {
            $nav[2]['item'] = sprintf('[%d] %s', $entry_id, $this->msg['detail_msg']);
        }

        return $nav;
    }


    function parseScheduleBlock(&$tpl, $obj, $status_range) {
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl2 = new tplTemplatez($template_dir . 'block_schedule_detail.html'); 
        
        foreach ($obj->getSchedule() as $v) {
            $schedule = $this->getScheduleData($v, $status_range);
            $tpl2->tplParse(array_merge($schedule, $this->msg), 'schedule');
        }
        
        $tpl->tplAssign('schedule', $tpl2->tplPrint(1));
    }


    function getScheduleData($schedule, $status_range) {
        $sh_status = (isset($schedule['st'])) ? $schedule['st'] : 0;
        $note = (isset($schedule['note'])) ? $schedule['note'] : '';
        $timestamp = (isset($schedule['date'])) ? $schedule['date'] : time();

        $a = array();
        $a['schedule_note'] = $note;
        $a['schedule_status'] = $status_range[$sh_status];
        $a['schedule_date'] = $this->getFormatedDate($timestamp, 'datetime');

        return $a;
    }
    
    
    function parseCustomBlock(&$tpl, $obj, $manager, $cat_records = [], $categories = []) {        
        $custom_rows = $manager->cf_manager->getCustomField($cat_records, $categories);
        $custom_data = CommonCustomFieldView::getCustomData($obj->getCustom(), $manager->cf_manager, 'checkbox', '');
        foreach($custom_rows as $k => $v) {
            if (isset($custom_data[$k])) {
                $tpl->tplParse($custom_data[$k], 'custom_row');
            } else {
                $tpl->tplParse($v, 'custom_row');
            }
        }
    }
    
    
    function parsePrivateBlock(&$tpl, $obj, $manager) {
        
        if ($obj->get('private')) {
            $roles_range = $manager->role_manager->getSelectRangeFolow();

            $roles = array(
                'read' => array($obj->get('id') => $obj->getRoleRead()),
                'write' => array($obj->get('id') => $obj->getRoleWrite())
            );
            $roles = CommonEntryView::parseEntryRolesMsg($roles, $roles_range, $this->msg);

            
            $row = $obj->get();
            if (@$obj->get('category_id')) {
                $category_roles = $manager->cat_manager->getRoleById($obj->get('category_id'), 'id_list');
                $category_roles = CommonEntryView::parseEntryCategoryRolesMsg($category_roles, $roles_range, $this->msg);
            
                $tpl->tplAssign('roles', CommonEntryView::getEntryPrivateMsg(@$roles[$obj->get('id')], @$category_roles[$obj->get('category_id')], $this->msg));
                $row['category_private'] = $manager->cat_manager->isPrivate($obj->get('category_id'));
            
            } else {
                
                $tpl->tplAssign('roles', CommonEntryView::getEntryPrivateMsg(@$roles[$obj->get('id')], array(), $this->msg));
                $row['category_private'] = false;
            }

            $tpl->tplAssign(CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg));
        }
        
    }
}
?>