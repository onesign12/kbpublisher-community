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


class CronLogView_list_group extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('range', 'last_executed', 'is_error');
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        $msg = $this->msg['failed_tasks_msg'];
        $more = array('filter[s]' => 'all', 'filter[e]' => 0, 'popup' => $this->controller->getMoreParam('popup'));
        $link = $this->getLink('this', 'this', false, false, $more);
        $button = array($msg => $link);
        
        $tpl->tplAssign('header', $this->commonHeaderList('', $this->getFilter($manager), $button, false));
        
        $magic = $manager->getCronMagic();
        foreach($magic as $title => $num) {
        
            $row = $this->stripVars($manager->getSummaryRecord($num));
            if($row) {
                
                $str = '%s - %s';
                $start_date = $this->getFormatedDate($row['date_started_ts'], 'datetimesec');
                $finish_date = $this->getFormatedDate($row['date_finished_ts'], 'datetimesec');                
                $row['date_range'] = sprintf($str, $start_date, $finish_date);
                
                $str = '<b>%s</b>&nbsp;&nbsp;(%s)';
                $date_formatted = $this->getFormatedDate($row['date_finished_ts'], 'datetime');
                $date_interval = $this->getTimeInterval($row['date_finished_ts']);                
                $row['last_executed'] = sprintf($str, $date_interval, $date_formatted);
                
                $row['is_error'] = ($row['exitcode']) ? false : true;
                $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
                $row['output2'] = $this->getSubstringSign($row['output2'], 350);
                
                if(APP_DEMO_MODE) { 
                    $row['output2'] = 'Hidden in DEMO mode';
                }
                            
            } else {
                $row = array();
                $row['id'] = false;
                $row['exitcode'] = 1;
                $row['date_range'] = '';
                $row['last_executed'] = '--';
                $row['is_error'] = '';
                $row['output2'] = '';
            }
            
            $row['title'] = $this->msg['cron_type'][$title];

            $row += $this->getViewListVarsCustom($row['id'], $row['exitcode'], $num, $row['output2']);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars(array(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsCustom($id, $active, $magic, $output) {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active == 0) ? 'color: red;' : '';
        $row['view_msg'] = $this->msg['view_msg'];
        
        $more = array('filter[s]' => $magic);
        if ($this->controller->getMoreParam('popup')) {
            $more['popup'] = 1;
        }
        
        $row['magic_link'] = $this->getLink('this', 'this', 'this', false, $more);
        $row['magic_img'] = $this->getImgLink($row['magic_link'], 'load', $this->msg['detail_msg']);        
        
        if(!$id) {
            $row['detail_link'] = '';
            $row['view_msg'] = '';
            
            $row['magic_link'] = '';
            $row['magic_img'] = '';            
        }
        
        $row += $this->getVliewListLogVars($row['detail_link'], $output);
        $row['update_link'] = $row['detail_link'];
        
        return $row;
    }
    
    
    function getFilter($manager) {
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
        
        //magic
        $range = array();
        $range['all'] = '__';
        foreach($manager->getCronMagic() as $title => $num) {
            $range[$num] = $this->msg['cron_type'][$title];
        }
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select());
        
        
        // status
        $select->setRange(array(
            'all'=> '__', 
            '0' => $this->msg['yes_msg'], 
            '1' => $this->msg['no_msg'])
        );
        
        $tpl->tplAssign('exitstatus_select', $select->select());
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'range' => array(
                'type' => 'link',
                'title' => 'range_msg',
                'width' => 150,
                'params' => array(
                    'text' => 'title',
                    'link' => 'magic_link')
            ),
            
            'last_executed', 
            
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