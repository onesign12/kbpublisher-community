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


class LoginLogView_list extends AppView
{
    
    var $template = 'list.html';
    var $columns = array('date_executed', 'user_id', 'user', 'user_ip', 'type', 'is_error');
    
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


        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'user_id');
        
        // users
        if (!empty($rows)) {
            $users = $manager->getUserByIds($ids);
        }
        
        // type
        $type = $manager->getLoginTypeSelectRange($this->msg);
        
        // status
        $status = $manager->getLoginStatusSelectRange($this->msg);
        
        
        foreach($rows as $entry => $row) {

            $date_search = preg_replace('#[^0-9]#', '', $row['date_login']);
            $row['id'] = sprintf('%s_%s', $row['user_id'], $date_search);
            
            // date executed
            $str = '<b>%s</b> (%s)';
            $date_formatted = $this->getFormatedDate($row['date_login_ts'], 'datetime');
            $date_interval = $this->getTimeInterval($row['date_login_ts']);                
            $row['date_executed_formatted'] = sprintf($str, $date_interval, $date_formatted);  

            // user
            $user = ExtFunc::arrayValue($users, $row['user_id']);
            $row += $this->getUserToList($user, 'user');
            
            $row['type'] = $type[$row['login_type']];

            $more = array('filter[q]' => 'user_id:' . $row['user_id']);
            $row['user_id_filter_link'] = $this->getLink('log', 'login_log', false, false, $more);
            
            $more = array('filter[q]' => 'username:' . $row['username']);
            $row['username_filter_link'] = $this->getLink('log', 'login_log', false, false, $more);
            
            $more = array('filter[q]' => 'ip:' . $row['user_ip_formatted']);
            $row['ip_filter_link'] = $this->getLink('log', 'login_log', false, false, $more);
            
            $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);

            $row += $this->getViewListVarsCustom($row['id'], $row['exitcode'], $row['output2']);
            
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
    

    function getViewListVarsCustom($id = false, $active = false, $output = '') {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active != 1) ? 'color: red;' : '';
        
        $more = array('id' => urldecode($id));
        $row['detail_link'] = $this->getActionLink('detail', false, $more);

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

        $sort->setSortItem('date_msg',  'date_login', 'date_login', $this->msg['date_msg'], 2);
        $sort->setSortItem('user_id_msg', 'user_id', 'user_id', $this->msg['user_id_msg']);
        $sort->setSortItem('user_ip_msg','user_ip', 'user_ip', $this->msg['user_ip_msg']);
        $sort->setSortItem('type_msg','login_type', 'login_type', $this->msg['type_msg']);
        $sort->setSortItem('is_error_msg','exitcode', 'exitcode', $this->msg['is_error_msg']); 
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $type = $manager->getLoginTypeSelectRange($this->msg);
        
        // remove pluginable
        if(!AppPlugin::isPlugin('auth')) {
            $skip = AppPlugin::getPluginData('auth', 'loginlog');
            $type = array_diff_key($type, array_flip($skip));
        }
        
        $select->setRange($type, array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange($manager->getLoginStatusSelectRange($this->msg),
                          array('all'=> '__'));

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

        // login_type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND login_type = '$v'";
        }

        // login_status
        @$v = $values['s'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND exitcode = '$v'";
        }
        
        // period
        @$v = $values['p'];
        if(!empty($v)) {
            $arr[] = $this->getPeriodSql($v, $values, 'date_login', $this->week_start);
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
        
        return $arr;
    }
    
    
    function getSpecialSearch() {
        $search = array('user_id', 'username', 'ip', 'ip_range');
        return $search;
    }


    function getSpecialSearchSql($manager, $ret, $string) {
        
        $arr = array();
        
        if($ret['rule'] == 'user_id') {
            $arr['where'] = sprintf("AND user_id = '%d'", $ret['val']);

        } elseif($ret['rule'] == 'username') {
            $v = addslashes(stripslashes($ret['val']));
            $user_id = $manager->getUserIdByUsername($v);
            $user_id = ($user_id) ? $user_id : '11111111111111111111111';
            $arr['where'] = sprintf("AND user_id = '%d'", $user_id);

        } elseif($ret['rule'] == 'ip') {
            
            $s = strlen($ret['val']);
            if($ret['val'][$s - 1] == '.') {
                $ret['val'] = substr($ret['val'], 0, $s - 1);
            }
            
            $ip = explode('.', $ret['val']);
            $c = count($ip);
            
            if ($c != 4) {
                
                $ip_mask_start = $ip;
                $ip_mask_end = $ip; 
                
                for($i = 0; $i < 4; $i++) {
                    if (!isset($ip[$i])) {
                        $ip_mask_start[$i] = '0';
                        $ip_mask_end[$i] = '255';
                    }
                }

                $ip_mask_start = implode('.', $ip_mask_start);
                $ip_mask_end = implode('.', $ip_mask_end);

                $str = "AND user_ip BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
                $arr['where'] = sprintf($str, $ip_mask_start, $ip_mask_end);
            } else {
    
                $arr['where'] = sprintf("AND user_ip = INET_ATON('%s')", $ret['val']);
            }
          
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

            $str = "AND user_ip BETWEEN INET_ATON('%s') AND INET_ATON('%s')";
            $arr['where'] = sprintf($str, $ip_mask_start, $ip_mask_end);
        }
        
        // echo '<pre>', print_r($arr, 1), '</pre>';
        return $arr;    
    }
        
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            'date_executed' => array(
                'title' => 'date_msg',
                'width' => 280,
                'params' => array(
                    'text' => 'date_executed_formatted'
                ), 
            ),    
                    
            'user_id' => array(        
                'type' => 'link',   
                'width' => 80,
                'params' => array(
                    'link' => 'user_id_filter_link'
                )
            ),
            
            'username' => array(
                'type' => 'link_tooltip',
                'title' => 'username_msg',
                'params' => array(
                    'text' => 'username',
                    'title' => 'user_title',
                    'link' => 'username_filter_link')
            ),
            
            'user' => array(
                'type' => 'link_tooltip',
                'title' => 'user_msg',
                'params' => array(
                    'text' => 'user',
                    'title' => 'username',
                    'link' => 'username_filter_link')
            ),
            
            'type' => array(      
                'width' => 120
            ),
            
            'user_ip' => array(     
                'type' => 'link',   
                'width' => 120,
                'params' => array(
                    'text' => 'user_ip_formatted',
                    'link' => 'ip_filter_link'
                )
            ),
            
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