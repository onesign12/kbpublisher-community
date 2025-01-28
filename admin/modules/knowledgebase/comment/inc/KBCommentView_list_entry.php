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


class KBCommentView_list_entry extends KBCommentView_list
{

    var $tmpl = 'list.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBCommentModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            
            $view = new KBCommentView_bulk;
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
        
        // filter sql
        // $params = $this->getFilterSql($manager);
        // $manager->setSqlParams($params['where']);
        // $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParams(sprintf("AND e.id = %d", $entry_id));
        
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
        if ($this->controller->page == 'kb_comment') {
            $back_link = $this->controller->getLink('knowledgebase', 'kb_comment');
            
            $entry = $manager->getArticleData($entry_id);
            
            // subscription
            $icon_block = '';
            $setting = SettingModel::getQuick(100, 'allow_subscribe_comment');
            if ($setting) {
                $icon = '<svg class="%s" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#ffffff" d="M12 12.713l-11.985-9.713h23.971l-11.986 9.713zm-5.425-1.822l-6.575-5.329v12.501l6.575-7.172zm10.85 0l6.575 7.172v-12.501l-6.575 5.329zm-1.557 1.261l-3.868 3.135-3.868-3.135-8.11 8.848h23.956l-8.11-8.848z"/>
                </svg>';
                $icon_class = 'unsubscribed';
                $new_status = 1;
                $title = $this->msg['subscribe_msg'];
                if ($manager->isUserSubscribedToComments($obj->get('entry_id'))) {
                    $icon_class = 'subscribed';
                    $new_status = 0;
                    $title = $this->msg['unsubscribe_msg'];
                }
                
                $icon = sprintf($icon, $icon_class);
                
                $icon_block = '<div id="subscribe_button" title="%s" onclick="xajax_subscribe(%s);"
                    style="float: right;margin-right: 20px;cursor: pointer;">%s</div>';
                $icon_block = sprintf($icon_block, $title, $new_status, $icon);
            }
            
            $left_side = '<div style="float: left;" class="commentEntryTitle">[%d], %s</div>%s';
            $left_side = sprintf($left_side, $entry_id, $this->getSubstringStrip($entry['title'], 80), $icon_block);
            
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
            $bulk_link = $this->controller->getLink('knowledgebase', 'kb_comment', '', 'bulk', $more);
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

        // list records
        foreach($rows as $row) {
            $this->parseCommentRow($tpl, $row, $obj, $manager, $parser);
        }

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        if ($this->controller->page == 'kb_comment') {
            $more = array('entry_id' => $this->controller->getMoreParam('entry_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }

        $this->parser = $parser;

        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        $xajax->registerFunction(array('addComment', $this, 'ajaxAddComment'));
        $xajax->registerFunction(array('subscribe', $this, 'ajaxSubscribe'));
        $xajax->registerFunction(array('deleteAllComments', $this, 'ajaxDeleteAllComments'));

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->setStatusFormVars(1, false));

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getCommentForm(&$obj, &$manager) {
        $tpl = new tplTemplatez($this->template_dir . 'form_comment.html');
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function ajaxAddComment($comment, $status) {
        $obj = new KBComment;
        
        $entry_id = ($this->controller->page == 'kb_comment') ? $_GET['entry_id'] : $_GET['id'];
        $obj->set('entry_id', $entry_id);
        
        $this->manager->updateCommentDateForEntry($obj->get('entry_id'));
        
        return $this->_ajaxAddComment($obj, $comment, $status);
    }


    function ajaxSubscribe($value) {

        $objResponse = new xajaxResponse();

        $value = (int) $value;
        $user_id = AuthPriv::getUserId();

        
        $manager = new SubscriptionModel;

        if($value) {
            $manager->saveSubscription(array($this->obj->get('entry_id')), 31, $user_id);
            $objResponse->script('$("#subscribe_button svg").removeClass("unsubscribed");');
            $objResponse->script('$("#subscribe_button svg").addClass("subscribed");');
            $objResponse->script('$("#subscribe_button").attr("onclick", "xajax_subscribe(0);");');
            $objResponse->script(sprintf('$("#subscribe_button").attr("title", "%s");', $this->msg['unsubscribe_msg']));

        } else {
            $manager->deleteSubscription(array($this->obj->get('entry_id')), 31, $user_id);
            $objResponse->script('$("#subscribe_button svg").removeClass("subscribed");');
            $objResponse->script('$("#subscribe_button svg").addClass("unsubscribed");');
            $objResponse->script('$("#subscribe_button").attr("onclick", "xajax_subscribe(1);");');
            $objResponse->script(sprintf('$("#subscribe_button").attr("title", "%s");', $this->msg['subscribe_msg']));
        }

        return $objResponse;
    }


    function ajaxDeleteAllComments() {
        $this->manager->updateCommentDateForEntry($this->obj->get('entry_id'), NULL);
        return $this->_ajaxDeleteAllComments();
    }
    
}
?>