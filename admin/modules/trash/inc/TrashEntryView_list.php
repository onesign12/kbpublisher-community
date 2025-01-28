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

class TrashEntryView_list extends AppView
{
        
    var $template = 'list.html';
    var $columns = array('date_deleted', 'entry_id', 'title', 'type');
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // filter sql
        $manager->setSqlParams($this->getFilterSql($manager));
        
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        if(!AppPlugin::isPlugin('news')) {
            $news_type = array_flip($manager->record_type)['news'];
            $manager->setSqlParams("AND entry_type != $news_type");
        }
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('entry_obj'));
        $entry_type = $manager->getEntryTypeSelectRange();
       
        $users = array();
        $user_ids = $manager->getValuesString($rows, 'user_id');
        if (!empty($user_ids)) {
            $users = $manager->getUserByIds($user_ids);
        }
        
        // empty button
        $button = array();
        if($rows && $this->priv->isPriv('delete')) {
            $button[$this->msg['empty_trash_msg']] = 'javascript:emptyTrash()';
        }
        
        $options = array('bulk_form' => false);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), $button, $options));
        
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $row['type'] = $entry_type[$row['entry_type']];
            
            // title
            $class_name = $manager->record_type[$obj->get('entry_type')];
            $title = htmlspecialchars(TrashAction::getTitle($class_name, $row['entry_obj']));
            $row += $this->getTitleToList($title, 100);
            
            // users
            $user = ExtFunc::arrayValue($users, $obj->get('user_id'));
            $row += $this->getUserToList($user, 'user', $obj->get('user_id'));
            
            // dates
            $row += $this->getDateToList($row['date_deleted'], 'date_deleted', 'datetime', $user);
            
            // actions/links
            $link = $this->getActionLink('preview', $row['id']);
            $links = array();
            
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            if($row['entry_type'] == 2) { // file
                $links['preview_link'] = $link;
            }
            
            $links['restore_link'] = $this->getActionLink('restore', $row['id']);
            $row['preview_link'] = $links['preview_link'];
            
            $actions = $this->getListActions($links);
            $row += $this->getViewListVarsJs($row['id'], true, true, $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        
        $args = array(
            'sure_empty_trash_msg' => $this->msg['sure_empty_trash_msg'],
            'empty_link' => $this->controller->_replaceArgSeparator($this->getActionLink('empty'))
        );
        
        $func = array(
            array('tplAssign', array($args))
        );
            
        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html', $func);
    }
    
    
    function getListActions($links) {
        
        $actions = array();
        
        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'], 
            'link' => $links['preview_link']);
            
        if($this->priv->isPriv('update')) {
            $actions['restore'] = array(
                'msg'  => $this->msg['put_back_msg'],
                'confirm_msg'  => $this->msg['sure_common_msg'],
                'link' => $links['restore_link']);
        }
        
        if($this->priv->isPriv('delete')) {
            $actions['delete'] = array(
                'confirm_msg'  => $this->msg['sure_erase_item_msg']
            );
        }
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_deleted_msg', 'date_deleted', 'date_deleted', $this->msg['date_deleted_msg'], 2);
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // entry type
        $real_range = $manager->getEntryTypesInTrash();
        $news_type = array_flip($manager->record_type)['news'];
        if(isset($real_range[$news_type]) && !AppPlugin::isPlugin('news')) {
            unset($real_range[$news_type]);
        }
        $range = $manager->getEntryTypeSelectRange();
        $range = array_intersect_key($range, $real_range);

        $select->setRange($range, array('all'=> '__'));
        $type_id = (isset($values['entry_type'])) ? $values['entry_type'] : 'all';
        $tpl->tplAssign('entry_type_select', $select->select($type_id));

        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // entry type
        @$v = $values['entry_type'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND entry_type = '$v'";
        }
        
        return implode(" \n", $arr);
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'date_deleted' => array(
                'type' => 'text_tooltip',
                'title' => 'date_deleted_msg',
                'width' => 160,
                'params' => array(
                    'text' =>  'date_deleted_formatted',
                    'title' => 'date_deleted_full')
            ),
            
            'entry_id' => array(
                'type' => 'link_tooltip',
                'width' => 80,
                'params' => array(
                    'link' => 'preview_link', 
                    'options' => 'entry_link_option'
                )
            ),
            
            'title' => array(
                'type' => 'link_tooltip',
                'params' => array(
                    'link' => 'preview_link',
                    'title' => 'title_title',
                    'text' => 'title_entry')
            ),
            
            'type',
            
            'user' => array(
                'type' => 'text_tooltip',
                'title' => 'user_msg',
                'width' => 150,
                'params' => array(
                    'text' => 'user',
                    'title' => 'user_title')
            ),
        );
            
        return $options;
    }   
}
?>