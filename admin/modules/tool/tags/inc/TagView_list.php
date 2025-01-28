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

class TagView_list extends AppView
{
    
    var $template = 'list.html';
    var $columns = array('title', 'description', 'date_added', 'entries', 'active');
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('random_msg.ini');
        
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
		
        $tpl->tplAssign('msg', $this->getShowMsg2($manager));
				
        // bulk
        $manager->bulk_manager = new TagModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'TagView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
                   
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id');
        
        // attached to entries
        $entries_num = ($ids) ? $manager->getReferencedEntriesNum($ids) : array();
        $entry_type_msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        
        $tip_str = '<b>%s</b>: <a href=\'%s\'>%d</a>';
        
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            $row['date_posted_interval'] = $this->getTimeInterval($row['date_posted']);
            $row['date_posted_formatted'] = $this->getFormatedDate($row['date_posted']);
            $row['date_posted_formatted_full'] = $this->getFormatedDate($row['date_posted'], 'datetime');
            
            $row['entries_num'] = '--';
            $row['entries_num_title'] = '';
            
            $in_use = isset($entries_num[$row['id']]);
            if($in_use) {
                
                $entries_num_title = array();
                foreach ($entries_num[$row['id']] as $entry_type => $entry_num) {
                    
                    $msg_key = $manager->record_type[$entry_type];
                    $more = array('filter[q]'=>'tag:'.$row['title']);
                    
                    $url_params = $manager->entry_type_to_url[$entry_type];
                    $link = $this->getLink($url_params[0], $url_params[1], false, false, $more);
                    
                    $entries_num_title[] = sprintf($tip_str, $entry_type_msg[$msg_key], $link, $entry_num);  
                }
                
                $row['entries_num'] = array_sum($entries_num[$row['id']]);
                $row['entries_num_title'] = implode('<br/>', $entries_num_title);
            }

            $row += $this->getViewListVarsJs($obj->get('id'), $obj->get('active'));
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);    
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);    
        
        $sort->setSortItem('tag_msg',  'title', 'title',   $this->msg['tag_msg']);
        $sort->setSortItem('date_added_msg',  'dp', 'date_posted',   $this->msg['date_added_msg'], 2);
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;        
        
        //status
        @$status = $values['s'];
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_visible_msg'],
               0 => $this->msg['status_hidden_msg']
              );
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
                
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND active = '$v'";
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
                $mysql['where'][]  = "AND (title LIKE '%{$v}%')";
                $sphinx['match'][] = $v;
            }        
        }
        
        @$v = $values['q'];
        $options = array('index' => 'tag', 'id_field' => 'id');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
	
	
	function getShowMsg2($manager) {
	    
        @$key = $_GET['show_msg2'];
        if ($key == 'note_remove_tag_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_tags_bulk'];
            $msg['body'] = $msgs['note_remove_tag_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'id', 
            
            'title' => array(
                'type' => 'text',
                'title' => 'tag_msg'
            ),  
                
            'description',
            
            'date_added' => array(
                'type' => 'text_tooltip',
                'title' => 'date_added_msg',
                'width' => 120,
                'params' => array(
                    'text' => 'date_posted_interval',
                    'title' => 'date_posted_formatted_full')
            ),
            
            'entries' => array(
                'type' => 'text_tooltip_width',
                'title' => 'entries_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'entries_num',
                    'title' => 'entries_num_title')
            ),
            
            'active' => array(
                'type' => 'text',
                'title' => 'status_visible_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'active_img')
            ), 
        );
            
        return $options;
    } 
 
}
?>