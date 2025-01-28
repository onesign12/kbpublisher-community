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


class BulkFileEntryView_form extends FileEntryView_form
{
    
    var $template = 'form.html';
    
    
    // function execute(&$obj, &$manager, $draft_manager) {
    function execute(&$obj, &$manager, $data = array()) {
                
        $draft_manager = $data['draft_manager'];
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $this->controller->module));
        
        // categories
        $cat_records = $manager->getCategoryRecords();
        $add_option = $this->priv->isPriv('insert', 'file_category');
        $more = array('popup' => 1, 'field_id' => 'selHandler', 'autosubmit' => 1);
        $referer = WebUtil::serialize_url($this->getLink('file', 'file_entry', false, 'category', $more));
        $tpl->tplAssign('category_block_search_tmpl', 
            CommonEntryView::getCategoryBlockSearch($manager, $cat_records, $add_option, $referer, 'file', 'file_category'));
        
        $cat_records = $this->stripVars($cat_records);
        $categories = &$manager->cat_manager->getSelectRangeFolow($cat_records);
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, 'file', 'file_bulk'));
        
        $select = new FormSelect();
        $select->select_tag = false;        
        
        // status
        $cur_status = false;
        $range = $manager->getListSelectRange('file_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;
        
        $select->resetOptionParam();
        $select->setRange($range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));        
        
        // s3
        if($manager->getSetting('aws_s3_allow')) {
            $tpl->tplSetNeeded('/s3_type');
        } else {
            $this->msg['dir_s3_info_msg'] = $this->msg['directory_msg'];
        }
        
        //xajax
        $this->s3_manager = $manager->s3_manager;
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        $author = $obj->getAuthor();
        if ($author) {
            $tpl->tplSetNeeded('/author');
            $tpl->tplAssign('author_id', $author['id']);
            $tpl->tplAssign('name', $author['last_name'] . ' ' . $author['first_name']); 
        }
        
        // user link
        $more = array('filter[priv]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);   
		
		// tag
        $this->parseTagBlock($tpl, $xajax, $obj);
		
        // roles
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);
        
        // custom field
        $this->parseCustomField($tpl, $xajax, $obj, $manager, $cat_records);    
    
        //files ajax
        $xajax->registerFunction(array('getFileList', $this, 'ajaxGetFileList'));
        
        // buttons
        if ($this->priv->isPrivOptional('insert', 'draft')) {
            
            $workflow = $draft_manager->getAppliedWorkflow();
            $submission_block = '';
            if ($workflow) {
                $submission_block = $this->getSubmissionBlock();
            }
            
            $tpl->tplAssign('submission_block', $submission_block);
            
        } else {
            
            $tpl->tplSetNeeded('/file_button');
            $tpl->tplSetNeeded('/status');
        }
        
        if ($obj->get('directory')) {
            $files = $this->getFileListBlock($obj->get('directory'), $manager);
            $tpl->tplAssign('file_list_block', $files);
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFileList($files, $entries, $foptions = array()) {
        
        $tpl = new tplTemplatez($this->template_dir . 'file_list.html');
        
        $entry_ids = array();
        $indexes_to_ids = array();
        foreach ($entries as $k => $entry) {
            $fname = (!empty($entry['filename_disk'])) ? $entry['filename_disk'] : $entry['filename'];
            $entry_ids[$entry['id']] = $entry['directory'] . $fname;
            $indexes_to_ids[$entry['id']] = $k; 
        }
        
        $i = 0;
        foreach($files as $k => $v) {
            
            if(isset($foptions[$k])) {
                $a = $foptions[$k];
            }
            
            $a['filename'] = $v;
            $a['filename_short'] = str_replace($foptions['directory'], '', $v);
            $a['disabled_str'] = '';
            $a['color'] = 'black';
            
            $a['checked'] = '';
            if (!empty($_POST['files']) && in_array($v, $_POST['files'])) {
                $a['checked'] = 'checked';
            }
            
            // already added to db
            $entry_id = array_search($v, $entry_ids);
            if ($entry_id !== false) {
                $tpl->tplSetNeeded('row/entry_link');
                
                $more = array('id' => $entry_id);
                $a['entry_link'] = $this->getLink('file', 'file_entry', false, 'detail', $more);
                $a['entry_id'] = $entry_id;
                $a['disabled_str'] = 'disabled';
                $a['color'] = 'grey';
                
                $index = $indexes_to_ids[$entry_id];
                $a['date_formatted'] = $this->getFormatedDate($entries[$index]['date_posted'], 'datetime');   
            }
            
            $a['id'] = $i;
            $i++;
            
            $tpl->tplParse(array_merge($a, $this->msg), 'row');
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);        
    }
    
    
    function ajaxGetFileList($directory, $manager_str) {
        
        $objResponse = new xajaxResponse();
        $objResponse->call('emptyGrowls');
        
        $manager = $this->$manager_str;
        $files = $this->getFileListBlock($directory, $manager);
        $objResponse->addAssign("file_root", "innerHTML", $files);
        
        $objResponse->call('onFileListLoaded');
        $objResponse->call("HideDiv('spinner_files')");
    
        return $objResponse;    
    }
    
    
    function getFileListBlock($directory, $manager) {
        
        if(APP_DEMO_MODE) {
            $msgs = AppMsg::getMsgs('after_action_msg.ini', false, 'not_allowed_demo');
            $block = BoxMsg::factory('error', $msgs);

        } elseif(empty($directory)) {
            $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
            $msg['title'] = false;
            $msg['body'] = $msgs['specify_dir_msg'];
            $block = BoxMsg::factory('error', $msg);

        } elseif(!$manager->isDirectoryAllowed($directory)) {
            $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
            $msg['title'] = false;
            $msg['body'] = $msgs['dir_not_readable_msg'];
            $block = BoxMsg::factory('error', $msg);

        } else {
            
            $foptions = array();
            
            try {
                
                $options['one_level'] = false;
                $files = $manager->readDirectory($directory, $options);
                
                if($files) {

                    // add uploaded type as it is new field and old records set to 1
                    if($manager instanceof FileEntryModel_dir) {
                        $manager->addtype = (string)$manager->addtype . ',1';  
                    }
                    
                    $dir = $files['options']['directory'];
                    $entries = $manager->getFilesByDirectory($dir, $manager->addtype);
                    
                    $files['files'] = Utf8::stripBadUtf($files['files'], $this->encoding);
                    $block = $this->getFileList($files['files'], $entries, $files['options']);
            
                } else {
                    $msgs = AppMsg::getMsgs('error_msg.ini', $this->controller->module);
                    $msg['title'] = false;
                    $msg['body'] = $msgs['no_files_in_directory_msg'];
                    $block = BoxMsg::factory('hint', $msg);
                }
        
            } catch (\Aws\S3\Exception\S3Exception $e) {
                
                $msg['title'] = false;
                $msg['body'] = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();
                $block = BoxMsg::factory('error', $msg);
            }
            
        }
        
        return $block;
    }
    
    
    function getSubmissionBlock() {
        
        $tpl = new tplTemplatez(APP_PLUGIN_DIR . 'draft/kb_draft/template/block_submission.html');
        
        $tpl->tplSetNeeded('/submission_block');
        // $tpl->tplAssign('submission_title', $this->msg['send_approval_msg']);
        
        $tpl->tplSetNeeded('/comment');
        $tpl->tplAssign('step_comment', @$_POST['step_comment']);
        
        // $tpl->tplAssign('button_value', $this->msg['send_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


	function parseTagBlock(&$tpl, &$xajax, $obj) {
        CommonEntryView::parseTagBlock($tpl, $xajax, $obj, $this);
    }
}
?>