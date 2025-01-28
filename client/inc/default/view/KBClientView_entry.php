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


class KBClientView_entry extends KBClientView_common
{
    
    var $action_menu = array();
    var $entry = false; // to keep entry data if defined in action, need for emode 
    var $comment_form;
		

    function &execute(&$manager) {
        
        if($this->entry === false) { // defined in action, need for emode
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
        } else {
            $row = $this->entry;
        }
        
        $row = $this->stripVars($row);

        // does not matter why no article, deleted, or inactive or private
        if(!$row) { 
            
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
        
        // related
        $related = $manager->getEntryRelated($this->entry_id);
        
        $title = $row['title'];
        $this->meta_title = $this->getSubstring($title, 150);
        $this->meta_keywords = $row['meta_keywords'];
        $this->meta_description = $row['meta_description'];
        
        
        $this->nav_title = false;
        if($manager->getSetting('show_title_nav')) {
            $prefix = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);
            $this->nav_title = $prefix . $this->getSubstring($title, 70, '...');
        }
        
        // custom
        $custom_rows = $manager->getCustomDataByEntryId($row['id']);
        $custom_data = $this->getCustomData($custom_rows);
        
        // comments   
        if($manager->getSetting('comments_entry_page')) {
            
            $comment = new KBClientView_comment();
            $comment_list = $comment->getList($manager, $row);

            $comment_form = '';
            if($this->isCommentable($manager, $row['commentable'])) {
                if($comment_list) {
                    $comment_form = $comment->getForm($manager, $row, $this->msg['add_comment_msg'], 'entry');
					$this->comment_form = true;
                }
            }
        }
        
        $data = array();
        $data[] = $this->getEntry($manager, $row, $related['inline'], $custom_data);
        $data[] = $this->getEntryListCustomField($custom_data[3]);
        
        $data[] = $this->getEntryListTags($manager);
        $data[] = $this->getEntryListAttachment($manager);
        $data[] = $this->getEntryListRelated($manager, $type, $related['attached']);
        $data[] = $this->getEntryListPublished($manager, $type);
        $data[] = $this->getEntryListExternalLink($row, $type);
        
        if(isset($comment_list)) {

            $data['prev_next'] = $this->getEntryPrevNext($manager, $type);
            $data['comment_list'] =& $comment_list;
            $data['comment_form'] =& $comment_form;

            if($data['comment_list']) {
                $data[] = $data['prev_next'];
            }
                        
        } else {
            $data[] = $this->getEntryCommentsNum($manager);
            $data['prev_next'] = $this->getEntryPrevNext($manager, $type);
        }
        
        $data[] = $this->getEntryListCategory($manager, $type);
        
        $data = implode('', $data);

        return $data;        
    }
    
    
    function parseBody($manager, &$body, $related_inline) {
        $glossary_items = $manager->getGlossaryItems();
        if($glossary_items) {
            DocumentParser::parseGlossaryItems($body, $glossary_items, $manager);
        }
    
        if(DocumentParser::isTemplate($body)) {
            DocumentParser::parseTemplate($body, array($manager, 'getTemplate'));
        }        
    
        if(DocumentParser::isLink($body)) {
            DocumentParser::parseLink($body, array($this, 'getLink'), $manager, 
                $related_inline, $this->entry_id, $this->controller);    
        }
    
        if(DocumentParser::isCode($body)) {
            DocumentParser::parseCode($body, $manager, $this->controller);
        }
        
        if(DocumentParser::isCode2($body)) {
            DocumentParser::parseCode2($body, $this->controller);
        }
    
        DocumentParser::parseCurlyBraces($body);
    }
    
    
    function getEntry(&$manager, &$row, $related_inline, $custom_data)  {
        
        $this->parseBody($manager, $row['body'], $related_inline);
		
        $article_block_pos = $manager->getSetting('article_block_position');
        $tmpl = ($article_block_pos == 'bottom') ? 'article_bb.html' : 'article.html';
        
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        if($manager->getSetting('show_entry_block_top')) {
            $tpl->tplSetNeeded('/entry_block_top');
        }
        
        if(DocumentParser::isToc($row['body'], $row['body_index'], $manager)) {
            $options['data_toc_headings'] = $manager->getSetting('toc_tags');
            $tpl->tplAssign('toc_tmpl', DocumentParser::getToc($options));
        }
        
        $tpl->tplAssign('custom_tmpl_top', $this->parseCustomData($custom_data[1], 1));
        $tpl->tplAssign('custom_tmpl_bottom', $this->parseCustomData($custom_data[2], 2));
            
        $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($row['ts_updated']));
        $tpl->tplAssign('updated_date', $this->getFormatedDate($row['ts_updated']));
        
