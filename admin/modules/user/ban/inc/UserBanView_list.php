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

class UserBanView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // filter
        $manager->setSqlParams($this->getFilterSql($manager));
                                              
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
                               
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $rules = $manager->getRuleSelectRange();
        $types = $manager->getTypeSelectRange();
        
        foreach($rows as $row) {
            
            $obj->set($row);
      
            $start_date = $this->getFormatedDate($row['date_start'], 'datetime');
            $tpl->tplAssign('date_start_formatted', $start_date);
      
            if ($row['date_end'] == NULL) {
                $end_date = $this->msg['permanent_msg'];
            } else {
                $end_date = $this->getFormatedDate($row['date_end'], 'datetime'); 
            }

            $tpl->tplAssign('date_end_formatted', $end_date);
            $tpl->tplAssign('ban_rule', $rules[$row['ban_rule']]);
            $tpl->tplAssign('ban_type', $types[$row['ban_type']]);
            
            
            $actions = $this->getListActions($obj);
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), 1, $actions));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
                        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj) {
        $actions = array('detail');
        
        if ($obj->get('active')) {
            $actions[] = 'update';
            $actions['status'] = array('msg' => $this->msg['deactivate_msg']);
            
        } else {
            $actions[] = 'clone';
        }
        
        return $actions;
    }
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setDefaultSortItem('date_start', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_start_msg', 'date_start', 'date_start', $this->msg['date_start_msg']);  
        $sort->setSortItem('date_end_msg', 'date_end', 'date_end', $this->msg['date_end_msg']);
        
        return $sort;
    }
        
    
    function getFilter($manager) {

        @$values = $_GET['filter'];        
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
        
        // rule
        @$status = $values['r'];
        $range = $manager->getRuleSelectRange();
        
        $select->setRange($range, array('all'=> '__'));
        $tpl->tplAssign('rule_select', $select->select($status));
        
        // type
        @$type = $values['t'];
        $select->setRange($manager->getTypeSelectRange());
        $tpl->tplAssign('type_select', $select->select($type));
        
        //status
        @$status = $values['s'];
        $range = array(
            //'all'=> '__',
            1 => $this->msg['status_active_msg'],
            0 => $this->msg['status_not_active_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // status
        @$v = $values['r'];
        if($v != 'all' && isset($values['r'])) {
            $v = (int) $v;
            $arr[] = "AND ban_rule = '$v'";
        }
        
        // type
        @$v = $values['t'];
        if(isset($values['t'])) {
            $v = (int) $v;
            $arr[] = "AND ban_type = '$v'";
        }
        
        // status
        @$v = $values['s'];
        $v = (isset($values['s'])) ? (int) $v : 1;
        $arr[] = "AND active = '$v'";
        
        @$v = $values['q'];
        
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v);
                $mysql = array_merge_recursive($mysql, $sql);
                
            } else {
                
                $v = addslashes(stripslashes($v));
                $sql_str = "%s LIKE '%%%s%%'";
                $f = array('ban_value', 'admin_reason', 'user_reason');
                foreach($f as $field) {
                    $sql[] = sprintf($sql_str, $field, $v);
                }
                
                $arr[] = 'AND ' . implode(" OR \n", $sql);
            }    
        }
        
        return implode(" \n", $arr);
    }
        
    
    function getSpecialSearch($str) {
        $search = array('user_id', 'ip', 'ip_range');
        return $search;
    }
    
    
    function getSpecialSearchSql($manager, $ret, $string) {
        $mysql = array();

        if($ret['rule'] == 'user_id') {
            $mysql['where'] = sprintf("AND ban_rule = 1 AND ban_value = '%d'", $ret['val']);
        
        } elseif($ret['rule'] == 'ip') {
            $mysql['where'] = sprintf("AND ban_rule = 2 AND ban_value = '%s'", $ret['val']);  
            
        } elseif($ret['rule'] == 'ip_range') {
            
            $ip = explode('-', $ret['val']);
            $ip[0] = explode('.', trim($ip[0]));
            $ip[1] = explode('.', trim($ip[1]));
            
            for($i = 0; $i < 4; $i++) {                        
                if(!isset($ip[0][$i])) $ip[0][$i] = '0';
                if(!isset($ip[1][$i])) $ip[1][$i] = '255';
            }
            
            $ip_mask_start = implode('.', $ip[0]);
            $ip_mask_end = implode('.', $ip[1]);

            $str = "AND ban_rule = 2 AND ban_value BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
            $mysql['where'] = sprintf($str, $ip_mask_start, $ip_mask_end);   
        }
        
        return $mysql;
    }
}
?>