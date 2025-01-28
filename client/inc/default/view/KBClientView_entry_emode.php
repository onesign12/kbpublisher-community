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


class KBClientView_entry_emode extends KBClientView_entry
{
    
    var $entry_locked = false;
    var $entry_autosaved = false;
    

    function &execute(&$manager) {

        // does not matter why no article, deleted, or inactive or private
        if(!$this->eobj->get('id')) {
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isEntryExistsAndActive($this->entry_id, $this->category_id)) {
                    $this->controller->goAccessDenied('index');
                }
            }
            
            $this->controller->goStatusHeader('404');
        }
        
        // entry_type
        $type = ListValueModel::getListRange('article_type', false);
        
        $title = $this->eobj->get('title');
        $this->meta_title = $this->getSubstring($title, 150);
        $this->meta_keywords = $this->eobj->get('meta_keywords');
        $this->meta_description = $this->eobj->get('meta_description');
        
        
        $this->nav_title = false;
        if($manager->getSetting('show_title_nav')) {
            $prefix = $this->getEntryPrefix($this->eobj->get('id'), $this->eobj->get('entry_type'), $type, $manager);
            $ntitle = $prefix . $this->getSubstring($title, 70, '...');
            $link = $this->getLink('entry', false, $this->entry_id);
            $this->nav_title = array(
                $link => $ntitle, 
                sprintf('[%s]', $this->msg['update_entry_quick_msg'])
                );
        }
        
        if ($this->entry_locked || $this->entry_autosaved) { // blocked
            $more = array('id' => $this->entry_id, 'referer' => 'emode', 'popup' => 1);
            
            $action = ($this->entry_locked) ? 'lock' : 'autosave';
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, $action, $more, false);
            
            $str = '<iframe class="client_frame" style="height: 270px;" scrolling="no" src="%s"></iframe>';
            $data = sprintf($str, $link);
            
            return $data;
        }
        
        $data = array();
        $data[] = $this->getEntryForUpdate($manager);
        
        $data[] = $this->getEntryCommentsNum($manager);
        $data['prev_next'] = $this->getEntryPrevNext($manager, $type);
        
        $data[] = $this->getEntryListCategory($manager, $type);
        $data = implode('', $data);

        return $data;
    }
    
    
    function getEntryForUpdate(&$manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('article_bb_emode.html'));
        
        $tpl->tplSetNeededGlobal('update');
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        
        $body = $this->eobj->get('body');
        $body_raw = $this->eobj->get('body');
        
        $related = $manager->getEntryRelated($this->entry_id, true);
        // echo '<pre>', print_r($related, 1), '</pre>';
        
        $this->parseBody($manager, $body, $related['inline']);
        $this->eobj->set('body', $body);
        //$tpl->tplSetNeededGlobal('update');
        
        $hidden = array('id', 'body', 'title', 'author_id', 'date_posted', 'hits');
        $arr = array();
        foreach($hidden as $v) {
            $arr[$v] = $this->eobj->get($v);
            if ($v == 'body') {
                $arr['body'] = htmlentities($this->eobj->get('body'));
            }
        }
        
        $arr += self::getCsrfTokenArray(true);
        $tpl->tplAssign('hidden_fields', http_build_hidden($arr));
        
        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $url = $this->controller->getAjaxLink('all');
        $xajax->setRequestURI($url);
        
        $xajax->registerFunction(array('updateEntry', $ajax, 'updateEntry'));
        $xajax->registerFunction(array('validateEntry', $ajax, 'validateEntry'));
        $xajax->registerFunction(array('cancelHandler', $ajax, 'cancelHandler'));
        
        // custom
        $custom_rows = $manager->getCustomDataByEntryId($this->eobj->get('id'));
        $custom_data = $this->getCustomData($custom_rows);
        
        $categories = $this->emanager->getCategoryRecords();
        $custom_fields = $this->emanager->cf_manager->getCustomField($categories, $this->eobj->getCategory());
        
        if (!empty($custom_data)) {
            $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
            $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
        }
        
        $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate(strtotime($this->eobj->get('date_updated'))));
        $tpl->tplAssign('updated_date', $this->getFormatedDate(strtotime($this->eobj->get('date_updated'))));
        
        if($manager->getSetting('show_entry_block_top')) {
            $tpl->tplSetNeeded('/entry_block_top');
        }
        
        $manager->setting['article_block_position'] = 'bottom';
        $manager->setting['entry_published'] = 1;
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);        
        
        $tpl->tplAssign('article_block', $this->getEntryBlock($row, $manager));
		$tpl->tplAssign('rating_block', $this->getRatingBlock($manager, $row));
		
        $this->parseActionMenu($tpl); // populated in getEntryBlock
        
		// edit code
        $tpl->tplAssign('body_raw', $body_raw);
        
        $admin_path = $this->controller->getAdminJsLink();
        $tpl->tplAssign('config_path', $admin_path . 'tools/ckeditor_custom/ckconfig_article.js');
        
        $setting = SettingModel::getQuick(1);
        if ($setting['entry_autosave']) {
            $xajax->registerFunction(array('autoSave', $ajax, 'autoSave'));
            $xajax->registerFunction(array('deleteAutoSave', $ajax, 'deleteAutoSave'));
            
            // draft key
            if(!empty($_POST['id_key'])) {
                $id_key = htmlentities($_POST['id_key'], ENT_QUOTES);
            } else {
                $id_key = array($manager->user_id, 1, null, 'insert'); 
                $id_key = md5(serialize($id_key));
            }
            
            $tpl->tplAssign('autosave_key', $id_key);
            
            $autosave_period = 60000 * $setting['entry_autosave']; 
            $tpl->tplAssign('autosave_period', $autosave_period);
            
            $tpl->tplSetNeeded('/auto_save');
        } 
        
        $more = array('emode' => 1, 'popup' => 1);
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'insert', $more);
        $tpl->tplAssign('more_options_link', $link);
        
        $tpl->tplAssign('cancel_link', $this->getLink('entry', $this->category_id, $this->entry_id));
        
        $tpl->tplAssign('save_button_class', 'button buttonDisabled');
        $tpl->tplAssign('save_button_attr', 'disabled="true"');
        $tpl->tplAssign('save_button_text', $this->msg['save_msg']);
        
        
		$items = array(
            'save' => array(
                'text' => $this->msg['save_msg'],
                'action' => 'submitFormAjax()',
                'name' => 'save_button'
            ),
                
            'save_skip' => array(
                'text' => $this->msg['save_skip_msg'],
                'action' => "submitFormAjax()",
                'name' => 'submit_skip'
            )
		);
    
        $options = array(
            // 'button' => true, 
            'class' => 'primary',
            // 'class' => 'button buttonDisabled', 
            // 'attribute' => 'disabled="true"'
        );
        $tpl->tplAssign('split_button', AppView::getSplitButton($items, $options));
        
        
        $entry_blocks = array();
        $entry_blocks[] = $this->getEntryListCustomField($custom_data[3]);
        $entry_blocks[] = $this->getEditableTagBlock($manager);
        $entry_blocks[] = $this->getEditableAttachmentBlock($manager);
        $entry_blocks[] = $this->getEditableRelatedBlock($manager, $related['attached']);
        $entry_blocks[] = $this->getEditableCategoryBlock($manager);
        $entry_blocks[] = $this->getEditableCustomFieldBlock($custom_rows, $custom_fields);
        $entry_blocks[] = $this->getEditableAdvancedBlock($manager, $row);
        
        $entry_blocks = implode('', $entry_blocks);
        $tpl->tplAssign('entry_blocks', $entry_blocks);
        
        $tpl->tplAssign('sure_leave_msg', RequestDataUtil::stripVars($this->msg['sure_leave_msg']));
        
        $tpl->tplParse($this->eobj->get());
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListCustomField($rows, $return_blocks = false) {
    
        if(!AppPlugin::isPlugin('fields')) {
            $rows = [];
        }
    
        if(!$rows) {
            return '<div id="custom_block"></div>';
        }

        $rows = DocumentParser::parseCurlyBracesSimple($rows);

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_custom.html'));
        $tpl->tplAssign('block_attr', 'id="custom_block"');
            
        foreach($rows as $id => $row) {
            $row['id'] = $id;
            $tpl->tplParse($row, 'row');
        }
        
        if ($return_blocks) {
            return $tpl->parsed['row'];
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_custom');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableCustomFieldBlock($rows, $cfields) {
        
        if(!AppPlugin::isPlugin('fields')) {
            return false;
        }
        
        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('getCustomBlocks', $ajax, 'getCustomBlocks'));
        $xajax->registerFunction(array('getCustomByCategory', $ajax, 'getCustomByCategory'));
        $xajax->registerFunction(array('getCustomToDelete', $ajax, 'getCustomToDelete'));
        
        if (empty($cfields)) {
            return '';
        }
        
        $cfields = DocumentParser::parseCurlyBracesSimple($cfields);
        
        $values = array();        
        foreach ($cfields as $field_id => $v) {    
            if (!empty($rows[$field_id])) {
                $values['custom[' . $field_id . ']'] = $rows[$field_id]['data'];
            }
        }
        
        $values = RequestDataUtil::stripVars($values, array(), true);
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_custom_emode.html'));
        
        $tpl->tplAssign('hidden_fields', http_build_hidden($values));
        
        $more = array('popup' => 1);
        if (!empty($row['id'])) {
            $more['id'] = $row['id'];
        }
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'custom_field', $more);
        $tpl->tplAssign('popup_link', $link);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableAttachmentBlock(&$manager, $entry_id = false, $msg_id = false) {

        $entry_id = ($entry_id) ? $entry_id : $this->entry_id;
        
        $attachments = $this->eobj->getAttachment();
        $ids = implode(',', array_keys($attachments));
        $rows = ($ids) ? $this->emanager->getAttachmentInfoByIds($ids) : array();
                
        $visible_rows = $manager->getAttachmentList($entry_id);

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_attachment_emode.html'));

        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('parseAttachmentIconAndLink', $ajax, 'parseAttachmentIconAndLink'));
                    
        foreach($rows as $file_id => $row) {
            $row['id'] = $file_id;
            
            $ext = substr($row['filename'], strrpos($row['filename'], ".")+1);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, false, 'file', $ext);
            $row['filesize'] = WebUtil::getFileSize($row['filesize']);
            
            if (!empty($visible_rows[$file_id])) {
                $more = array('AttachID' => $file_id);
                $attachment_link = $this->controller->getLink('afile', false, $entry_id, $msg_id, $more, 1);
                
                $row['item'] = sprintf('<a href="%s" class="articleLinkOther">%s</a>', $attachment_link, $row['filename']);
                $row['class'] = '';
                
            } else {
                $row['class'] = 'class="emode_disabled_row"';
                $row['item'] = $row['filename'];
            }
            
            $row['attachment_title'] = ($row['title']) ? $row['title'] : $row['filename'];
            $row['attachment_title'] = $this->stripVars($row['attachment_title']);
            $tpl->tplParse($row, 'row');
        }
        
        $empty_display = 'none';
        if (empty($rows)) {
            $empty_display = 'block';
            
        } else {            
            $values['attachment'] = array();
            foreach ($rows as $id => $v) {
                $values['attachment'][] = $id;
            }
            
            $tpl->tplAssign('hidden_fields', http_build_hidden($values));
        }
        
        $tpl->tplAssign('empty_display', $empty_display);
        
        $tpl->tplAssign('anchor', 'anchor_entry_attachment');
        $tpl->tplAssign('title_msg', $this->msg['attachment_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableRelatedBlock($manager, $visible_rows = array()) {
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_related_emode.html'));
        
        $more = array();
        if ($this->entry_id) {
            $more = array('exclude_id' => $this->entry_id);
        }
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('related_popup_link', $link);
        
        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('parseRelatedLink', $ajax, 'parseRelatedLink'));
        
        $rows = $this->eobj->getRelated();
        foreach($rows as $k => $row) {
            $row['id'] = $k;
            
            $row['item_img'] = $this->_getItemImg($manager->is_registered, false, 'article');
            $title = $this->stripVars($row['title']);
            
            if (!empty($visible_rows[$k])) {
                $entry_id = $this->controller->getEntryLinkParams($k, $row['title']);
                $entry_link = $this->getLink('entry', $this->category_id, $entry_id);
                $row['item'] = sprintf('<a href="%s" class="articleLinkOther">%s</a>', $entry_link, $title);
                $row['class'] = '';
                
            } else {
                $row['class'] = 'class="emode_disabled_row"';
                $row['item'] = $title;
            }
            
            $tpl->tplParse($row, 'row');
        }
        
        $empty_display = 'none';
        if (empty($rows)) {
            $empty_display = 'block';
            
        } else {
            $values['related'] = array();
            foreach ($rows as $id => $v) {
                $values['related'][] = $id;
            }
            
            $tpl->tplAssign('hidden_fields', http_build_hidden($values));
        }
        
        $tpl->tplAssign('empty_display', $empty_display);
        
        $item_img = $this->_getItemImg(true, false, 'article');
        $tpl->tplAssign('item_img', $item_img);
        
        $tpl->tplAssign('anchor', 'anchor_entry_related');
        $tpl->tplAssign('title_msg', $this->msg['entry_related_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableCategoryBlock(&$manager) {
        
        $ret = false;
        if(!$manager->getSetting('entry_published')) {
            return $ret;
        }
        
        $rows = $this->eobj->getCategory();
        
        $main_category_id = $this->category_id;
        if ($rows) {
            $main_category_id = $rows[0];
            unset($rows[0]);
        }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other_emode.html'));
        
        $ajax = &$this->getAjax('emode');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('parseCategoryLinks', $ajax, 'parseCategoryLinks'));
        
        $tpl->tplAssign('main_category_id', ($main_category_id) ? $main_category_id : 0);
        
        $categories = $this->emanager->getCategoryRecords();
        $full_path = $this->emanager->cat_manager->getSelectRangeFolow($categories); // private here
        
        $tpl->tplAssign('main_category_name', $full_path[$main_category_id]);
        
        $full_path = RequestDataUtil::addslashes($full_path);
        
        foreach($rows as $cat_id) {
            $row['item_img'] = $this->_getItemImg($manager->is_registered, false, true);
            $row['category_id'] = $cat_id;
            
            $title = $full_path[$cat_id];
            
            if (!empty($categories[$cat_id]['active'])) {
                $entry_link = $this->getLink('index', $cat_id);
                $row['item'] = sprintf('<a href="%s" class="articleLinkOther">%s</a>', $entry_link, $title);
                $row['class'] = '';
                
            } else {
                $row['class'] = 'class="emode_disabled_row"';
                $row['item'] = $title;
            }
               
            $tpl->tplParse($row, 'row');
        }
        
        $empty_display = 'none';
        if (empty($rows)) {
            $empty_display = 'block';
            
        } else {
            $values['category'] = array();
            foreach ($rows as $k => $cat_id) {
                $values['category'][] = $cat_id;
            }
            
            array_unshift($values['category'], '');
            unset($values['category'][0]);
            
            
            $values['category'] = array_values($values['category']);
            
            $tpl->tplAssign('hidden_fields', http_build_hidden($values));
        }
        
        $tpl->tplAssign('empty_display', $empty_display);
        
        //admin/index.php?module=knowledgebase&page=kb_entry&action=category2
        $more = array('mode' => 'emode_secondary');
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'category', $more);
        $tpl->tplAssign('popup_link', $link);
        
        $more = array('mode' => 'emode_main', 'popup' => 1);
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'category', $more);
        $tpl->tplAssign('main_category_popup_link', $link);
        
        $item_img = $this->_getItemImg(true, false, true);
        $tpl->tplAssign('item_img', $item_img);
        
        $tpl->tplAssign('anchor', 'anchor_entry_published');
        $tpl->tplAssign('title_msg', $this->msg['entry_published_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableTagBlock($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_tag_emode.html'));
        
        $tags = $this->eobj->getTag();
        $msg = AppMsg::getMsg('common_msg.ini');
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'tags');
        
        $tpl2 = CommonEntryView::getTagBlock($tags, $link, array('public' => 1, 'msg' => $msg));
        
        $tpl->tplAssign('editable_block', $tpl2->tplPrint(1));
        
        $ajax2 = &$this->getAjax('emode');
        $ajax2->emanager = $this->emanager;
        
        $xajax = &$ajax2->getAjax($manager);
        $xajax->registerFunction(array('addTag', $ajax2, 'addTag'));
            
        $empty_display = 'none';
        if (empty($tags)) {
            $empty_display = 'block';
        }
        
        $tags = $this->getTagsArray($tags);
        if (!empty($tags)) {
            foreach ($tags as $id => $v1) {
				$v1['id'] = $id;
				$tpl->tplParse($v1, 'row');
            }
        }
        
        $tpl->tplAssign('empty_display', $empty_display);
        $tpl->tplAssign('title_msg', $this->msg['tags_msg']);
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getEditableAdvancedBlock($manager, $row) {
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_advanced_emode.html'));
        
        if (!empty($row)) {
            $obj = $this->eobj;
            
            $arr = array();
            $arr['meta_description'] = $obj->get('meta_description');
            $arr['external_link'] = $obj->get('external_link');
            $arr['active'] = $obj->get('active');
            
            if(AppPlugin::isPlugin('private')) {

                $arr['role_read'] = $obj->getRoleRead();
                $arr['role_write'] = $obj->getRoleWrite();
                
                $arr['private'] = array();
                if (PrivatePlugin::isPrivateRead($obj->get('private'))) {
                    $arr['private'][] = 'r';
                }
                
                if (PrivatePlugin::isPrivateWrite($obj->get('private'))) {
                    $arr['private'][] = 'w';
                }
            }
            
            $arr['schedule'] = $obj->getSchedule();
            
            if (count($arr['schedule']) > 0) {
                $arr['schedule_on[1]'] = 1;
                $arr['schedule'][1]['date'] = date('Y-m-d H:i:s', $arr['schedule'][1]['date']);
            }
            
            if (count($arr['schedule']) == 2) {
                $arr['schedule_on[2]'] = 1;
                $arr['schedule'][2]['date'] = date('Y-m-d H:i:s', $arr['schedule'][2]['date']);
            }
            
            $arr['sort_values'] = $obj->getSortValues();
            foreach ($obj->getSortValues() as $cat_id => $sort_order) {
                $entries = $this->emanager->getSortRecords($cat_id);
                $sort_val = CommonEntryView::getSortOrder($cat_id, $obj->get('id'), 
                                        $sort_order, $entries, false);
                $arr['sort_values'][$cat_id] = $sort_val;
            }
            
            RequestDataUtil::stripVars($arr, array(), 'display');
            
            $tpl->tplAssign('hidden_fields', http_build_hidden($arr));
        }
        
        $more = array('popup' => 1);
        if (!empty($row['id'])) {
            $more['id'] = $row['id'];
        }
        $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'advanced', $more);
        $tpl->tplAssign('popup_link', $link);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>