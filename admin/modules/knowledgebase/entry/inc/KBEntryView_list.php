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


class KBEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = array(
        1 => 'list_popup.html', 
        2 => 'list_popup_review.html', //? do we need this
        3 => 'list_popup_featured.html', 
    );
    var $columns = array(
        'id', 'private', 'schedule', 'mustread', 'date_posted', 'date_updated', 
        'title', 'category', 'hits','status'
    );
    var $columns_popup = array('id', 'private', 'title', 'category', 'status');


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

        $popup = $this->controller->getMoreParam('popup');
        $add_button = ($popup) ? false :  true;
        

        // add sort_order column 
        @$fcategory = $_GET['filter']['c'];
        if(!empty($fcategory) && $fcategory != 'all') {
            array_splice($this->columns, count($this->columns), 0, 'sort_order');
        }
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);

        // autosave message
        if ($this->setting['entry_autosave'] && !$show_msg2) {
            if($manager->isAutosaved(0, '2011-01-01')) {
                $tpl->tplAssign('msg', KBEntryView_common::getDraftsMessage($this, 'kb_autosave'));
            }
        }

        // check
        $update_allowed = true;
        $bulk_allowed = array();
        $au = KBValidateLicense::getAllowedEntryRest($manager);
        if($au !== true) {
            if($au <= 0) {
                $key = ($au == 0) ? 'license_limit_entry' : 'license_exceed_entry';
                $tpl->tplAssign('msg', AppMsg::licenseBox($key));
                $add_button = false;

                if($key == 'license_exceed_entry') {
                    $update_allowed = false;
                    $bulk_allowed = array('delete');
                }
            }
        }

        // bulk
        $manager->bulk_manager = new KBEntryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'KBEntryView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }

        // filter sql
        $categories = $manager->getCategoryRecords();
        $params = $this->getFilterSql($manager, $categories);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        $manager->setSqlParamsJoin($params['join']);
        $manager->sql_params_group =  $params['group'];

        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // bp
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);

        // xajax
        $this->bp = $bp;
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        // header generate
        $button = ($add_button) ? CommonEntryView::getButtons($this, $xajax, 'kb_draft') : array();
        $tpl->tplAssign('header',
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories, $xajax), $button));


        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id');

        // categories
        $entry_categories = ($ids) ? $manager->getCategoryByIds($ids) : array();
        $entry_categories = $this->stripVars($entry_categories);
        // echo "<pre>"; print_r($entry_categories); echo "</pre>";

        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);
        //echo "<pre>"; print_r($full_categories); echo "</pre>";

        $this->full_categories = $full_categories;

        // users
        $author_ids = $manager->getValuesArray($rows, 'author_id');
        $updater_ids = $manager->getValuesArray($rows, 'updater_id');
        $users = array();
        if($author_ids || $updater_ids) {
            $users = implode(',', array_unique(array_merge($author_ids, $updater_ids)));
            $users = $manager->getUser($users, false);
            $users = $this->stripVars($users);
        }

        // roles to entry
        $roles_range = $manager->getRoleRangeFolow();

        $roles = ($ids) ? $manager->getRoleById($ids, 'id_list') : array();
        $roles = $this->parseEntryRolesMsg($roles, $roles_range);

        $category_ids = $manager->getValuesString($rows, 'category_id', true);
        $category_roles = ($category_ids) ? $manager->cat_manager->getRoleById($category_ids, 'id_list') : array();
        $category_roles = $this->parseEntryCategoryRolesMsg($category_roles, $roles_range);

        // other
        $mustreads = array();
        if($ids && AppPlugin::isPlugin('mustread')) {
            $mustreads = $manager->mr_manager->getMustreadByEntryIds($ids);
        }
        $schedules = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();
        $history = ($ids) ? $manager->getHistoryNum($ids) : array();
        $comments_num = ($ids) ? $manager->getCommentsNum($ids) : array(); 
        $rcomments_num = ($ids) ? $manager->getRatingCommentsNum($ids) : array(); 
        $attachments_num = ($ids) ? $manager->getAttachmentsNum($ids) : array(); 

        $status = $manager->getEntryStatusData('article_status');
        $publish_status_ids = $manager->getEntryStatusPublished('article_status');
        $types = $this->stripVars($manager->getListSelectRange('article_type', false));
        $client_controller = &$this->controller->getClientController();

        $status_range = $manager->getListSelectRange('article_status', true);
        $status_allowed = $this->priv->getPrivStatusSet($status_range, 'status');
        $status_range = array_intersect($status_range, $status_allowed);

        // popup
        if(in_array($popup, array(1, 'ckeditor'))) {
            $tpl->tplSetNeededGlobal('insert');
        }

        if($popup && empty($_GET['no_attach'])) {
            $tpl->tplSetNeededGlobal('attach');
        }

        if($popup == 3) {
            $featured_url = $this->getLink('knowledgebase', 'kb_featured', false, false);
            $featured_url = $this->controller->_replaceArgSeparator($featured_url);
            $tpl->tplAssign('featured_url', $featured_url);
        }
        
        $tpl->tplAssign('do_confirm', ($popup == 'ckeditor') ? 'false' : 'true');

        if (in_array($popup, array(1, 'text', 'public'))) {
            $tpl->tplSetNeeded('/close_button');
        }

        // create an empty box for a message block
        if ($popup) {
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());

            $menu_msg = AppMsg::getMenuMsgs('knowledgebase');
            $tpl->tplAssign('popup_title', $menu_msg['kb_entry']);
        }

        // list records
        foreach($rows as $row) {

            if ($row['id'] == $this->controller->getMoreParam('exclude_id')) {
                continue;
            }

            $obj->set($row);
            
            $row['sort_order'] = $row['real_sort_order'];
            
            $row['votes_num'] = ($row['votes']) ? $row['votes'] : '';
            $row['rating'] = $this->getRatingImg($row['rating'], $row['votes']);
            
            $row['type_title'] = ($row['entry_type']) ? $types[$row['entry_type']] : '';
            $row['type'] = ($row['entry_type']) ? $this->getSubstringSignStrip($types[$row['entry_type']], 6) : '';

            // title
            $row += $this->getTitleToList($row['title'], 100);
            $row['escaped_title'] = $this->getSubstringJsEscape($row['title'], 100);// for popup window

            // mustread
            $mustread = ExtFunc::arrayValue($mustreads, $obj->get('id'));
            $row += CommonEntryView::getMustreadToList($mustread, $this, $manager);

            // schedule
            $schedule = ExtFunc::arrayValue($schedules, $obj->get('id'));
            $row += CommonEntryView::getScheduleToList($schedule, $status, $this);
            
            // roles
            $role = ExtFunc::arrayValue($roles, $obj->get('id'));
            $category_role = ExtFunc::arrayValue($category_roles, $obj->get('category_id'));
            $row += CommonEntryView::getPrivateToList($row, $role, $category_role, $this->msg);

            // users
            $author = ExtFunc::arrayValue($users, $obj->get('author_id'));
            $row += $this->getUserToList($author, 'author');
            
            $updater = ExtFunc::arrayValue($users, $obj->get('updater_id'));
            $row += $this->getUserToList($updater, 'updater');
            
            // dates
            $row += $this->getDateToList($row['date_posted'], 'date_posted', 'date', $author);
            if(strtotime($row['date_updated']) - strtotime($row['date_posted'])) {
                $row += $this->getDateToList($row['date_updated'], 'date_updated', 'date', $updater);
            }
            
            // attachments
            $attachment_num = '--';
            if(isset($attachments_num[$row['id']])) {
                $link = $this->getLink('file', 'file_entry', false, false, array('filter[q]'=>'attached:'.$row['id']));
                $row['attachment_link'] = $link;
                $row['attachment_num'] = $attachments_num[$row['id']];
            }

            // comments
            if(isset($comments_num[$row['id']])) {
                $link = $this->getLink('knowledgebase', 'kb_comment', false, false, array('entry_id'=>$row['id']));
                $row['comment_link'] = $link;
                $row['comment_num'] = $comments_num[$row['id']];
            }

            // rating comments
            if(isset($rcomments_num[$row['id']])) {
                $link = $this->getLink('knowledgebase', 'kb_rate', false, false, array('entry_id'=>$row['id']));
                $row['rating_comment_link'] = $link;
                $row['rating_comment_num'] = $rcomments_num[$row['id']];
            }

            // category
            $row['category'] = $this->getSubstringSignStrip($row['category_title'], 20);
            $cat_nums = count($entry_categories[$obj->get('id')]);
            $row['category_num'] = ($cat_nums > 1) ? "[$cat_nums]" : '';
            
            $more = array('filter' => array('c' => $row['category_id']));
            if($popup) { $more['popup'] = $popup; }
            $row['category_filter_link'] = $this->controller->getLink('all', '', '', '', $more);

            // full categories
            $_full_categories = array();
            $first_row = true;
            foreach(array_keys($entry_categories[$obj->get('id')]) as $cat_id) {
                $_full_category = ($first_row) ? sprintf('<b>%s</b>', $full_categories[$cat_id]) : $full_categories[$cat_id];

                if ($this->priv->isPriv('update', 'kb_category')) {
                    $more = array(
                        'id' => $cat_id,
                        'referer'=> WebUtil::serialize_url($this->controller->getCommonLink()));
                    $update_link = $this->getLink('knowledgebase', 'kb_category', false, 'update', $more);

                    $_full_category = sprintf('<a href="%s">%s</a>', $update_link, $_full_category);
                }

                $_full_categories[] = RequestDataUtil::stripVars($_full_category, array(), true);
                $first_row = false;
            }

            $row['category_title'] = implode('<br />',  $_full_categories);

            // featured
            if (isset($row['featured_index_order'])) {
                $this->parseFeatured($tpl, $row);
            }

            // status vars
            $st_vars = CommonEntryView::getViewListEntryStatusVars($obj->get(),
                                            $entry_categories[$obj->get('id')], $publish_status_ids, $status);
            $row += $st_vars;

            // actions/links
            $links = array();
            $link = $this->getActionLink('preview', $obj->get('id'), array('detail_btn'=>1));
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $links['entry_link'] = $this->controller->getPublicLink('entry', $obj->get());
            $links['history_link'] = $this->getActionLink('history', $obj->get('id'));

            // if some of categories is private
            // and user do not have this role so he can't update it
            $has_private = $manager->isCategoryNotInUserRole(array_keys($entry_categories[$obj->get('id')]));

            $actions = $this->getListActions($obj, $links, $manager,
                                             $has_private, $st_vars['published'],
                                             $update_allowed, $history, $status_range);

            $row += $this->getViewListVarsJsCustom($obj->get(), $actions, $manager,
                                                        $has_private, $st_vars['published']);

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplAssign($this->msg);
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html');
    }


    function parseFeatured(&$tpl, $row) {

        $a = array('category_id' => 0);

        $cats = array(
            'featured_index_order' => 0,
            'featured_cat_order' => $row['category_id']
            );

        foreach($cats as $k => $v) {
            $a = array();
            $a['category_id'] = $v;

            if ($row[$k]) {
                $a['new_value'] = 0;
                $a['assign_display'] = 'none';
                $a['remove_display'] = 'block';

            } else {
                $a['new_value'] = 1;
                $a['assign_display'] = 'block';
                $a['remove_display'] = 'none';
            }

            $tpl->tplParse($a, 'row/assign');
        }

        $tpl->tplSetNested('row/assign');
    }


    function getViewListVarsJsCustom($entry, $actions, $manager, $has_private, $is_published) {

        $own_record = ($entry['author_id'] == $manager->user_id);
        $status = $entry['active'];
        $row = $this->getViewListVarsJs($entry['id'], $status, $own_record, $actions);

        $row['entry_link'] = ($is_published) ? $actions['public2']['link'] : $actions['preview']['link'];
        $row['entry_link_option'] = ($is_published) ? 'target="_blank"' : '';

        if($has_private) {
            $row['bulk_ids_ch_option'] = 'disabled';
        }

        // featured
        if($this->controller->getMoreParam('popup') == 3) {
            $more = array('id' => $entry['id'], 'popup' => 1);
            $row['featured_link'] = $this->getLink('this', 'kb_featured', false, 'insert', $more);
        }

        return $row;
    }


    function getListActions($obj, $links, $manager, $has_private, $is_published, $update_allowed, $history, $status_range) {

        $record_id = $obj->get('id');
        $status = $obj->get('active');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        $actions = array('detail');

        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'],
            'link' => $links['preview_link'],
            'img'  => '');

        if($is_published) {
            $actions['public2'] = array( // will be skipped in action
                'msg'  => $this->msg['entry_public_link_msg'],
                'link' => $links['entry_link'],
                'link_attributes' => 'target="_blank"',
                'img'  => ''
            );
        }

        if(!$has_private) {
            $actions[] = 'clone';
            $actions[] = 'update';
            $actions[] = 'trash';
        
            foreach ($status_range as $k => $v) { // status
                $actions['status'][] = array(
                    'msg' => $v,
                    'value' => $k
                );
            }
        }

        // drafts
        $as_draft = false;
        if(!$has_private && $this->isEntryUpdateable($record_id, $status, $own_record)) {
            if($this->priv->isPriv('insert', 'kb_draft')) {
                $as_draft = true;
            }

            if($this->priv->isPrivOptional('update', 'draft')) {
                unset($actions[array_search('update', $actions)]);
                $as_draft = true;
            }
        }

        if($as_draft) {
            
            $rlink = $this->controller->getLink('all');
            $referer = WebUtil::serialize_url($rlink);
            
            $more = array('id' => $obj->get('id'), 'referer' => $referer);
            $link = $this->getLink('this', 'this', false, 'edit_as_draft', $more);
            
            $actions['draft'] = array(
                'msg'  => $this->msg['update_as_draft_msg'],
                'link' => $link,
                'img'  => '');
        }
        
        
        // history
        if(isset($history[$obj->get('id')]) && !$has_private) {
            $msg = sprintf('%s: %s', $this->msg['history_msg'], $history[$obj->get('id')]);
            $actions['history'] = array(
                'msg'  => $msg,
                'link' => $links['history_link'],
                'img'  => '');
        }    

        if($this->priv->isPrivOptional('insert', 'draft')) {
            unset($actions[array_search('clone', $actions)]);
        }

        // license
        if($update_allowed == false) {
            unset($actions[array_search('status', $actions)]);
            unset($actions[array_search('update', $actions)]);
            unset($actions[array_search('clone', $actions)]);
            unset($actions[array_search('draft', $actions)]);
        }

        $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        $actions = array_diff_key($actions, $pluginable);

        return $actions;
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('title', 1);
        $sort->setCustomDefaultOrder('sort_oder', 1);

        $article_sort_order = 'updated_desc';
        if(isset($this->setting['article_sort_order'])) {
            $article_sort_order = $this->setting['article_sort_order'];
        }

        $order = CommonEntryView::getSortOrderSetting($article_sort_order);
        $sort->setDefaultSortItem($order);

        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('posted_msg',  'datep', 'date_posted',  $this->msg['posted_msg']);
        $sort->setSortItem('updated_msg', 'dateu', 'date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('title_msg',   'title', 'title',        $this->msg['title_msg']);

        $sort->setSortItem('id_msg', 'id', 'e.id', $this->msg['id_msg']);
        $sort->setSortItem('votes_num_msg',  'votes', 'votes', $this->msg['votes_num_msg']);
        $sort->setSortItem('rating_msg',  'rating', 'rating', $this->msg['rating_msg']);
        $sort->setSortItem('hits_num_msg', 'hits', 'hits', $this->msg['hits_num_msg']);
        $sort->setSortItem('entry_type_msg', 'type', 'entry_type', $this->msg['entry_type_msg']);

        $sort->setSortItem('category_msg', 'cat', 'main_category', $this->msg['category_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_oder', 'real_sort_order', $this->msg['sort_order_msg']);

        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearch($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);
            }
        }

        //echo '<pre>', print_r($sort->getSql(), 1), '</pre>';
        return $sort;
    }


    function getFilter($manager, $categories, $xajax) {

        $values = $this->parseFilterVars(@$_GET['filter']);

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');


        $categories = $manager->getCategorySelectRangeFolow($categories); // private removed

        // category
        if(!empty($values['c'])) {
            $category_id = (int) $values['c'];
            $category_name = $this->stripVars($categories[$category_id]);
            $tpl->tplAssign('category_name', $category_name);
        } else {
            $category_id = 0;
        }

        $tpl->tplAssign('category_id', $category_id);

        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('categories', $js_hash);

        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));


        $select = new FormSelect();
        $select->select_tag = false;

        // type
        $select->setRange($manager->getListSelectRange('article_type', false),
                          array('all' => '__',
                                'none' => $this->msg['none_entry_type_msg'],
                                'any' => $this->msg['any_entry_type_msg'],
                                ));
        @$status = $values['et'];
        $tpl->tplAssign('type_select', $select->select($status));


        // status
        $select->setRange($manager->getListSelectRange('article_status', false),
                          array('all'=>'__'));
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));


        // custom
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));

        //if($this->controller->getMoreParam('popup') == 3) {
            $xajax->registerFunction(array('featureArticle', $this, 'ajaxFeatureArticle'));
        //}


        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }


    function getFilterSql(&$manager, $categories) {

        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];


        // category roles
        // probably we should not apply it in pop up window
        $mysql['where'][] = 'AND ' . $manager->getCategoryRolesSql(false);

        // category
        @$v = $values['c'];
        if(!empty($v)) {
            $category_id = (int) $v;

            if(!empty($values['ch'])) {
                // need to group because one article could belong
                // to parent and to child
                $mysql['group'][] = 'GROUP BY e.id';

                $child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
                $child = implode(',', $child);
                $mysql['where'][] = "AND cat.id IN($child)";

            } else {
                $mysql['where'][] = "AND cat.id = $category_id";
                $sphinx['where'][] = "AND category IN ($category_id)";
            }

            $sphinx['group'][] = 'GROUP BY e.id';

            $manager->select_type = 'category';
        }


        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND e.active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }


        // type
        @$v = $values['et'];
        if($v == 'none') {
            $mysql['where'][] = "AND e.entry_type = 0";
            $sphinx['where'][] = "AND entry_type = 0";

        } elseif($v == 'any') {
            $mysql['where'][] = "AND e.entry_type != 0";
            $sphinx['where'][] = "AND entry_type != 0";

        }  elseif($v != 'all' && !empty($v)) {
            $v = (int) $v;

            $mysql['where'][] = "AND e.entry_type = '$v'";
            $sphinx['where'][] = "AND entry_type = $v";
        }


        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v);
                $mysql = array_merge_recursive($mysql, $sql);

            } else {

                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v') AS score";
                $mysql['where'][] = "AND MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v' IN BOOLEAN MODE)";

                $sphinx['match'][] = $v;
            }
        }


        // custom
        @$v = $values['custom'];
        if($v) {
            $v = RequestDataUtil::stripVars($v);
            $sql = $manager->cf_manager->getCustomFieldSql($v);
            $mysql['where'][] = 'AND ' . $sql['where'];
            $mysql['join'][] = $sql['join'];

            $sql = $manager->cf_manager->getCustomFieldSphinxQL($v);
            if (!empty($sql['where'])) {
                $sphinx['where'][] = 'AND ' . $sql['where'];
            }
            $sphinx['select'][] = $sql['select'];
            $sphinx['match'][] = $sql['match'];
        }


        // featured, not a filter actually, called in popup where set featured
        if($this->controller->getMoreParam('popup') == 3) {
            $category_id = (empty($values['c'])) ? 'e.category_id' : (int) $values['c'];

            $delim = (!empty($mysql['select'])) ? ',' : '';
            $mysql['select'][] = $delim . 'ef.sort_order as featured_index_order, ef2.sort_order as featured_cat_order';
            // $mysql['select'][] = 'ef.sort_order as featured_index_order, ef2.sort_order as featured_cat_order';
            $mysql['join'][] = "LEFT JOIN {$manager->tbl->entry_featured} ef
                ON e.id = ef.entry_id
                    AND ef.entry_type = 1
                    AND ef.category_id = 0

                LEFT JOIN {$manager->tbl->entry_featured} ef2
                ON e.id = ef2.entry_id
                    AND ef2.entry_type = 1
                    AND ef2.category_id = {$category_id}";
        }

        @$v = $values['q'];
        $options = array('index' => 'article', 'own' => 1, 'entry_private' => 1, 'cat_private' => 'main');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        
        // echo '<pre>', print_r($arr, 1), '</pre>';
        // exit;

        return $arr;
    }


    function getSpecialSearch() {
        $search = CommonEntryView::getSpecialSearch();
        
        // get all articles that have link to searched article (where related_entry_id = '[relared:id]')
        $search['related'] = array(
            'search' => '#^related(?:-inline|-all)?:(\d+)$#',
            'prompt' => 'related[-inline | -all]:{article_id}',
            'insert' => 'related:'
        );
        
        // get all articles that have link to file (where attachment_id = '[attached:id]')
        $search['attachment'] = array(
            'search' => '#^attachment(?:-inline|-attached|-all)?:(\d+)$#',
            'prompt' => 'attachment[-inline | -attached | -all]:{file_id}',
            'insert' => 'attachment:'
        ); 
        
        return $search;
    }


    function getSpecialSearchSql($manager, $ret, $string) {
        $mysql = array(); 

        if($sql = CommonEntryView::getSpecialSearchSql($manager, $ret, $string)) {
            $mysql = $sql;

        } elseif ($ret['rule'] == 'related') {
            $type = strpos($string, 'inline') ? '2,3' : '1,2,3';
            $type = strpos($string, 'attached') ? '1' : $type;

            $related = $manager->getEntryToRelated($ret['val'], $type);
            $related = ($related) ? implode(',', $related) : "'no_related'";
            $mysql['where'] = sprintf("AND e.id IN(%s)", $related);

        } elseif ($ret['rule'] == 'attachment') {
            $type = strpos($string, 'inline') ? '2,3' : '1,2,3';
            $type = strpos($string, 'attached') ? '1' : $type;

            $related = $manager->getEntryToAttachment($ret['val'], $type);
            $related = ($related) ? implode(',', $related) : "'no_attachment'";
            $mysql['where'] = sprintf("AND e.id IN(%s)", $related);
        }
        
        return $mysql;
    }


    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if($key == 'note_remove_reference') {
            @$r = $this->isSpecialSearch($_GET['filter']['q']);
            $vars['article_id'] = $r['val'];
            $vars['delete_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'delete',
                                                        array('id' => $r['val']));

            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_references'];
            $msg['body'] = $msgs['note_remove_reference'];
            return BoxMsg::factory('error', $msg, $vars);

        } elseif ($key == 'note_remove_reference_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_references_bulk'];
            $msg['body'] = $msgs['note_remove_reference_bulk'];
            return BoxMsg::factory('error', $msg);

        } elseif ($key == 'note_drafted_entries_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_entry_drafted'];
            $msg['body'] = $msgs['note_drafted_entries_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }


    function getRatingImg($rate, $votes) {
        $str = '<img src="images/rating/rate_star/rate_%s.gif" alt="rate" />';
        $rate = round((int) $rate);
        return ($votes) ? sprintf($str, $rate) : '';
    }


    function getRating($rate, $votes) {
        $rate = round($rate);
        return ($votes) ? $rate : '';
    }

    function parseDateFull($user, $date) {
        return CommonEntryView::parseDateFull($user, $date, $this);
    }

    function parseEntryCategoryRolesMsg($roles, $roles_range) {
        return CommonEntryView::parseEntryCategoryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
    }

    function parseEntryRolesMsg($roles, $roles_range) {
        return CommonEntryView::parseEntryRolesMsg($roles, $this->stripVars($roles_range), $this->msg);
    }

    function getEntryPrivateMsg($entry_roles, $category_roles) {
        return CommonEntryView::getEntryPrivateMsg($entry_roles, $category_roles, $this->msg);
    }

    function getEntryColorsAndRolesMsg($row) {
        return CommonEntryView::getEntryColorsAndRolesMsg($row, $this->msg);
    }


    // FILTER // -----------

    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }

    function ajaxFeatureArticle($id, $type, $num) {
        return CommonEntryView::ajaxFeatureArticle($id, $type, $num, $this);
    }


    // SORT // -----------

    function ajaxGetSortableList($alphabetical = false) {
        return CommonEntryView::ajaxGetSortableList('title', $alphabetical, $this->manager, $this);
    }
    
    
    // LIST // --------
     
    function getListColumns() {
        
        $options = array(
            
            'id',
            'private',
            'schedule',
            'mustread',
         
            'date_posted' => array(
                'type' => 'text_tooltip',
                'title' => 'posted_msg',
                'width' => 85,
                'params' => array(
                    'text' =>  'date_posted_formatted',
                    'title' => 'date_posted_full')
            ),
            
            'date_updated' => array(
                'type' => 'text_tooltip',
                'title' => 'updated_msg',
                'width' => 85,
                'params' => array(
                    'text' =>  'date_updated_formatted',
                    'title' => 'date_updated_full')
            ),
            
            'title',
            
            'category' => array(
                'type' => 'link_tooltip_right_text',
                'params' => array(
                    'link' => 'category_filter_link',
                    'title' => 'category_title',
                    'text' => 'category',
                    'text_right' => 'category_num')
            ),  
            
            'author',
            'updater',
            
            'type' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'title' => 'type_title',
                    'text' => 'type')
            ),       
            
            'rating' => array(
                'type' => 'text',
                'shorten_title' => '<svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" width="17" height="17" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" d="m11.322 2.923c.126-.259.39-.423.678-.423.289 0 .552.164.678.423.974 1.998 2.65 5.44 2.65 5.44s3.811.524 6.022.829c.403.055.65.396.65.747 0 .19-.072.383-.231.536-1.61 1.538-4.382 4.191-4.382 4.191s.677 3.767 1.069 5.952c.083.462-.275.882-.742.882-.122 0-.244-.029-.355-.089-1.968-1.048-5.359-2.851-5.359-2.851s-3.391 1.803-5.359 2.851c-.111.06-.234.089-.356.089-.465 0-.825-.421-.741-.882.393-2.185 1.07-5.952 1.07-5.952s-2.773-2.653-4.382-4.191c-.16-.153-.232-.346-.232-.535 0-.352.249-.694.651-.748 2.211-.305 6.021-.829 6.021-.829s1.677-3.442 2.65-5.44zm.678 2.033-2.361 4.792-5.246.719 3.848 3.643-.948 5.255 4.707-2.505 4.707 2.505-.951-5.236 3.851-3.662-5.314-.756z" fill-rule="nonzero"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'options' => 'text-align: center;'
            ),
            
            'votes_num' => array(
                'type' => 'text',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path fill="#fff" d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm3.698 15.354c-.405-.031-.367-.406.016-.477.634-.117.913-.457.913-.771 0-.265-.198-.511-.549-.591-.418-.095-.332-.379.016-.406.566-.045.844-.382.844-.705 0-.282-.212-.554-.63-.61-.429-.057-.289-.367.016-.461.261-.08.677-.25.677-.755 0-.336-.25-.781-1.136-.745-.614.025-1.833-.099-2.489-.442.452-1.829.343-4.391-.845-4.391-.797 0-.948.903-1.188 1.734-.859 2.985-2.577 3.532-4.343 3.802v4.964c3.344 0 4.25 1.5 6.752 1.5 1.6 0 2.426-.867 2.426-1.333 0-.167-.136-.286-.48-.313z"/></svg>',
                'width' => 'min',
                'align' => 'center',
            ),
                        
            'comment_num' => array(
                'type' => 'link',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path fill="#fff" d="M10 3.002c4.411 0 8 2.849 8 6.35 0 3.035-3.029 6.311-7.925 6.311-1.58 0-2.718-.317-3.718-.561-.966.593-1.256.813-3.006 1.373.415-1.518.362-2.182.331-3.184-.837-1.001-1.682-2.069-1.682-3.939 0-3.501 3.589-6.35 8-6.35zm0-2.002c-5.281 0-10 3.526-10 8.352 0 1.711.615 3.391 1.705 4.695.047 1.527-.851 3.718-1.661 5.312 2.168-.391 5.252-1.258 6.649-2.115 1.181.289 2.312.421 3.382.421 5.903 0 9.925-4.038 9.925-8.313 0-4.852-4.751-8.352-10-8.352zm11.535 11.174c-.161.488-.361.961-.601 1.416 1.677 1.262 2.257 3.226.464 5.365-.021.745-.049 1.049.138 1.865-.892-.307-.979-.392-1.665-.813-2.127.519-4.265.696-6.089-.855-.562.159-1.145.278-1.74.364 1.513 1.877 4.298 2.897 7.577 2.1.914.561 2.933 1.127 4.352 1.385-.53-1.045-1.117-2.479-1.088-3.479 1.755-2.098 1.543-5.436-1.348-7.348zm-15.035-3.763c-.591 0-1.071.479-1.071 1.071s.48 1.071 1.071 1.071 1.071-.479 1.071-1.071-.48-1.071-1.071-1.071zm3.5 0c-.591 0-1.071.479-1.071 1.071s.48 1.071 1.071 1.071 1.071-.479 1.071-1.071-.48-1.071-1.071-1.071zm3.5 0c-.591 0-1.071.479-1.071 1.071s.48 1.071 1.071 1.071 1.071-.479 1.071-1.071-.48-1.071-1.071-1.071z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'comment_link',
                    'text' => 'comment_num')
            ), 
            
            'rcomment_num' => array(
                'type' => 'link',
                'title' => 'rating_comment_num_msg',
                'shorten_title' => '<svg viewBox="0 0 24 24" width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path fill="#fff" d="M24 1v16.981h-13l-7 5.02v-5.02h-4v-16.981h24zm-2 15v-12.999h-20v12.999h4v3.105l4.357-3.105h11.643zm-4-9.715l-6.622 7.715-4.378-3.852 1.319-1.489 2.879 2.519 5.327-6.178 1.475 1.285z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'rating_comment_link',
                    'text' => 'rating_comment_num')
            ),
             
            'attachment_num' => array(
                'type' => 'link',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 24 24"><path fill="#fff" d="M17 5v12c0 2.757-2.243 5-5 5s-5-2.243-5-5v-12c0-1.654 1.346-3 3-3s3 1.346 3 3v9c0 .551-.449 1-1 1s-1-.449-1-1v-8h-2v8c0 1.657 1.343 3 3 3s3-1.343 3-3v-9c0-2.761-2.239-5-5-5s-5 2.239-5 5v12c0 3.866 3.134 7 7 7s7-3.134 7-7v-12h-2z"/></svg>',
                'width' =>'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'attachment_link',
                    'text' => 'attachment_num')
            ),
            
            'hits',   
            'sort_order',            
            'status'
        
        );
            
        return $options;
    }

}
?>