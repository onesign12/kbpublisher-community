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


class KBCommentView_list extends AppView
{
    
    var $tmpl = 'list.html';

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        $this->escapeMsg(array('sure_delete_entry_comment_msg'));

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBCommentModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'KBCommentView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
    
        // BB CODE
        $parser = KBCommentView_helper::getBBCodeObj();
    
        // filter sql        
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);    
    
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));

        // bottom page by page
        if($bp->num_pages > 1) {
            $bp_options = array('class' => 'page');
            $bp_bottom =& $this->pageByPage($manager->limit, $count, $bp_options);
            $tpl->tplAssign('page_by_page_bottom', $bp_bottom->nav);
        }
        
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        
        // comments num
        if($rows) {
            $entry_ids = $manager->getValuesString($rows, 'entry_id');
            $num_comment = $manager->getCountCommentsPerEntry($entry_ids);
        }
        
        $client_controller = &$this->controller->getClientController();
        
        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/update');
        }
        
        // list records
        foreach($rows as $row) {
            $this->parseCommentRow($tpl, $row, $obj, $manager, $parser, $num_comment);
        }

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $this->parser = $parser;
        
        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function parseCommentRow($tpl, $row, $obj, $manager, $parser, $num_comment = false, $active = false) {
        
        $obj->set($row);
        // $obj->set('comment', nl2br($parser->qparse($obj->get('comment'))));
        $row['comment'] = nl2br($parser->qparse($obj->get('comment')));
        
        $tpl->tplAssign('raw_comment', $obj->get('comment'));
        $tpl->tplAssign('title', $row['title']);
        
        if ($num_comment) {
            $tpl->tplSetNeeded('row/filter_link');
            $tpl->tplAssign('num_comment', $num_comment[$row['entry_id']]);
            $tpl->tplAssign('short_title', $this->getSubstringStrip($row['title'], 50));
        }
        
        $date_formatted = $this->getFormatedDate($row['date_posted'], 'datetime');
        $tpl->tplAssign('date_formatted', $date_formatted);

        $date_interval = $this->getTimeInterval($row['date_posted']);
        $tpl->tplAssign('date_interval', $date_interval);
        
        $username = $row['username'];
        if($row['username']) {
            $username = sprintf('%s [%s]', PersonHelper::getFullName($row), $row['username']);
            $tpl->tplAssign('email', $row['r_email']); // user email
        
        } else {
            $username = $this->msg['anonymous_user_msg'];
            if(!empty($row['name'])) {
                $username = sprintf('%s [%s]', $this->getSubstringSignStrip($row['name'], 30), $this->msg['anonymous_user_msg']);
            }
        }
        
        $tpl->tplAssign('username', $username);
        
        // filter link
        $more = array('entry_id' => $obj->get('entry_id'));
        $tpl->tplAssign('filter_link', $this->getActionLink('entry', false, $more));
                 
        
        // actions/links
        $links = array();
        
        $more = array(
            'id' => $obj->get('entry_id'),
            'referer' => WebUtil::serialize_url($this->controller->getCommonLink())
        );
        $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);
        $links['entry_detail_link'] = $link;
        
        // status
        $status = $this->getStatusRange($manager);
        if($this->priv->isPriv('status')) {
            $tpl->tplAssign('status', $status[$row['active']]['title']);
            if (!empty($status[$row['active']]['color'])) {
                $tpl->tplAssign('color', $status[$row['active']]['color']);
            }
        }
        
        $actions = $this->getListActions($obj, $manager, $links);
        if ($active === false) {
            $active = $obj->get('active');
        }
        $row += $this->getViewListVarsJs($obj->get('id'), $active, true, $actions);
        
        $more = array('id' => $obj->get('id'));
        $link = $this->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);
        $tpl->tplAssign('detail_link', $link);
        
        $extra_class = '';
        if (!$row['active']) {
            $extra_class = 'ignoredComment';
        }
        
        if (empty($row['extra_class'])) {
            $row['extra_class'] = '';
        }
        
        $row['extra_class'] .= $extra_class;
        
        $tpl->tplParse(array_merge($row, $this->msg), 'row');
    }


    function getListActions($obj, $manager, $links) {
        $actions = array(
            'status',
            'update' => array(
                'link' => '#',
                'link_attributes' => sprintf('onclick="$(\'#comment_%d\').find(\'.formatted_comment\').click();"', $obj->get('id'))
            ),
            'delete' => array(
                'link' => '#',
                'link_attributes' => sprintf('onclick="deleteComment(%d);"', $obj->get('id'))
            )
        );
        
        if($this->priv->isPriv('select', 'kb_entry')) {
            $actions['detail'] = array(
                'msg'  => $this->msg['entry_detail_msg'], 
                'link' => $links['entry_detail_link']
            );
        }
        
        return $actions;
    }
    
    
    function getStatusRange($manager) {
        $range = array(
            0 => array(
                'title' => $this->msg['status_not_published_msg']
            ),
            1 => array(
                'title' => $this->msg['status_published_msg']
            )
        );
        
        return $range;
    }


    function ajaxUpdateComment($id, $comment) {
        $objResponse = new xajaxResponse();

        $data = $this->manager->getById($id);
        $this->obj->set($data);

        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $this->obj->set('comment', $comment);

        $this->manager->save($this->obj);
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'));

        $objResponse->script('$("#comment").val("");');

        $comment = $this->obj->get('comment');
        $comment = RequestDataUtil::stripVars($comment, array(), 'stripslashes');
        $objResponse->call('insertUpdatedComment', $id, nl2br($this->parser->qparse($comment)), $comment);

        return $objResponse;
    }
    
    
    function ajaxDeleteComment($id) {
        $objResponse = new xajaxResponse();

        $this->manager->delete($id);
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'));

        $fade_js = "$('#comment_{$id}, #template_{$id}').fadeOut(1000, function() {location.reload();});";
        $objResponse->script($fade_js);

        return $objResponse;
    }
    
    
    function _ajaxAddComment($obj, $comment, $status) {
        $objResponse = new xajaxResponse();

        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $obj->set('comment', $comment);

        $obj->set('date_posted', null);
        $obj->set('user_id', AuthPriv::getUserId());
        $obj->set('active', $status);

        $comment_id = $this->manager->save($obj);

        $objResponse->script('location.reload();');
        
        return $objResponse;
    }
    
    
    function _ajaxDeleteAllComments() {

        $objResponse = new xajaxResponse();

        $this->manager->deleteByEntryId($this->obj->get('entry_id'));
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'), NULL);

        $objResponse->addAssign('commentsBlock', 'innerHTML', '');
        $objResponse->script('location.reload();');

        return $objResponse;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setDefaultSortItem('date', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_posted_msg', 'date', 'c_date_posted', $this->msg['date_posted_msg']);
        
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

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_published_msg'],
               0 => $this->msg['status_not_published_msg']);
        
        $select->setRange($range);    
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager, $index = 'comment') {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $status = (int) $v;
            $mysql['where'][] = "AND c.active = '$status'";
            $sphinx['where'][] = "AND active = $status";
        }
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v, 'c.id');
                $mysql = array_merge_recursive($mysql, $sql);
            
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (c.comment) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (c.comment) AGAINST ('$v' IN BOOLEAN MODE)";
                
                $sphinx['match'][] = $v;
            }
        }        
        
        @$v = $values['q'];
        $options = array('index' => $index, 'id_field' => 'c.id');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }


    
    function getSpecialSearch() {
        return array('id');
    }
}
?>