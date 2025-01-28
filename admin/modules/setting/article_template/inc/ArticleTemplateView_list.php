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

class ArticleTemplateView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    var $columns = array('id', 'title', 'description', 'key', 'active');
    var $columns_popup = array('id', 'title', 'description', 'key');
    
    
    function execute(&$obj, &$manager) {
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = $this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());        
        
        if($this->controller->getMoreParam('popup')) {
            $manager->setSqlParams('AND active = 1');
        }
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            $row['popup'] = ($this->controller->getMoreParam('popup')) ? 2 : 1;
            
            $row += $this->msg;
            $more = array('id' => $row['id']);
            $row['preview_link'] = $this->getLink('this', 'this', false, 'preview', $more);
            
            $str = "javascript:PopupManager.create('%s',false,false,%d);";
            $row['preview_link_js'] = sprintf($str, $row['preview_link'], $row['popup']);
            
            // actions/links
            $links = array('preview_link' => $row['preview_link']);
            $actions = $this->getListActions($obj, $links);
            $row += $this->getViewListVarsJs($obj->get('id'), $obj->get('active'), true, $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj, $links) {
        $actions = array('clone', 'status', 'update', 'delete');
        
        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'], 
            'link' => sprintf("javascript:PopupManager.create('%s', false, false, 1)", $links['preview_link']), 
            'img'  => '');
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);    
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);    
        
        $sort->setSortItem('id_msg', 'id', 'e.id',  $this->msg['id_msg']);
        $sort->setSortItem('key_msg', 'key', 'e.tmpl_key',  $this->msg['key_msg']);
        $sort->setSortItem('title_msg',  'title', 'e.title',   $this->msg['title_msg'], 1);
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        $arr_select = array();
        $arr_from = array();
        @$values = $_GET['filter'];
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            $v = addslashes(stripslashes($v));
            // $arr_select[] = "MATCH (title, description, tmpl_key) AGAINST ('$v') AS score";
            // $arr[]  = "AND MATCH (title, description, tmpl_key) AGAINST ('$v' IN BOOLEAN MODE)";
            $arr[]  = "AND (title LIKE '%{$v}%' OR description LIKE '%{$v}%' OR tmpl_key LIKE '%{$v}%')";
        }
        
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(" \n", $arr_select);
        $arr['from'] = implode(" \n", $arr_from);
        
        return $arr;
    }


    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'id', 
            'title' => array(
                'type' => 'link_js',
                'params' => array(
                    'onclick' => 'preview_link_js', 
                    'text' => 'title'),
            ),
            
            'description',
            'key' => array(
                'type' => 'text',
                'params' => array(
                    'text' => 'tmpl_key'),
            ),
            'active'
        );
            
        return $options;
    }  
}
?>