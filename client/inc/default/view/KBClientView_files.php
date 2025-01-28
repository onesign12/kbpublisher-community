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

class KBClientView_files extends KBClientView_common
{    

    var $num_subcategories = 0;

    
    function &execute(&$manager) {
        
        if(!$this->category_id) {
            $this->meta_title = $this->msg['file_title_msg'];
            $data = $this->getGrid('files', $manager);
            
        // category page
        } else {
            
            // does not matter why no category, deleted, or inactive or private
            if(!isset($manager->categories[$this->category_id])) { 
                
                // new private policy, check if category exists 
                if($manager->is_registered) { 
                    if($manager->isCategoryExistsAndActive($this->category_id)) {
                        $this->controller->goAccessDenied('files');
                    }
                }
                
                $this->controller->goStatusHeader('404');
            }            
            
            $this->meta_title = $this->stripVars($manager->getCategoryTitle($this->category_id));
            $this->meta_keywords = '';
            $this->meta_description = $this->stripVars($manager->getCategoryDescription($this->category_id));

        
            // sub categories
            $block_settings = array();
            $block_settings['num_columns'] = $manager->getSetting('num_category_cols');
            $block_settings['title'] = $this->msg['subcategory_title_msg'];
        
            $rows = $this->stripVars($manager->getCategoryList($this->category_id));
            $this->num_subcategories = count($rows);

            $data = array();
            $data[2] = $this->getEntryList($manager);            
            $data[1] = $this->getCategoryList($rows, $block_settings, $manager, ($data[2]));
            ksort($data);
        
            $data = implode('', $data);
        }
        
        return $data;
    }
    
    
    function getCategoryList($rows, $block_settings, $manager, $is_articles = true) {
        
        if(!$rows || !$this->display_categories) {
            return;
        }
        
        // no articles and 'num_category_cols' set to 0
        // we need display categories not to show empty page
        $num_category_td = $block_settings['num_columns'];
        if($num_category_td == 0) {
            if($is_articles) {
                return;
            }
            
            $num_category_td = 1;
        }
                
        $num = count($rows);
        
        // less categories than setting num_columns, set to categories categories
        if($num < $num_category_td) {
            $num_category_td = $num;
        }
        
        $rows = array_chunk($rows, $num_category_td);
        
        $grid_num = round(12 / $num_category_td);
        $tpl = new tplTemplatez($this->getTemplate('category_list.html'));
        
        if (!empty($block_settings['title'])) {
            $tpl->tplSetNeeded('/title');
            $tpl->tplAssign('list_title', $block_settings['title']);
        }

        foreach($rows as $k => $v) {
            $i = 0;
            
            foreach($v as $k1 => $v1) {
                $v1['grid_num'] = $grid_num;
                
                $private = $this->isPrivateEntry(false, $v1['private']);
                $v1['item_img'] = $this->_getItemImg($manager->is_registered, $private, true);
                $v1['description'] = nl2br($v1['description']);
                
                $cat_id = $this->controller->getEntryLinkParams($v1['id'], $v1['name']);
                $v1['category_link'] = $this->getLink('files', $cat_id);

                // no preview if private
                if(!$this->getSummaryLimit($manager, $private)) {
                    $v1['description'] = '';
                }

                $tpl->tplParse($v1, 'row_tr/row_td'); // parse nested

                $i ++;
            }
            
            $tpl->tplSetNested('row_tr/row_td');
            $tpl->tplParse('', 'row_tr');
        }
        
        $tpl->tplAssign('title_colspan', $num_category_td*2);
        $tpl->tplAssign('meta_title', $this->meta_title);
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }

    
    function getTopCategoryList($manager, $block_settings) {
        $rows = $this->stripVars($manager->getCategoryList($this->top_parent_id));
        $title = $this->msg['category_title_msg'];
        
        return $this->getCategoryList($rows, $block_settings, $manager, true);
    }


