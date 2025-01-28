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


class KBClientView_index extends KBClientView_common
{

    var $num_subcategories = 0;
    var $has_featured;


    function &execute(&$manager) {

        // index page
        if(!$this->category_id) {
            $this->meta_title = '';
            $this->meta_keywords = $this->stripVars($manager->getSetting('site_keywords'));
            $this->meta_description = $this->stripVars($manager->getSetting('site_description'));
            
            // menu
            $menu_setting = SettingModel::getQuick(11, 'page_design_index_menu'); 
            if (!$menu_setting) {
                $this->parse_form = false;
            }
            
            $data = $this->getGrid('index', $manager);

        // category page
        } else {

            // does not matter why no category, deleted, or inactive or private
            if(!isset($manager->categories[$this->category_id])) {

                // new private policy, check if category exists
                if($manager->is_registered) {
                    if($manager->isCategoryExistsAndActive($this->category_id)) {
                        $this->controller->goAccessDenied('index');
                    }
                }

                $this->controller->goStatusHeader('404');
            }

            $this->meta_title = $this->stripVars($manager->getCategoryTitle($this->category_id));
            $this->meta_keywords = '';
            $this->meta_description = $this->stripVars($manager->getCategoryDescription($this->category_id));
            
            // featured
            $block_settings = array();
            $block_settings['num_entries'] = $manager->getSetting('num_featured_entries_cat');
            $block_settings['title'] = $this->msg['featured_entries_title_msg'];
            $featured = $this->getFeaturedList($manager, $block_settings);
            $this->has_featured = ($featured);
            
            // sub categories
            $block_settings = array();
            $block_settings['num_columns'] = $manager->getSetting('num_category_cols');
            $block_settings['title'] = $this->msg['subcategory_title_msg'];
            
            $rows = $this->stripVars($manager->getCategoryList($this->category_id));
            $this->num_subcategories = count($rows);
            
            $data = array();
            $data[0] = $featured;
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
                $v1['category_link'] = $this->getLink('index', $cat_id);

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


        // if more then 1 category returned and no articles
        // to be able to export all categories, display export in sub cats block
        if(empty($is_articles) && $num > 1) {
            $tpl->tplAssign('block_list_option_tmpl',
                $this->getBlockListOption($tpl, $manager, array('pdf', 'rss', 'subscribe')));
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
    
    
    function getMostViewedList($manager, $block_settings) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'pe');
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false), 'cr');
        
