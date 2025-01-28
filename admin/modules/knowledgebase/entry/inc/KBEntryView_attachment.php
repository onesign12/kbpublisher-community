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


class KBEntryView_attachment extends AppView 
{
    
    var $template = 'form_attachment.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('common_msg.ini', 'file');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $block_name = (!empty($_GET['client'])) ? 'public' : 'admin';
        $tpl->tplSetNeeded('/' . $block_name);
        
        
        $ext = $this->setting['file_allowed_extensions'];
        if(!empty($ext)) {
            $tpl->tplSetNeeded('/accepted_files');
            
            $ext = explode(',', $ext);
            $tpl->tplAssign('allowed_extensions', sprintf("'.%s'",  implode(',.', $ext)));
            
            // $msg = AppMsg::getMsgs('setting_msg.ini', 'admin_setting', 'file_allowed_extensions');
            // $msgs['body'] = sprintf('%s: %s', $msg['title'], implode(', ', $ext));
            // $tpl->tplAssign('extensions_hint', BoxMsg::factory('hint', $msgs));
            
        } else { // need to do smth
            
            $ext = $this->setting['file_denied_extensions'];
            if(!empty($ext)) {
                $tpl->tplSetNeeded('/denied_extensions');
                
                $ext = explode(',', $ext);
                $tpl->tplAssign('denied_extensions', sprintf('\.%s',  implode('|\.', $ext)));
                
                // $msg = AppMsg::getMsgs('setting_msg.ini', 'admin_setting', 'file_denied_extensions');
                // $msgs['body'] = sprintf('%s: %s', $msg['title'], implode(', ', $ext));
                // $tpl->tplAssign('extensions_hint', BoxMsg::factory('hint', $msgs));
            }
        }
        
        // msg
        $from_disk = '<a href="#" onclick="$(\'#input_file\').click();return false;" style="cursor: pointer;">$1</a>';
        $from_files = '<a href="javascript:PopupManager.create(\'%sindex.php?module=file&page=file_entry\', \'r\', \'r\');" style="cursor: pointer;">$1</a>';
        $from_files = sprintf($from_files, APP_ADMIN_PATH);
        
        $pattern = '/<a>(.*?)<\/a>/';
        
        $choose_msg = preg_replace($pattern, $from_disk, $this->msg['attachment_drop_file_msg'], 1);
        $choose_msg = preg_replace($pattern, $from_files, $choose_msg, 1);
        
        $tpl->tplAssign('attachment_drop_file', $choose_msg);
        
        $choose_msg = preg_replace($pattern, $from_disk, $this->msg['attachment_drop_file_disabled_msg'], 1);
        $choose_msg = preg_replace($pattern, $from_files, $choose_msg, 1);
        
        $tpl->tplAssign('attachment_drop_disabled', $choose_msg);
                
        // same name
        $same_name_msg = $this->msg['file_exists_desc_msg'];
        
        $link = '<a id="dialog_popup_link" href="javascript:openReplacePopup();"><span id="dialog_filename" style="font-weight: bold;"></span></a>';        
        $same_name_msg = str_replace('{link}', $link, $same_name_msg);
        
        $replace = '<a id="dialog_replace_link" href="javascript:replaceFile();">%s</a>';
        $replace = sprintf($replace, $this->msg['replace_msg']);
        $same_name_msg = str_replace('{replace}', $replace, $same_name_msg);
        
        $tpl->tplAssign('same_name_msg', $same_name_msg);
        
        
        $open_link_str = $this->getLink('file', 'file_entry', false, 'fopen', array('id' => ''));
        $open_link_str = $this->controller->_replaceArgSeparator($open_link_str) . '[id]';
        
        $tpl->tplAssign('preview_link_str', $open_link_str);
        
        $update_link_str = '/admin/index.php?module=file&page=file_entry&action=update&id=[id]';
        $tpl->tplAssign('update_link_str', $update_link_str);
        
        
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('checkFilePresence', $this, 'ajaxCheckFilePresence'));
        
        $max_file_size = $this->setting['file_max_filesize'] / 1024;
        $tpl->tplAssign('max_file_size', round($max_file_size, 2));
        
        $size = WebUtil::getFileSize($this->setting['file_max_filesize'] * 1024);
        $tpl->tplAssign('max_file_size_str', $size);
        
        $link = $this->controller->getRefLink('this', 'this', false, 'attachment');        
        $tpl->tplAssign('file_upload_url', $link);
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxCheckFilePresence($filename, $div_id) {
        
        $filename = str_replace('C:\fakepath\\', '', $filename);
        $filename = basename($filename);
        
        $dir = $this->setting['file_dir'];
        
        $file =  $dir . '/' . $filename;
        $file = str_replace('//', '/', $file);
        
            
        $objResponse = new xajaxResponse();
        
        if(file_exists($file)) {
            $file_params = array(
                'filename' => $filename
            );
            
            // fetching this file
            
            $f_manager = new FileEntryModel;
            
            $v = addslashes(stripslashes(trim($filename)));
            $v = str_replace('*', '%', $v);
            $f_manager->setSqlParams("AND e.filename LIKE '{$v}'");
             
            $rows = $this->stripVars($f_manager->getRecords());
            
            if (empty($rows)) { // found on disk but not in the db
                return $objResponse;
                
            } else {
                $file_params['id'] = $rows[0]['id'];
                $file_params['size'] = $rows[0]['filesize'];
                
                $more = array('filter[f]' => $filename, 'replace_id' => $div_id);
                $link = $this->controller->getLink('file', 'file_entry', false, false, $more);
                $file_params['link'] = $this->controller->_replaceArgSeparator($link);
            }
            
            $msgs = AppMsg::getMsgs('error_msg.ini');
            $msg = str_replace('{file}', $filename, $msgs['file_exists_dir_msg']);
            $msg = str_replace('. ', '.<br />', $msg);
            
            $objResponse->script(sprintf('$("#%s").find(".dz-image").css("background", "#FFFFE1");', $div_id, $msg));
            //$objResponse->script(sprintf('$("<div>%s</div>").insertAfter("#%s");', $msg, $id));
            
            $objResponse->call('showHint', $div_id, $file_params);
        }
        
        return $objResponse;
    }
    
}
?>