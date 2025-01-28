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



class KBClientView_comment extends KBClientView_common
{
 
    function getForm($manager, $data, $title, $entry_page = false) {
        
        $tpl = new tplTemplatez($this->getTemplate('comment_form.html'));
        
        $action = ($this->msg_id == 'update') ? 'update' : 'post';
        $more = ($this->msg_id == 'update') ? array('id'=> (int) $_GET['id']) : array();
        $link = $this->getLink('comment', $this->category_id, $this->entry_id, $action, $more);
        $tpl->tplAssign('action_link', $link);        
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        if(!$manager->is_registered) {
            $tpl->tplSetNeeded('/not_registered');
        }

        // internal not implemented
        // if(AuthPriv::getPrivId()) {
        //     $tpl2 = new tplTemplatez($this->getTemplate('block_comment_type.html'));
        //     $tpl->tplAssign('comment_type_block', $tpl2->tplPrint(1));
        // }
        
        
        if($action == 'post') { 
            if($this->useCaptcha($manager, 'comment')) {
                $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'comment'));
            }
        }
        
        if($entry_page == 'entry') {
            $tpl->tplAssign('toggle_form', 1);
            $tpl->tplAssign('display_form', 'none');
        
        } elseif($entry_page == 'comment') {
            $tpl->tplAssign('toggle_form', 0);
            $tpl->tplAssign('display_form', 'block');
        
        } else {
            $tpl->tplAssign('toggle_form', 0);
            $tpl->tplAssign('display_form', 'block');
            
            $entry_id = $this->controller->getEntryLinkParams($data['id'], $data['title'], $data['url_title']);
            $tpl->tplAssign('cancel_link', $this->getLink('entry', $this->category_id, $entry_id));
            $tpl->tplSetNeeded('/cancel_btn');    
        }

        // not allowed for not registered    
        if(!$manager->is_registered && $manager->getSetting('allow_comments') == 2) {
            $tpl->tplAssign('login_link', $this->getLink('comment', $this->category_id, $this->entry_id, 'post'));
            $tpl->tplAssign('display_form', 'none');
            $tpl->tplSetNeeded('/add_login');
        } else {
            $tpl->tplSetNeeded('/add_js');
        }
        
        // subscribe    
        if($manager->isSubscribtionAllowed('comment') && $action != 'update') {
            $ch = ($_POST && empty($_POST['subscribe'])) ? 0 : 1;
            $tpl->tplAssign('ch_subscribe', $this->getChecked($ch));
            $tpl->tplSetNeeded('/subscribe');
        }

        // bbcode help
        $msg['body'] = AppMsg::getMsgMutliIni('text_msg.ini', 'public', 'bbcode_help');
        $msg['body'] = str_replace(array('((', '))'), array('<b>[', ']</b>'), $msg['body']);
        $tpl->tplAssign('bbcode_help_block', $msg['body']);
        
        $context = ($manager->getSetting('view_format') == 'fixed') ? '#content' : 'html, body';
        $tpl->tplAssign('context', $context);
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign('comment_title', $title);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFormPopup($manager/*, $data, $title*/) {
        
        $tpl = new tplTemplatez($this->getTemplate('comment_form_popup.html'));
        
        $link = $this->getLink('comment', $this->category_id, $this->entry_id, 'post');
        $tpl->tplAssign('action_link', $link);        
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        if(!$manager->is_registered) {
            $tpl->tplSetNeeded('/not_registered');
        }

        if($this->useCaptcha($manager, 'comment')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'comment', 'placeholder'));
        }
        
        // subscribe    
        if($manager->isSubscribtionAllowed('comment')) {
            $ch = ($_POST && empty($_POST['subscribe'])) ? 0 : 1;
            $tpl->tplAssign('ch_subscribe', $this->getChecked($ch));
            $tpl->tplSetNeeded('/subscribe');
        }

        // bbcode help
        $msg['body'] = AppMsg::getMsgMutliIni('text_msg.ini', 'public', 'bbcode_help');
        $msg['body'] = str_replace(array('((', '))'), array('<b>[', ']</b>'), $msg['body']);
        $tpl->tplAssign('bbcode_help_block', $msg['body']);
        
        $context = ($manager->getSetting('view_format') == 'fixed') ? '#content' : 'html, body';
        $tpl->tplAssign('context', $context);
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign('comment_title', 'asdasd');
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getList(&$manager, $entry, $limit = false) {
        
        $link = $this->getLink('comment', $this->category_id, $this->entry_id);
        $num = $manager->getSetting('num_comments_per_page');
        $limit = ($limit) ? $limit : $num;
        $num_comment = $manager->getCommentListCount($this->entry_id);
        $bp = $this->pageByPage($limit, $num_comment, $link);        
        
        $rows = $this->stripVars($manager->getCommentList($this->entry_id, $bp->limit, $bp->offset));
        if(!$rows) { return; }
        
        
        $tpl = new tplTemplatez($this->getTemplate('comment_list.html'));
                
        // by page
        $by_page =& $bp; 
        if($by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }
        
        // rss
        if($manager->getSetting('rss_generate') != 'none') {
            if(!$this->isPrivateEntry($entry['private'], $entry['category_private'])) {
                $link = $this->controller->kb_path . "rss.php?e={$this->entry_id}";
                $tpl->tplAssign('comment_rss_link', $link);
                $tpl->tplSetNeeded('/rss_link');
            }
        }
        
        
        $parser = KBCommentView_helper::getBBCodeObj();
        
        // actions
        $action_allowed = AuthPriv::getPrivAllowed('kb_comment');        
        
        if($action_allowed) {
            
            if(!$manager->getSetting('allow_comments')) {
                $update_key = array_search('update', $action_allowed);
                if($update_key) {
                    unset($action_allowed[$update_key]);
                }
            }
            
            $action_required = array('update', 'delete');
            $action_allowed_all = array_intersect($action_required, $action_allowed);
            $action_allowed_self = array();

            // check self priv 
            if($entry['author_id'] == $manager->user_id) {
                $action_required = array('self_update', 'self_delete');
                $action_allowed_self = array_intersect($action_required, $action_allowed);
                foreach($action_allowed_self as $k => $v) {
                    $action_allowed_self[$k] = str_replace('self_', '', $v);
                }
            }
            
            $action_allowed = array_unique(array_merge($action_allowed_all, $action_allowed_self));
            foreach ($action_allowed as $v) {
                $tpl->tplSetNeededGlobal($v);
            }            
        }
                        
        
        $r = new Replacer();
        $r->s_var_tag = '[';
        $r->e_var_tag = ']';
        $r->strip_var_sign = '--';            
        $comments_author_str = $manager->getSetting('comments_author_format');
        
        $social_items = $manager->getSetting('item_share_link');        
        $social_items = unserialize($social_items);
        
        $dropdown_item_keys = array();
        foreach ($social_items['active'] as $social_item) {
            $dropdown_item_keys[] = (is_array($social_item)) ? 'custom_' . $social_item['id'] : $social_item; 
        }
        
        $dropdown_items['share'] = SettingData::getEntryBlockItems($dropdown_item_keys, $manager, $this);
        $dropdown_block = $this->getEntryActionsMorePopup($dropdown_items, $manager);
        
        foreach($rows as $k => $row) {
            
            // registered user
            if($row['real_user_id']) {
                @$row['short_first_name'] = _substr($row['first_name'], 0, 1);    
                @$row['short_last_name'] = _substr($row['last_name'], 0, 1);
                @$row['short_middle_name'] = _substr($row['middle_name'], 0, 1); 
                $row['comment_name'] = $r->parse($comments_author_str, $row);
            
            // not registered or deleted, empty comment_name
            } elseif(!$row['comment_name']) {
                $row['comment_name'] = $this->msg['anonymous_user_msg'];
            }
            
            if($action_allowed) {
                
                $more = array('id'=>$row['id']);
                $link = $this->getLink('comment', $this->category_id, $this->entry_id, 'update', $more);
                $row['update_link'] = $link;
                
                $link = $this->getLink('comment', $this->category_id, $this->entry_id, 'delete', $more);
                $row['delete_link'] = $link;
                
                $tpl->tplSetNeeded('row/action');
            }
        
            $row['comment'] = nl2br($parser->qparse($row['comment']));
            $row['formated_date'] = $this->getFormatedDate($row['ts'], 'datetime');
            $row['date_interval'] = $this->getTimeInterval($row['ts'], true);
            
            $anchor = $row['anchor'] = 'c' . $row['id'];
            
            $more = (isset($_GET['bp'])) ? array('bp' => $bp->cur_page) : array();
            // inc comments we always go to comments page 
            // $entry_id = $this->controller->getEntryLinkParams($entry['id'], $entry['title'], $entry['url_title']); 
            $entry_id = $entry['id'];
            $row['anchor_link'] = $this->getLink('comment', 0, $entry_id, false, $more) . '#' . $anchor;
            
            $row['message_num'] = (($bp->cur_page - 1) * $num) + $k + 1;
            
            // share link
            if($manager->getSetting('show_share_link') && $manager->getSetting('item_share_link')) {
                $tpl->tplSetNeeded('row/share_block');
                
                $more = array('message_id' => $row['id']);
                
                $share_link_full = $this->controller->getLinkNoRewrite('comment', false, $this->entry_id, false, $more);
                $share_block = str_replace('[full_url]', $share_link_full, $dropdown_block);
                
                $share_link = $this->controller->getRedirectLink('comment', false, $this->entry_id, false, $more);
                $share_link = urlencode($share_link);
                
                $share_block = str_replace('[url]', $share_link, $share_block);
                $share_block = str_replace('[title]', urlencode($entry['title']), $share_block);
                
                $tpl->tplAssign('share_block', $share_block);
            }
            
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        
        $v = new Validator($values, false);
        
        $v->csrfCookie();
        $v->required('required_msg', array('comment'));
        
        if(isset($values['email'])) {
            $v->regex('email_msg', 'email', 'email', false);
        }
        
        $action = ($this->msg_id == 'update') ? 'update' : 'post';
        if($action == 'post') {      
            if($error = $this->validateCaptcha($manager, $values, 'comment')) {
                $v->setError($error[0], $error[1], $error[2], $error[3]);
            }
        }
        
        return $v->getErrors();
    }
    
    
    function isSpam($values) {
        if(!empty($values['s_company']) || !empty($values['s_company_set'])) {
            return true;
        }
    }
}
?>