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


class KBRateView_list_entry extends KBRateView_list
{
    
    var $tmpl = 'list.html';

    
    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/comment/template/';
        $tpl = new tplTemplatez($template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBRateModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            
            $view = new KBRateView_bulk;
            $bulk = $view->execute($obj, $manager);
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
        
        $entry_id = $obj->get('entry_id');    
        $client_controller = &$this->controller->getClientController();
        
        // when comment id in url set style
        if(isset($_GET['comment_id'])) {
            $tpl->tplAssign('comment_id', (int) $_GET['comment_id']);
            $tpl->tplSetNeeded('/comment_id_css');
        }
        
        // header generate
        $button = array();
    
        // delete all link
        /*$more = array('entry_id' => $entry_id);
        $tpl->tplAssign('delete_entry_link', $this->getActionLink('delete_entry', false, $more));*/
        
        // filter sql    
        // $params = $this->getFilterSql($manager, 'ratingFeedback');
        // $manager->setSqlParams($params['where']);
        // $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParams(sprintf("AND e.id = %d", $entry_id));
        
        // status, all, for list
        $status_range_list = $manager->getListSelectRange(false);
        
        
        $bp_options = array('get_name' => 'bp2');
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count, $bp_options);
    
        if(!$bp->num_records) {
            return AppView::getNoRecordsBox();
        }
        
        // add comment
        if($this->priv->isPriv('insert')) {
            $button['insert'] = "javascript:$('#comment_form').toggle();$('#comment').focus();return false";
        }
        
        // feedback module
        if ($this->controller->page == 'kb_rate') {
            $back_link = $this->controller->getLink('knowledgebase', 'kb_rate');
            
            $entry = $manager->getArticleData($entry_id);
            
            $left_side = '<div class="commentEntryTitle">[%d], %s</div>';
            $left_side = sprintf($left_side, $entry_id, $this->getSubstringStrip($entry['title'], 80));
            
            $button['...'][] = array(
                'msg' => $this->msg['entry_public_link_msg'],
                'link' => $client_controller->getLink('entry', false, $entry_id)
            );
            
            // update article link
            $more = array(
                'id' => $obj->get('entry_id'), 
                'referer' => WebUtil::serialize_url($this->controller->getCommonLink())
            );
            $detail_link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);
                
            $button['...'][] = array(
                'msg' => $this->msg['entry_detail_msg'],
                'link' => $detail_link
            );
        
        // kb_entry module
        } else {
            $left_side = '';
        }
        
        // delete
        if($this->priv->isPriv('delete')) {
            $button['...'][] = array(
                'msg' => $this->msg['delete_entry_comment_msg'],
                'link' => sprintf("javascript:deleteAllComments('%s');", $this->msg['sure_delete_entry_comment_msg'])
            );
        }
        
        
        $options = array();
        if ($this->controller->page == 'kb_entry') {
            $more = array('referer' => WebUtil::serialize_url($this->controller->getCurrentLink()));
            $bulk_link = $this->controller->getLink('knowledgebase', 'kb_rate', '', 'bulk', $more);
            $options = array('bulk_action' => $bulk_link);
        }
        
        $bp_form = $this->getCommentForm($obj, $manager) . $bp->nav;
        $tpl->tplAssign('header', $this->commonHeaderList($bp_form, $left_side, $button, $options));
        
        
        if($bp->num_pages > 1) {
            $bp_options = array('class' => 'page') + $bp_options;
            $bp_bottom =& $this->pageByPage($manager->limit, $count, $bp_options);
            $tpl->tplAssign('page_by_page_bottom', $bp_bottom->nav);
        }
        
        // sort generate
        // $sort = $this->getSort();
        // $manager->setSqlParamsOrder($sort->getSql());
        $manager->setSqlParamsOrder('ORDER BY c_date_posted DESC');
        
        // get records
        $rows = $manager->getRecords($bp->limit, $bp->offset);
        
        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/update');
        }
        
        // BB CODE
        $parser = KBCommentView_helper::getBBCodeObj();
        
        $tpl->tplSetNeededGlobal('colorbox');
        
        // list records
        foreach($rows as $row) {
            $this->parseCommentRow($tpl, $row, $obj, $manager, $parser);
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        if ($this->controller->page == 'kb_rate') {
            $more = array('entry_id' => $this->controller->getMoreParam('entry_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }
        
        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        $xajax->registerFunction(array('addComment', $this, 'ajaxAddComment'));
        $xajax->registerFunction(array('updateCommentStatus', $this, 'ajaxUpdateCommentStatus'));
        $xajax->registerFunction(array('deleteAllComments', $this, 'ajaxDeleteAllComments'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->setStatusFormVars(1, false));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getCommentForm(&$obj, &$manager) {
        $tpl = new tplTemplatez($this->template_dir . 'form_comment.html');
        
        // status, active only, for new comment
        $status_range = $manager->getListSelectRange(true);

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->setSelectName('active');
        $select->setRange($status_range);
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxAddComment($comment, $status) {
        $obj = new KBRate;
        
        $entry_id = ($this->controller->page == 'kb_rate') ? $_GET['entry_id'] : $_GET['id']; 
        $obj->set('entry_id', $entry_id);
        
        return $this->_ajaxAddComment($obj, $comment, $status);
    }
    
    
    function ajaxDeleteAllComments() {
        return $this->_ajaxDeleteAllComments();
    }
}
?>