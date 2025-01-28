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


class MailPoolLogView_list extends AppView
{
        
    var $template = 'list_no_customize.html';
    var $columns = array('date_created', 'date_executed', 'type', 'num_tries', 'mail_sent');
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        $this->addMsg('log_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);

        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $letter_type = $manager->getLetterTypeSelectRange($this->msg);
        
        $i = 0;

        foreach($rows as $row) {
            
            $obj->set($row);
            
            // date created
            $str = '<b>%s</b> (%s)';
            $date_formatted = $this->getFormatedDate($row['date_created'], 'datetime');
            $date_interval = $this->getTimeInterval($row['date_created']);                
            $row['date_created_formatted'] = sprintf($str, $date_interval, $date_formatted);
                            
            // date sent
            $row['date_executed_formatted'] = '';
            if($obj->get('date_sent')) {
                $date_formatted = $this->getFormatedDate($row['date_sent'], 'datetime');
                $date_interval = $this->getTimeInterval($row['date_sent']);                
                $row['date_executed_formatted'] = sprintf($str, $date_interval, $date_formatted);
            }
            
            
            $row['type'] = $letter_type[$obj->get('letter_type')];
            $row['mail_sent'] = ($obj->get('status') == 1) ? $this->msg['yes_msg'] : $this->msg['no_msg'];            

            $row += $this->getViewListVarsCustom($obj->get('id'), $obj->get('status'), 
                                                 $obj->get('failed'), $obj->get('failed_message'));
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getViewListVarsCustom($record_id = false, $active = false, $failed = true, $failed_msg = '') {
        
        $row = parent::getViewListVars($record_id, $active);
        $row['style'] = ($failed > 0 && $active != 1) ? 'color: red;' : '';
        $row['num_tries'] = ($failed == 0 && $active == 1) ? 1 : $failed;
        
        $row += $this->getVliewListLogVars($row['detail_link'], $failed_msg);
        $row['update_link'] = $row['detail_link'];
        
        return $row;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_created_msg',  'date_created', 'date_created', $this->msg['date_created_msg'], 2);
        $sort->setSortItem('date_executed_msg', 'date_sent', 'date_sent', $this->msg['date_executed_msg']);
        // $sort->setSortItem('type_msg','letter_type', 'letter_type', $this->msg['type_msg']);
        $sort->setSortItem('num_tries_msg','failed', 'failed', $this->msg['num_tries_msg']);
        // $sort->setSortItem('mail_sent_msg','status', 'status', $this->msg['mail_sent_msg']); 
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $letter_type = $manager->getLetterTypeSelectRange($this->msg);
        
        // remove pluginable
        $plugins  = AppPlugin::getPluginsFilteredOff('mailpool');
        foreach($plugins as $v) {
            $letter_type = array_diff_key($letter_type, array_flip($v['mailpool']));
        }
        
        // echo '<pre>' . print_r($letter_type, 1) . '</pre>';exit;
        $select->setRange($letter_type, array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array(
			'all'=> '__', 
			'1' => $this->msg['yes_msg'], 
			'0' => $this->msg['no_msg'])
		);

        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));  
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {

        $arr = array();
        $arr_select = array();       
        
        @$values = $_GET['filter']; 

        // letter type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND letter_type = '$v'";
        }

        // mail sent
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $v = ($v == 0) ? '0,2' : 1;
            $arr[] = "AND status IN ($v)";
        }
		
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            $v = addslashes(stripslashes($v));
            $arr[] = "AND (message LIKE '%{$v}%' OR failed_message LIKE '%{$v}%')";
        }

        $arr = implode(" \n", $arr);

        return $arr;
    }
    
    
    function getListColumns() {
        
        $options = array(
            'date_created' => array(
                'width' => 280,
                'params' => array(
                    'text' => 'date_created_formatted'
                )
            ),
            
            'date_executed' => array(
                'width' => 280,
                'params' => array(
                    'text' => 'date_executed_formatted'
                ), 
            ),
            
            'num_tries' => array(
                'width' => 60,
                'options' => 'text-align: center;',
            ),
            
            'type', 
            
            'mail_sent' => array(        
                'type' => 'text',
                'width' => 60,
                'options' => 'text-align: center;'
            )
        );
            
        return $options;
    } 
}
?>