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


class FeedbackView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // bulk
        $manager->bulk_manager = new FeedbackModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'FeedbackView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
        
        // filter sql        
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        $manager->setSqlParamsJoin($params['join']);

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        $bp_options = array('class' => 'page');
        $bp_bottom =& $this->pageByPage($manager->limit, $manager->getRecordsSql(), $bp_options);
        $this->num_records = $bp_bottom->num_records;

        if($bp_bottom->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $this->commonHeaderList($bp_bottom->nav, false, false));
        }
        
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        
        // subjects
        $subject = $manager->getSubjectSelectRange();
        
        $status_range = AppMsg::getMsgs('ranges_msg.ini', false, 'user_questions');
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('question', $obj->get('question'));
            
            $num = 250;
            $more = (strlen($obj->get('question')) > $num);
            if($more) {
                $tpl->tplSetNeeded('row/question_more');
                $tpl->tplAssign('full_question', nl2br($obj->get('question')));
                $obj->set('question', substr($obj->get('question'), 0, $num) . '...');
            }
            
            if ($obj->get('attachment')) {
                $tpl->tplSetNeeded('row/attachments');
                
                $files = explode(';', $obj->get('attachment'));
                foreach($files as $k => $file) {
                    $v = array();
                
                    $v['filename'] = basename($file);
                    
                    $more = array('type' => 'question', 'f' => $k);
                    $v['open_link'] = $this->getActionLink('file', $obj->get('id'), $more);
                    $v['download_link'] = $this->getActionLink('file_download', $obj->get('id'), $more);
                    
                    $tpl->tplParse($v, 'row/file');
                }

                $tpl->tplSetNested('row/file');
            }
            
            $username = $row['username'];
            if($row['username']) {
                $username = sprintf('%s [%s]', PersonHelper::getFullName($row), $row['username']);
            } else {
                $username = $this->msg['anonymous_user_msg'];
                if($row['name']) {
                    $username = sprintf('%s [%s]', $this->getSubstringSignStrip($row['name'], 30), $this->msg['anonymous_user_msg']);
                }
            }
            
            $tpl->tplAssign('username', $username);
            
            $date_formatted = $this->getFormatedDate($row['ts'], 'datetime');
            $tpl->tplAssign('date_formatted', $date_formatted);
            
            $date_interval = $this->getTimeInterval($row['ts']);
            $tpl->tplAssign('date_interval', $date_interval);
            
            $more = array('filter[q]' => sprintf('user_id:%d', $row['user_id']));
            $tpl->tplAssign('userfilter_link', $this->getLink('this', 'this', null, null, $more));
            
            $subj = (isset($subject[$row['subject_id']])) ? $subject[$row['subject_id']] : '';
            $tpl->tplAssign('subject', $subj);
            
            
            // actions/links
            $links = array();
            $links['email_link'] = $this->getActionLink('answer', $obj->get('id'));
            
            $active_var = ($obj->get('answered')) ? '0' : '1';
            $links['status_link'] = $this->getActionLink('answer_status', $obj->get('id'), array('status' => $active_var));
            
            $more_param = array(
                'question_id' => $obj->get('id'), 
                'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $links['update_link'] = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'question', $more_param);
            
            // answered
            $extra_class = '';
            if($obj->get('answered')) {
                $detail_link = $this->getActionLink('answer', $obj->get('id'));
                
                $tpl->tplAssign('detail_link', $detail_link);
                
                $status_yes_display = 'inline';
                $status_no_display = 'none';
                $li_status_yes_display = 'none';
                $li_status_no_display = 'block';
                
                $extra_class = 'ignoredComment';
                
            } else {
                $status_yes_display = 'none';
                $status_no_display = 'inline';
                $li_status_yes_display = 'block';
                $li_status_no_display = 'none';
            }
            
            $tpl->tplAssign('status_yes_display', $status_yes_display);
            $tpl->tplAssign('status_no_display', $status_no_display);
            $tpl->tplAssign('li_status_yes_display', $li_status_yes_display);
            $tpl->tplAssign('li_status_no_display', $li_status_no_display);
            
            $tpl->tplAssign('extra_class', $extra_class);
            
            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $links, $actions));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateStatus', $this, 'ajaxUpdateStatus'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsJsCustom($entry, $links, $actions = array()) {
        
        // another title for uppdate button
        $msg = $this->msg['answer_msg'] . ' / ' . $this->msg['move_to_entries_msg'];
        $this->msg['update_msg'] = $msg;
        
        $active_status = (!$entry['answered']);
        $row = $this->getViewListVarsJs($entry['id'], $active_status, true, $actions);
        
        // for dblclick
        // if(!empty($actions['place_to_kb'])) {
            // $row['update_link'] = $actions['place_to_kb']['link'];
        // }

        return $row;
    }


    function ajaxUpdateStatus($id, $status) {
        $objResponse = new xajaxResponse();

        $status = ($status) ? 1 : 0;
        
        $this->manager->status($status, $id, 'answered');

         if($status) {
            $objResponse->addAssign('status_yes_' . $id, 'style.display', 'none');
            $objResponse->addAssign('status_no_' . $id, 'style.display', 'inline');
            
            $objResponse->addAssign('status_text_yes_' . $id, 'style.display', 'inline');
            $objResponse->addAssign('status_text_no_' . $id, 'style.display', 'none');

        } else {
            $objResponse->addAssign('status_yes_' . $id, 'style.display', 'inline');
            $objResponse->addAssign('status_no_' . $id, 'style.display', 'none');
            
            $objResponse->addAssign('status_text_yes_' . $id, 'style.display', 'none');
            $objResponse->addAssign('status_text_no_' . $id, 'style.display', 'inline');
        }

        return $objResponse;
    }
    
    
    function ajaxDeleteComment($id) {
        $objResponse = new xajaxResponse();
        
        $this->manager->delete($id);

        $fade_js = "$('#comment_{$id}').fadeOut(1000);";
        $objResponse->script($fade_js);

        return $objResponse;
    }
    
    
    function getListActions($obj, $links) {
        
        $status_msg_str = '%s - %s';
        $new_status_msg = ($obj->get('answered')) ? $this->msg['not_answered_status_msg'] 
                                                  : $this->msg['answered_status_msg'];
        $actions = array(
            'status' => array(
                'msg' => sprintf($status_msg_str, $this->msg['set_status2_msg'], $new_status_msg),
                'link' => $links['status_link']
            ),
            'delete' => array(
                'link' => '#',
                'link_attributes' => sprintf('onclick="deleteComment(%d);"', $obj->get('id'))
            )
        );
        
        if ($obj->get('answered')) {
            $actions[] = 'detail';
        }
        
        if($this->priv->isPriv('insert', 'kb_entry')) {
            $actions['insert'] = array(
                'msg'  => $this->msg['create_article_msg'], 
                'link' => $links['update_link']
            );
        }

        
        if($this->priv->isPriv('update')) {
            $actions['update'] = array(
                'msg'  => $this->msg['answer_msg'], 
                'link' => $links['email_link']
            );  
        }
            
        return $actions;
    }
    
    
    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setDefaultSortItem('date', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('subject_msg', 'subj', 'subject_id', $this->msg['subject_msg']);
        $sort->setSortItem('email_msg', 'email', 'email', $this->msg['email_msg']);
        $sort->setSortItem('answered_status_msg','status', 'answered', $this->msg['answered_status_msg']);
        $sort->setSortItem('date_posted_msg', 'date', 'date_posted', $this->msg['date_posted_msg']);
        $sort->setSortItem('placed_status_msg', 'placed', 'placed', $this->msg['placed_status_msg']);
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearch($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);
            }
        }        
        
        return $sort;
    }    
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);

        //xajax
        $xobj = null;
        $ajax = &$this->getAjax($xobj, $manager);
        $xajax = &$ajax->getAjax();


        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // category
        $select->setRange($manager->getSubjectSelectRange(), array('all'=>'__'));
        @$subject_id = $values['c'];
        $tpl->tplAssign('subject_select', $select->select($subject_id));
        
        
        // status
        $range = AppMsg::getMsgs('ranges_msg.ini', false, 'user_questions');
        if(isset($range['all'])) { unset($range['all']); }
        $select->setRange($range, array('all'=>'__'));        
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));        
        
        // custom 
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));        
        
                
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];        
        
        @$v = $values['c'];
        if($v != 'all' && !empty($v)) {
            $id = (int) $v;
            $mysql['where'][] = "AND subject_id = '{$id}'";
            $sphinx['where'][] = "AND subject_id = $id";
        }
        
        
        @$v = $values['s'];
        if($v && $v != 'all') {
            
            if($v == 'answered' || $v == 'not_answered') {
                $val = ($v == 'answered') ? 1 : 0;
                $f = 'answered';
                
            } else {
                $val = ($v == 'placed') ? 1 : 0;
                $f = 'placed';
            }
            
            $mysql['where'][] = "AND $f = $val";
            $sphinx['where'][] = "AND $f = $val";
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
                $mysql['select'][] = "MATCH (e.title, e.question, e.answer) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (e.title, e.question, e.answer) AGAINST ('$v' IN BOOLEAN MODE)";
                
                $sphinx['match'][] = $v;
            }
        }
        
        // custom 
        @$v = $values['custom'];
        if($v) {
            $v = RequestDataUtil::stripVars($v);
            $sql = $manager->cf_manager->getCustomFieldSql($v);
            $mysql['where'][] = 'AND ' . $sql['where'];
            $mysql['join'][] = $sql['join'];
            
            $sql = $manager->cf_manager->getCustomFieldSphinxQL($v);
            if (!empty($sql['where'])) {
                $sphinx['where'][] = 'AND ' . $sql['where'];
            }
            $sphinx['select'][] = $sql['select'];
            $sphinx['match'][] = $sql['match'];
        }
        
        @$v = $values['q'];
        $options = array('index' => 'feedback');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    
    
    function getSpecialSearch() {
        $search = array(
            'id',
            'user_id',
            'username',
            'admin_id' => array(
                'search' => '#^admin(?:_id)?:(\d+)$#',
                'prompt' => 'admin_id:{user_id}')
        );
        
        return $search;
    }  
    
    
    function getSpecialSearchSql($manager, $ret, $string) {
        $mysql = array();
        if ($ret['rule'] == 'user_id') {
            $mysql['where'] = sprintf("AND e.user_id=%d", $ret['val']);
            if($ret['val'] == 0) {
                $mysql['where'] = sprintf("OR e.user_id IS NULL");
            }

        } elseif ($ret['rule'] == 'admin_id') {
            $mysql['where'] = sprintf("AND e.admin_id=%d", $ret['val']);

        } elseif ($ret['rule'] == 'username') {
            $mysql['where'] = sprintf("AND u.username ='%s'", $ret['val']);
        }
        
        return $mysql;
    }
    
    
    // Filter // -----------

    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }
    
}
?>