        $tpl->tplAssign('article_block', $this->getEntryBlock($row, $manager));
        
        $action_block_position = $manager->getSetting('article_action_block_position');
        // $action_block_position = 'none'; // we may need to add such setting in admin area
        $right_panel = ($action_block_position == 'right');
        
        $article_padding = ($right_panel) ? 'ab_padding_class' : '';
        $tpl->tplAssign('article_padding', $article_padding);
        
        if ($right_panel) {
            $items = $manager->getSetting('float_panel');
            $items = explode(',', $items);
            
            $tpl->tplAssign('right_panel', $this->getEntryActionsFloatPanel($items, $row, $manager));
            
            $min_height = $this->getEntryActionsFloatPanelMinHeight($items);
            $min_height = sprintf('min-height: %dpx;', $min_height);
            $tpl->tplAssign('min_height', $min_height);
        }
		$tpl->tplAssign('rating_block', $this->getRatingBlock($manager, $row));
		
		if($article_block_pos == 'right') { // for small devices
			$tpl->tplAssign('article_block2', $this->getEntryBlock($row, $manager, 'bottom'));
		}

        
        // confirm mustread after login
        $user_id = (int) $manager->user_id;
        if($user_id && !empty($_GET['mustread'])) {
            $mustread_id = $manager->confirmEntryMustread($this->entry_id, $user_id);
            $msg_key = ($mustread_id) ? 'confirmed' : false;
            $this->controller->go($this->view_id, false, $this->entry_id, $msg_key, array(), 1);  
        }

        // check if mustread should be confirmed
        if(AppPlugin::isPlugin('mustread')) {
            if($user_id || !empty($_GET['mstr'])) {
                $mustread_id = $manager->isEntryMustread($this->entry_id, $user_id);
            
                if($mustread_id) {
                    $ajax = &$this->getAjax('entry');
                    $ajax->view = &$this;
                    $xajax = &$ajax->getAjax($manager);
                    $xajax->registerFunction(array('confirmMustreadEntry', $ajax, 'confirmMustreadEntry'));
                    
                    $tpl->tplAssign('mustread_block', MustreadPlugin::getMustreadBlockConfirm($manager, $mustread_id, $user_id, $this));
                }
            }
        }

        
        // update title in place, it worked but commented 
        // we should call isEntryUpdatableByUser one time.
        // /Volumes/DataHD_1/www/vhosts/localhost/kbp/kbp_dev/client/jscript/UpdateInplace.js
        // $updateable = $manager->isEntryUpdatableByUser($this->entry_id, $this->category_id,
        //                                                $row['private'], $row['category_private'],
        //                                                $row['active']);
        // if($updateable) {
        //     $tpl->tplSetNeededGlobal('update_title_inplace');
        //     $tpl->tplAssign('title_extra_class', 'updatable');
        // }

		
        $ajax = &$this->getAjax('entry_update');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        
        $xajax->registerFunction(array('saveTitle', $ajax, 'saveTitle'));
        
        $this->parseActionMenu($tpl); // populated in getEntryBlock
        
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    

    function parseActionMenu($tpl) {
        if (!empty($this->action_menu)) {
			$menu = $this->getActionMenu($this->action_menu);
            $tpl->tplSetNeeded('/admin_block_menu');
            $tpl->tplAssign('action_menu', $menu);
        }
    }
	
    
    function getEntryBlock($data, $manager, $position = false) {

        $ret = '';
        $display_action = false;
        $display_info = false;
		
		if (!$position) {
			$position = $manager->getSetting('article_block_position');
		}
		
		$tmpl = 'article_block.html';
        if($position == 'bottom') {
            $tmpl = 'article_block_bb.html';
        }
        
        // $this->addMsg('user_msg.ini');
        $umsg = AppMsg::getMsgs('user_msg.ini');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];
        $this->msg['private_msg'] = $umsg['private_msg'];
        $this->msg['public_msg'] = $umsg['public_msg'];        

        
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        $tpl->tplAssign('article_id', $data['id']);
        $tpl->tplAssign('category_id', $data['category_id']);
        