    function getMostDownloadedList($manager, $block_settings) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'pe');
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false), 'cr');
        
        return $this->_getMostViewed($manager, $block_settings);
    }
    
    
    function getRecentFilesList($manager, $block_settings) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'pe');
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false), 'cr');
        
        return $this->_getRecentlyPosted($manager, $block_settings);
    }
    
    
    function getEntryList(&$manager) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        $num = $manager->getSetting('num_entries_per_page');
        return $this->_getCategoryEntries($manager, $num);
    }
        
    
    // parse data with files
    function &_parseFileList(&$manager, $rows, $title, $options = array()) {
        
        $umsg = AppMsg::getMsgs('user_msg.ini');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];        
        
        $by_page = (isset($options['by_page'])) ? $options['by_page'] : '';
        $more_link = (isset($options['more_link'])) ? $options['more_link'] : false;
        $type = (isset($options['type'])) ? $options['type'] : '';
        
        if(!$rows) { $empty = ''; return $empty; }
        
        $tpl = new tplTemplatez($this->getTemplate('file_list.html'));
        
        // what date to display
        $date = 'ts_updated';
        if($by_page && strpos($manager->getSetting('entry_sort_order'), 'added') !== false) {
            $date = 'ts_posted';
        }        
        
        if($manager->getSetting('preview_show_hits')) {
            $tpl->tplSetNeededGlobal('show_hits');
        }        
        
        // subscribe
        $subsc_allowed = $this->isSubscriptionAllowed('allow_subscribe_entry', $manager);
        if($subsc_allowed) {
        
            $ids = $manager->getValuesString($rows, 'id');     
            $subscribed = ($ids) ? $manager->getEntrySubscribedByIds($ids, 2) : array();
            $tpl->tplSetNeededGlobal('subscribe');
                    
            //xajax
            $ajax = &$this->getAjax('entry');
            $ajax->view = &$this;
            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeFileResponse'));
        }
    
        // quick access to details to make some actions
        $detail_link = '';
        if($manager->isUserPriv()) {
            $referer = WebUtil::serialize_url($this->getLink('all'));
            $more = array('id'=>'_id_', 'referer'=>$referer);
            $detail_link = $this->controller->getAdminRefLink('file', 'file_entry', false, 'detail', $more, false);
            $tpl->tplSetNeededGlobal('admin_block_menu');
        }
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
    
            $row['sid'] = $row['id'] . $type;
            $row['padding_value'] = ($row['title'] || $row['description']) ? 3 : 0;
            $row['margin_value'] = ($row['description']) ? 3 : 0;
            
            // title first, bold if exists
            if($row['title']) {
                $filename = $row['filename'];
                $row['filename'] = $row['title'];
                $row['title'] = $filename;
            }
            
            $row['filesize'] = WebUtil::getFileSize($row['filesize']);
            
            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $ext = _substr($row['filename'], _strrpos($row['filename'], ".")+1);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, false, $ext);

            $row['description'] = nl2br($row['description']);
            
            // no preview if private
            if(!$this->getSummaryLimit($manager, $private)) {
                $row['description'] = '';
            }

            $tpl->tplAssign('updated_date', $this->getFormatedDate($row['ts_updated']));
            $tpl->tplAssign('detail_link', str_replace('_id_', $row['id'], $detail_link));
		   	
		   	$tpl->tplAssign('target', $this->isPrivateEntryLocked($manager->is_registered, $private) ? '_self' : '_blank');
		   	$tpl->tplAssign('entry_link', $this->getLink('file', $this->category_id, $row['id'], false, array('f'=>1), 1));
		   	$tpl->tplAssign('download_link', $this->getLink('file', $this->category_id, $row['id']));
            
            if($subsc_allowed) {
                if($manager->is_registered && isset($subscribed[$row['id']])) {
                    $tpl->tplAssign('subscribe_yes_display', 'none');
                    $tpl->tplAssign('subscribe_no_display', 'inline');
                } else {
                    $tpl->tplAssign('subscribe_yes_display', 'inline');
                    $tpl->tplAssign('subscribe_no_display', 'none');
                }                
            }
            
            $row['base_href'] = $this->controller->kb_path;
            $tpl->tplAssign($this->msg);
            
            $tpl->tplParse($row, 'row');
        }    
        
        
        // by page
        if($by_page && $by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
        
        // more links
        if(!empty($more_link['active'])) {
            $tpl->tplAssign('more_link', $more_link['url']);
            $tpl->tplSetNeeded('/more_link');
        }

        // dinamic
        if (!empty($this->dynamic_limit)) {
            KBClientView_dynamic::parseDinamicBlock($tpl, $manager, $this);
        }
        
        
        if ($title) {
            $tpl->tplSetNeeded('/title');
            $tpl->tplAssign('list_title', $title);
        }
		
        $tpl->tplParse();
        
        return $tpl;
    }    
    
    
    function parseFileList(&$manager, $rows, $title, $options = array()) {
        $tpl = $this->_parseFileList($manager, $rows, $title, $options);
        return ($tpl instanceof tplTemplatez) ? $tpl->tplPrint(1) : '';
    }
    
    
    function _getCategoryEntries($manager, $num) {
            
        $manager->setSqlParams("AND cat.id = '{$this->category_id}'");
        
        $cat_title = $manager->getCategoryTitle($this->category_id);
        $cat_id_params = $this->controller->getEntryLinkParams($this->category_id, $cat_title);
        $action_page = $this->getLink($this->view_id, $cat_id_params);
        $bp = $this->pageByPage($num, $manager->getEntryCount(), $action_page);
         
        $sort = $manager->getSortOrder($this->category_id);
        $manager->setSqlParamsOrder('ORDER BY ' . $sort);
        $rows = $manager->getEntryList($bp->limit, $bp->offset, 'category');
        
        if(!$rows && !$this->num_subcategories) {
            $msg = $this->getActionMsg('success', 'no_category_files');
            return $msg;
        }
        
        $options = array('by_page' => $bp);
        return $this->parseFileList($manager, $this->stripVars($rows), $this->meta_title, $options);
    }
    

    function &_getMostViewed($manager, $block_settings) {
        $num = $block_settings['num_entries'];
        $title = false;
        
        $this->setMostViewedSqlParams($manager);
        $rows = $manager->getEntryList($num+1, 0, 'index', 'FORCE INDEX (downloads)');

        $more_link = array(
            'url' => $this->getMoreLink(array('files', 'popular')),
            'active' => false
        );
        
        if(count($rows) > $num) {
            $more_link['active'] = true;
            $last = array_keys($rows)[count($rows)-1];
            unset($rows[$last]);
        }
                                        
        $options = array('more_link' => $more_link, 'type' => 'most');
        $list = $this->parseFileList($manager, $this->stripVars($rows), $title, $options);
    
        return $list;
    }
    
    
    function &_getRecentlyPosted($manager, $block_settings) {
        $num = $block_settings['num_entries'];
        $title = false;
        
        $this->setRecentlyPostedSqlParams($manager);
        $rows = $manager->getEntryList($num+1, 0, 'index', 'FORCE INDEX (date_updated)');

        $more_link = array(
            'url' => $this->getMoreLink(array('files', 'recent')),
            'active' => false
        );
        
        if(count($rows) > $num) {
            $more_link['active'] = true;
            $last = array_keys($rows)[count($rows)-1];
            unset($rows[$last]);
        }
        
        $options = array('more_link' => $more_link, 'type' => 'recent');
        $list = $this->parseFileList($manager, $this->stripVars($rows), $title, $options);
    
        return $list;
    }
    
    
    function setRecentlyPostedSqlParams(&$manager) {
        $manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');
    }


    function setMostViewedSqlParams(&$manager) {
        $manager->setSqlParamsOrder('ORDER BY e.downloads DESC');
    }
}
?>