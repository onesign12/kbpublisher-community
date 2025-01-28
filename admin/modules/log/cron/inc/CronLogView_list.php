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


class CronLogView_list extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('date_executed', 'is_error');
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');        
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // header generate
        $bp = &$this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $magic = array_flip($manager->getCronMagic());

        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $str = '%s - %s';
            $start_date = $this->getFormatedDate($row['date_started_ts'], 'datetimesec');
            $finish_date = ($row['date_finished']) ? $this->getFormatedDate($row['date_finished_ts'], 'datetimesec') : '';                
            $row['date_range'] = sprintf($str, $start_date, $finish_date);
            
            $str = '<b>%s</b> (%s)';
            $date_formatted = $this->getFormatedDate($row['date_finished_ts'], 'datetime');
            $date_interval = $this->getTimeInterval($row['date_finished_ts']);                
            $row['date_executed_formatted'] = sprintf($str, $date_interval, $date_formatted);
        
            $row['is_error'] = ($row['exitcode']) ? false : true;
            $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
            $row['output2'] = $this->getSubstringSign($row['output2'], 350);
            
            if(APP_DEMO_MODE) { 
                $row['output2'] = 'Hidden in DEMO mode';
            }            
            
            // $row['range'] = $this->msg['cron_type'][$magic[$row['magic']]];
            
            $row += $this->getViewListVarsCustom($obj->get('id'), $obj->get('exitcode'), $row['output2']);
                                               
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars(array(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getViewListVarsCustom($id, $active, $output) {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active == 0) ? 'color: red;' : '';
        
        $row += $this->getVliewListLogVars($row['detail_link'], $output);
        $row['update_link'] = $row['detail_link'];
        
        return $row;
    }
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);
        
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
        
        //magic
        @$status = $values['s'];
        $range = array();
        $range['all'] = '__';
        foreach($manager->getCronMagic() as $title => $num) {
            $range[$num] = $this->msg['cron_type'][$title];
        }
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        // status
        $select->setRange(array(
            'all'=> '__', 
            '0' => $this->msg['yes_msg'], 
            '1' => $this->msg['no_msg'])
        );

        @$status = $values['e'];
        $tpl->tplAssign('exitstatus_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // magic
        @$v = $values['s'];
        
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $arr[] = "AND magic = '$v'";
        }
        
        // status
        @$v = $values['e'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $arr[] = "AND exitcode = '$v'";
        }
        
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            $v = addslashes(stripslashes($v));
            $arr[]  = "AND output LIKE '%{$v}%'";
        }
        
        return implode(" \n", $arr);
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