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



class SphinxLogView_list extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('date_executed', 'type', 'is_error');
    
    var $start_day;
    var $end_day;
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
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

        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager), false, false)); 

        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // type
        $type = $manager->getActionTypeSelectRange($this->msg);
        
        
        foreach($rows as $entry => $row) {
            
            // date executed
            $str = '<b>%s</b> (%s)';
            $date_formatted = $this->getFormatedDate($row['date_executed_ts'], 'datetime');
            $date_interval = $this->getTimeInterval($row['date_executed_ts']);                
            $row['date_executed_formatted'] = sprintf($str, $date_interval, $date_formatted);
            
            
            $row['type'] = $type[$row['action_type']];
            $row['output2'] = nl2br($row['output']);
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);

            $row += $this->getViewListVarsCustom($row['id'], $row['exitcode'], $row['output2']);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars(array(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        // export
        $export = CommonExportView::getExportVars($this);
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($export['tmpl'], $export['func']);
    }
    

    function getViewListVarsCustom($id, $active, $output) {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active != 1) ? 'color: red;' : '';
        
        $row += $this->getVliewListLogVars($row['detail_link'], $output);
        $row['update_link'] = $row['detail_link'];

        return $row;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_msg',  'date_executed', 'id', $this->msg['date_msg'], 2);
        $sort->setSortItem('type_msg','action_type', 'action_type', $this->msg['type_msg']);
        $sort->setSortItem('is_error_msg','exitcode', 'exitcode', $this->msg['is_error_msg']); 
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $select->setRange($manager->getActionTypeSelectRange($this->msg),
                          array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array(
			'all'=> '__', 
			'0' => $this->msg['yes_msg'], 
			'1' => $this->msg['no_msg'])
		);

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

        // type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND action_type = '$v'";
        }

        // status
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $arr[] = "AND exitcode = '$v'";
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_executed', $this->week_start);
        }
        
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->getSpecialSearchSql($manager, $ret, $v);
                $arr = array_merge_recursive($arr, $sql);
                
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND output LIKE '%{$v}%'";
            }
        }

        $arr = implode(" \n", $arr);
        // echo '<pre>', print_r($arr, 1), '</pre>';

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
            
            'type',
            
            'is_error' => array(        
                'type' => 'bullet',
                'width' => 80,
                'options' => 'text-align: center;'
            )
        );
            
        return $options;
    } 
}
?>