        return $this->_getMostViewed($manager, $block_settings);
    }
    
    
    function getRecentList($manager, $block_settings) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'pe');
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false), 'cr');
        
        return $this->_getRecentlyPosted($manager, $block_settings);
    }


    function getMostDownloadedList($manager, $block_settings) {
        return $this->getExternalList('files', 'getMostDownloadedList', $manager, $block_settings);
    }
    
    
    function getRecentFilesList($manager, $block_settings) {
        return $this->getExternalList('files', 'getRecentFilesList', $manager, $block_settings);
    }
    
    
    function getEntryList(&$manager) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));

        $num = $manager->getSetting('num_entries_per_page');
        return $this->_getCategoryEntries($manager, $num);
    }
    

    function getFeaturedList($manager, $block_settings) {

        $ret = false;
        $num = $block_settings['num_entries'];
        
        if($num) {
            
            $manager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'pe');
            $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false), 'cr');
            
            if ($this->category_id) {
                $rows = $manager->getFeaturedInCategory($num+1, 0, $this->category_id);
                
            } else {
                $this->setFeaturedSqlParams($manager);
                $rows = $manager->getEntryList($num+1, 0, 'category');
            }

             // empty sql params, not to apply in recent, etc.
            $manager->setSqlParams(false, null, true);
            $manager->setSqlParamsFrom(false, null, true);

            $more_link = array(
                'url' => $this->getMoreLink('featured'),
                'active' => false
            );
            
            if(count($rows) > $num) {
                $more_link['active'] = true;
                $last = array_keys($rows)[count($rows)-1];
                unset($rows[$last]);
            }
            
            
            $options = array('more_link' => $more_link);
            $title = (!empty($block_settings['title'])) ? $block_settings['title'] : false;
            
            $list = $this->parseArticleList($manager, $this->stripVars($rows), $title, $options);
            return $list;
        }

        return $ret;
    }


    function getNewsList($manager, $block_settings) {

        $ret = false;
        if($this->category_id) {
            return $ret;
        }

        if(!$manager->isModule('news')) {
            return $ret;
        }
        
        return $this->getExternalList('news', 'getListIndexPage', $manager, $block_settings);
    }


    // parse data with articles
    function &_parseArticleList(&$manager, $rows, $title, $options = array()) {
        
        $by_page = (isset($options['by_page'])) ? $options['by_page'] : '';
        $more_link = (isset($options['more_link'])) ? $options['more_link'] : false;

        if(!$rows) { $empty = ''; return $empty; }

        $tpl = new tplTemplatez($this->getTemplate('article_list.html'));

        $article_staff_padding = '1';
        $article_description_padding = 3;
        if($manager->getSetting('preview_article_limit') == 0) {
            $article_description_padding = 0;
            $article_staff_padding = 0;
        }
        $tpl->tplAssign('article_description_padding', $article_description_padding);

        // what date to display
        $date = 'ts_updated';
        if($by_page && strpos($manager->getSetting('entry_sort_order'), 'added') !== false) {
            $date = 'ts_posted';
        }

        // entry_type
        $types = ListValueModel::getListRange('article_type', false);
        
        //coments
        $comments = array();
        if($this->isCommentable($manager) && $manager->getSetting('preview_show_comments')) {
            $entry_ids = $manager->getValuesString($rows);
            $comments = $manager->getCommentsNumForEntry($entry_ids);
        }

        // staff
        if($manager->getSetting('preview_show_date')) {
            $tpl->tplSetNeededGlobal('show_date');
            $article_staff_padding = '3';
        }

        if($manager->getSetting('preview_show_hits')) {
            $tpl->tplSetNeededGlobal('show_hits');
            $article_staff_padding = '3';
        }

        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];

            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);
            $row['updated_date'] = $this->getFormatedDate($row[$date]);

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $row['category_id'], $entry_id);
            //$row['comments_link'] =  $this->getLink('comments', $row['category_id'], $row['id']));

            $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $types, $manager);

            $summary_limit = $this->getSummaryLimit($manager, $private);
            $row['body'] = DocumentParser::getSummary($row['body'], $summary_limit);
            
            if($this->isRatingable($manager, $row['ratingable'])) {
                if($manager->getSetting('preview_show_rating')) {
                    $tpl->tplSetNeeded('row/show_rate');
                    $row['rating'] = $this->_getRating($row['rating']);
                    $article_staff_padding = '3';
                }
            }

            if($this->isCommentable($manager, $row['commentable'])) {
                if($manager->getSetting('preview_show_comments')) {
                    $row['comment_num'] = (isset($comments[$row['id']])) ? $comments[$row['id']] : 0;
                    $tpl->tplSetNeeded('row/show_comments');
                    $article_staff_padding = '3';
                }
            }

            $row['article_staff_padding'] = $article_staff_padding;
            $tpl->tplAssign($this->msg);
            
            $tpl->tplParse($row, 'row');
        }


        // by page
        if($by_page) {
            if($by_page->num_pages > 1) {
                $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
                $tpl->tplSetNeeded('/by_page_bottom');
            }

            // list option block
            $tpl->tplAssign('block_list_option_tmpl',
                $this->getBlockListOption($tpl, $manager, array('pdf', 'rss', 'subscribe')));
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


        // $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);
        // $tpl->tplAssign('comment_num_msg', $this->msg['comment_num_msg']);
        // $tpl->tplAssign('sort_by_msg', $this->msg['sort_by_msg']);
		
        if ($title) {
            $tpl->tplSetNeeded('/title');
            $tpl->tplAssign('list_title', $title);
        }

        $tpl->tplParse();

        return $tpl;
    }

    
    function parseArticleList(&$manager, $rows, $title, $options = array()) {
        $tpl = $this->_parseArticleList($manager, $rows, $title, $options);
        return ($tpl instanceof tplTemplatez) ? $tpl->tplPrint(1) : '';
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {

        $item = false;

        $umsg = AppMsg::getMsgs('user_msg.ini');
        $this->msg['subscribe_msg'] = $umsg['subscribe_msg'];
        $this->msg['unsubscribe_msg'] = $umsg['unsubscribe_msg'];

        $tpl = new tplTemplatez($this->getTemplate('block_list_option.html'));
        $tpl->tplSetNeeded('/form');

        if(in_array('pdf', $options)) {
            if(AppPlugin::isPlugin('export') && BaseModel::getExportTool()) {
                if($show_pdf = $manager->getSetting('show_pdf_category_link')) {
                    if($show_pdf == 2 && !$manager->is_registered) { // logged only
                        $show_pdf = false;
                    } elseif($show_pdf == 3 && !$manager->user_priv_id) { // staff only
                        $show_pdf = false;
                    }
                    
                    if($show_pdf) {
                        $item = true;
                        $tpl->tplSetNeeded('/view_pdf');
                        $tpl->tplAssign('pdf_link', $this->getLink('pdf-cat', $this->category_id));
                    }
                }
            }
        }

/*
        if(in_array('rss', $options)) {
            // if($manager->getSetting('rss_generate') == 'all') {
                $private = $manager->private_rule['read'];
                // echo '<pre>', print_r($private, 1), '</pre>';
                // echo '<pre>', print_r($manager->categories, 1), '</pre>';

                if($manager->categories[$category_id]['parent_id']) {
                    $item = true;
                    $tpl->tplSetNeeded('/view_rss');
                    $link = $this->controller->kb_path . 'rss.php?c=%d';
                    $tpl->tplAssign('rss_link', sprintf($link, $this->category_id));
                }
            // }
        }
*/

        // subscribe to category
        if(in_array('subscribe', $options)) {

            if($this->isSubscriptionAllowed('allow_subscribe_entry', $manager)) {
                $sub_type = $manager->entry_type_cat;

                $parents = $manager->categories_parent;
                if(!$parents) {
                    $parents = TreeHelperUtil::getParentsById($manager->categories, $this->category_id, 'name');
                }

                if(isset($parents[$this->category_id])) {
                    unset($parents[$this->category_id]);
                }
                $parents = array_keys($parents);
                $parents[] = 0; // all categories
                $parent_str = implode(',', $parents);

                // if subscribed to parent categories
                $subscribed_parent = false;
                if($manager->is_registered && $manager->isEntrySubscribedByUser($parent_str, $sub_type)) {
                    $subscribed_parent = true;
                }

                if(!$subscribed_parent) {
                    $tpl->tplSetNeeded('/view_subscribe');

                    //xajax
                    $ajax = &$this->getAjax('entry');
                    $ajax->view = &$this;
                    $xajax = &$ajax->getAjax($manager);
                    $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeArticleCatResponse'));
                    
                    if($manager->is_registered && $manager->isEntrySubscribedByUser($this->category_id, $sub_type)) {
                        $tpl->tplAssign('subscribe_yes_display', 'none');
                        $tpl->tplAssign('subscribe_no_display', 'inline');
                    } else {
                        $tpl->tplAssign('subscribe_yes_display', 'inline');
                        $tpl->tplAssign('subscribe_no_display', 'none');
                    }

                } else {
                    $tpl->tplSetNeeded('/view_subscribe_parent');
                    $link = $this->getLink('member_subsc', false, false, false, array('type'=>11));
                    $tpl->tplAssign('susbscription_link', $link);
                }
                
                $item = true;
            }
        }


        // print
        if(in_array('print', $options)) {
            $item = true;
                
            $tpl->tplSetNeeded('/view_print');
            $tpl->tplAssign('print_link', $this->getLink('print-cat', $this->category_id));
        }

        // update
        // if(in_array('update', $options)) {
            if($manager->isCategoryUpdatableByUser($this->category_id)) {
                $item = true;
                
                $tpl->tplSetNeeded('/view_update');

                $referer = 'client';
                $more = array('id' => $this->category_id, 'referer' => $referer);
                $update_link = $this->controller->getAdminRefLink('knowledgebase', 'kb_category', false, 'update', $more, false);

                $tpl->tplAssign('update_link', $update_link);
            }
        // }


        // search
        $sp = $this->_getSearchFormParams();
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        $tpl->tplAssign('advanced_search_link', $this->getLink('search', $this->category_id) . $sp['search_str']);

        $tpl->tplAssign('category_id', $this->category_id);
        $tpl->tplAssign('search_in', 'article');
		$tpl->tplAssign('alert_empty_search', addslashes($this->msg['alert_empty_search_msg']));

        // always display because we have search option
        $tmpl->tplSetNeeded('/list_option_button');

        $tpl->tplParse();

        return $tpl->tplPrint(1);
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

        if(!$rows && !$this->num_subcategories && !$this->has_featured) {
            $msg = $this->getActionMsg('success', 'no_category_articles');
            return $msg;
        }
        
        $options = array('by_page' => $bp);
        return $this->parseArticleList($manager, $this->stripVars($rows), $this->meta_title, $options);
    }


    function &_getMostViewed($manager, $block_settings) {        
        $num = $block_settings['num_entries'];
        $title = false;
        
        $this->setMostViewedSqlParams($manager);
        $rows = $manager->getEntryList($num+1, 0, 'category', 'FORCE INDEX (hits)');

        $more_link = array(
            'url' => $this->getMoreLink('popular'),
            'active' => false
        );
        
        if(count($rows) > $num) {
            $more_link['active'] = true;
            $last = array_keys($rows)[count($rows)-1];
            unset($rows[$last]);
        }

        $options = array('more_link' => $more_link);
        $list = $this->parseArticleList($manager, $this->stripVars($rows), $title, $options);
        
        return $list;
    }


    function &_getRecentlyPosted($manager, $block_settings) {        
        $num = $block_settings['num_entries'];
        $title = false;
        
        $this->setRecentlyPostedSqlParams($manager);
        $rows = $manager->getEntryList($num+1, 0, 'category', 'FORCE INDEX (date_updated)');
        $more_link = array(
            'url' => $this->getMoreLink('recent'),
            'active' => false
        );
        
        if(count($rows) > $num) {
            $more_link['active'] = true;
            $last = array_keys($rows)[count($rows)-1];
            unset($rows[$last]);
        }
        
        $options = array('more_link' => $more_link);
        $list = $this->parseArticleList($manager, $this->stripVars($rows), $title, $options);
        
        return $list;
    }


    function setRecentlyPostedSqlParams(&$manager) {
        $manager->setSqlParams('AND cat.category_type NOT IN (2,4)', 'index_sort'); // faq
        $manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');
    }


    function setMostViewedSqlParams(&$manager) {
        $manager->setSqlParams('AND cat.category_type NOT IN (2,4)', 'index_sort'); //faq
        $manager->setSqlParamsOrder('ORDER BY e.hits DESC');
        
        
        // $manager->setSqlParamsJoin('s_report_entry re ON e.id = re.entry_id AND re.report_id = 1');
        // $manager->setSqlParamsOrder('ORDER BY report_entry_hits DESC');

        //         LEFT JOIN s_report_entry re ON e.id = re.entry_id AND re.report_id = 1
        // SELECT entry_id, SUM(value_int) AS `report_entry_hits` FROM s_report_entry
        // WHERE CURRENT_DATE > DATE_SUB(date_day, INTERVAL 30 DAY)
        // GROUP BY entry_id
        // ORDER BY report_entry_hits DESC;
        
    }


    function setFeaturedSqlParams(&$manager) {
        $from = sprintf(', %s ef', $manager->tbl->entry_featured);
        $manager->setSqlParamsFrom($from, null, true);

        $manager->setSqlParams('AND e.id = ef.entry_id');
        $manager->setSqlParams('AND ef.entry_type = 1');
        $manager->setSqlParams('AND ef.category_id = 0');
        
        $manager->setSqlParamsOrder('ORDER BY ef.sort_order');
    }
    
}
?>