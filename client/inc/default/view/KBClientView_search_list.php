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


class KBClientView_search_list extends KBClientView_search
{

    var $num_per_page = 10;
    var $view_manager = array();

    var $spell_suggest;
    var $spell_mistake;

    var $left_filter_data;
    

    function &execute(&$manager) {

        $this->advanced_search = (isset($_GET['period']));
        $this->advanced_search = false;

        $this->home_link = true;
        $this->parse_form = ($manager->getSetting('search_filter'));
        $this->meta_title = $this->msg['search_result_msg'];
        $this->category_nav_generate = false;

        if($this->advanced_search) {
            $link = $this->getLinkToSearchForm();
            $nav = array($link => $this->msg['advanced_search_msg'], $this->msg['search_result_msg']);
        } else {
            $nav = $this->msg['search_result_msg'];
        }

        $this->nav_title = $nav;

        $in_vals = KBClientSearchHelper::getInValue($_GET, $manager);
        $in = $in_vals['in'];
        $by = $in_vals['by'];
        $qs = $in_vals['qs'];
        $special_search = $in_vals['sk'];
        
        if(!$special_search) {
            $wildcards = array('*');
            if (SphinxModel::isSphinxOnSearch($_GET['q'])) {
                $wildcards[] = '?';
            }
    
            $_query = SphinxModel::getSphinxString($_GET['q']);
            foreach ($wildcards as $wildcard) {
                if (strpos($_query, $wildcard) !== false) {
                    $special_search = true;
                }
            }
        }
        
        // echo '<pre>', print_r($in, 1), '</pre>';
        // echo '<pre>', print_r("================", 1), '</pre>';

        if(!$special_search && $by != 'id' && $by != 'keyword') {
            
            if ($manager->getSetting('search_spell_suggest')) {            
                $spell_suggest = $this->spellSuggest($_GET['q'], $manager);
            
                if ($spell_suggest) {
                    $this->spell_mistake = $_GET['q'];
                    $this->spell_suggest = $spell_suggest;
                
                    $_GET['q'] = $this->spell_suggest;
                }
            }
        }
        
        $file_manager = false;
        if(in_array('file', $in)) {
            $file_manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'files');   
        }
        
        $search_str = (!empty($_GET['q'])) ? $_GET['q'] : false;
        $this->engine_name = $this->getSearchEngineName($manager, $search_str);

        $data = $this->getListForm($manager);
        $data .= $this->getList($manager, $in, $by);

