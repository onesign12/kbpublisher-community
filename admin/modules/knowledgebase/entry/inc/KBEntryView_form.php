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


class KBEntryView_form extends AppView
{
    
    var $template = 'form.html';
    
    var $draft_view = false;
    var $show_required_sign = true;
    
    var $module = 'knowledgebase';
    var $page = 'kb_entry';
    
    
    function execute(&$obj, &$manager) {
        $tpl = $this->_executeTpl($obj, $manager);
        $tpl->tplAssign('related_templates', $this->getRelatedBlockTemplate());
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    // return tplTemplatez obj
    function _executeTpl(&$obj, &$manager, $template = false) {
                                             
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $template = ($template) ?  $template : $this->template_dir . $this->template;
        
        $tpl = new tplTemplatez($template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormEntry'));
        
		$draft_button = false;
		
        // draft_view
        if ($this->draft_view) {
            $history_needed = ($obj->get('id')) ? true : false;
                
        } else {
            $tpl->tplSetNeededGlobal('entry_view');
            
            $history_needed = false;
            if($this->controller->action == 'update') {
                $history_needed = (KBEntryModel::getHistoryAllowedRevisions());
                // CommonEntryView::parseInfoBlock($tpl, $obj, $this);
            }

            // tabs
            if ($obj->get('id')) {
                $tpl->tplAssign('menu_block', KBEntryView_common::getEntryMenu($obj, $manager, $this));
            }

            // for trial
            $this->validateAllowedEntryRest($manager);
            
            // save as draft 
            $dactions = array('insert', 'clone');
            if(AppPlugin::isPlugin('draft')) {
                if (in_array($this->controller->action, $dactions) && $this->priv->isPriv('insert', 'kb_draft')) {
                    $draft_button = true;
                    $draft_action = $this->getLink('knowledgebase', 'kb_draft', false, 'insert');
                    $tpl->tplAssign('draft_action', $this->controller->_replaceArgSeparator($draft_action));
                }
            }
        }
        
        // body
        $tpl->tplAssign('ckeditor', $this->getEditor($obj->get('body'), 'article'));
                 
        // categories
        $cat_records = $manager->getCategoryRecords();
        $add_option = $this->priv->isPriv('insert', 'kb_category');
        $more = array('popup' => 1, 'field_id' => 'selHandler', 'autosubmit' => 1);
        $referer = WebUtil::serialize_url($this->getLink('this', 'this', false, 'category', $more));
        $tpl->tplAssign('category_block_search_tmpl', 
            CommonEntryView::getCategoryBlockSearch($manager, $cat_records, $add_option, $referer, $this->module, 'kb_category'));
        
        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, $this->module, $this->page));
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // status
        $cur_status = ($this->controller->action == 'update') ? $obj->get('active') : false; 
        $range = $manager->getListSelectRange('article_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;

        $select->resetOptionParam();
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
        // history
        $opt['history_needed'] = $history_needed;
        $tpl->tplAssign('history_block_tmpl', CommonEntryView::getHistoryBlock($obj, $this, $opt));

        
        // type
        $cur_status = ($this->controller->action == 'update') ? $obj->get('entry_type') : false;
        $range = $manager->getListSelectRange('article_type', true, $cur_status);
        $select->resetOptionParam();
        $select->setRange($range, array(0 => '___'));
        $tpl->tplAssign('entry_type_select', $select->select($obj->get('entry_type')));  
        
        // article template         
        $range = $manager->getArticleTemplateSelectRange();
        $select->setRange($range, array(0 => $this->msg['template_msg'] . ':'));
        $tpl->tplAssign('template_select', $select->select());
        
        $link = $this->getLink('knowledgebase', 'article_template', false, 'browse');
        $tpl->tplAssign('template_link', $link);

        // file converter
        if(AppPlugin::isPlugin('farticle')) {
            $reg = &Registry::instance();
            $conf = &$reg->getEntry('conf');
            if(!empty($conf['web_service_url'])) {
                $tpl->tplSetNeeded('/converter');
                $tpl->tplAssign('convert_link', $this->getActionLink('convert'));
            }
        }
        
        //related
        $more = array();
        if ($obj->get('id')) {
            $more = array('exclude_id' => $obj->get('id'));
        }
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('related_popup_link', $link);
        
        
        $preview_link_str = $this->getLink('this', 'this', false, 'preview', array('id' => ''));
        $preview_link_str = $this->controller->_replaceArgSeparator($preview_link_str) . '[id]';
        
        $preview_link_str = sprintf("PopupManager.create('%s', 'r', 'r', 2);", $preview_link_str);
        $tpl->tplAssign('preview_link_str', $preview_link_str);
        
        $update_link_str = '/admin/index.php?module=knowledgebase&page=kb_entry&action=update&id=[id]';
        $tpl->tplAssign('update_link_str', $update_link_str);
        
        foreach($obj->getRelated() as $id => $data) {
            $data['article_id'] = $id;

            $data['title'] = $this->getSubstring($data['title'], 100);
            $data['atitle'] = '';
            if($this->isSubstring($data['title'], 100)) {
                $data['atitle'] = addslashes($data['title']);
            }

            $data['preview_link'] = $this->getActionLink('preview', $id);
            $data['update_link'] = $this->getActionLink('update', $id);
            
            $data['related_ref_ch'] = $this->getChecked($data['ref']);
            $data['delete_msg'] = $this->msg['delete_msg'];
            $data['update_msg'] = $this->msg['update_msg'];
            $data['sure_common_msg'] = $this->msg['sure_common_msg'];
            $data['insert_as_link_msg'] = $this->msg['insert_as_link_msg'];
            $data['related_crossref_msg'] = $this->msg['related_crossref_msg'];
            $tpl->tplParse($data, 'related_row');
        }
        
        //attachment
        $attachment_popup_link = $this->getLink('knowledgebase', 'kb_entry', false, 'attachment');
        $tpl->tplAssign('attachment_popup_link', $attachment_popup_link);
        
        foreach($obj->getAttachment() as $id => $filename) {
            $data = array('attachment_id'=>$id, 'filename'=>$filename);
            
            $data['open_link'] = $this->getLink('file', 'file_entry', false, 'fopen', array('id' => $id));
            $data['update_link'] = $this->getLink('file', 'file_entry', false, 'update', array('id' => $id));
            
            $data['delete_msg'] = $this->msg['delete_msg'];
            $data['update_msg'] = $this->msg['update_msg'];
            
            $data['sure_common_msg'] = $this->msg['sure_common_msg'];
            $data['insert_as_link_msg'] = $this->msg['insert_as_link_msg'];
            $tpl->tplParse($data, 'attachment_row');
        }
        
        // custom field
        $this->parseCustomField($tpl, $xajax, $obj, $manager, $cat_records);

        // tag    
        $this->parseTagBlock($tpl, $xajax, $obj);
        
        // private
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);
        
        // mustread
        $this->parseMustreadBlock($tpl, $obj, $manager);

        // sort order
        $xajax->registerFunction(array('populateSortSelect', $this, 'ajaxPopulateSortSelect'));
        $xajax->registerFunction(array('getNextCategories', $this, 'ajaxGetNextCategories'));
        
        foreach($obj->getCategory() as $category_id) {
            $cat_title = $categories[$category_id];
            $a['sort_order_select'] = CommonEntryView::populateSortSelect($manager, $obj, $category_id, $cat_title);
            $tpl->tplParse($a, 'sort_order_row');
        }
        
        // entry template
        $xajax->registerFunction(array('setTypeTemplate', $this, 'ajaxSetTypeTemplate'));
        $xajax->registerFunction(array('setEntryTemplate', $this, 'ajaxSetEntryTemplate'));
        $xajax->registerFunction(array('getEntryTemplate', $this, 'ajaxGetEntryTemplate'));

        // cancel
        $xajax->registerFunction(array('cancelHandler', $this, 'ajaxCancelHandler'));
        
        // auto save        
        if ($this->setting['entry_autosave']) {
            $xajax->registerFunction(array('autoSave', $this, 'ajaxAutoSave'));
            $xajax->registerFunction(array('deleteAutoSave', $this, 'ajaxDeleteAutoSave'));
            $tpl->tplAssign(CommonEntryView::getAutosaveValues($obj, $manager, $this));
            $tpl->tplSetNeeded('/auto_save');    
        }        
        
        // schedule
        $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range));
        
        $vars = $this->setCommonFormVars($obj);
        $vars['cancel_link'] = $this->controller->_replaceArgSeparator($vars['cancel_link']);
        $vars['preview_link'] = $this->getLink($this->module, $this->page, false, 'preview');
        
        $vars['title_required_class'] = $vars['required_class'];
        if (!$this->show_required_sign) {
            $vars['required_class'] = '';
        }
        
        // button text
        $button_text = $this->msg['save_msg'];
        $button_text_enabled = 0;
        if(in_array($this->controller->action, array('insert', 'clone'))) {
            $button_text_enabled = 1;
            
            $published_statuses = $manager->getEntryStatusPublished('article_status');
            $tpl->tplAssign('published_statuses', implode(',', $published_statuses));
            
            $non_active_categories = array();
            foreach ($cat_records as $category) {
                if (!$category['active']) {
                    $non_active_categories[] = $category['id'];
                }
            }
            
            $js_hash = implode(',', $non_active_categories);
            $tpl->tplAssign('non_active_categories', $js_hash);
            
            $button_text = $this->msg['publish_msg'];
        }
        
        $tpl->tplAssign('button_text_enabled', $button_text_enabled);
        $tpl->tplAssign('button_text', $button_text);
        $tpl->tplAssign('draft_module', ($this->draft_view) ? 1 : 0);
        
		$items = array(
			'save' => array(
				'text' => $button_text,
				'action' => 'validateForm()'
			)
		);
		
		if ($draft_button) {
			$items['draft'] = array(
        		'text' => $this->msg['save_as_draft_msg'],
        		'action' => "validateForm('submit', 'validate', 'draftValidateCallback')"
			);
		}
        
		if($this->priv->isPriv('update')) {
            $items['save_continue'] = array(
                'text' => $this->msg['save_update_msg'],
                'action' => "validateForm('submit_save', 'validate')",
                'name' => 'submit_save'
            );
        }
        
        // do not update date_updated
        if(in_array($this->controller->action, array('update'))) {
            $items['save_skip'] = array(
                'text' => $this->msg['save_skip_msg'],
                'action' => "validateForm('submit_skip', 'validate')",
                'name' => 'submit_skip'
            );
        }
		
		$tpl->tplAssign('split_button', $this->getSplitButton($items));
        
        $tpl->tplAssign($vars);
		
		$to_entry = (in_array($this->controller->action, array('update', 'clone')));
		$entry_id = ($this->controller->action == 'clone') ? (int) $_GET['id'] : $obj->get('id');
        $link = ($to_entry) ? array('entry', false, $entry_id) 
			                : array('index', $obj->get('category_id'));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], $link));
        
        $tpl->tplAssign($obj->get());

        return $tpl;
    }
    
    
    function ajaxValidateFormEntry($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if ($this->obj->errors) {
            if($this->obj->errors['key'][0]['rule'] == 'mustread') {
                $objResponse->script("$('#tabs').tabs('option', 'active', 1);");
            } else {
                $objResponse->script("$('#tabs').tabs('option', 'active', 0);");
            }
        }
        
        return $objResponse;
    }
    
    
    // will work only with trial
    function validateAllowedEntryRest($manager) {
        $au = KBValidateLicense::getAllowedEntryRest($manager);
        if($au !== true) {
            if($au <= 0) {
                if($au < 0) {
                    $this->controller->go('', true);
                }
                                                                                         
                // disable insert
                if($au <= 0 && $this->controller->action != 'update') {
                    $this->controller->go('', true);
                }                    
            }
        }
    }
    
    
    function getRelatedBlockTemplate() {
        $tpl = new tplTemplatez($this->template_dir . 'block_related.html');
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    // PRIVATE // ----------------------------
    
    function ajaxGetCategoryPrivateInfo($category_id, $category_title) {
        if(AppPlugin::isPlugin('private')) {
            return PrivatePlugin::ajaxGetCategoryPrivateInfo($category_id, $category_title, $this->manager);
        }
    }
    
    
    function getCategoryPrivateInfo($category_id, $cat_title, $cat_manager) {
        if(AppPlugin::isPlugin('private')) {
            return PrivatePlugin::getCategoryPrivateInfo($category_id, $cat_title, $cat_manager);
        }
    }
    
    
    function parsePrivateStuff(&$tpl, &$xajax, $obj, $manager) {
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplSetNeeded('/block_private');
            $tpl->tplAssign('block_private_tmpl',
                PrivatePlugin::getPrivateEntryBlock($xajax, $obj, $manager, $this, $this->module, $this->page));
        }
    }
    
    // MUSTREAD // -----------------------------
    
    function parseMustreadBlock(&$tpl, $obj, $manager) {
        if(AppPlugin::isPlugin('mustread')) {
            $tpl->tplSetNeeded('/block_mustread');
            $tpl->tplAssign('block_mustread_tmpl', 
                CommonEntryView::getMustreadBlock($obj, $manager, $this, $this->module, $this->page, false)); 
        } 
    }
    
    
    // SORT ORDER // ----------------------------
    
    function ajaxPopulateSortSelect($category_id, $title) {
        return CommonEntryView::ajaxPopulateSortSelect($category_id, $title, $this->manager, $this->obj);
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


    // ENTRY TYPE // ---------------------------
    
    function ajaxSetTypeTemplate($type_key) {

        $template = false;
        if($type_key != 0) {
            $template = ListValueModel::getListData('article_type', $type_key);
            $template = $template['custom_1'];            
        }
        
        $objResponse = new xajaxResponse();
        //$objResponse->addAlert($template);    
        
        if($template) {
            $this->msg['sure_replace_body_msg'] = str_replace('\n', '<br />', $this->msg['sure_replace_body_msg']);
            $objResponse->addScriptCall("call_SetEntryTemplate", $template, 'replace', $this->msg['sure_replace_body_msg']);
        }
    
        return $objResponse;
    }
    
    
    // ENTRY TEMPLATE // ---------------------------
    
    function ajaxSetEntryTemplate($template_id, $action = 'insert') {
        
        if($action == 'insert' || $action == 'replace') {
            $template = $this->manager->getArticleTemplate($template_id);
            if ($template['is_widget']) {
                $template['body'] = sprintf('<div class="template_widget">%s</div>', $template['body']);
            }
            $template = $template['body'];
            
        } else {
            if($action == 'include') {
                $template = sprintf('[tmpl:include|%d]', $template_id);
            
            } elseif($action == 'js') {
                $template = sprintf('<a href="[tmpl:js|%d]">Text here to display template content</a>', $template_id);
            
            } elseif($action == 'ajax') {
                $template = sprintf('<a href="[tmpl:ajax|%d]">Text here to display template content</a>', $template_id);
            }
        }

        
        $objResponse = new xajaxResponse();
        //$objResponse->addAlert(1);
        
        $this->msg['sure_replace_body_msg'] = str_replace('\n', '<br />', $this->msg['sure_replace_body_msg']);
        $objResponse->addScriptCall("call_SetEntryTemplate", $template, $action, $this->msg['sure_replace_body_msg']);
    
        return $objResponse;    
    }
    
    
    function ajaxGetEntryTemplate($category_id) {
        
        $objResponse = new xajaxResponse();
        
        $categories = $this->manager->getArticleTemplateSelectRange();
        $objResponse->call('setSelectTemplate', $categories);    
    
        return $objResponse;    
    }
    
    
    // AUTOSAVE // ----------------------------    
    
    function ajaxAutoSave($data, $id_key) {
        $entry_type = $this->manager->entry_type;
        if ($this->draft_view) {
            $entry_type = $this->manager->draft_type;
        }
        
        $obj = new KBEntry;
        return CommonEntryView::ajaxAutoSave($data, $id_key, $obj, $this, $this->manager, $entry_type);   
    }
    
        
    function ajaxDeleteAutoSave($id_key) {
        return CommonEntryView::ajaxDeleteAutoSave($id_key, $this->manager);
    }


    // LOCK // -----------------------

    function ajaxCancelHandler($cancel_link) {

        $objResponse = new xajaxResponse();

        // unlock entry
        if($this->controller->action == 'update') {
            
            if ($this->draft_view) {
                $draft_id = (int) $_GET['id'];
                $this->manager->setEntryReleased($draft_id, $this->manager->draft_type);

            } else {
                $entry_id = (int) $this->obj->get('id');
                $this->manager->setEntryReleased($entry_id);
            }
        }
        
        // cancel
        $cancel_link = $this->controller->_replaceArgSeparator($cancel_link);
        $objResponse->addScript("location.href='{$cancel_link}';");
        
        return $objResponse;    
    }
    
    
    // TAG // ---------------------------

    function parseTagBlock(&$tpl, &$xajax, $obj) {
        CommonEntryView::parseTagBlock($tpl, $xajax, $obj, $this);
    }

    
    function ajaxAddTag($string) {
        return CommonEntryView::ajaxAddTag($string, $this->manager);
    }

    
    function ajaxGetTags($limit = false, $offset = 0) {
        return CommonEntryView::ajaxGetTags($limit, $offset, $this->manager);
    }    
    
    
    // CUSTOM // ---------------------------   
    
    function parseCustomField(&$tpl, &$xajax, $obj, $manager, $categories) {
        if(AppPlugin::isPlugin('fields')) {
            $xajax->registerFunction(array('getCustomByCategory', $this, 'ajaxGetCustomByCategory'));
            $xajax->registerFunction(array('getCustomToDelete', $this, 'ajaxGetCustomToDelete'));
            
            $use_default = ($this->controller->action != 'update' && empty($this->controller->rp->vars));
            $use_default = (empty($_GET['dkey'])) ? $use_default : false; //loaded from draft not to set defaults
            $rows = $manager->cf_manager->getCustomField($categories, $obj->getCategory());
            
            $tpl->tplAssign('custom_field_block_bottom', 
                CommonCustomFieldView::getFieldBlock($rows, $obj->getCustom(), $manager->cf_manager, $use_default, $this->show_required_sign));
        }
    }
     
     
    function ajaxGetCustomByCategory($categories) {
        $use_default = ($this->controller->action != 'update');
        $entry_id = $this->obj->get('id');
        return CommonCustomFieldView::ajaxGetCustomByCategory($categories, $entry_id, $use_default, $this->manager);
    }
    
    
    function ajaxGetCustomToDelete($category_id, $categories = array()) {
        return CommonCustomFieldView::ajaxGetCustomToDelete($category_id, $categories, $this->manager);    
    }
        
}
?>