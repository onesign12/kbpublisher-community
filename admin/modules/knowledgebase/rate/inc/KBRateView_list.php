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


class KBRateView_list extends KBCommentView_list
{
    
    var $tmpl = 'list.html';

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        $this->escapeMsg(array('sure_delete_entry_comment_msg'));
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/comment/template/';
        $tpl = new tplTemplatez($template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBRateModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'KBRateView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }
    
        // filter sql        
        $params = $this->getFilterSql($manager, 'ratingFeedback');
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
        
        if($this->priv->isPriv('update')) {
            $tpl->tplSetNeeded('/update');
        }
        
        // status_msg
        $status = $manager->getEntryStatusData();        
        
        $client_controller = &$this->controller->getClientController();
        
        // BB CODE
        $parser = KBCommentView_helper::getBBCodeObj();
                        
        $tpl->tplSetNeededGlobal('colorbox');
        
        // list records
        foreach($rows as $row) {
            $this->parseCommentRow($tpl, $row, $obj, $manager, $parser, $num_comment);
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $this->parser = $parser;
        
        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateCommentStatus', $this, 'ajaxUpdateCommentStatus'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function parseCommentRow($tpl, $row, $obj, $manager, $parser, $num_comment = false, $active = false) {
        $extra_class = '';
        $active = $row['active'];
        if ($row['active'] == 2) {
            $active = 0;
            $extra_class = 'ignoredComment';
        }
        $row['extra_class'] = $extra_class;
        
        parent::parseCommentRow($tpl, $row, $obj, $manager, $parser, $num_comment, $active);
    }
    
    
    function getListActions($obj, $manager, $links) {
        $actions = array(
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
        
        
        // status
        $status = $manager->getListSelectRange(true, false);
        $sub_actions = array();
        foreach ($status as $k => $v) {
            $status_display = ($obj->get('active') == $k) ? 'none' : 'block';
            
            $sub_actions[] = array(
                'msg' => $v,
                'link' => '#',
                'link_attributes' => sprintf('onclick="updateCommentStatus(%d, %d);"', $obj->get('id'), $k),
                'li_attributes' => sprintf(' id="status_%d_%d" style="display: %s;"', $obj->get('id'), $k, $status_display)
            );
        }
        
        $actions['status'] = $sub_actions;
        
        return $actions;
    }


    function getStatusRange($manager) {
        return $manager->getEntryStatusData();
    }
    
    
    function ajaxUpdateCommentStatus($id, $status) {
        $objResponse = new xajaxResponse();
        
        $this->manager->status($status, $id);
        
        $status_range = $this->manager->getEntryStatusData();
        
        $script = sprintf('$("#status_box_%s > div").css("background", "%s");', $id, $status_range[$status]['color']);
        $objResponse->script($script);
        
        $script = sprintf('$("#status_box_%s > div").attr("title", "%s");', $id, $status_range[$status]['title']);
        $objResponse->script($script);
        
        $script = sprintf('$("#status_text_%s").html("%s");', $id, $status_range[$status]['title']);
        $objResponse->script($script);
        
        $script = sprintf('$("li[id^=status_%s_]").show();', $id);
        $objResponse->script($script);
        
        $script = sprintf('$("#status_%s_%s").hide();', $id, $status);
        $objResponse->script($script);
        
        if ($status == 2) {
            $script = sprintf('$("#comment_%s").addClass("ignoredComment");', $id);
            
        } else {
            $script = sprintf('$("#comment_%s").removeClass("ignoredComment");', $id);
        }
        
        $objResponse->script($script);
        
        return $objResponse;
    }
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/comment/template/';
        $tpl = new tplTemplatez($template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // status
        $v = (!empty($values['s'])) ? $values['s'] : 'all';
        $extra_range = array('all'=>'__');
        $select->setRange($manager->getListSelectRange(false), $extra_range);
        $tpl->tplAssign('status_select', $select->select($v));        
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
       
}
?>