        // authors,  dates
        if($manager->getSetting('show_author')) {
            $display_info = true;
            
            $r = new Replacer();
            $r->s_var_tag = '[';
            $r->e_var_tag = ']';
            $r->strip_var_sign = '--';
            
            $str = $manager->getSetting('show_author_format');
            
            $by_msg = $this->msg['by_msg'];
            $user = $manager->getUserInfo($data['author_id']);
            if($user) {
                @$user['short_first_name'] = _substr($user['first_name'], 0, 1);    
                @$user['short_last_name'] = _substr($user['last_name'], 0, 1);
                @$user['short_middle_name'] = _substr($user['middle_name'], 0, 1); 
                $tpl->tplAssign('author', $r->parse($str, $user));    
            } else {
                $tpl->tplAssign('author', '--');
            }
            
            $user = $manager->getUserInfo($data['updater_id']);
            if($user) {
                @$user['short_first_name'] = _substr($user['first_name'], 0, 1);
                @$user['short_last_name'] = _substr($user['last_name'], 0, 1); 
                @$user['short_middle_name'] = _substr($user['middle_name'], 0, 1);
                $tpl->tplAssign('updater', $r->parse($str, $user));
            } else {
                $tpl->tplAssign('author', '--');
            }
            
            $tpl->tplAssign('date_posted_formatted', $this->getFormatedDate($data['ts_posted']));
            $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($data['ts_updated']));
                                    