        return $data;
    }

    // reassing to display search filter block 
    function &getLeftMenu($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('search_form_filter.html'));
        
        if($manager->getSetting('view_format') == 'fixed') {
            $tpl->tplSetNeeded('/sidebar');
        }
        
        $tpl->tplAssign('menu_title_top', $this->msg['menu_title_msg']);
    
        $fmap = [
            'type' => [
                'title' => $this->msg['search_type_msg'],
                'search_in' => 'in',
            ], 
            'cat' => [
                'title' => $this->msg['category_msg'],
                'search_in' => 'c',
            ],
            'entry_type' => [
                'title' => $this->msg['entry_type_msg'],
                'search_in' => 'et',
            ]
        ];
    
        $params = $this->getSearchParams(true);
        $params_hidden = $params_link = $params;
        $params_hidden['s'] = 1;
        if(!$this->controller->mod_rewrite) {
            $params_hidden['View'] = 'search';
        }
        
        unset($params_hidden['in'], $params_hidden['c'], $params_hidden['et'], $params_hidden['custom']);
        
        $sign = ($this->controller->mod_rewrite) ? '?' : '&';
        $link = $this->getLink('search');
        $tpl->tplAssign('action_link', $link);
        
        $hidden = http_build_hidden($params_hidden, false);
        $hidden = preg_replace('/id="date_(\w+)/', '$0' . '_2', $hidden); // replace id for date fileds not to parse it
        $tpl->tplAssign('hidden', $hidden);
        
        $filter = [];
        $filter['type'] = $this->left_filter_data['type'];
        
        if(!isset($params['in'])) {
            $params['in'] = array($manager->getSetting('search_default'));
        }
        
        $search_filter_default = $manager->getSetting('search_filter_default');
        $tpl->tplAssign('filter_default', $search_filter_default);
        
        $_in = $params['in'][0];
        if($search_filter_default && $_in == 'all') {
            $_in = ($search_filter_default == 1) ? 'article' : 'file';
            $tpl->tplAssign('filter_default_id', 'filter_in_' . $_in);
        }
        
        if($this->left_filter_data['data']) {
            $filter += $this->left_filter_data['data'][$_in];
            $filter = array_filter($filter);
            $smanager = $this->left_filter_data['managers'][$_in];
        } 
        
        $tmap = [
            'article' => 'entry_title_msg',
            'file' => 'file_title_msg',
            'attachment' => 'file_title_msg',
            'news' => 'news_title_msg'
        ];

        // type
        if(isset($filter['type'])) {
            foreach($filter['type'] as $type => $num) {
                $ilimit = KBClientSearchModel::$filter_limit;
                $num = ($ilimit > 0 && $num > $ilimit) ? '>' . $ilimit : $num;
                $filter['type'][$type] = [$type, $this->msg[$tmap[$type]], $num];
            }
        }
        
        // categories
        if(!empty($filter['cat'])) {
            foreach($filter['cat'] as $cat_id => $num) {
                $parents = TreeHelperUtil::getParentsById($smanager->categories, $cat_id, 'name');
                $parents = $this->stripVars($parents);
                $cat_full = (count($parents) > 1) ? implode(' > ', $parents) : '';
                $cat_sign = (count($parents) > 1) ? '> ' : ''; 
                $filter['cat'][$cat_id] = [$cat_id, $cat_sign . end($parents), $num, $cat_full];
            }
        }
        
        // entry type
        if(!empty($filter['entry_type'])) {
            $type = ListValueModel::getListRange('article_type', false);
            $type = $this->stripVars($type);
            foreach($filter['entry_type'] as $type_id => $num) {
                $filter['entry_type'][$type_id] = [$type_id, $type[$type_id], $num];
            }
        }
        
        // custom fields
        if(!AppPlugin::isPlugin('fields')) {
            unset($filter['custom']);
        }
        
        if(!empty($filter['custom'])) {
            $_rtype = array_search($_in, $manager->record_type);
            $cf_manager = new CommonCustomFieldModel($manager);
            $fields = $cf_manager->getCustomFieldByEntryType($_rtype, true);
            $fields = $this->stripVars($fields);
            $ranges = $cf_manager->getCustomFieldRanges();
            
            foreach($filter['custom'] as $custom_id => $v) {
                $f = $fields[$custom_id];
                $fmap['custom'][$custom_id] = [
                    'title' => $f['title'],
                    'search_in' => 'custom['. $custom_id .']'
                ];
            
                foreach($v as $value => $num) {
                    $title = ($f['range_id']) ? $ranges[$custom_id][$value] : $f['caption'];
                    $filter['custom'][$custom_id][$value] = [$value, $title, $num, $f['tooltip']];
                }
            }
        }
        
        // echo '<pre>' . print_r($fmap, 1) . '</pre>';
        // echo '<pre>' . print_r($fields, 1) . '</pre>';
        // echo '<pre>' . print_r($filter, 1) . '</pre>';
        // echo '<pre>' . print_r($custom_count, 1) . '</pre>';
        // exit;
        
        $filters = unserialize($manager->getSettings(2, 'search_filter_item'))['active'];
        array_unshift($filters, 'type');
        
        foreach($filters as $block_key) {
            if(!isset($filter[$block_key])) {
                continue;
            }
            
            if($block_key == 'custom') {
                foreach($fmap[$block_key] as $block_key2 => $block_value2) {
                    $this->_parseFilterBlock($tpl, $filter['custom'][$block_key2], $fmap['custom'][$block_key2], $params, $block_key2);
                }
            } else {
                $this->_parseFilterBlock($tpl, $filter[$block_key], $fmap[$block_key], $params, $block_key);
            }
        }    
    
        if($filter) {
            $tpl->tplSetNeeded('/apply');
        }
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _parseFilterBlock(&$tpl, $block, $fmap, $params, $block_key) {
        usort($block, function($a, $b) {
            return $b[2] <=> $a[2];
        });
        
        foreach($block as $sparam => $v) {
            $search_in = $fmap['search_in'];
            $value = $v[0];

            $item['value'] = $value;
            $item['name'] = $v[1];
            $item['num'] = $v[2];
            $item['tooltip'] = !empty($v[3]) ? $v[3] : '';
            $item['search_in'] = $search_in;
            $item['id_in'] = str_replace(['[',']'], ['_', ''], $search_in);
            
            $checked = 0;
            if(strpos($search_in, '[') !== false) {
                $search_in_ = preg_replace('/\[\d+\]/', '', $search_in);
                if(isset($params[$search_in_][$block_key])) {
                    $checked = in_array($value, $params[$search_in_][$block_key]);
                }
            } else {
                if(isset($params[$search_in])) {
                    $checked = in_array($value, $params[$search_in]);
                }
            }    
            
            $item['checked'] = $this->getChecked($checked);
            
            $tpl->tplParse($item, 'row/row_in');
        }
        
        $tpl->tplSetNested('row/row_in');
        $tpl->tplParse(['title' => $fmap['title']], 'row');
    }


    function getListForm($manager) {
        
        $this->controller->getView('search_form');
        $view_sf = new KBClientView_search_form($manager);
        $content = $view_sf->getForm($manager, false);
        
        //xajax
        $ajax = &$this->getAjax('search');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getCategories', $ajax, 'ajaxGetCategories'));

        return $content;
    }


    function getLimitVars($bp) {
        
        $ret = array(
            'limit' => $bp->limit,
            'offset' => $bp->offset,
            'slice_offset' => 0
        );
        
        $multiple = ($this->engine_name == 'mysql');
        if($multiple && $bp->cur_page > 1) {
            $ret = array(
                'limit' => $bp->limit * $bp->cur_page,
                'offset' => 0,
                'slice_offset' => $bp->limit * $bp->cur_page - $bp->limit
            );
        }
        
        return $ret;
    }


    // parse data with all
    function &getList($manager, $in, $by) {

        $num_per_page = $this->num_per_page;

        $trows2 = array(
            'article' => array('index',  'entry',    false,   'article', $this->msg['entry_title_msg']),
            'file'    => array('files',  'download', 'file',  'file',    $this->msg['file_title_msg']),
            'news'    => array('news',   'news',     'news',  'news',    $this->msg['news_title_msg']),
            'attachment'  => array('index',  'entry',    false,   'attachment',  $this->msg['attachments_msg'])
        );
        
        $search = $this->getSearchParams();    
        $search['in'] = $in;
        $search['by'] = $by;     
        
        // try by article id first
        if($manager->getSetting('search_article_id') == 2) {
            $sparams = $this->getSearchParams();
            if($this->isSearchableById($sparams)) {
                $sparams = $this->getParamsToSearchById($sparams, $in, $by);
                $this->controller->go('search', false, false, false, $sparams);
            }
        }
        
        $trows = array();
        
        $sengine = $this->getSearchEngine($manager);
        $smanager = $sengine->getManager($manager, $search, 'all');
        
        $bp = $this->getPageByPage($num_per_page);
        $limits = $this->getLimitVars($bp);
        
        list($count, $rows, $managers, $info) 
            = $sengine->getSearchData($manager, $this->controller,
                                        $search, $limits['limit'], $limits['offset']);
        
        // will be used for filters on the left
        $this->left_filter_data = [
            'type' => array_filter($count),
            'data'  => $info,
            'managers' => $managers
        ];
        
        // echo '<pre>' . print_r($count, 1) . '</pre>';
        // echo '<pre>' . print_r($rows, 1) . '</pre>';
        // echo '<pre>' . print_r($managers, 1) . '</pre>';
        // echo '<pre>' . print_r($info, 1) . '</pre>';
        
        // articles
        if(!empty($rows['article'])) {
            $rows['article'] = $this->stripVars($rows['article']);
            
            $article_type = ListValueModel::getListRange('article_type', false);
            $full_categories['article'] = &$manager->getCategorySelectRangeFolow();
            $full_categories['article'] = $this->stripVars($full_categories['article']);

            foreach(array_keys($rows['article']) as $entry_id) {
                // @$score = $rows['article'][$entry_id]['score'];
                $score = $this->getSortField($rows['article'][$entry_id], $search);
                $trows[] = array($score, 'article', $entry_id, 1);
            }
        }

        // attachments
        if(!empty($rows['attachment'])) {
            $rows['attachment'] = $this->stripVars($rows['attachment']);
            
            if(empty($article_type)) {
                $article_type = ListValueModel::getListRange('article_type', false);
            }

            foreach(array_keys($rows['attachment']) as $entry_id) {
                // @$score = $rows['attachment'][$entry_id]['score'];
                $score = $this->getSortField($rows['attachment'][$entry_id], $search);
                $trows[] = array($score, 'attachment', $entry_id, 2);
            }
        }
        
        // files
        if(!empty($rows['file'])) {
            $rows['file'] = $this->stripVars($rows['file']);
            
            $full_categories['file'] = $managers['file']->getCategorySelectRangeFolow();
            $full_categories['file'] = $this->stripVars($full_categories['file']);

            foreach(array_keys($rows['file']) as $entry_id) {
                // @$score = $rows['file'][$entry_id]['score'];
                $score = $this->getSortField($rows['file'][$entry_id], $search);
                $trows[] = array($score, 'file', $entry_id, 2);
            }
        }

        // news
        if(!empty($rows['news'])) {
            $rows['news'] = $this->stripVars($rows['news']);
            
            foreach(array_keys($rows['news']) as $entry_id) {
                // @$score = $rows['news'][$entry_id]['score'];
                $score = $this->getSortField($rows['news'][$entry_id], $search, 'news');
                $trows[] = array($score, 'news', $entry_id, 3);
            }
        }
        
        uasort($trows, 'kbpSortByScore');
        $trows_count = array_slice($trows, $limits['slice_offset'], $num_per_page+1, true);
        $trows = array_slice($trows, $limits['slice_offset'], $num_per_page, true);
        
        // log()
        if(!$this->controller->isAjaxCall()) { 
            $_map = $smanager->record_type + [0=>'all'];
            $search_type = implode(',', array_keys(array_intersect($_map, $in)));
            $exitcode = (count($trows_count) > 10) ? 11 : count($trows);
            $smanager->logUserSearch($_GET, $search_type, $exitcode);
        }

        // parse
        $tpl = new tplTemplatez($this->getTemplate('search_list.html'));
        $this->parseResultPadding($tpl, $manager);
        $tpl->tplSetNeededGlobal('show_date');

        // no rows
        if(!$trows) {
            
            // firtst search in content, not found, try by id
            if($manager->getSetting('search_article_id') == 1) {
                $sparams = $this->getSearchParams();
                if($this->isSearchableById($sparams)) {
                    $sparams = $this->getParamsToSearchById($sparams, $in, $by);
                    $this->controller->go('search', false, false, false, $sparams);
                }
            }
            
            $tpl->tplAssign('msg', $this->getNoRecordsMsg());
        

        // has rows
        } else {

            // one row,  can redirect to article
            if(count($trows) == 1 && array_intersect($in, array('all', 'article')) && $by == 'id') {
                
                $redirect = true;
                $record_type = $trows[0][1];
                $row = $rows[$record_type][$entry_id];

                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                if($private) {
                    if($manager->getSetting('private_policy') == 2) { // 2 = display with lock sign
                        if(!$manager->isUserPrivIgnorePrivate()) {
                            $redirect = false;
                        }
                    }
                }

                if($redirect) {
                    $this->controller->go('entry', $row['category_id'], $row['id']);
                }
            }

        
            $keywords = $smanager->getKeywords();
            
            foreach(array_keys($trows) as $k) {

                $trow = $trows[$k];
                $entry_id = $trow[2];
                $record_type = $trow[1];
                $trow2 = $trows2[$record_type];
                $row = $rows[$record_type][$entry_id];

                if($record_type === 'news') {
                    $row['category_link'] = $this->getLink($trow2[0], $row['category_id']);
                    
                    // required for rewrite
                    $row['category_link'] = preg_replace("#(news/)(\d{4})#", "$1c$2", $row['category_link']);
                    $row['full_category'] = $trow2[4].' -> '.$row['category_id'];
                
                } elseif($record_type !== 'attachment') {
                    $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
                    $row['category_link'] = $this->getLink($trow2[0], $cat_id);
                    $row['full_category'] = $trow2[4].' -> '.$full_categories[$record_type][$row['category_id']];
                }

                $private = $this->isPrivateEntry($row['private'], $row['category_private']);
                $ext = false;
                if(isset($row['filename'])) {
                    $ext = _substr($row['filename'], _strrpos($row['filename'], ".")+1);
                }
                
                $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $record_type, $ext);
                $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);

                $entry_id = $row['id'];
                if($record_type !== 'file') {
                    $url_title = (isset($row['url_title'])) ? $row['url_title'] : '';
                    $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $url_title);
                }
                
                $row['entry_link'] = $this->getLink($trow2[1], false, $entry_id);

                if($record_type === 'article') {
                    $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $article_type, $manager);
                }

                if($record_type === 'file') {
                    
                    $summary_limit = $this->getSearchSummaryLimit($manager, $private);
                    $title = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                    $filename = $smanager->highlightTitle($row['filename'], $_GET['q'], $keywords);
                    $description = $smanager->highlightBody($row['description'], $_GET['q'], $keywords, $summary_limit);
                    
                    $row['title'] = (empty($title)) ? $filename : $title;
                    $row['body'] = (empty($title)) ?  '' : '<b>' . $filename . '</b><br />';
                    $row['body'] .= nl2br($description);
                    
                    $row['entry_link_options'] = 'target="_blank"';
                    if($this->isPrivateEntryLocked($manager->is_registered, $private)) {
                        $row['entry_link_options'] = '';
                    }
                    
                    $row['entry_link'] = $this->getLink('file', $this->category_id, $row['id'], false, array('f'=>1));
                    $row['download_link'] = $this->getLink('file', $this->category_id, $row['id']);
                    
                    $tpl->tplSetNeeded('row/download_link');

                } elseif($record_type === 'attachment') {
                    
                    // change category to article 
                    $row['category_link'] = $row['entry_link'];
                    $row['full_category'] = $row['title'];
                    
                    $summary_limit = $this->getSearchSummaryLimit($manager, $private);
                    $title = $smanager->highlightTitle($row['file_title'], $_GET['q'], $keywords);
                    $filename = $smanager->highlightTitle($row['filename'], $_GET['q'], $keywords);
                    $description = $smanager->highlightBody($row['file_description'], $_GET['q'], $keywords, $summary_limit);
                    
                    $row['title'] = (empty($title)) ? $filename : $title;
                    $row['body'] = (empty($title)) ?  '' : '<b>' . $filename . '</b><br />';
                    $row['body'] .= nl2br($description);
                    
                    $row['entry_link_options'] = 'target="_blank"';
                    if($this->isPrivateEntryLocked($manager->is_registered, $private)) {
                        $row['entry_link_options'] = '';
                    }
                    
                    $msg_id = ''; // ? 
                    $more = array('AttachID' => $row['file_id']);                    
                    $row['download_link'] = $this->getLink('afile', false, $row['id'], $msg_id, $more, 1);
                    
                    $more['f'] = 1;
                    $row['entry_link'] = $this->getLink('afile', false, $row['id'], $msg_id, $more, 1);
                    
                    $tpl->tplSetNeeded('row/download_link');

                } else {
                    $summary_limit = $this->getSearchSummaryLimit($manager, $private);
                    $row['title'] = $smanager->highlightTitle($row['title'], $_GET['q'], $keywords);
                    $row['body'] = $smanager->highlightBody($row['body'], $_GET['q'], $keywords, $summary_limit);
                }

                $row['article_staff_padding'] = 3;
                $tpl->tplParse($row, 'row');
            }
        }


        // search results
        $params = $this->getSearchParams(false);
        $params['s'] = 1;
        if(isset($count['attachment'])) {
            @$count['file'] += $count['attachment'];
            unset($count['attachment']);
        }
        
        foreach($count as $k => $v) {
            if ($v == 0) {
                continue;
            }
            
            $a = array();
            $trow2 = $trows2[$k];

            $params2 = $params;
            $params2['in'] = array(''=>$trow2[3]);
            $sign = ($this->controller->mod_rewrite) ? '?' : '&';
            $link = $this->getLink('search') . $sign . http_build_query($params2);

            $a['search_in_link'] = $link;
            $a['search_in_title'] = $trow2[4];

            $tpl->tplParse($a, 'row_count');
        }

        // sorting 
        $sort = (KBClientSearchHelper::isOrderByScore($search)) ? 'rel' : 'date';
        $sort = (@$params['sort'] == 'date') ? 'date' : $sort;
        $sort_types = array(
            'rel' => $this->msg['sort_by_msg'] . ': ' . $this->msg['sort_by_relevancy_msg'],
            'date' => $this->msg['sort_by_msg'] . ': ' . $this->msg['sort_by_date_msg'],
        );
        
        if(1) {
            $tpl->tplSetNeeded('/type_switch');
            
            foreach ($sort_types as $key => $title) {
                $v = array();
            
                $v['key'] = $key;
                $v['title'] = $title;
                $v['class'] = ($sort == $key) ? 'selected' : '';
            
                $params2 = $params;
                $params2['sort'] = $key;
                $sign = ($this->controller->mod_rewrite) ? '?' : '&';
                $v['link'] = $this->getLink('search') . $sign . http_build_query($params2);
            
                $tpl->tplParse($v, 'type_switch_row');
            }
        }
        // <-- sorting

        // spell suggest
        $this->parseSpellSuggestData($tpl, $manager);
        
        $bp->countAll(array_sum($count));

        $count_limit = $smanager->count_limit*3;
        $tpl->tplAssign('page_by_page', $this->getSearchResult($bp, $count_limit));
        if($this->isPageByPageBottom($bp)) {
            $tpl->tplAssign('page_by_page_bottom', $bp->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');
        }

        // $tpl->tplAssign('views_num_msg', $this->msg['views_num_msg']);
        // $tpl->tplAssign('comment_num_msg', $this->msg['comment_num_msg']);
        $tpl->tplAssign('list_title', $this->msg['search_result_msg']);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getSortField($row, $values, $entry_type = false) {
        $val = (isset($row['score'])) ? $row['score'] : $row['ts_updated'];
        if(isset($values['sort']) && $values['sort'] == 'date') {
            $val = $row['ts_updated'];
            if($entry_type == 'news') {
                $val = $row['ts_posted'];
            }
        }
        
        return $val;
    }


    // will be used in quick responce
    function &getEntryListQuickResponce($manager, $values) {
        
        $this->engine_name = $this->getSearchEngineName($manager, 'always');
        
        if ($this->engine_name == 'sphinx') {
            $keywords = explode(' ', $values['q']);
            $values['q'] = implode(' | ', $keywords);
        }
        
        $values['by'] = 'all';
        
        $sengine = $this->getSearchEngine($manager, $values);
        $smanager = $sengine->getManager($manager, $values, 'article');
        
        // we may need to move this inside getArticleSearchData etc.
        if ($this->engine_name == 'sphinx') {
            $smanager->smanager->setIndexParams('article');
        }
        
        list($count, $rows) = $smanager->getArticleSearchData(5, 0);
        
        // $rows = $this->stripVars($rows, array('body'), 'not_display_123');

        if(!$rows) {
            $a = false; return $a;
        }

        $utf_replace = true;
        if(strtolower($this->encoding) != 'utf-8') {
            $utf_replace = false;
        }

        if($utf_replace) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';
        }


        $tpl = new tplTemplatez($this->template_dir . 'article_list_responce.html');
        $this->parseResultPadding($tpl, $manager);

        // entry_type
        $type = ListValueModel::getListRange('article_type', false);

        $full_categories = &$manager->getCategorySelectRangeFolow();
        $full_categories = $this->stripVars($full_categories);
        
        $keywords = $smanager->getKeywords();
        
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];

            $cat_id = $this->controller->getEntryLinkParams($row['category_id'], $row['category_name']);
            $row['category_link'] = $this->getLink('index', $cat_id);
            $row['full_category'] = $full_categories[$row['category_id']];

            $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            $row['item_img'] = $this->_getItemImg($manager->is_registered, $private);

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $row['category_id'], $entry_id);

            $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);
            $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);

            $summary_limit = $this->getSearchSummaryLimit($manager, $private);
            $row['title'] = $smanager->highlightTitle($row['title'], $values['q'], $keywords);
            $row['body'] = $smanager->highlightBody($row['body'], $values['q'], $keywords, $summary_limit);

            // 2011-01-G without this if bad utf8 IE gives xml error,
            // firefox does not display anything
            if($utf_replace) {
                if(!utf8_compliant($row['title'])) {
                    $row['title'] = utf8_bad_replace($row['title'], '?');
                }

                if(!utf8_compliant($row['body'])) {
                    $row['body'] = utf8_bad_replace($row['body'], '?');
                }
            }

            $tpl->tplParse($row, 'row');
        }


        $tpl->tplAssign('base_href', $this->controller->kb_path);
        $tpl->tplAssign('msg', $this->getActionMsg('success', 'quick_response', 'xml'));
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }


    function getSearchEngine($manager) {
        return KBClientSearchEngine::factory($this->engine_name);
    }
    
    
    function getSearchEngineName($manager, $search_str) {
        $sphinx = SphinxModel::isSphinxOnSearch($search_str, $manager->setting);
        return ($sphinx) ? 'sphinx' : 'mysql';
    }


    function getRelevancy($score, $cur_page, $first_row) {

        if($cur_page == 1 && empty($first_row)) {
            $_SESSION['biggest_score_'] = $score;
        }

        if($_SESSION['biggest_score_'] != 0) {
            $score = $score/$_SESSION['biggest_score_']*100;
        } else {
            $score = '100'; // if only one record
        }

        $ret = sprintf('%01.2f%s ', $score, '%'); // [%01.0f%s]

        return $ret;
    }


    function getNoRecordsMsg() {
        
        $str = '<a href="%s">%s</a>';
        $vars['link'] = sprintf($str, $this->getLinkToSearchForm(), $this->msg['advanced_search_msg']);
        return AppMsg::afterActionBox('no_search_result', 'hint', 'public', $vars);
    }


    function getSearchParams($strip = true) {
        return KBClientSearchHelper::getSearchParams($strip);
    }


    function isSearchableById($sparams) {
        $p = $sparams;
        if(empty($p['by'])) {
            $p['by'] = 'all';
        }
        
        $ret = ($p['by'] == 'all' && is_numeric($p['q']));
        return $ret;
    }
    
    
    function getParamsToSearchById($sparams, $in, $by) {
        unset($sparams['View']);
        // $sparams['in'] = ($in == 'all') ? 'article' : $in;
        $sparams['in'] = ($in == 'all' || in_array('all', $in)) ? ['article'] : $in;
        $sparams['by'] = 'id';
        $sparams['by_set'] = $by;
        $sparams['s'] = 1;
        
        return $sparams;
    }
    

    function getLinkToSearchForm($msg = false) {
        $sparams = $this->getSearchParams(false);
        $sign = ($this->controller->mod_rewrite) ? '?' : '&';
        return $this->controller->getLink('search', false, false, $msg) . $sign . http_build_query($sparams);
    }


    function goToSearchForm($msg = false) {
        $link = $this->controller->_replaceArgSeparator($this->getLinkToSearchForm($msg));
        header("Location: " . $link);
        exit;
    }


    function goToSearchFormNoRecords() {
        $link = $this->controller->_replaceArgSeparator($this->getLinkToSearchForm('no_search_result'));
        header("Location: " . $link);
        exit;
    }
    
    
    // no summary if private
    function getSearchSummaryLimit($manager, $private, $limit = false) {
        $limit = ($limit === false) ? $manager->getSetting('search_preview_limit') : $limit;
        return $this->getSummaryLimit($manager, $private, $limit);
    }
    
    
    function parseResultPadding($tpl, $manager) {
        $article_description_padding = 3;
        if($manager->getSetting('search_preview_limit') == 0) {
            $article_description_padding = 0;
        }
        $tpl->tplAssign('article_description_padding', $article_description_padding);
    }


    // BY PAGE // -------------------------------

    function &getPageByPage($limit, $multiple = false) {

        $bp = $this->getPageByPageObj('page', $limit, $_GET);
        if($multiple) {
            $bp->setMultiple(4);
        }

        return $bp;
    }


    function getSearchResult($bp, $count_limit) {
        $msg = $this->msg['search_found_msg'];
        if($count_limit <= $bp->num_records) {
            $msg = $this->msg['search_found_about_msg'];
        }

        return sprintf($msg, $bp->num_records);
    }


    function isPageByPageBottom($bp) {
        return ($bp->num_pages > 1);
    }


    // SPELL SUGGEST // ---------------------------

    // return spell suggest if any, false otherwise
    function spellSuggest($str, $manager) {
        $ret = false;

        // ignore too small or empty
        $str = trim($str);
        if(empty($str) || _strlen($str) <= 3) {
            return $ret;
        }
        
        // ignore suggest, clicked on search instead
        if(isset($_COOKIE['kb_spell_ignore_'])) {
            if($_COOKIE['kb_spell_ignore_'] == md5($str)) {
                return $ret;
            }
        }

        $spell_checker = $manager->getSetting('search_spell_suggest');

        // validate, enchant could be exported from cloud
        $checkers = array('pspell', 'enchant'); 
        if(in_array($spell_checker, $checkers)) {
            $method = sprintf('validate%s', ucwords($spell_checker));
            $val = PublicSetting\SettingValidator::$method($manager->setting);
            if(is_array($val)) {
                return $ret;
            }
        }

        $method = sprintf('get%sSuggest', ucwords($spell_checker));
        if (method_exists($this, $method)) {
            $ret = $this->$method($str, $manager);
        }
        
        if (!empty($ret)) {
            $best = key($ret);
            return ($str == $best) ? false : $best;
        }
        
        return $ret;
    }


    function getPspellSuggest($str, $manager) {
        
        $dictionary = $manager->getSetting('search_spell_pspell_dic');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);

        $spell = SpellSuggest::factory('pspell');
        return $spell::suggest($dictionary, $custom_words, $str);
    }


    function getBingSuggest($str, $manager) {
        
        $key = $manager->getSetting('search_spell_bing_spell_check_key');
        $url = $manager->getSetting('search_spell_bing_spell_check_url');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);
        
        $spell = SpellSuggest::factory('bing');
        return $spell::suggest($key, $url, $str, $custom_words);
    }


    function getEnchantSuggest($str, $manager) {
        
        $provider = $manager->getSetting('search_spell_enchant_provider');
        $dictionary = $manager->getSetting('search_spell_enchant_dic');
        
        $custom_words = $manager->getSetting('search_spell_custom');
        $custom_words = explode(' ', $custom_words);

        $spell = SpellSuggest::factory('enchant');
        return $spell::suggest($provider, $dictionary, $custom_words, $str);
    }


    function getspellsuggestdata() {

        $suggest = $this->spell_suggest;
        $mistake = $this->spell_mistake;

        $a = array();

        $sign = ($this->controller->mod_rewrite) ? '?' : '&';
        $params = $this->getSearchParams(false);
        $params['s'] = 1;

        $params['q'] = $suggest;
        $link = $this->getLink('search') . $sign . http_build_query($params);
        $a['suggest_link'] = $link;
        $a['suggest_str'] = $this->stripVars($suggest, array());

        $params['q'] = $mistake;
        $link = $this->getLink('search') . $sign . http_build_query($params);
        $a['mistake_link'] = $link;
        $a['mistake_link_escaped'] = $link;
        
        $a['mistake_str'] = $this->stripVars($mistake, array());
        $a['mistake_str_encoded'] = md5($mistake);
        $a['mistake_str_escaped'] = addslashes($a['mistake_str']);

        // save icon
        $mistake_str = sprintf('<strong>%s</strong>', $a['mistake_str']);
        $title_str = sprintf($this->msg['add_spell_exclude_msg'], $mistake_str);
        $a['title_str'] = $title_str;

        return $a;
    }
    
    
    function parseSpellSuggestData($tpl, $manager) {
        
        if(AuthPriv::getUserId()) {
            $auth = new AuthPriv;
            if($auth->isPriv('update', 'setting')) {
                $tpl->tplSetNeeded('spell_suggest/dictionary_link');
                
                $ajax = &$this->getAjax('search');
                $xajax = &$ajax->getAjax($manager);
        
                $xajax->registerFunction(array('addToSpellExcludeDisctionary', $ajax, 'ajaxAddToSpellExcludeDisctionary'));
            }
        }

        if($this->spell_suggest) {
            $data = $this->getSpellSuggestData();
            $tpl->tplParse($data, 'spell_suggest');
        }
    }
    
}


function kbpSortByScore($a, $b) {
    return ($a[0] < $b[0]) ? 1 : 0;
}
?>
