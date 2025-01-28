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


class UserListView_list extends AppView
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('title', 'date', 'is_mail');
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $type_msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        
        $allow_subscribe = SettingModel::getQuick(2, 'allow_subscribe_entry');
        $allow_subscribe = KBClientModel::isSubscribtionAllowed('entry', $allow_subscribe);
        
        
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        
        // bulk
        $manager->bulk_manager = new UserListModelBulk();
        
        if(!$allow_subscribe) {
            $manager->bulk_manager->actions = array('remove');
        }
        
        // changed as we use it on client and user could be without priv
        // if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) { 
            $manager->bulk_manager->setActionsAllowed($manager, $manager->priv);
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'UserListView_bulk');
            
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        // }
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // header
        $bp_options = [
            'class'=>'short',
            'limit_range' => [10]
        ];
        
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql(), $bp_options);
        
        $button = [];
        $tpl->tplAssign('header', 
            $this->commonHeaderList('', $this->getFilter($manager, $type_msg), $button));
        
        $cc = &$this->controller->getClientController();
        
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        foreach($rows as $row) {
        
            $row['id'] = sprintf('%d_%d', $row['entry_type'], $row['entry_id']);
            
            $row['date_subscribed_formatted'] = $this->getFormatedDate($row['date_subscribed'], 'datetime');
            $row['date_subscribed_interval'] = $this->getTimeInterval($row['date_subscribed']);    
            // $row['type_title'] = $type_msg[$manager->record_type[$row['entry_type']]];
            // $row['type_icon'] = ($row['entry_type'] == 1) ? '../sidebar/knowledgebase.svg' : '../sidebar/file.svg';
            
            $type_title = $type_msg[$manager->record_type[$row['entry_type']]];
            $type_icon = ($row['entry_type'] == 1) ? 'knowledgebase.svg' : 'file.svg';
            $type_icon = ($row['entry_type'] == 3) ? 'news.svg' : $type_icon;
            $type_icon = $this->controller->base_href . 'admin/images/sidebar/' . $type_icon;
            $str = '<img src="%s" title="%s" style="margin-right: 10px; width: 12px; height: 12px;" />';
            $type_icon = sprintf($str, $type_icon, $type_title);
            
            // title
            $row += $this->getTitleToList($row['title'], 100);
            $row['title_entry'] = $type_icon . $row['title_entry'];
            
            $row['is_mail'] = ($allow_subscribe) ? $row['is_mail'] : 0;
            
            // actions/links
            $links = array();
            
            $more = array('type' => $row['entry_type']);
            $links['delete_link'] = $this->getActionLink('delete', $row['entry_id'], $more);
            
            $more['mail'] = 1;
            $links['enable_mail_link'] = $this->getActionLink('mail', $row['entry_id'], $more);
            
            $more['mail'] = 0;
            $links['disable_mail_link'] = $this->getActionLink('mail', $row['entry_id'], $more);
            
            $more = array('id' => $row['entry_id']);
            if ($row['entry_type'] == 1) {
                $link = $this->getActionLink('preview', $row['entry_id']);
                $links['preview_link'] = $cc->getLink('entry', false, $row['entry_id']);
                $links['entry_link'] = $cc->getLink('entry', false, $row['entry_id']);
                
            } else {
                $links['preview_link'] = $cc->getLink('file', false, $row['entry_id'], false, array('f'=>1));
                $links['entry_link'] = $cc->getLink('file', false, $row['entry_id']);
            }
            
            $actions = $this->getListActions($row, $links, $allow_subscribe);
            
            $row['entry_link'] = $links['entry_link'];
            $row += $this->getViewListVarsJs($row['id'], true, true, $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('subscribe', $this, 'ajaxSubscribe'));
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        
        // no records show msg 
        $func = [];
        if(!$rows && empty($_GET['filter'])) {
            $msg = AppMsg::hintBoxCommon('note_entry_save');
            $tpl = new tplTemplatezString($msg);
        } else {
            $func = array(
                array('tplAssign', array('by_page_bottom', $bp->nav)),
            );
        }

        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html', $func);
    }


    function ajaxSubscribe($id, $entry_type) {
        
        $objResponse = new xajaxResponse();
        
        $user_id = AuthPriv::getUserId();

        $manager = new SubscriptionModel;        
        $manager->saveSubscription(array($id), $entry_type, $user_id, true);

        $objResponse->script('location.reload();');
        return $objResponse;
    }


    function getListActions($row, $links, $allow_subscribe) {
        
        $actions = array(
            'delete' => array(
                'msg'  => $this->msg['remove_msg'],
                'link' => $links['delete_link']
            )
        );
        
        if ($row['entry_type'] == 1) { // article
            $actions['preview'] = array(
                'msg'  => $this->msg['open_msg'],
                'link' => $links['preview_link'],
                'link_attributes' => 'target="_blank"'
            );
            
        } else {
            $actions['preview'] = array(
                'msg' => $this->msg['open_msg'],
                'link' => $links['preview_link'],
                'link_attributes' => 'target="_blank"'
            );
            
            $actions['file'] = array(
                'msg'  => $this->msg['download_msg'],
                'link' => $links['entry_link']
            );
        }
        
        $bulk_msg = AppMsg::getMsg('bulk_msg.ini', false, 'bulk_user_list');
        
        if ($allow_subscribe) {
            if ($row['is_mail']) {
                $actions['custom1'] = array(
                    'msg'  => $bulk_msg['disable_mail'],
                    'link' => $links['disable_mail_link']
                );
            
            } else {
                $actions['custom1'] = array(
                    'msg'  => $bulk_msg['enable_mail'],
                    'link' => $links['enable_mail_link']
                );
            }
        }
        
        // echo '<pre>', print_r($actions,1), '<pre>';
        return $actions;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_added_msg', 'date_subscribed', 'date_subscribed', $this->msg['date_added_msg'], 2);
        
        return $sort;
    }
    
    
    function getFilter($manager, $type_msg) {
        
        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;    
        
        //type
        @$type = $values['e'];
        $range = array(
            'all'=> '__',
            1 => $type_msg['article'],
            2 => $type_msg['file']
        );

        $select->setRange($range);
        $tpl->tplAssign('type_select', $select->select($type));
        
        //email
        @$is_email = $values['s'];
        $range = array(
            'all'=> '__',
            1 => $this->msg['yes_msg'],
            0 => $this->msg['no_msg']
        );
        
        $select->setRange($range);
        $tpl->tplAssign('email_select', $select->select($is_email));
        
        
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
        
        // subscribers
        $sphinx['where'][] = 'AND subscriber = ' . $manager->user_id;
        
        // type
        @$v = $values['e'];
        if($v != 'all' && isset($values['e'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND s.entry_type = '$v'";
            $sphinx['where'][] = "AND source_id = $v";
        }
        
        // email
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND s.is_mail = '$v'";
            
            if ($v) {
                $sphinx['where'][] = 'AND email_subscriber = ' . $manager->user_id;
            } else {
                $sphinx['where'][] = 'AND email_subscriber != ' . $manager->user_id;
            }
        }
        
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearch($v)) {
                $sql = $this->parseSpecialSearchSql($manager, $ret, $v);
                $mysql = array_merge_recursive($mysql, $sql);
                
            } else {
                $v = addslashes(stripslashes($v));
                
                $mysql['select'][] = "MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v') AS kb_score";
                $mysql['select'][] = "MATCH (f.title, f.filename_index, f.meta_keywords, f.description, f.filetext) AGAINST ('$v') AS file_score";
                $mysql['where'][] = "AND (MATCH (e.title, e.body_index, e.meta_keywords, e.meta_description) AGAINST ('$v' IN BOOLEAN MODE)
                    OR MATCH (f.title, f.filename_index, f.meta_keywords, f.description, f.filetext) AGAINST ('$v' IN BOOLEAN MODE))";
                    
                $sphinx['match'][] = $v;
            }
        }

        
        @$v = $values['q'];
        $options = array('index' => array('article', 'file'), 'id_field' => 'entry_id');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);

        return $arr;
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'title' => array(
                'type' => 'link',
                'params' => array(
                    'link' => 'entry_link ',
                    'options' => '%target="_blank" rel="noopener noreferrer"%',
                    'title' => 'title_title',
                    'text' => 'title_entry')
            ),
            
            'date' => array(
                'type' => 'text_tooltip',
                'title' => 'date_added_msg',
                'width' => 150,
                'class' => 'hide-for-small-only',
                'params' => array(
                    'text' =>  'date_subscribed_interval',
                    'title' => 'date_subscribed_formatted')
            ),
            
            'is_mail' => array(
                'type' => 'bullet',
                'title' => 'email_subscribed_msg',
                'width' => 50,
                'options' => 'text-align: center;',
                'class' => 'hide-for-small-only'
            )
        );
            
        return $options;
    }

}
?>