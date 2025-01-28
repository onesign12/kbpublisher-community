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


class KBClientAjax_emode extends KBClientAjax
{
    
    function validateEntry($values, $submit = true) {
        
        $objResponse = new xajaxResponse();
        $obj = new KBEntry;
        $emanager = new KBEntryModel;
        
        $is_error = $obj->validate($values, $emanager);
        if ($is_error) {
            reset($obj->errors);
            $key = key($obj->errors);
            
            $msgs = AppMsg::_gerErrorMessages($obj->errors);
            if ($obj->errors[$key][0]['msg'] == 'required_msg') {
                $msg = AppMsg::getErrorMsgs();
                $msgs['body'][0] = $msg['required2_msg'];
            }
            
            
            $objResponse->script(sprintf('$.growl.error({title: "", message: "%s"});', $msgs['body'][0]));
            
            
            $fields = $obj->errors[$key][0]['field'];
            if (!is_array($fields)) {
                $fields = array($fields);
            }
            $objResponse->call('highlightErrors', $fields);
            
            // $objResponse->script(sprintf('$("#submit_button").val("%s");', $this->view->msg['save_msg']));
            // $objResponse->script('$("#submit_button").removeClass("processing");');
        
            return $objResponse;
        }
        
        //if ($submit) {
            // $('#submit_button').attr('onclick', '');
            
            // December 23, 2019 eleontev buttons moved to submit need to skip date updated an dhistory
            // need to test 
            $objResponse->script('$("#submit_button").attr("onclick", ""); $("#submit_button").click();');
            // $objResponse->script('$("#aContentForm").submit();');
            
        /*} else {
            $objResponse->call('xajax_updateEntry', $values);
        }*/
        
        return $objResponse;
    }
    
    
    function updateEntry($values) {
        
        $objResponse = new xajaxResponse();
        $values = RequestDataUtil::stripVars($values, array(), 'addslashes');
        
        $obj = new KBEntry;
        $emanager = new KBEntryModel;
        
        $obj->populate($values, $emanager);
        
        $old_entry = $this->manager->getEntryById($this->entry_id, $this->category_id);
        $obj->set('body', $old_entry['body']);
        $obj->set('body_index', $emanager->getIndexText($old_entry['body']));
        
        $obj->set('updater_id', null);
        
        $emanager->save($obj);
            
        if(!empty($values['id_key'])) {
            $emanager->deleteAutosaveByKey($values['id_key']);
        }
            
        UserActivityLog::add('article', 'update', $this->entry_id);
        
        
        $published_status = explode(',', $this->manager->entry_published_status);
        if(!in_array($obj->get('active'), $published_status)) { // not visible anymore
            $this->controller->setCrowlMsg('updated');
            $link = $this->controller->getLink('index', $this->category_id);
            $objResponse->redirect($link);
            return $objResponse;
        }
        
        // button
        // $objResponse->script(sprintf('$("#submit_button").text("%s");', $this->view->msg['save_msg']));
        // $objResponse->script('$("#submit_button").removeClass("processing");');
        // $objResponse->script('$("#submit_button").attr("disabled", true).addClass("buttonDisabled");LeaveScreenMsg.changes = false;');
        
        $this->controller->setCrowlMsg('updated');
        $link = $this->controller->getLink('entry', $this->category_id, $this->entry_id);
        $link = $this->controller->_replaceArgSeparator($link);
        
        $objResponse->addScript("location.href='{$link}';");
        //$objResponse->redirect($link);
        
        return $objResponse;
        
        /*
        // title
        $objResponse->script(sprintf('$("span.navigation").last().prev().find("a").html("%s");', $values['title']));
        $objResponse->script(sprintf('$("span.treeNodeSelected").last().html("%s");', $values['title']));
        $objResponse->script(sprintf('$("#menu_item_entry_%s a").html("%s");', $this->entry_id, $values['title']));
        
        $objResponse->script(sprintf('$(".date_updated").html("%s");', $this->view->getFormatedDate(time())));
        
        // updater
        $r = new Replacer();
        $r->s_var_tag = '[';
        $r->e_var_tag = ']';
        $r->strip_var_sign = '--';
            
        $str = $this->manager->getSetting('show_author_format');
        
        $user = $this->manager->getUserInfo(AuthPriv::getUserId());
        $user['short_first_name'] = _substr($user['first_name'], 0, 1);
        $user['short_last_name'] = _substr($user['last_name'], 0, 1);
        $user['short_middle_name'] = _substr($user['middle_name'], 0, 1);
        $objResponse->assign('updater', 'innerHTML', $r->parse($str, $user));
        
        $msg = AppMsg::getMsgs('public/after_action_msg.ini', false, 'updated');
        $objResponse->script(sprintf('$.growl({title: "", message: "%s"});', $msg['body']));*/
        
        return $objResponse;
    }
    
    
    function parseAttachmentIconAndLink($attachment_id) {
        $objResponse = new xajaxResponse();
        
        $attachment = $this->view->emanager->getAttachmentInfoByIds($attachment_id);
        $attachment = $attachment[$attachment_id];
        $filename = $attachment['filename'];        
        
        $ext = substr($filename, strrpos($filename, '.') + 1);
        $item_img = $this->view->_getItemImg($this->manager->is_registered, false, 'file', $ext);
        
        $objResponse->script(sprintf('$("#attachment_row_%s").find("span:first").html(\'%s\');', $attachment_id, $item_img));
        
        if ($attachment['active']) { // visible
            $more = array('AttachID' => $attachment_id);
            $link = $this->controller->getLink('afile', false, $this->entry_id, false, $more, 1);
            $link = $this->controller->_replaceArgSeparator($link);
            $script = sprintf('$("#attachment_row_%s").find("a.articleLinkOther").attr("href", "%s");', $attachment_id, $link);
            
        } else { // not visible
            $script = sprintf('$("#attachment_row_%s a").wrapInner("<span/>");$("#attachment_row_%s a span").unwrap();', $attachment_id, $attachment_id);
            $script .= sprintf('$("#attachment_row_%s").addClass("emode_disabled_row");', $attachment_id);
        }
        
        $objResponse->script($script);
        
        return $objResponse;    
    }
    
    
    function parseRelatedLink($related_id) {
        $objResponse = new xajaxResponse();
        
        $related_entry = $this->manager->getEntryRelated(false, true, $related_id);
        
        if (!empty($related_entry['attached'])) { // visible
            $link = $this->view->getLink('entry', false, $related_id);
            $script = sprintf('$("#related_row_%s").find("a").attr("href", "%s");', $related_id, $link);
            
        } else { // not visible
            $script = sprintf('$("#related_row_%s a").wrapInner("<span/>");$("#related_row_%s a span").unwrap();', $related_id, $related_id);
            $script .= sprintf('$("#related_row_%s").addClass("emode_disabled_row");', $related_id);
        }
        
        $objResponse->script($script);
        
        return $objResponse;    
    }
    
    
    function parseCategoryLinks($category_ids) {
        $objResponse = new xajaxResponse();
		
        $sort_values = array();
        $script = array();
        foreach ($category_ids as $id) {
            $sort_values[$id] = 1;
            $script[] = sprintf('$("#category_row_%s").find("a").attr("href", "%s");', $id, $this->view->getLink('index', $id));
        }
        
        $objResponse->script(implode('', $script));
                
        return $objResponse;
    }
    
    
    function getCustomBlocks($data) {
        $objResponse = new xajaxResponse();
        
        $custom = array();
        foreach ($data as $v) {
            if (substr($v['name'], -2) == '[]') { // array
                $id = substr(substr($v['name'], 7), 0, -3);
                $custom[$id][] = $v['value'];
            
            } else {
                $id = rtrim(substr($v['name'], 7), ']');
                if ($v['value']) {
                    $custom[$id] = $v['value'];
                }
            }
        }
        
        if (!empty($custom)) {
            $rows = $this->view->emanager->cf_manager->getCustomFieldByIds(implode(',', array_keys($custom)));
            foreach (array_keys($rows) as $k) {
                $rows[$k]['data'] = $custom[$k];
            }
            
        } else {
            $rows = array();
        }
        
        $custom_data = $this->view->getCustomData($rows);
        
        $data['top'] = $this->view->parseCustomData($custom_data[1], 1);
        $data['bottom'] = $this->view->parseCustomData($custom_data[2], 2);
        $data['block'] = $this->view->getEntryListCustomField($custom_data[3]);
        
        $objResponse->call('insertCustomBlocks', $data, 0);
        
        return $objResponse;
    }
    
    
    function getCustomByCategory($categories) {
        $objResponse = new xajaxResponse();
        
        $emanager = new KBEntryModel;
        
        $rows = CommonCustomFieldView::getCustomRowsByCategory($categories, $emanager);
        
        $custom_rows = array();
        foreach ($rows as $custom_id => $row) {
            if (strlen($row['default_value']) && !($row['input_id'] == 5 && $row['default_value'] == 0)) {
                $custom_rows[$custom_id] = $row;
                $custom_rows[$custom_id]['data'] = $row['default_value'];
            }
        }
        
        $hidden_fields = '';
        foreach ($custom_rows as $id => $v) {
            $hidden_fields .= sprintf('<input type="hidden" name="custom[%s]" value="%s" />', $id, $v['data']);
        }
        
        //$objResponse->append('aContentForm', 'innerHTML', $hidden_fields);
        $objResponse->script(sprintf("$('#aContentForm').append('%s');", $hidden_fields));
        
        $custom_data = $this->view->getCustomData($custom_rows);
        
        $data['top'] = $this->view->parseCustomData($custom_data[1], 1);
        $data['bottom'] = $this->view->parseCustomData($custom_data[2], 2);
        $data['block'] = $this->view->getEntryListCustomField($custom_data[3], true);
        
        $objResponse->call('insertCustomBlocks', $data, 1);
        
        return $objResponse;
    }
    
    
    function getCustomToDelete($category_id, $entry_categories) {
        $emanager = new KBEntryModel;
        return CommonCustomFieldView::ajaxGetCustomToDelete($category_id, $entry_categories, $emanager); 
    }
    
    
    function autoSave($values, $id_key) {
        $emanager = new KBEntryModel;
        
        $entry_type = $emanager->entry_type;
        $obj = new KBEntry;
        
        return CommonEntryView::ajaxAutoSave($values, $id_key, $obj, $this->view, $emanager, $entry_type); 
    }
    
    
    function cancelHandler($cancel_link) {

        $objResponse = new xajaxResponse();
        
        $emanager = new KBEntryModel;
        $emanager->setEntryReleased($this->entry_id);
        
        // cancel
        $cancel_link = $this->controller->_replaceArgSeparator($cancel_link);
        $objResponse->addScript("location.href='{$cancel_link}';");
        
        return $objResponse;    
    }
    
    
    function addTag($string) {

        $objResponse = new xajaxResponse();


        if (_strlen($string) == 0) {
            return $objResponse;
        }

		$titles = $this->emanager->tag_manager->parseTagString($string);
        $this->emanager->tag_manager->saveTag($titles);

		$tags = $this->emanager->tag_manager->getTagArray($titles);
        $tags = RequestDataUtil::stripVars($tags, array(), true);
		
		$objResponse->call('createList', $tags);
        $objResponse->addAssign('tag_input', 'value', '');

        return $objResponse;
    }
    
}
?>