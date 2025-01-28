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


class NotificationView_list extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('date_posted', 'title');
    
    var $start_day;
    var $end_day;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $bulk_msg = AppMsg::getMsg('bulk_msg.ini', false, 'bulk_notification');
        

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        
        $manager->setSqlParams('AND user_id = ' . $manager->user_id);
        
        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // bulk
        $manager->bulk_manager = new NotificationModelBulk();
        
        // changed as we use it on client and user could be without priv
        // if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) { 
            $manager->bulk_manager->setActionsAllowed($manager, $manager->priv);
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'NotificationView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        // }
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // header
        $bp_options = [
            'class'=>'short',
            'limit_range' => [10]
        ];

        $bp = &$this->pageByPage($manager->limit, $manager->getRecordsSql(), $bp_options);
        
        $button = [];
        $tpl->tplAssign('header', $this->commonHeaderList('', $this->getFilter($manager), $button));

        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        foreach($rows as $entry => $row) {

            $row['date_posted_formatted'] = $this->getFormatedDate($row['date_posted'], 'datetime');
            $row['date_posted_interval'] = $this->getTimeInterval($row['date_posted'], true);
            
            $link = $this->getActionLink('detail', $row['id']);
            //$link = $this->controller->getFullLink('account', 'this', false, 'detail', ['id' => $row['id']]);
            $row['detail_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            
            // title
            $row += $this->getTitleToList($row['title'], 100);
            
            // actions/links
            $links = array();
            $links['detail_link'] = $row['detail_link'];
            $links['read_link'] = $this->getActionLink('read', $row['id']);
            $links['unread_link'] = $this->getActionLink('unread', $row['id']);
             
            $actions = $this->getListActions($links, $row['active'], $bulk_msg);
            $row += $this->getViewListVarsJsCustom($row['id'], $row['active'], $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        
        // no records show msg 
        $func = [];
        if(!$rows && empty($_GET['filter'])) {
            $msg = AppMsg::hintBoxCommon('note_no_records');
            $tpl = new tplTemplatezString($msg);
        } else {
            $func = array(
                array('tplAssign', array('by_page_bottom', $bp->nav)),
            );
        }
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html', $func);
    }


    function getListActions($links, $unread, $bulk_msg) {
        
        $actions = array(
            'detail' => array(
                'msg' => $this->msg['view_msg'],
                'link' => $links['detail_link']
            ),
            'delete'
        );
        
        if($unread) {
             $actions['read'] = array(
                'msg'  => $bulk_msg['read'],
                'link'  => $links['read_link']
             );
             
        } else {
            $actions['unread'] = array(
                'msg'  => $bulk_msg['unread'],
                'link'  => $links['unread_link']
             );
        }
        
        return $actions;
    }
    
    
    function getViewListVarsJsCustom($record_id, $active, $actions) {        
        
        $row = parent::getViewListVarsJs($record_id, $active, 1, $actions);
        $row['style'] = ($active == 1) ? 'font-weight: bold;' : '';

        return $row;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_msg',  'date_posted', 'date_posted', $this->msg['date_msg'], 2); 
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;    
        
        //status
        @$status = (isset($values['s'])) ? $values['s'] : 'all';
        $range = array(
            'all'=> '__',
            1 => $this->msg['status_not_read_msg'],
            0 => $this->msg['status_read_msg']
        );
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        // period  
        $skip = array('never', 'this_year', 'previous_year');      
        $dates = CommonFilterView::parsePeriodFilterInput($tpl, $values, $manager, false, $skip);
        $tpl->tplAssign($this->setDatepickerVars($dates));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    

    function getFilterSql($manager) {

        $arr = array();
        $arr_select = array();       
        
        @$values = $_GET['filter'];
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_posted', $this->week_start);
        }
        
        // status
        @$v = isset($values['s']) ? $values['s'] : 'all';
        if($v != 'all') {
            $v = (int) $v;
            $arr[] = "AND active = '$v'";
        }
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v);
                $arr = array_merge_recursive($mysql, $sql);
                
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND title LIKE '%{$v}%'";
            }
        }

        $arr = implode(" \n", $arr);
        // echo '<pre>', print_r($arr, 1), '</pre>';

        return $arr;
    }
    
    
    function getListColumns() {
        
        $options = array(
            
            'date_posted' => array(
                'type' => 'text_tooltip',
                'title' => 'date_msg',
                'width' => 180,
                'params' => array(
                    'text' =>  'date_posted_interval',
                    'title' =>  'date_posted_formatted',)
            ),
            
            'title' => array(
                'type' => 'link_tooltip',
                'params' => array(
                    'link' => 'detail_link',
                    'title' => 'title_title',
                    'text' => 'title_entry')
            )
        );
            
        return $options;
    }

}
?>