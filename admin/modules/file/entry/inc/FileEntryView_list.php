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


class FileEntryView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = array(
        1 => 'list_popup.html',
        'ckeditor' => 'list_popup.html',
        'embed'    => 'list_popup.html',
        'ckeditor_inline' => 'list_popup_ck.html'
    );
    
    var $columns = array(
        'id', 'private', 'schedule', 'date_posted', 'date_updated', 
        'filename', 'category', 'filesize', 'filetext', 'hits', 'status'
    );
    var $columns_popup = array('id', 'private', 'filename', 'category', 'status');
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        $popup = $this->controller->getMoreParam('popup');
        
        // add sort_order column 
        if(!empty($_GET['filter']['c'])) {
            if($_GET['filter']['c'] != 'all') {
                array_splice($this->columns, count($this->columns), 0, 'sort_order'); // splice in at position 3
            }
        }
        
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);
        
        // bulk
        $bulk_allowed = array(); // it should be defined in license check, not now in files
        $manager->bulk_manager = new FileEntryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'FileEntryView_bulk');
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
        
        if($popup == 1 || $popup == 'embed') {
            $manager->setSqlParams("AND cat.attachable = 1");
        }
        
        if($popup == 'embed') {
            $manager->setSqlParams("AND filename LIKE '%.pdf'");
        }
        // echo '<pre>' . print_r($manager, 1) . '</pre>';exit;
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // set force index date_updated
        /*if(strpos($sort_order, 'date_updated') !== false) {
            $manager->entry_sql_force_index = 'FORCE INDEX (date_updated)';
        }*/
        
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);

        // xajax
        $this->bp = $bp;
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // header generate
        $button = CommonEntryView::getButtons($this, $xajax, 'file_draft');
        
        if($this->priv->isPriv('insert', 'file_bulk')) {
            $button_bulk = [
                'msg' => AppMsg::getMenuMsgs('file')['file_bulk'],
                'link' => $this->getLink('this', 'file_bulk')
            ];
            array_unshift($button['...'], $button_bulk);
        }
        
        if(in_array($popup, array('ckeditor', 'ckeditor_inline', 'embed'))) {
            $button = false;
        }
        
        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), $button));
        

        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id', true);

        // categories
        $entry_categories = ($ids) ? $manager->getCategoryByIds($ids) : array();
        $entry_categories = $this->stripVars($entry_categories);

        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);

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
        $articles_num = ($ids) ? $manager->getReferencedArticlesNum($ids) : array();
        $schedules = ($ids) ? $manager->getScheduleByEntryIds($ids) : array();
        $history = ($ids) ? $manager->getHistoryNum($ids) : array();
        
        $status = $manager->getEntryStatusData('file_status');
        $publish_status_ids = $manager->getEntryStatusPublished('file_status');
        $linked_range = $manager->getAddTypeSelectRange();
        
        $status_range = $manager->getListSelectRange('file_status', true);
        $status_allowed = $this->priv->getPrivStatusSet($status_range, 'status');
        $status_range = array_intersect($status_range, $status_allowed);
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $row['sort_order'] = $row['real_sort_order'];
            $row['size'] = $row['filesize'];
            $row['filesize'] = WebUtil::getFileSize($obj->get('filesize'));
            $row['filetext'] = ($row['filetext']) ? 1 : 0;
            
            $row['escaped_filename'] = addslashes($row['filename']);
            $row['downloads'] = ($row['downloads']) ? $row['downloads'] : '';
            
            // title
            $row += $this->getTitleToList($row['title'], 100);
            $row += $this->getTitleToList($row['filename'], 100, 'filename');
            $title = (empty($row['title'])) ? '' :  $row['title'] . '<br/>';
            $row['title_title_filename'] = sprintf('%s%s', $title, $row['filename_title']);
            
            
            // schedule
            $schedule = ExtFunc::arrayValue($schedules, $obj->get('id'));
            $row += CommonEntryView::getScheduleToList($schedule, $status, $this);
            
            // roles
            $role = ExtFunc::arrayValue($roles, $obj->get('id'));
            $category_role = ExtFunc::arrayValue($category_roles, $obj->get('category_id'));
            $row += CommonEntryView::getPrivateToList($row, $role, $category_role, $this->msg, $manager->entry_type);
            
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
            
            // as attachments
            if(isset($articles_num[$row['id']])) {
                $more = array('filter[q]'=>'attachment:'.$row['id']);
                $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                $row['attached_link'] = $link;
                $row['attached_num'] = $articles_num[$row['id']];
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

                if ($this->priv->isPriv('update', 'file_category')) {
                    $more = array(
                        'id' => $cat_id,
                        'referer'=> WebUtil::serialize_url($this->controller->getCommonLink()));
                    $update_link = $this->getLink('file', 'file_category', false, 'update', $more);

                    $_full_category = sprintf('<a href="%s">%s</a>', $update_link, $_full_category);
                }

                $_full_categories[] = RequestDataUtil::stripVars($_full_category, array(), true);
                $first_row = false;
            }

            $row['category_title'] = implode('<br />',  $_full_categories);
            
            // link type
            $row['addtype_text'] = _substr($linked_range[$obj->get('addtype')], 0, 1);
            $row['addtype_title'] = $linked_range[$obj->get('addtype')];
            
            // $more = array('filter[q]' => sprintf('addtype:%d', $row['addtype']));
            $more = array('filter[q]' => sprintf('addtype:%s', strtoupper($manager->map_add_type[$row['addtype']][0])));
            $row['addtype_link'] = $this->getLink('this', 'this', null, null, $more);
                
            
            // popup actions
            if ($popup) {
                $row += $this->getPopupValuesToList($popup);
            }
            
            // status vars
            $st_vars = CommonEntryView::getViewListEntryStatusVars($obj->get(),
                                            $entry_categories[$obj->get('id')], $publish_status_ids, $status);
            $row += $st_vars;
            
            
            // actions/links
            $links = array();
            $links['entry_link'] = $this->controller->getPublicLink('file', $obj->get());
            $links['file_link'] = $this->getActionLink('file', $obj->get('id'));
            $links['fopen_link'] = $this->getActionLink('fopen', $obj->get('id'));
            $links['draft_link'] = $this->getLink('this', 'file_draft', false, 'insert', array('entry_id' => $obj->get('id')));
            $links['history_link'] = $this->getActionLink('history', $obj->get('id'));
            
            
            // if some of categories is private
            // and user do not have this role so he can't update it
            $categories = $entry_categories[$obj->get('id')];
            $has_private = $manager->isCategoryNotInUserRole(array_keys($categories));
            
            $actions = $this->getListActions($obj, $links, $manager, 
                                             $has_private, $st_vars['published'],
                                             $history, $status_range);
                                                            
            $row += $this->getViewListVarsJsCustom($obj->get(), $actions, $manager, 
                                                            $has_private, $st_vars['published']);
                        
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplAssign($this->msg);
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        $this->parsePopup($tpl, $manager);
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        $tmpl = APP_MODULE_DIR . 'knowledgebase/entry/template/list_in.html';
        return $tpl->tplPrintIn($tmpl);
    }
    
    
    function getPopupValuesToList($popup) {
        
        $link_title = array(
            '1' => $this->msg['insert_as_attachment_title_msg'],
            'ckeditor' => $this->msg['insert_as_link_title_msg'],
            'ckeditor_inline' => $this->msg['choose_msg'],
            'embed' => $this->msg['choose_msg']
        );
        
        $img_alt = array(
            '1' => $this->msg['insert_as_attachment_msg'],
            'ckeditor' => $this->msg['insert_as_link_msg'],
            'ckeditor_inline' => $this->msg['choose_msg'],
            'embed' => $this->msg['choose_msg']
        );
        
        $v = array();        
        $v['link_title'] = $link_title[$popup];
        $v['img_alt'] = $img_alt[$popup];
        
        return $v;
    }
    
    
    function parsePopup($tpl, $manager) {
        
        $popup = $this->controller->getMoreParam('popup');
        
        // upload and attach - close the window immediately
        if ($popup && $this->controller->getMoreParam('attach_id')) {
            $attach_file_id = (int) $this->controller->getMoreParam('attach_id');
            if($attach_file_id) {
                $file_to_attach = $manager->getById($attach_file_id);
                
                $tpl->tplSetNeeded('/upload_and_attach');
                
                $tpl->tplAssign('attach_id', $file_to_attach['id']);
                $tpl->tplAssign('attach_escaped_filename', addslashes($file_to_attach['filename']));
                $tpl->tplAssign('attach_size', $file_to_attach['filesize']);
            }
        }
        
        if ($popup) {
            $event_mode = $this->controller->getMoreParam('event_mode');
            if (empty($event_mode)) {
                $event_mode = 'default';
            }
            
            $tpl->tplAssign('event_mode', addslashes($event_mode));
            
            // create an empty box for a message block
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());
            
            $menu_msg = AppMsg::getMenuMsgs('file');
            $tpl->tplAssign('popup_title', $menu_msg['file_entry']);
            
            if (!empty($_GET['replace_id'])) {
                $tpl->tplSetNeeded('/replace');
                $tpl->tplAssign('replace_id', (int) $_GET['replace_id']);
            }
        }
        
        if($popup && !in_array($popup, array('ckeditor', 'ckeditor_inline', 'embed'))) {
            $tpl->tplSetNeeded('/close_button');
        }
        
        $command_name = ($popup != 'ckeditor_inline') ? 'insertLink' : 'insertInlineLink';
        $command_name = ($popup == 'embed') ? 'embedPdf' : $command_name;
        $tpl->tplAssign('command_name', $command_name);
    }
    
    
    function getViewListVarsJsCustom($entry, $actions, $manager, $has_private, $is_published) {

        $own_record = ($entry['author_id'] == $manager->user_id);
        $status = $entry['active'];
        $row = $this->getViewListVarsJs($entry['id'], $status, $own_record, $actions);
        
        $link = $this->getActionLink('text', $entry['id']);
        $row['filetext_title'] = sprintf('<a href="%s">%s</a>', $link, $this->msg['filetext_msg']);
        $row['filetext_title'] = $this->stripVars($row['filetext_title']);
        
        $row['entry_link'] = ($is_published) ? $actions['public2']['link'] : $actions['file']['link'];
        
        if($has_private) {
            $row['bulk_ids_ch_option'] = 'disabled';
            $row['filetext_title'] = '';
        }
        
        return $row;
    }
    

    function getListActions($obj, $links, $manager, $has_private, $is_published, $history, $status_range) {

        $record_id = $obj->get('id');
        $status = $obj->get('active');
        $own_record = ($obj->get('author_id') == $manager->user_id);

        $actions = array('detail');

        $actions['file'] = array(
            'msg'  => $this->msg['download_msg'], 
            'link' => $links['file_link']);
        
        $actions['fopen'] = array(
            'msg'  => $this->msg['open_msg'], 
            'link' => $links['fopen_link'], 
            'link_attributes'  => 'target="_blank"');
        
        if($is_published) {
            $actions['public2'] = array( // will be skipped in action
                'msg'  => $this->msg['entry_public_link_msg'],
                'link' => $links['entry_link'],
                'link_attributes' => 'target="_blank"',
                'img'  => '');
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
            if($this->priv->isPriv('insert', 'file_draft')) {
                $as_draft = true;
            }

            if($this->priv->isPrivOptional('insert', 'draft')) {
                unset($actions[array_search('update', $actions)]);
                $as_draft = true;
            }
        }
    
        if($this->priv->isPrivOptional('insert', 'draft')) {
            unset($actions[array_search('clone', $actions)]);
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
    
        $pluginable = AppPlugin::getPluginsFilteredOff('tabs', 1);
        $actions = array_diff_key($actions, $pluginable);
        
        return $actions;
    }   
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setCustomDefaultOrder('fname', 1);
        $sort->setCustomDefaultOrder('sort_oder', 1);
    
        $order = CommonEntryView::getSortOrderSetting($this->setting['file_sort_order']);
        $sort->setDefaultSortItem($order);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('posted_msg',  'datep',    'date_posted',  $this->msg['posted_msg']);
        $sort->setSortItem('updated_msg', 'dateu',    'date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('filename_msg',     'fname',    'filename',     $this->msg['filename_msg']);
        
        $sort->setSortItem('id_msg','id', 'e.id', $this->msg['id_msg']);
        $sort->setSortItem('filesize_msg', 'filesize', 'filesize', $this->msg['filesize_msg']);
        $sort->setSortItem('filetype_msg', 'filetype', 'filetype', $this->msg['filetype_msg']);
        $sort->setSortItem('download_num_msg', 'dowload', 'downloads', $this->msg['download_num_msg']);
        
        $sort->setSortItem('category_msg', 'cat', 'e.category_id', $this->msg['category_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_oder', 'real_sort_order', array($this->msg['sort_order_msg'], 5));
        
        
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
    
    
    function getFilter($manager, $categories) {

        $values = $this->parseFilterVars(@$_GET['filter']);
    
        if(isset($values['f'])) {
            $values['f'] = RequestDataUtil::stripVars($values['f'], array(), true);
            $values['f'] = trim($values['f']);
        }        

        //xajax
        $xobj = null;
        $ajax = &$this->getAjax($xobj, $manager);
        $xajax = &$ajax->getAjax();
    
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        
        $categories = $manager->getCategorySelectRangeFolow($categories);  // private removed
                
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
    
        
        // status
        $select->setRange($manager->getListSelectRange('file_status', false), 
                          array('all'=>'__'));            
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        // custom 
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }    
    
    
    function getFilterSql($manager, $categories) {
        
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
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            
            $v = trim($v);
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v);
                $mysql = array_merge_recursive($mysql, $sql);
            
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.filename_index, e.meta_keywords, e.description, e.filetext) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (e.title, e.filename_index, e.meta_keywords, e.description, e.filetext) AGAINST ('$v' IN BOOLEAN MODE)";
                
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
        
        
        @$v = $values['q'];
        $options = array('index' => 'file', 'own' => 1, 'entry_private' => 1, 'cat_private' => 'main');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    

    function getSpecialSearch() {
        $search = CommonEntryView::getSpecialSearch();
        $search[] = 'fname';
        $search['attached'] = array(
            'search' => '#^attached(?:-inline|-all)?:(\d+)$#',
            'prompt' => 'attached[-inline | -all]:{article_id}',
            'insert' => 'attached:'
        );
        
        $search['addtype'] = array(
            'search' => '#^addtype:(\d|\w+)\s*$#',
            'prompt' => 'addtype:U|L|A',
            'insert' => 'addtype:'
        );
        
        return $search;
    }
    
    
    function getSpecialSearchSql($manager, $ret, $string) {
        $mysql = array();

        if($sql = CommonEntryView::getSpecialSearchSql($manager, $ret, $string)) {
            $mysql = $sql;
        
        } elseif ($ret['rule'] == 'attached') {
            $type = strpos($ret['rule'], 'inline') ? '2,3' : '1,2,3';
            $related = $manager->getAttachmentToEntry($ret['val'], $type);
            $related = ($related) ? implode(',', $related) : "'no_attached'";
            $mysql['where'] = sprintf("AND e.id IN(%s)", $related);    

        } elseif ($ret['rule'] == 'fname') {
            $fname = addslashes(stripslashes($ret['val']));
            $fname = str_replace('*', '%', $fname);
            $mysql['where'] = sprintf("AND e.filename LIKE '%s'", $fname);
            
        } elseif ($ret['rule'] == 'addtype') {
            $val = (is_numeric($ret['val'])) ? $ret['val'] : strtolower($ret['val']);
            foreach($manager->map_add_type as $k => $v) {
                $map_letter[$v[0]] = $k;
            }            

            if(is_numeric($val)) { $val = $val; }
            elseif(isset($map_letter[$val])) { $val = $map_letter[$val]; }
            elseif($val = array_search($val, $manager->map_add_type)) {  }
            
            $mysql['where'] = sprintf("AND e.addtype = '%d'", $val);
        }
        
        return $mysql;
    }
    
    
    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if ($key == 'note_drafted_entries_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_entry_drafted'];
            $msg['body'] = $msgs['note_drafted_entries_bulk'];
            return BoxMsg::factory('error', $msg);            
        
        } elseif ($key == 'note_remove_reference_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_references_bulk'];
            $msg['body'] = $msgs['note_remove_reference_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }


    function getScheduleMsg($data, $status) {
        return CommonEntryView::getScheduleMsg($data, $status, $this);
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
    

    // Filter // -----------
        
    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }
    
    
    // SORT // -----------
    
    function ajaxGetSortableList($alphabetical = false) {
        return CommonEntryView::ajaxGetSortableList('filename', $alphabetical, $this->manager, $this);
    }


    // LIST // -------------

    function getListColumns() {
        
        $options = array(
            
            'id',
            'private',
            'schedule',
            
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
            
            'title' => array(
                'type' => 'link',
                // 'title' => 'filename_msg',
                'params' => array(
                    'link' => 'entry_link', 
                    'options' => 'entry_link_option', 
                    'title' => 'title_title', 
                    'text' => 'title_entry'
                )
            ),   
                     
            'filename' => array(
                'type' => 'link_tooltip',
                // 'title' => 'filename_msg',
                'params' => array(
                    'link' => 'entry_link', 
                    'options' => 'entry_link_option', 
                    'title' => 'title_title_filename', 
                    'text' => 'filename_entry')
            ),
            
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

            'filesize' => array(
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M4 22v-20h16v11.543c0 4.107-6 2.457-6 2.457s1.518 6-2.638 6h-7.362zm18-7.614v-14.386h-20v24h10.189c3.163 0 9.811-7.223 9.811-9.614zm-11.788-1.02c.463-1.469 1.341-3.229 1.496-3.675.225-.646-.173-.934-1.429.171l-.279-.525c1.432-1.559 4.382-1.91 3.379.504-.627 1.508-1.076 2.525-1.332 3.31-.374 1.144.57.68 1.494-.173.126.206.167.271.293.508-2.053 1.953-4.33 2.125-3.622-.12zm3.895-6.71c-.437.372-1.084.364-1.446-.018-.362-.382-.302-.992.135-1.364.437-.372 1.084-.363 1.446.018.361.382.302.992-.135 1.364z"/></svg>',
                'width' => 'min',
                'align' => 'right',
                'options' => 'white-space: nowrap;'
            ),
                        
            'filetext' => array(
                'type' => 'bullet',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M22 0h-20v6h1.999c0-1.174.397-3 2.001-3h4v16.874c0 1.174-.825 2.126-2 2.126h-1v2h9.999v-2h-.999c-1.174 0-2-.952-2-2.126v-16.874h4c1.649 0 2.02 1.826 2.02 3h1.98v-6z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'title' => 'filetext_title',
                    'options' => '%style="padding: 3px 5px;"%'
                )
            ),
            
            'attachment_num' => array(
                'type' => 'link',
                'title' => 'attached_num_msg', 
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" height="14" width="14" viewBox="0 0 24 24"><path fill="#fff" d="M17 5v12c0 2.757-2.243 5-5 5s-5-2.243-5-5v-12c0-1.654 1.346-3 3-3s3 1.346 3 3v9c0 .551-.449 1-1 1s-1-.449-1-1v-8h-2v8c0 1.657 1.343 3 3 3s3-1.343 3-3v-9c0-2.761-2.239-5-5-5s-5 2.239-5 5v12c0 3.866 3.134 7 7 7s7-3.134 7-7v-12h-2z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'attached_link',
                    'text' => 'attached_num')
            ),
            
            'addtype' => array(
                'type' => 'link_tooltip',
                'title' => 'addtype_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M15 10h4l-7 8-7-8h4v-10h6v10zm3.213-8.246l-1.213 1.599c2.984 1.732 5 4.955 5 8.647 0 5.514-4.486 10-10 10s-10-4.486-10-10c0-3.692 2.016-6.915 5-8.647l-1.213-1.599c-3.465 2.103-5.787 5.897-5.787 10.246 0 6.627 5.373 12 12 12s12-5.373 12-12c0-4.349-2.322-8.143-5.787-10.246z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'addtype_link',
                    'text' => 'addtype_text',
                    'title' => 'addtype_title')
            ),
            
            'hits' => array(
                'type' => 'text',
                'title' => 'download_num_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="M12.015 7c4.751 0 8.063 3.012 9.504 4.636-1.401 1.837-4.713 5.364-9.504 5.364-4.42 0-7.93-3.536-9.478-5.407 1.493-1.647 4.817-4.593 9.478-4.593zm0-2c-7.569 0-12.015 6.551-12.015 6.551s4.835 7.449 12.015 7.449c7.733 0 11.985-7.449 11.985-7.449s-4.291-6.551-11.985-6.551zm-.015 3c-2.21 0-4 1.791-4 4s1.79 4 4 4c2.209 0 4-1.791 4-4s-1.791-4-4-4zm-.004 3.999c-.564.564-1.479.564-2.044 0s-.565-1.48 0-2.044c.564-.564 1.479-.564 2.044 0s.565 1.479 0 2.044z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'text' => 'downloads')
            ),
            
            'sort_order',
            'status'
        );
            
        return $options;
    }

}
?>