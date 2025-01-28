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


class FileEntryView_form extends AppView
{

    var $template = 'form.html';

    var $draft_view = false;
    var $show_required_sign = true;

    var $module = 'file';
    var $page = 'file_entry';


    function execute(&$obj, &$manager, $data = array()) {

        $tpl = $this->_executeTpl($obj, $manager);

        $more = ['id' => $obj->get('id')];
        $file_link = $this->getLink('file', 'file_entry', false, 'file', $more);
        $tpl->tplAssign('file_link', $file_link);

        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _executeTpl(&$obj, &$manager, $template = false) {

        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');


        $attachment = false;
        if($this->controller->getMoreParam('popup')) {
            $manager->cat_manager->setSqlParams("AND c.attachable = 1");
            $attachment = true;
        }

        $template_dir = APP_MODULE_DIR . 'file/entry/template/';
        $template = ($template) ?  $template : $template_dir . $this->template;

        $tpl = new tplTemplatez($template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        // check if file exists
        // if($obj->get('id')) {
        //     $file_dir = $manager->getSetting('file_dir');
        //     if(!FileEntryUtil::getFileDir($obj->get(), $file_dir)) {
        //         $tpl->tplAssign('error_msg', AppMsg::afterActionBox('inaccessible_file'));
        //     }
        // }

        // tabs
        if ($obj->get('id') && !$this->draft_view) {
            $tpl->tplAssign('menu_block', FileEntryView_common::getEntryMenu($obj, $manager, $this));
        }

        // message for uploaded files if error
        if($obj->success_files) {
            $msgs = AppMsg::getMsgs('error_msg.ini');
            $msg['title'] = $msgs['uploaded_success_msg'];
            $msg['body'] = implode('<br />', $obj->success_files);
            $tpl->tplAssign('success_msg', BoxMsg::factory('success', $msg));
        }

        // categories
        $cat_records = $manager->getCategoryRecords();
        $add_option = $this->priv->isPriv('insert', 'file_category');
        $more = array('popup' => 1, 'field_id' => 'selHandler', 'autosubmit' => 1);
        $referer = WebUtil::serialize_url($this->getLink('this', 'this', false, 'category', $more));
        $tpl->tplAssign('category_block_search_tmpl',
            CommonEntryView::getCategoryBlockSearch($manager, $cat_records, $add_option, $referer, $this->module, 'file_category'));

        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);

        $b_options = ($attachment) ? array('popup_params' => array('attachment' => 1)) : array();
        $tpl->tplAssign('category_block_tmpl',
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, $this->module, $this->page, $b_options));


        $select = new FormSelect();
        $select->select_tag = false;

        // status
        $cur_status = ($this->controller->action == 'update') ? $obj->get('active') : false;
        $range = $manager->getListSelectRange('file_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;


        if ($this->draft_view) {
            $history_needed = ($obj->get('id')) ? true : false;

        } else {
            $tpl->tplSetNeededGlobal('entry_view');

            $history_needed = false;
            if($this->controller->action == 'update') {
                $history_needed = (FileEntryModel::getHistoryAllowedRevisions());
                // CommonEntryView::parseInfoBlock($tpl, $obj, $this);
            }
            
            $select->resetOptionParam();
            $select->setRange($range);
            $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        }


        // history
        $opt['history_needed'] = $history_needed;
        $tpl->tplAssign('history_block_tmpl', CommonEntryView::getHistoryBlock($obj, $this, $opt));
        $tpl->tplAssign('history_needed', ($history_needed) ? 1 : 0);
            

        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array();
        if($this->controller->getMoreParam('popup')) {
            $more_ajax = array('popup'=>1);
        }

        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));

        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormFile'));

        // custom field
        $this->parseCustomField($tpl, $xajax, $obj, $manager, $cat_records);

        // tag
        $this->parseTagBlock($tpl, $xajax, $obj);

