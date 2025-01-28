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


class FileEntryView_reference extends AppView
{
    
    var $template_notice = 'form_ref_notice.html';
    var $template_remove = 'form_ref_remove.html';
    
    
    function execute(&$obj, &$manager, $rtype) {
        
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        if($rtype == 'ref_notice') {
            return $this->getReferencesNotice($obj, $manager);
        } else {
            return $this->getReferencesRemove($obj, $manager);
        }
    }
    

    function getReferencesNotice(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template_notice);
        
        $move_to_draft = (!empty($_GET['rtype']) && $_GET['rtype'] == 'move_to_draft');
        // $error_msg_key = ($move_to_draft) ? 'note_references_file' : 'note_references_file';
        $button_msg_key = ($move_to_draft) ? 'move_to_drafts_msg' : 'delete_file_references_msg';
        $button_action = ($move_to_draft) ? 'move_to_draft' : 'delete';
        
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('note_references_file', 'error', false, $this->msg));    
        
        $file_id = $obj->get('id');
        
        $more = array('filter[q]'=>'attachment-attached:' . $file_id);        
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('review_link', $link);
        
        $more = array('ignore_reference' => 1);
        $link = $this->getActionLink($button_action, $file_id, $more);
        $tpl->tplAssign('delete_link', $link);
        $tpl->tplAssign('delete_value', $this->msg[$button_msg_key]);
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    } 
    
    
    function getReferencesRemove(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template_remove);
        
        $move_to_draft = (!empty($_GET['rtype']) && $_GET['rtype'] == 'move_to_draft');
        $error_msg_key = ($move_to_draft) ? 'note_remove_reference_file_move_to_draft' : 'note_remove_reference_file';
        $button_msg_key = ($move_to_draft) ? 'move_to_drafts_msg' : 'delete_file_msg';
        $button_action = ($move_to_draft) ? 'move_to_draft' : 'delete';
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox($error_msg_key, 'error', false, $this->msg));    
        
        $file_id = $obj->get('id');
        
        $more = array('filter[q]'=>'attachment-inline:' . $file_id);
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
        $tpl->tplAssign('review_link', $link);
        
        $more = array();
        $link = $this->getActionLink($button_action, $file_id, $more);
        $tpl->tplAssign('delete_link', $link);
        $tpl->tplAssign('delete_value', $this->msg[$button_msg_key]);
        
        //$tpl->tplAssign('filename', $manager->getFileDir($obj->get()));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }    
    
    
    
    function getShowMsg2($manager) {
        @$key = $_GET['show_msg2'];
        if($key) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);            
        }
        
        // inline
        $file_id = $_GET['id'];
        
        $articles = $manager->getEntryToAttachment($file_id, '2,3');
        $filter = array('filter[q]'=>implode(',', $articles));
        $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $filter);
        $str = "javascript:OpenPopup('%s','r','r',2,'popup_review')";
        $vars['filter_link'] = sprintf($str, $link);
        
        $more = array();
        $link = $this->getActionLink('delete', $file_id, $more);
        $str = '%s'; //"location.href='%s'";
        $vars['delete_link'] = sprintf($str, $link);
        
        $vars['file_id'] = $file_id;
        
        $msg['title'] = $msgs['title_remove_references_file'];
        $msg['body'] = $msgs['note_remove_reference_file'];
        return BoxMsg::factory('error', $msg, $vars);
    }    
}
?>