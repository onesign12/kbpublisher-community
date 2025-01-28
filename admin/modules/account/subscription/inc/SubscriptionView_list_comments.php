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


class SubscriptionView_list_comments extends SubscriptionView_list
{

    var $template = 'list_no_customize.html';
    var $columns = array('title', 'date_subscribed');
    
    var $table_name = 'articles';

  
    function execute(&$obj, &$manager) {      

        $this->addMsg('user_msg.ini');
        

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

        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // header
        $bp = $this->getPageByPage($manager);
        $title = $this->getTitle($manager);
        $tpl->tplAssign('header', $this->titleHeaderList('', $title, []));

        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));


        // note message
        // $tpl->tplAssign('msg', $note_msg . '<br/>');

        // rows
        $table_name = $this->table_name;
        $manager->tbl->table = $manager->tbl->$table_name;

        $ids = $manager->getValuesString($rows, 'entry_id');
        $manager->setSqlParamsOrder('');
        $entries = ($ids) ? $manager->getRowsByIds($ids) : array();
        $entries = $this->stripVars($entries);
        
        $cc = &$this->getClientController();

        foreach($rows as $row) {

            $row['id'] = $row['entry_id'];
            $row['date_lastsent_formatted'] = $this->getFormatedDate($row['date_lastsent']);
            $row['date_subscribed_formatted'] = $this->getFormatedDate($row['date_subscribed']);
            
            // $row['date_posted'] = $this->getFormatedDate($row['date_posted']);
            // $row['date_updated'] = $this->getFormatedDate($row['date_updated']);
            
            // title
            $row += $this->getTitleToList($entries[$row['entry_id']]['title'], 100);
            
            $row['entry_link'] = $cc->getLink('entry', false, $row['entry_id']);

            $links = array();
            $actions = $this->getListActions($row, $links);
            $row += $this->getViewListVarsJs($row['entry_id'], true, true, $actions);

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        
        // no records show msg 
        $func = [];
        if(!$rows && empty($_GET['filter'])) {
            $msg = AppMsg::hintBoxCommon('note_comment_subscribe');
            $tpl = new tplTemplatezString($msg);
        } else {
            $func = array(
                array('tplAssign', array('by_page_bottom', $bp->nav)),
            );
        }
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html', $func);
    }
}
?>