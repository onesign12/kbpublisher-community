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



class SearchLogView_list extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('date_executed', 'search_string', 'search_in', 'returned_rows');
    
    var $start_day;
    var $end_day;
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);

        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // header                                                                                                    
        $bp = &$this->pageByPage($manager->limit, $manager->getRecordsSql());

        $msg = $this->msg['export_msg'];
        $link = sprintf("javascript:showExportPopup('%s')", $this->msg['export_msg']);
        $button = array($msg => $link);

        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager), $button, false)); 


        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('search_option'));
        $ids = $manager->getValuesString($rows, 'user_id');
        
        // users
        if (!empty($rows)) {
            $users = $manager->getUserByIds($ids);
        }
        
        // search type
        $type = $manager->getSearchTypeSelectRange();
        
        
        // echo '<pre>' . print_r($type, 1) . '</pre>';
        // 
        // $_in = explode(',', '1,2');
        // echo '<pre>' . print_r($_in, 1) . '</pre>';
        // 
        // $_in = array_intersect_key($type, array_flip($_in));
        // echo '<pre>' . print_r($_in, 1) . '</pre>';
        // 
        // exit;
        
        foreach($rows as $entry => $row) {
            
            $date_search = preg_replace('#[^0-9]#', '', $row['date_search']);
            $row['id'] = sprintf('%s_%s_%s', $row['user_id'], $row['user_ip'], $date_search);
            
            // date executed
            $str = '<b>%s</b> (%s)';
            $date_formatted = $this->getFormatedDate($row['date_search_ts'], 'datetime');
            $date_interval = $this->getTimeInterval($row['date_search_ts']);                
            $row['date_executed_formatted'] = sprintf($str, $date_interval, $date_formatted);            
            
            $_in = array_intersect_key($type, array_flip(explode(',', $row['search_type'])));
            $row['search_in'] = implode(',', $_in);
            // $row['search_in'] = $type[$row['search_type']];            
            
            $row['search_link'] = $manager->getSearchLink(unserialize($row['search_option']));
            $row['returned_rows'] = ($row['exitcode'] > 10) ? '> 10' : $row['exitcode'];

            $row += $this->getViewListVarsCustom($row['id'], $row['returned_rows']);

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        // export
        $export = CommonExportView::getExportVars($this);

        $tpl->tplAssign($list->getListVars(array(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($export['tmpl'], $export['func']);
    }
    

    function getViewListVarsCustom($id = false, $returned_rows = false, $own = false) {
        $row = parent::getViewListVars($id, $returned_rows);
        $row['style'] = (!$returned_rows) ? 'color: red;' : '';
        
        $more = array('id' => urldecode($id));
        $row['detail_link'] = $this->getActionLink('detail', false, $more);
        
        $row += $this->getVliewListLogVars($row['detail_link'], '');
        $row['update_link'] = $row['detail_link'];

        return $row;
    }
    
    
    function &getSort() {
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_msg',  'date_search', 'date_search', $this->msg['date_msg'], 2);
        $sort->setSortItem('search_string_msg', 'search_string', 'search_string', $this->msg['search_string_msg']);
        $sort->setSortItem('returned_rows_msg','exitcode', 'exitcode', $this->msg['returned_rows_msg']);
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $select->setRange($manager->getSearchTypeSelectRange($this->msg),
                          array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array('all'=> '__', 
                                    1 => $this->msg['yes_msg'], 
                                    0 => $this->msg['no_msg']));

        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));  
        
        
        // period
        $skip = array('this_year', 'previous_year');      
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

        // search type      
        @$v = $values['t'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            // $arr[] = "AND search_type = '$v'";
            $arr[] = "AND FIND_IN_SET ('$v', search_type)";
        }

        // is success
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            if($v) {
                $arr[] = "AND exitcode > 0";
            } else {
                $arr[] = "AND exitcode = 0";
            }
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_search', $this->week_start);
        }
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            $view_login = new LoginLogView_list();

            if($ret = $view_login->isSpecialSearch($v)) {
                 
                if($sql = $view_login->getSpecialSearchSql($manager, $ret, $v)) {
                    $arr[] = $sql['where'];
                    if(isset($sql['from'])) {
                        $arr_from[] = $sql['from'];
                    }
                }
                
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND search_string LIKE '%{$v}%'";
            }
        }

        $arr = implode(" \n", $arr);
        //echo '<pre>', print_r($arr, 1), '</pre>';

        return $arr;
    }



    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            'date_executed' => array(
                'width' => 280,
                'params' => array(
                    'text' => 'date_executed_formatted'
                ), 
            ),
            
            'search_string',
            
            'search_in' => array(        
                'width' => 120
            ),
            
            'returned_rows' => array(   
                'type' => 'link',     
                'width' => 50,
                'options' => 'text-align: center;',
                'params' => array(
                    'options' => '%target="_blank"%',
                    'link' => 'search_link'
                )
            )
        );
            
        return $options;
    } 
}
?>