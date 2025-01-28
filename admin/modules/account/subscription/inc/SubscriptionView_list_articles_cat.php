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


class SubscriptionView_list_articles_cat extends SubscriptionView_list
{
    
    var $template = 'list_no_customize.html';
    var $columns = array('title', 'date_subscribed');

    var $table_name = 'category_articles';
    var $entry_type = 11;
    var $client_view_str = 'index';

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $bp = $this->getPageByPage($manager);
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $is_all = (isset($rows[0]['entry_id']) && $rows[0]['entry_id'] == 0);
        $add_button = false;
        
        $add_link = $this->getActionLink('category');
        // $add_link = $this->controller->getFullLink('account', 'this', false, 'category');
        $add_link = "javascript: PopupManager.create('{$add_link}');void(0)";
        if (!$is_all) {
            $msg = 'insert';
            $add_button = array($msg => $add_link);
        }
        

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // bulk
        $manager->bulk_manager = new SubscriptionModelBulk();
        
        // changed as we use it on client and user could be without priv
        // if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) { 
            $manager->bulk_manager->setActionsAllowed($manager, $manager->priv);
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'SubscriptionView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        // }

        // header generate
        $title = $this->getTitle($manager);
        $tpl->tplAssign('header', $this->titleHeaderList('', $title, $add_button));

        if($is_all) {
            $entries[0]['id'] = 'all';
            $entries[0]['name'] = $this->msg['all_categories_msg'];
            $entries[0]['cat_path'] = $this->msg['all_categories_msg'];
            
        } else {
            
            $ids = $manager->getValuesString($rows, 'entry_id');

            // rows
            $table_name = $this->table_name;
            $manager->tbl->table = $manager->tbl->$table_name;

            $manager->setSqlParamsOrder('');
            $entries = ($ids) ? $manager->getRowsByIds($ids) : array();
            $entries = $this->stripVars($entries);
    
            $am = $this->getCategoryManager($manager);
            $full_categories = &$am->cat_manager->getSelectRangeFolow();
            $full_categories = $this->stripVars($full_categories);

            foreach($entries as $id => $row) {
                $entries[$id]['cat_path'] = $full_categories[$id];
            }            
        }
        
        $cc = &$this->getClientController();
        
        foreach($rows as $row) {

            $row['id'] = $row['entry_id'];
            $row['date_lastsent_formatted'] = $this->getFormatedDate($row['date_lastsent']);
            $row['date_subscribed_formatted'] = $this->getFormatedDate($row['date_subscribed']);
            
            // title
            $row += $this->getTitleToList($entries[$row['entry_id']]['name'], 100);

            $row['entry_link'] = $cc->getLink($this->client_view_str, $row['entry_id']);
            if($row['entry_link'] == 'all') {
                $row['entry_link'] = $cc->getLink($this->client_view_str);
            }    

            $links = array();
            $links['entry_link'] = $row['entry_link'];
            $actions = $this->getListActions($row, $links);
            $row += $this->getViewListVarsJs($row['entry_id'], true, true, $actions);

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('saveCategories', $this, 'ajaxSaveCategories'));

        
        // no records show msg 
        $func = [];
        if(!$rows && empty($_GET['filter'])) {
            $link = sprintf('<a href="%s">%s</a>', $add_link, $this->msg['subscribe_msg']);
            $msg = AppMsg::hintBoxCommon('note_category_subscribe', array('link' => $link));
            $tpl = new tplTemplatezString($msg);
        } else {
            $func = array(
                array('tplAssign', array('by_page_bottom', $bp->nav)),
            );
        }
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html', $func);
    }
    

    function getCategoryManager($manager) {
        return $manager->getArticleManager();
    }
    
    
    function ajaxSaveCategories($category_ids) {
        $objResponse = new xajaxResponse();
        
        $uid = AuthPriv::getUserId();
        $type = $this->controller->getMoreParam('type');
        
        if(in_array('0', $category_ids)) { // all
            $this->manager->deleteByEntryType($type, $uid);
            $this->manager->saveSubscription(array(0), $type, $uid);
            
        } else {
            $this->manager->deleteSubscription(0, $type, $uid);
            $this->manager->saveSubscription($category_ids, $type, $uid);
        }
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        
        return $objResponse;
    }
}
?>