            $tpl->tplSetNeeded('/author_block');
        }        


        // info block, last updated
        $display_entry = false;
        if($manager->getSetting('show_entry_block')) {
            $display_info = true;
            
            $tpl->tplAssign('date_updated_formatted', $this->getFormatedDate($data['ts_updated']));
            $tpl->tplSetNeeded('/entry_block');
            
            // revision
            if(AppPlugin::isPlugin('history')) {
                $tpl->tplAssign('revision', $manager->getRevisionNum($data['id']));            
                $tpl->tplSetNeeded('/revision');
            }
            
            // private 
            if(AppPlugin::isPlugin('private') && $manager->getSetting('show_private_block')) {
                
                $private_title = $umsg['public_msg'];
                $str = '<span style="cursor: pointer;">%s (%s)</span>';
                
                $private['private'] = $data['private'] | $data['category_private'];
                $is_private = false;
                foreach(['isPrivateRead', 'isPrivateUnlisted'] as $func) {
                    if(PrivatePlugin::$func($private['private'])) {
                        $is_private = true;
                        break;
                    }
                }
                
                if($manager->is_registered) {
                                        
                    $private['cat_role'] = array('read'=>[],'write'=>[]);
                    if(PrivatePlugin::isPrivateRead($data['category_private'])) {
                        $private['cat_role'] = $manager->getPrivateInfo($this->category_id, 'category');
                    }
                    
                    $private['entry_role'] = array('read'=>[], 'write'=>[]);
                    if(PrivatePlugin::isPrivateRead($data['private'])) {
                        $private['entry_role'] = $manager->getPrivateInfo($this->entry_id, 'entry');
                    }
            
                    if($is_private) {

                        $role_full = array();
                        if(!empty($private['cat_role']) || !empty($private['entry_role'])) {
                            $role_manager = &$manager->getRoleModel();
                            $role_arr = $role_manager->getSelectRecords();
                            $role_full = &$role_manager->getSelectRangeFolow($role_arr, 0, ' :: ');    
                        }
                    
                        $combined_roles = array_merge_recursive($private['cat_role'], 
                        $private['entry_role']);
                        $rread = array_unique($combined_roles['read']);
                        $rwrite = array_unique($combined_roles['write']);          
                    
                        $private['read'] = $this->getEntryRoles($role_full, $rread);
                        $private['write'] = $this->getEntryRoles($role_full, $rwrite);
                                    
                        $tmsg = &$this->_getPrivateToolTipMsg($private);
                        $tmsg['body'] = $this->stripVars($tmsg['body']);
                        $tpl->tplAssign('private_body', $tmsg['body']);                
                        
                        $private_title = sprintf($str, $umsg['private_msg'], $tmsg['title']);
                    }
                
                // <- $manager->is_registered
                } else {
                    
                    if($is_private) {
                        $private['read'] = [];
                        $private['write'] = [];
                        $tmsg = &$this->_getPrivateToolTipMsg($private);
                    
                        $private_title = sprintf($str, $umsg['private_msg'], $tmsg['title']);
                    }
                }
                
                $tpl->tplAssign('private_title', $private_title);
                $tpl->tplSetNeeded('/private');
            }
        }
    
        // hits
        $data_block = false;
        if($manager->getSetting('show_hits')) {
            $display_info = true;
            
            $data_block = ($data_block) ? '/show_hits' : '/data_block/show_hits';
            $tpl->tplSetNeeded($data_block);
        }
        
        // comments
        if($this->isCommentable($manager, $data['commentable'])) {
            
            if($manager->getSetting('show_comments')) {
                $display_info = true;
                
                $data_block = ($data_block) ? '/show_comments' : '/data_block/show_comments';
                $tpl->tplSetNeeded($data_block);                    
            
                $comments_num = $manager->getCommentsNumForEntry($data['id']);
                $tpl->tplAssign('comment_num', ($comments_num) ? $comments_num[$data['id']] : 0);
            }
        }
        

        $dropdown_extra_class = ($manager->getSetting('view_format') == 'fixed') ? 'jq-dropdown-relative' : '';
        $action_block_position = $manager->getSetting('article_action_block_position');
        if($action_block_position == 'info') { // action block
            $items = array(
                'print', 'pdf', 'save', 'stick', 'share', 'comment'
            );
            $items = SettingData::getEntryBlockItems($items, $manager, $this, $data);
            
            if (!empty($items) || $this->action_menu) {
                $tpl->tplSetNeeded('/action_block');
            } 
            
            foreach ($items as $item_key => $item_data) {
                $display_action = true;
                $own_block = false;
                
                $item_data['id'] = $this->entry_id;
                $item_data['dropdown_extra_class'] = $dropdown_extra_class;
                
                if($item_key == 'save') {
                    $own_block = true;
                    
                    $ajax = &$this->getAjax('entry');
                    $ajax->view = &$this;
                    $xajax = &$ajax->getAjax($manager);
                    
                    $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeArticleResponse'));
                    
                    if($manager->is_registered && $manager->isEntrySubscribedByUser($data['id'], 1) ) {
                        $tpl->tplAssign('subscribe_yes_display', 'none');
                        $tpl->tplAssign('subscribe_no_display', 'inline');
                    } else {
                        $tpl->tplAssign('subscribe_yes_display', 'inline');
                        $tpl->tplAssign('subscribe_no_display', 'none');                
                    }        
                }
                
                if($item_key == 'stick') {
                    $own_block = true;
                    
                    $display_add_pool = 'inline';
                    $display_delete_pool = 'none';
                    
                    if ($this->isEntryPinnedByUser($this->entry_id)) {
                        $display_add_pool = 'none';
                        $display_delete_pool = 'inline';
                    }
                    
                    $tpl->tplAssign('display_add_pool', $display_add_pool);        
                    $tpl->tplAssign('display_delete_pool', $display_delete_pool);
                }
                
                if($item_key == 'share') {
                    $own_block = true;
                    
                    $social_items = $manager->getSetting('item_share_link');        
                    $social_items = unserialize($social_items);
                    
                    $dropdown_item_keys = array();
                    foreach ($social_items['active'] as $social_item) {
                        $dropdown_item_keys[] = (is_array($social_item)) ? 'custom_' . $social_item['id'] : $social_item; 
                    }
                    $dropdown_item_keys[] = 'send';
                    
                    $dropdown_items['share'] = SettingData::getEntryBlockItems($dropdown_item_keys, $manager, $this, $data);
                    $dropdown_block = $this->getEntryActionsMorePopup($dropdown_items, $manager);
                    
                    $share_link_full = $this->controller->getLinkNoRewrite('entry', false, $data['id']);
                    $share_block = str_replace('[full_url]', $share_link_full, $dropdown_block);
                    
                    $share_link = $this->controller->getRedirectLink('entry', false, $data['id']);
                    $share_link = urlencode($share_link);
                    
                    $share_block = str_replace('[url]', $share_link, $share_block);
                    $share_block = str_replace('[title]', urlencode($data['title']), $share_block);
                    
                    $tpl->tplAssign('share_block', $share_block);
                }
                
                $action_block = sprintf('%s_item', ($own_block) ? $item_key : 'action');
                $tpl->tplParse($item_data, $action_block);
            }
        }
        
        
        // admin block - edit, add
        $display_admin_block = false;
        $updateable = $manager->isEntryUpdatableByUser($this->entry_id, $this->category_id, 
                                                       $data['private'], $data['category_private'], 
                                                       $data['active']);
        
        // update 
        if($updateable) {
            $display_admin_block = true;
            $referer = 'client';
            
            // draft
            $more = array('entry_id'=>$this->entry_id, 'referer'=>$referer);
            $draft_link = $this->controller->getAdminRefLink('knowledgebase', 'kb_draft', false, 'insert', $more, false);

             if($updateable === 'as_draft') {
                 $this->action_menu['draft'] = array($this->msg['update_entry_draft_msg'], $draft_link);

             } else {
                 
                 // no quick
                 if($updateable === true) {
                     $link = $this->getLink('entry', $this->category_id, $this->entry_id, false, array('em'=>1));
                     $this->action_menu['quick'] = array($this->msg['update_entry_quick_msg'], (empty($_GET['em'])) ? $link : false);
                 }

                 $more = array('id'=>$this->entry_id, 'referer'=>$referer);
                 $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'update', $more, false);

                 $this->action_menu['update_entry'] = array($this->msg['update_entry_msg'], $link);
                 $this->action_menu['update_entry_draft'] = array($this->msg['update_entry_draft_msg'], $draft_link);
             }

	         // duplicate
	         if($manager->isEntryAddingAllowedByUser($this->category_id)) {
	             $display_admin_block = true;
	             $referer = 'client';

	             $more = array('id'=>$this->entry_id, 'referer'=>$referer, 
	 						   'category_id'=>$this->category_id, 'show_msg'=>'note_clone');
	             $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'clone', $more, false);
	             $this->action_menu['duplicate'] = array($this->msg['duplicate_msg'], $link);
	         }


            $more = array('id'=>$this->entry_id, 'referer'=>$referer);
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'detail', $more, false);
            $this->action_menu['detail'] = array($this->msg['entry_detail_msg'], $link);
            
            $more = array('id'=>$this->entry_id, 'filter[c]'=>$this->category_id);
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, false, $more, false);
            $this->action_menu['category'] = array($this->msg['admin_category_list_msg'], $link);
            
        
            // delete
            $deleteable = $manager->isEntryDeleteableByUser($this->entry_id, $this->category_id, 
                                                           $data['private'], $data['category_private'], 
                                                           $data['active']);

            if($deleteable) {
                $display_admin_block = true;
                $referer = 'client';

                $more = array('id'=>$this->entry_id, 'referer'=>$referer, 'category_id'=>$this->category_id);
                $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'delete', $more, false);
                $this->action_menu['trash'] = array($this->msg['trash_msg'], $link, true);
            }
        }
        
        if(!AppPlugin::isPlugin('draft')) {
            unset($this->action_menu['draft']);
            unset($this->action_menu['update_entry_draft']);
        }
        
        $tpl->tplAssign('dropdown_extra_class', $dropdown_extra_class);
                
        if($display_admin_block && $action_block_position == 'info') {
            $this->parseActionMenu($tpl);
        }
        
        if($display_info || ($display_action && $action_block_position == 'info')) {
            $tpl->tplParse($data);
            return $tpl->tplPrint(1);
        }
    }
    
    
    function getEntryRoles($all_roles, $entry_roles_ids) {
        $r = array();
        foreach($entry_roles_ids as $id) {
            $r[$id] = $all_roles[$id];
        }
        
        return $r;
    }
    
    
    function &_getPrivateToolTipMsg($arr, $user_msg = array()) {
        return $this->getPrivateToolTipMsg($arr['private'], $arr['read'], $arr['write'], $user_msg);
    }
    
    
    function &getPrivateToolTipMsg($private, $read_role, $write_role, $umsg = array()) {
        
        if(!$umsg) {
            $umsg = AppMsg::getMsgs('user_msg.ini');
        }
        
        $private_msg = PrivatePlugin::getPrivateTypeMsgArr($private, $umsg);
        
        // remove private write info if no priv_id 
        if(!AuthPriv::getPrivId()) {
            unset($private_msg['write']);
            $write_role = [];
        }
        
        $private_msg = implode('/', $private_msg);
        $msg['title'] = sprintf('%s', $private_msg);
        
        $str = '%s: <div style="padding-left: 15px;">%s</div>';
        $msg['body'] = '';
        
        if($read_role) {
            $role = implode('<br/>', $read_role);
            $msg['body'] .= sprintf($str, $umsg['private2_read_msg'], $role);
        }
            
        if($write_role) {
            $role = implode('<br/>', $write_role);
            $msg['body'] .= sprintf($str, $umsg['private2_write_msg'], $role);
        }
    
        return $msg;
    }
    
    
    function getEntryListCustomField($rows) {
    
        if(!$rows) { return; }

        $rows = DocumentParser::parseCurlyBracesSimple($rows);

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_custom.html'));
            
        foreach($rows as $id => $row) {
            $row['id'] = $id;
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_custom');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListAttachment(&$manager, $entry_id = false, $msg_id = false) {

        $entry_id = ($entry_id) ? $entry_id : $this->entry_id;
        
        $rows = $manager->getAttachmentList($entry_id);
        if(!$rows) { return; }

        $tpl = new tplTemplatez($this->getTemplate('article_stuff_attachment.html'));
            
        foreach($rows as $file_id => $row) {
            
            $ext = substr($row['filename'], strrpos($row['filename'], ".")+1);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, false, 'file', $ext);
            $row['filesize'] = WebUtil::getFileSize($row['filesize']);
			
            $more = array('AttachID' => $file_id, 'f' => 1);
            $link = $this->controller->getLink('afile', false, $entry_id, $msg_id, $more, 1);
            $row['attachment_link'] = $link;
			
            $more = array('AttachID' => $file_id);
            $link = $this->controller->getLink('afile', false, $entry_id, $msg_id, $more, 1);
			$row['download_link'] = $link;
			
            $row['attachment_title'] = ($row['title']) ? $row['title'] : $row['filename'];
            $row['attachment_title'] = $this->stripVars($row['attachment_title']);
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_attachment');
        $tpl->tplAssign('title_msg', $this->msg['attachment_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListRelated($manager, $type, $rows) {
        
        if(!$rows) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        foreach($rows as $k => $row) {
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'article');
            $row['title'] = $this->stripVars($row['title']);    
            
            $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $this->category_id, $entry_id);
            $row['entry_id'] = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);
                                       
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_related');
        $tpl->tplAssign('title_msg', $this->msg['entry_related_title_msg']);
        $tpl->tplAssign('key', 'related');
        $tpl->tplAssign('icon_key', 'book');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryListPublished(&$manager, $type) {
        
        $ret = false;
        if(!$manager->getSetting('entry_published')) {
            return $ret;
        }

        $rows = $this->stripVars($manager->getEntryCategories($this->entry_id, $this->category_id));
        if(!$rows) { 
            return $ret; 
        }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        // TODO: it could be category will not be in full path categories 
        // because top category is private, normally private category
        // should not have public parent categories
        $full_path = &$manager->getCategorySelectRangeFolow();
        foreach($rows as $k => $row) {
        
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $row['private'], true);
            $row['entry_link'] = $this->getLink('index', $row['category_id']);
            $row['title'] = $full_path[$row['category_id']];
                                            
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_published');
        $tpl->tplAssign('title_msg', $this->msg['entry_published_title_msg']);
        $tpl->tplAssign('key', 'also_listed');
        $tpl->tplAssign('icon_key', 'folder-open');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseExternalLink($data){
        $row = array();
        $num = 0;
        foreach(explode("\n", $data) as $v) {
            $num++;
            @list($link, $title) = explode("|", $v);
            $row[$num]['entry_link'] = trim($link);
            $row[$num]['title'] = ($title) ? trim($title) : trim($link);
            $row[$num]['item_img'] = $this->getItemImgIcon('article_out');
            $row[$num]['entry_id'] = '';
            $row[$num]['link_options'] = ' target="_blank"';
        }
        
        return $row;
    }
    
    
    function getEntryListExternalLink($entry, $type) {
        
        if(!$entry['external_link']) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        $links = $this->parseExternalLink($entry['external_link']);
        foreach($links as $row) {
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign('anchor', 'anchor_entry_external');
        $tpl->tplAssign('title_msg', $this->msg['entry_external_link_msg']);
        $tpl->tplAssign('key', 'external');
        $tpl->tplAssign('icon_key', 'new-window');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryCommentsNum(&$manager) {
        
        $num_comments = $manager->getCommentListCount($this->entry_id);
        if(!$num_comments) { return; }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        $row['entry_link'] = $this->getLink('comment', $this->category_id, $this->entry_id);
        $row['title'] = sprintf('%s %s', $num_comments, $this->msg['comment_title_msg']);
        $row['item_img'] = $this->getItemImgIcon('comment');
            
        $tpl->tplParse($row, 'row');
        
        $tpl->tplAssign('anchor', 'anchor_comment');
        $tpl->tplAssign('title_msg', $this->msg['comment_title_msg']);
        $tpl->tplAssign('key', 'comment');
        $tpl->tplAssign('icon_key', 'comment');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getEntryListCategory(&$manager, $type) {
        
        $ret = false;
        $limit_num = $manager->getSetting('num_entries_category');
        if(!$limit_num) {
            return $ret;
        }

        $limit = ($limit_num == 'all') ? -1 : $limit_num + 1;
        $limit = -1;
        
        $sort = $manager->getSortOrder($this->category_id);
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);        
        $rows = $manager->getCategoryEntries($this->category_id, $this->entry_id, $limit);
        $rows_num = count($rows);
        if(!$rows) { 
            return $ret; 
        }
        
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_other.html'));
        
        if($limit_num != 'all' && ($rows_num > $limit_num)) {
            shuffle($rows);
            $rows = array_slice($rows, 0, $limit_num);
        
            $cat_name = $manager->getCategoryTitle($this->category_id);
            $cat_id = $this->controller->getEntryLinkParams($this->category_id, $cat_name);
            $tpl->tplAssign('category_link', $this->getLink('index', $cat_id));
            $tpl->tplAssign('category_link_msg', $this->msg['more_entries_msg']);
            $tpl->tplSetNeeded('/category_link');
        }

        $rows = $this->stripVars($rows);
        
        foreach($rows as $k => $row) {
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $row['private'], 'list');
            
            $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $this->category_id, $entry_id);
            $row['entry_id'] = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);

            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign('anchor', 'anchor_entry_category');
        $tpl->tplAssign('title_msg', $this->msg['entry_category_title_msg']);
        $tpl->tplAssign('key', 'category');
        $tpl->tplAssign('icon_key', 'folder-open');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getEntryListTags($manager) {
        
        $tags = false;
        if ($manager->getSetting('show_tags')) {
            $tags = $manager->getTagByEntryId($this->entry_id); 
            $tags = $this->stripVars($tags);
        }
        
        if (empty($tags)) {
            return;
        }
        
        $tpl = new tplTemplatez($this->getTemplate('article_stuff_tag.html'));
        
        $tags = $this->getTagsArray($tags);
        foreach ($tags as $tag) {
            $tpl->tplParse($tag, 'row');
        }

        $tpl->tplAssign('anchor', 'anchor_tags');
        $tpl->tplAssign('title_msg', $this->msg['tags_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getPrevNextValues(&$manager, $type) {
        
        $limit = 65; // words limit
        $sort = $manager->getSortOrder($this->category_id);
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        $category_id = $this->category_id;
        $categories = &$manager->categories;
        
        
        $nav['next'] = false;
        $nav['prev'] = false;
        
        $entries = $manager->getCategoryEntries($category_id, 0);
        // while (list($k, $v) = each($entries)) {//deprecated
        foreach($entries as $k => $v) {
            
            if($v['entry_id'] == $this->entry_id) {
                $num = $k+1;
                if(isset($entries[$num])) {
                    $row = $entries[$num];
                    $entry_id = $this->controller->getEntryLinkParams($row['entry_id'], $row['title'], $row['url_title']);
                    $link = $this->getLink('entry', $category_id, $entry_id);
                    $prefix = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);
                    $title = $this->stripVars($row['title']);
                    $short_title = $this->stripVars($this->getSubstring($row['title'], $limit));
                    
                    $nav['next'] = array('entry_id'     => $row['entry_id'],
                                         'category_id'     => $category_id,
                                         'title'        => $title,
                                         'short_title'    => $short_title,
                                         'prefix'        => $prefix,
                                         'link'            => $link
                                         );
                }
                
                $num = $k-1;
                if(isset($entries[$num])) {
                    $row = $entries[$num];
                    $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
                    $link = $this->getLink('entry', $category_id, $entry_id);
                    $prefix = $this->getEntryPrefix($row['entry_id'], $row['entry_type'], $type, $manager);    
                    $title = $this->stripVars($row['title']);
                    $short_title = $this->stripVars($this->getSubstring($row['title'], $limit));
                
                    $nav['prev'] = array('entry_id'     => $row['entry_id'],
                                         'category_id'  => $category_id,
                                         'title'        => $title,
                                         'short_title'  => $short_title,
                                         'prefix'       => $prefix,
                                         'link'         => $link
                                         );
                }                
                
                break;
            }
        }
        
        
        $next_cat = false;
        $prev_cat = false;
        if(empty($nav['next']) || empty($nav['prev'])) {
            
            $tree_helper = &$manager->getTreeHelperArray($categories);
            reset($tree_helper);
            // while (list($cat_id, $sort_order) = each($tree_helper)) {//deprecated
            foreach($tree_helper as $cat_id => $sort_order) {
                next($tree_helper);
                // echo '<pre>', print_r($cat_id,1), '</pre>';
            
                if($cat_id == $category_id) {
                    // list($next_cat, $so) = each($tree_helper);//deprecated
                    $next_cat = key($tree_helper);
                    $so = current($tree_helper);
                    // echo '<pre>$next_cat: ', print_r($next_cat,1), '</pre>';
                    break;    
                }
            }
        
            $prev_cat = $category_id;
            //echo "<pre>"; print_r($tree_helper); echo "</pre>";        
        }
        
        
        if(empty($nav['next'])) {
            if($next_cat) {
                $cat_title = $manager->getCategoryTitle($next_cat);
                $cat_id_params = $this->controller->getEntryLinkParams($next_cat, $cat_title);
                $link = $this->getLink('index', $cat_id_params);
                $title = $this->stripVars($cat_title);
                $short_title = $this->stripVars($this->getSubstring($cat_title, $limit));   
                             
                $nav['next'] = array('entry_id'     => false,
                                     'category_id'  => $next_cat,
                                     'title'        => $title,
                                     'short_title'  => $short_title,
                                     'prefix'       => '',
                                     'link'         => $link
                                     );
            }
        }
        
        if(empty($nav['prev'])) {
            if($prev_cat) {
                $cat_title = $manager->getCategoryTitle($prev_cat);
                $cat_id_params = $this->controller->getEntryLinkParams($prev_cat, $cat_title);
                $link = $this->getLink('index', $cat_id_params);
                $title = $this->stripVars($cat_title);
                $short_title = $this->stripVars($this->getSubstring($cat_title, $limit));
                
                $nav['prev'] = array('entry_id'     => false,
                                     'category_id'  => $prev_cat,
                                     'title'        => $title,
                                     'short_title'  => $short_title,
                                     'prefix'       => '',                                     
                                     'link'         => $link
                                     );
            }
        }
        
        // echo '<pre>', print_r($nav,1), '<pre>';
        return $nav;
    }
    
    
    function &getEntryPrevNext(&$manager, $type) {
        
        $ret = '';
        // return $ret;

        $prev_next = $manager->getSetting('nav_prev_next');
        $others = $manager->getSetting('num_entries_category');
        
        if($prev_next == 'no') {
            return $ret;
            
        } elseif($prev_next == 'yes_no_others' && $others) {
            return $ret;
        }
        
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stuff_nextprev.html');
        
        $nav = &$this->getPrevNextValues($manager, $type);
        //echo "<pre>"; print_r($nav); echo "</pre>";
        
        $a['next_msg'] = '';
        if($nav['next']) {
            $a['next_link'] = $nav['next']['link'];
            $a['next_title'] = $nav['next']['title'];
            $a['next_msg'] = $this->msg['next_msg'];
            $a['next_prefix'] = $nav['next']['prefix'];
            $a['next_short_title'] = $nav['next']['short_title'];
        }
        
        $a['prev_msg'] = '';
        if($nav['prev']) {
            $a['prev_link'] = $nav['prev']['link'];
            $a['prev_title'] = $nav['prev']['title'];    
            $a['prev_msg'] = $this->msg['prev_msg'];
            $a['prev_prefix'] = $nav['prev']['prefix'];
            $a['prev_short_title'] = $nav['prev']['short_title'];            
        }
        
        $tpl->tplParse($a);
        return $tpl->tplPrint(1);
    }
}
?>