        // private
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);

        // sort order
        $xajax->registerFunction(array('populateSortSelect', $this, 'ajaxPopulateSortSelect'));
        $xajax->registerFunction(array('getNextCategories', $this, 'ajaxGetNextCategories'));

        foreach($obj->getCategory() as $category_id) {
            $cat_title = $categories[$category_id];
            $a['sort_order_select'] =
                CommonEntryView::populateSortSelect($manager, $obj, $category_id, $cat_title);
            $tpl->tplParse($a, 'sort_order_row');
        }

        // schedule
        $tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range));

        // update
        if($this->controller->action == 'update') {
            $tpl->tplAssign('hits', $obj->get('downloads'));
            $tpl->tplAssign('num_files_upload', 1);
            $tpl->tplSetNeeded('/update');
            // $this->show_required_sign = false;

        // when insert
        } else {
            $this->msg['file_help_msg'] = '';

            $post_max_size = Uploader::getIniValue('post_max_size');
            if($post_max_size && !$this->draft_view) {
                $tpl->tplAssign('postsize_max', WebUtil::getFileSize($post_max_size));
                $tpl->tplSetNeeded('/group_size');
            }

            $tpl->tplAssign('num_files_upload', $manager->num_files_upload);
        }

        // filename
        if ($this->controller->action == 'update' || $this->controller->getMoreParam('entry_id')) {
            $tpl->tplAssign('file_path', FileEntryUtil::getFilePath($obj->get(), $manager->getSetting('file_dir')));
            $tpl->tplAssign('file_addtype', $manager->getAddTypeSelectRange()[$obj->get('addtype')]);
            $tpl->tplSetNeeded('/filename');
        }


        // file input staff
        $size = WebUtil::getFileSize($manager->setting['file_max_filesize']*1024);
        $tpl->tplAssign('file_size_max', $size);

        $msgs = AppMsg::getMsgs('error_msg.ini');
        $tpl->tplAssign('denied_extension_msg', $msgs['denied_extension_msg']);


        $js_allowed_ext = 'false';
        $js_denied_ext = 'false';

        $ext = $manager->setting['file_allowed_extensions'];
        if(!empty($ext)) {
            foreach(array_keys($ext) as $i) {
                $ext[$i] = addslashes($ext[$i]);
            }
            $js_allowed_ext = "['" . implode("','", $ext) . "']";
        } else {
            $ext = $manager->setting['file_denied_extensions'];
            foreach(array_keys($ext) as $i) {
                $ext[$i] = addslashes($ext[$i]);
            }

            if (!empty($ext)) {
                $js_denied_ext = "['" . implode("','", $ext) . "']";
            }
        }

        $tpl->tplAssign('allowed_extension', $js_allowed_ext);
        $tpl->tplAssign('denied_extension', $js_denied_ext);

        $xajax->registerFunction(array('isFileExists', $this, 'ajaxCheckFilePresence'));


        if ($attachment) {
            $tpl->tplSetNeeded('/upload_and_attach_button');
        }

        // button text
        $button_text = $this->msg['save_msg'];
        $button_text_enabled = 0;
        if(in_array($this->controller->action, array('insert', 'clone'))) {
            $button_text_enabled = 1;

            $published_statuses = $manager->getEntryStatusPublished('file_status');
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


        $vars = $this->setCommonFormVars($obj);
        $vars['file_required_class'] = $vars['required_class'];
        if($this->controller->action == 'update') {
            $vars['file_required_class'] = '';
        }
        if (!$this->show_required_sign) {
            $vars['required_class'] = '';
        }

        $tpl->tplAssign($vars);
        $link = array('files', $obj->get('category_id'));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], $link));

        $tpl->tplAssign($obj->get());

        return $tpl;
    }


    function ajaxValidateFormFile($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);

        if (!$this->obj->errors) {
            $options['func'] = 'getValidateFile';
            $objResponse = $this->ajaxValidateForm($values['_files'], $options);
        }

        return $objResponse;
    }


    // PRIVATE // ----------------------------

    function ajaxGetCategoryPrivateInfo($category_id, $category_title) {
        if(AppPlugin::isPlugin('private')) {
            return PrivatePlugin::ajaxGetCategoryPrivateInfo($category_id, $category_title, $this->manager);
        }
    }


    function parsePrivateStuff(&$tpl, &$xajax, $obj, $manager) {
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplSetNeeded('/block_private');
            $tpl->tplAssign('block_private_tmpl',
                PrivatePlugin::getPrivateEntryBlock($xajax, $obj, $manager, $this, $this->module, $this->page));
        }
    }


    // SORT ORDER // ----------------------------

    function ajaxPopulateSortSelect($category_id, $title = 'main') {
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


    // GROUP UPLOAD // ----------------------------

    function ajaxCheckFilePresence($filename, $id) {

        $filename = str_replace('C:\fakepath\\', '', $filename);
        $filename = basename($filename);
        $dir = $this->manager->setting['file_dir'];
        
        $objResponse = new xajaxResponse();

        $v = addslashes(stripslashes(trim($filename)));
        $v = str_replace('*', '%', $v);
        $this->manager->setSqlParams("AND e.filename LIKE '{$v}'");

        $rows = $this->stripVars($this->manager->getRecords());
        if (!empty($rows)) {
            $more = array('filter[f]' => $filename);
            if($this->controller->getMoreParam('popup')) {
                $more = array('popup'=>1, 'field_id'=>1, 'field_name'=>1) + $more;
            }

            $link = $this->controller->getLink('file', 'file_entry', false, false, $more);
            $link_str = sprintf('<a href="%s">%s</a>', $link, $filename);

            $msgs = AppMsg::getMsgs('error_msg.ini');
            $msg = str_replace('{file}', $link_str, $msgs['file_exists_dir_msg']);

            $objResponse->call('sf.onFileExists', $id, $msg);

        } elseif ($this->draft_view) {
            
            $draft_manager = new FileDraftModel;

            $draft_manager->setSqlParams("AND d.title LIKE '{$v}'");
            $rows = $this->stripVars($draft_manager->getRecords());

            if (!empty($rows)) {
                $more = array('filter[f]' => $filename);
                if($this->controller->getMoreParam('popup')) {
                    $more = array('popup'=>1, 'field_id'=>1, 'field_name'=>1) + $more;
                }

                $link = $this->controller->getLink('file', 'file_draft', false, false, $more);
                $link_str = sprintf('<a href="%s">%s</a>', $link, $filename);

                $msgs = AppMsg::getMsgs('error_msg.ini');
                $msg = str_replace('{file}', $link_str, $msgs['draft_exists_dir_msg']);

                $objResponse->call('sf.onFileExists', $id, $msg);
            }
        }

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

    function parseCustomField(&$tpl, &$xajax, $obj, $manager, $categories)  {    
                
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