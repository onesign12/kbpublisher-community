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


class KBFeaturedEntryView_list extends AppView
{

    var $template = 'list.html';
    var $columns = array('entry_id', 'title', 'index_page', 'category_page', 'hits');


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);

        $categories = $manager->emanager->getCategoryRecords();
        $categories = $this->stripVars($categories, array(), false);

        $this->category_id = false;
        if (isset($_GET['filter']['t']) && $_GET['filter']['t'] != '') {
            $this->category_id = $_GET['filter']['t'];
            $this->category_name = ($this->category_id) ? $categories[$this->category_id]['name'] : $this->msg['index_page_msg'];
        }

        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);

        $bp =& $this->pageByPage($manager->limit, $manager->getCountRecords());
        $this->bp = $bp;

        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));

        $button = array();
        if ($this->priv->isPriv('insert')) {
            $inseert_link = false;
            if ($this->priv->isPriv('select', 'kb_entry')) {
                $msg = 'insert';
                $link = $this->getLink('knowledgebase', 'kb_entry');
                $inseert_link = $link = "javascript: PopupManager.create('{$link}', 'r', 'r', 3);void(0)";
                $button = array($msg => $link);
            }
        
            if(!$bp->num_records) {
                $add_link['link'] = $inseert_link;
                $add_link['msg'] = $this->msg['add_new_msg'];
                return AppView::getNoRecordsBox($add_link);
            }
        }

        if ($this->priv->isPriv('update')) {
            $disabled = (count($rows) < 1 || $this->category_id === false);
            $button['...'][] = array(
                'msg' => $this->msg['reorder_msg'],
                'link' => sprintf("javascript:xajax_getSortableList('%s');void(0);", $this->category_id),
                'disabled' => $disabled
            );
        }

        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager, $categories), $button));

        // bulk
        $manager->bulk_manager = new KBFeaturedEntryModelBulk;
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'KBFeaturedEntryView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }

        $star_html = '<img src="images/icons/check.svg" alt="" style="vertical-align:middle;">';
        $category_html = '<div style="float: left;margin-left: 15px;">%s</div><div style="float: right;margin-top: 1px;">(%s)</div>';

        foreach($rows as $entry_id => $row) {

            $row['entry_id'] = $entry_id;
            $row += $this->getTitleToList($row['title'], 100);

            if(!empty($row['category'][0])) {
                $row['index_page'] = 1;
                unset($row['category'][0]);
            }
            
            $row['category_page'] = (!empty($row['category']));


            // actions/links
            $more = array(
                'id' => $entry_id, 
                'referer' => WebUtil::serialize_url($this->controller->getCommonLink())
            );
            $links['detail_link'] = $this->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);
            
            $link = $this->getLink('knowledgebase', 'kb_entry', false, 'preview', $more);
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $row['preview_link'] = $links['preview_link'];

            $link = $this->getActionLink('update', $entry_id);
            $links['update_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 3);", $link);

            $link = $this->getActionLink('delete_category', $row['id']);
            $links['delete_category_link'] = $link;

            $actions = $this->getListActions($links, $categories);
            $row += $this->getViewListVarsJs($entry_id, 1, 1, $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html');
    }


    function getListActions($links) {

        $actions = array(
            'update',
            'delete' => array(
                'msg' => $this->msg['remove_msg'],
                'confirm_msg' => $this->msg['sure_common_msg']
                ));

        if ($this->priv->isPriv('select', 'kb_entry')) {
            $actions['detail'] = array(
                'link' => $links['detail_link']
            );
            
            $actions['preview'] = array(
                'msg'  => $this->msg['preview_msg'],
                'link' => $links['preview_link']);
        }

        if ($this->category_id !== false) {
            $actions['delete_category'] = array(
                'msg' => sprintf('%s - %s', $this->msg['remove_from_msg'], $this->category_name),
                'link' => $links['delete_category_link'],
                'confirm_msg' => $this->msg['sure_common_msg']
            );
        }

        return $actions;
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);

        //$order = $this->getSortOrderSetting();
        $sort->setDefaultSortItem('sort_order', 1);

        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('id_msg', 'id', 'e.id', $this->msg['id_msg']);
        $sort->setSortItem('hits_num_msg', 'hits', 'hits', $this->msg['hits_num_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_order', 'sort_order', $this->msg['sort_order_msg']);
        $sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg']);

        return $sort;
    }


    function getFilter($manager, $categories) {

        $values = $this->parseFilterVars(@$_GET['filter']);

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');

        // type
        $type = (!isset($values['t'])) ? 'index' : $values['t'];

        $select = new FormSelect();
        $select->select_tag = false;


        // category
        $full_categories = $manager->emanager->cat_manager->getSelectRangeFolow($categories); // private here

        $categories = array();
        $categories[0] = $this->msg['index_page_msg'];

        $used_categories = $manager->getUsedCategories();
        foreach ($used_categories as $v) {
            $categories[$v] = $full_categories[$v];
        }

        $categories = $this->stripVars($categories, array(), 'stripslashes'); // for compability with other $js_hash

        if(isset($values['t']) && ($values['t'] !== '')) {
            $category_id = (int) $values['t'];
            $category_name = $this->stripVars($categories[$category_id]);

            $tpl->tplAssign('category_id', $category_id);
            $tpl->tplAssign('category_name', $category_name);
        }


        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('categories', $js_hash);

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }


    function getFilterSql() {

        $arr = array();
        $arr_select = array();

        @$values = $_GET['filter'];

        @$v = (!isset($values['t'])) ? 'all' : $values['t'];
        if($v != 'all') {
            if (is_numeric($v)) {
                $arr[] = "AND ef.category_id = '$v'";

            } elseif($v == 'index') {
                $arr[] = "AND ef.category_id = 0";
            }
        }

        $arr = implode(" \n", $arr);

        return $arr;
    }


    function ajaxGetSortableList() {

        $additional_rows = 10;

        $limit = $this->bp->limit + $additional_rows;
        $offset = $this->bp->offset - ($additional_rows / 2);
        if ($offset < 0) {
            $offset = 0;
        }

        $rows = $this->manager->getRecords($limit, $offset);

        $sort_values = array();
        foreach ($rows as $entry_id => $v) {
            $sort_values[$entry_id]  = $v['category'][$this->category_id];
        }
        array_multisort($sort_values, SORT_ASC, $rows);


        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');

        $tpl->tplAssign('sort_values', implode(',', $sort_values));

        $lowest_sort_order = ($offset == 0) ? 1 : $sort_values[0];
        $tpl->tplAssign('lowest_sort_order', $lowest_sort_order);

        foreach($rows as $row) {
            $row['sort_order'] = $row['category'][$this->category_id];
            $tpl->tplParse($row, 'row');
        }

        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);

        $tpl->tplParse($this->msg);


        $objResponse = new xajaxResponse();
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort', $lowest_sort_order);

        return $objResponse;
    }
    
    
    function getListColumns() {
        
        $options = array(
            
            'id', 
            
            'entry_id' => array(
                'type' => 'link_tooltip',
                'width' => 80,
                'params' => array(
                    'link' => 'preview_link', 
                    'options' => 'entry_link_option'
                )
            ),
            
            'title' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'title' => 'title_title',
                    'text' => 'title_entry')
            ),

            'index_page' => array(
                'type' => 'bullet',
                'shorten_title' => '<svg fill="#fff" width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path d="M24 21h-24v-2h24v2zm0-4.024h-24v-2h24v2zm0-3.976h-24v-2h24v2zm0-4h-24v-2h24v2zm0-6v2h-24v-2h24z"/></svg>',
                'width' =>'min',
                'align' => 'center',                
                'options' => 'text-align: center;'
            ),
            
            'category_page' => array(
                'type' => 'bullet',
                'shorten_title' => '<svg fill="#fff" width="16" height="16" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m18.891 19.498h-13.782l-1.52-9.501h16.823zm-14.306-12.506h14.868l-.227 1.506h-14.415zm.993-2.494h12.882l-.13.983h-12.623zm16.421 4.998c0-.558-.456-.998-1.001-.998h-.253c.309-2.064.289-1.911.289-2.009 0-.58-.469-1.008-1-1.008h-.189c.193-1.461.187-1.399.187-1.482 0-.671-.575-1.001-1.001-1.001h-14.024c-.536 0-1.001.433-1.001 1 0 .083-.008.013.188 1.483h-.19c-.524 0-1.001.422-1.001 1.007 0 .101-.016-.027.29 2.01h-.291c-.569 0-1.001.464-1.001.999 0 .118-.105-.582 1.694 10.659.077.486.496.842.988.842h14.635c.492 0 .911-.356.988-.842 1.801-11.25 1.693-10.54 1.693-10.66z" fill-rule="nonzero"/></svg>',
                'width' =>'min',
                'align' => 'center',
                'options' => 'text-align: center;'
            ),
            
            'hits'
        );
            
        return $options;
    }  
}
?>