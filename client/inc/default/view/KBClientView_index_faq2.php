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


class KBClientView_index_faq2 extends KBClientView_index_faq
{

    var $padding = 20;
    var $template = 'article_list_faq2.html';
    
    
    // parse data with articles
    function &parseArticleListFaq(&$manager, &$rows, $title, $by_page = '') {
            
        if(!$rows) { $empty = ''; return $empty; } 
                            
        $ids = $manager->getValuesString($rows, 'id');
        $related = $manager->getEntryRelatedInline($ids);
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // list option block
        if ($by_page !== false) {
            $tpl->tplSetNeeded('/js');
            $tpl->tplAssign('block_list_option_tmpl',
                $this->getBlockListOption($tpl, $manager, array('pdf', 'rss', 'print', 'subscribe')));
        }
        
        $tpl->tplAssign('block_id', ($by_page === false) ? 'featured' : 'article');
        
        $private_msg = AppMsg::getMsg('after_action_msg.ini', 'public', 'need_to_login_entry');
        $private_msg = $private_msg['body'];
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            
            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_full_link'] = $this->getLink('entry', $row['category_id'], $entry_id);
            
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, 'list');
            if($this->isPrivateEntryLocked($manager->is_registered, $private)) {
                $row['body']= '';
                $row['article_full_view_msg']= $private_msg;
            }
            
            // entries
            if(DocumentParser::isTemplate($row['body'])) {
                DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
            }            

            if(DocumentParser::isLink($row['body'])) {
                DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, $related, 
                                                              $row['id'], $this->controller);
            }

            if(DocumentParser::isCode($row['body'])) {
                DocumentParser::parseCode($row['body'], $manager, $this->controller);    
            }

            DocumentParser::parseCurlyBraces($row['body']);
            
            
            $anchor_ = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $anchor[$row['id']] = (is_array($anchor_)) ? $anchor_['title'] : 'f'.$anchor_;
            $row['entry_link'] = $this->getLink('all') . '#' . $anchor[$row['id']];
            
            $row['anchor'] = $anchor[$row['id']];
            $row['top_link'] = $this->getLink('all') . '#top' . $this->category_id;

            $tpl->tplAssign('back_to_faqlist_msg', $this->msg['back_to_faqlist_msg']); 
            $tpl->tplSetNeeded('row/row_body'); 

            $tpl->tplParse($row, 'row');            
        }

        // glossary
        $glossary_items = $manager->getGlossaryItems();
        if($glossary_items) {
            DocumentParser::parseGlossaryItems($tpl->parsed['row'], $glossary_items, $manager, true);
        }

        $tpl->tplAssign('print_msg', $this->msg['print_msg']);
        $tpl->tplAssign('print_link', $this->getLink('print-cat', $this->category_id));
        $tpl->tplAssign('category_id', $this->category_id);
        $tpl->tplAssign('list_title', $title);
        
        $mode = ($this->controller->mod_rewrite == 3) ? 'title' : 'id';
        $tpl->tplAssign('mode', $mode);
            
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }    
}
?>