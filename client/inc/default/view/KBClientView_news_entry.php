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


class KBClientView_news_entry extends KBClientView_news
{

    function &execute(&$manager) {
        
        $row = $manager->getNewsById($this->entry_id);
        $row = $this->stripVars($row);
        
        // does not matter why no article, deleted, or inactive or private
        if(!$row) { 
            
            // new private policy, check if entry exists 
            if($manager->is_registered) { 
                if($manager->isNewsExistsAndActive($this->entry_id)) {
                    $this->controller->goAccessDenied('news');
                }
            }
            
            $this->controller->goStatusHeader('404');
        }
        
        
        $this->home_link = true;
        $this->meta_title = $this->getSubstring($row['title'], 150);
        $this->meta_keywords = $row['meta_keywords'];
        
        $title = $this->getSubstring($this->meta_title, 70);
        $link = $this->getLink('news');
        
        $year = $manager->getYearByEntryId($this->entry_id);
        $link2 = $this->getLink('news', $year);
        $link2 = $this->parseCategoryLink($link2);
        
        $this->nav_title = array($link => $this->msg['menu_news_msg'], $link2 => $year, $title);
        
        // custom
        $custom_rows = $manager->getCustomDataByEntryId($row['id']);
        $custom_data = $this->getCustomData($custom_rows);
                
        $data = array();
        $data[] = &$this->getEntry($manager, $row, $custom_data);
        $data[] = $this->getEntryListTags($manager);
        $data = implode('', $data);
        
        return $data;        
    }
    
    
    function &getEntry($manager, $row, $custom_data) {
        
        $tpl = new tplTemplatez($this->getTemplate('news_entry.html'));
                
        // DocumentParser::parseMarkdown($row['body']);
        DocumentParser::parseCurlyBraces($row['body']);
                
        $row['custom_tmpl_top'] = $this->parseCustomData($custom_data[1], 1);
        $row['custom_tmpl_bottom'] = $this->parseCustomData($custom_data[2], 2);
        $row['date_formatted'] = $this->getFormatedDate($row['date_posted']);
        
        $action_block_position = $manager->getSetting('article_action_block_position');
        $right_panel = ($action_block_position == 'right');
        
        $article_padding = ($right_panel) ? 'ab_padding_class' : '';
        $tpl->tplAssign('article_padding', $article_padding);
        
        $entry_block = $this->getEntryBlock($row, $manager);
        
        if ($right_panel) {
            $items = $manager->getSetting('float_panel');
            $items = explode(',', $items);
            
            $tpl->tplAssign('right_panel', $this->getEntryActionsFloatPanel($items, $row, $manager));
            
            $min_height = $this->getEntryActionsFloatPanelMinHeight($items);
            $min_height = sprintf('min-height: %dpx;', $min_height);
            $tpl->tplAssign('min_height', $min_height);
            
        } else {
            $row['news_block'] = $entry_block;
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
                
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    
    
    function &getEntryBlock($data, $manager) {
        
        $display = false;
        
        $tmpl = 'news_entry_block.html';
        
        $tpl = new tplTemplatez($this->getTemplate($tmpl));
        
        // print
        if($manager->getSetting('show_print_link')) {
            $display = true;
            $tpl->tplSetNeeded('/print_link');
            $tpl->tplAssign('print_link', $this->getLink('print-news', false, $data['id']));  
        }
        
        // share link
        if($manager->getSetting('show_share_link') && $manager->getSetting('item_share_link')) {
            $display = true;
            $tpl->tplSetNeeded('/share_link');
            
            $social_items = $manager->getSetting('item_share_link');        
            $social_items = unserialize($social_items);
            
            $dropdown_item_keys = array('send');
            foreach ($social_items['active'] as $social_item) {
                $dropdown_item_keys[] = (is_array($social_item)) ? 'custom_' . $social_item['id'] : $social_item; 
            }
            
            $dropdown_items['share'] = SettingData::getEntryBlockItems($dropdown_item_keys, $manager, $this, $data);
            $dropdown_block = $this->getEntryActionsMorePopup($dropdown_items, $manager);
                
            $share_link_full = $this->controller->getLinkNoRewrite('news', false, $this->entry_id);
            $share_block = str_replace('[full_url]', $share_link_full, $dropdown_block);
            
            $share_link = $this->controller->getRedirectLink('news', false, $this->entry_id);
            $share_link = urlencode($share_link);
            
            $share_block = str_replace('[url]', $share_link, $share_block);
            $share_block = str_replace('[title]', urlencode($data['title']), $share_block);
            
            $tpl->tplAssign('dropdown_block', $share_block);
        }
        
		
        // admin block - edit, add
        $display_admin_block = false;
		
        $updatable = $manager->isNewsUpdatableByUser($this->entry_id, $data['private']);
        if($updatable) {
            $display = true;
			$display_admin_block = true;
            
            $referer = 'client';
            $more = array('id' => $this->entry_id, 'referer' => $referer);
            $link = $this->controller->getAdminRefLink('news', 'news_entry', false, 'update', $more, false);
            $this->action_menu[] = array($this->msg['update_entry_msg'], $link);
        }
        
		
        if($display_admin_block) {
            $this->parseActionMenu($tpl);
        }
		
		
        if($display) {
            $tpl->tplParse($data);
            return $tpl->tplPrint(1);
        }
    }
    
	
    function parseActionMenu($tpl) {
        if (!empty($this->action_menu)) {
			$menu = $this->getActionMenu($this->action_menu);
            $tpl->tplSetNeeded('/admin_block_menu');
            $tpl->tplAssign('action_menu', $menu);
        }
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

        $tpl->tplAssign('title_msg', $this->msg['tags_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>