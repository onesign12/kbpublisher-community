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


class KBGlossaryView_list extends AppView
{
    
    var $template = 'list.html';
    var $columns = array('title', 'definition', 'highlight', 'case', 'published');
    
    
    function execute(&$obj, &$manager) {

        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // bulk
        $manager->bulk_manager = new KBGlossaryModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->controller->getView($obj, $manager, 'KBGlossaryView_bulk');
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }        
        
        //letter filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        
        // $params2 = $this->parseLetterFilter($tpl, $_GET, $manager);
        // $manager->setSqlParams('AND ' . $params2);
        
        

        // header generate
        $button = array();
        $button[] = 'insert';
        if($this->priv->isPriv('insert') && $this->priv->isPriv('insert', 'import_glossary')) {
            $button['...'] = array(array(
                'msg' => $this->msg['import_msg'],
                'link' => $this->getLink('import', 'import_glossary')
            ));
        }
        
        // letters filter
        $letters = $this->getLetterFilter($manager);
        
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav . $letters, $this->getFilter($manager), $button));
        
        // sort generate
        $sort = $this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset), array('definition'));
        
        $highlight_range = $manager->getHighlightRange();
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            // title
            $row += $this->getTitleToList($row['phrase'], 50);
            
            $definition = htmlspecialchars_decode(strip_tags($row['definition']));
            $definition = strip_tags($row['definition']);
            $row['definition_entry'] = $this->getSubstring($definition, 100);
            $row['definition_title'] = $this->stripVars($row['definition']);
            $row['highlight'] = $highlight_range[$row['highlight']];
            
            // actions/links
            $links = array();
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
        $actions = array('status', 'update', 'delete');
        return $actions;
    }
    
    
    function &getSort() {
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->default_order = 1;
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('phrase_msg',       'phrase', 'phrase', $this->msg['phrase_msg'], 1);
        $sort->setSortItem('glossary_highlight_msg', 'once', 'display_once', $this->msg['glossary_highlight_msg']);
        
        //$sort->getSql();
        //$sort->toHtml()
        return $sort;
    }
    
    
    function getFilter($manager) {

        $values = $this->parseFilterVars(@$_GET['filter']);

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;    
    
        //highlight
        @$highlight = $values['h'];
        $range = $manager->getHighlightRange();
        $select->setRange($range, array('all'=> '__'));
        $tpl->tplAssign('highlight_select', $select->select($highlight));
    
        //status
        @$status = $values['s'];
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_published_msg'],
               0 => $this->msg['status_not_published_msg']);
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
    
    
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
        
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];    
        
        // highlight
        @$v = $values['h'];
        if($v != 'all' && isset($values['h'])) {
            $v = (int) $v;
            // $mysql['where'][] = "AND display_once = '$v'";
            $bit = KBGlossaryModel::HIGHTLIGHT_BIT;
            $mysql['where'][] = "AND display_once & $bit = $v"; // display_once & 2 != 2
            // $sphinx['where'][] = "AND display_once = $v";
        }
                
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }
        
        @$v = $values['q'];
        if($v) {
            $v = addslashes(stripslashes($v));
            $mysql['where'][] = "AND phrase LIKE '%" . $v . "%'";
            $mysql['where'][] = "OR definition LIKE '%" . $v . "%'";
            
            $sphinx['match'][] = $v;
        }
        
        @$v = $_GET['letter'];
        if($v) {
            $l = addslashes(urldecode(_strtoupper($v)));
            $l2 = addslashes(urldecode(_strtolower($v)));
            $mysql['where'][] = "AND (phrase LIKE '$l%' OR phrase LIKE '$l2%')";
        }
        
        
        @$v = $values['q'];
        $options = array('index' => 'glossary');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }    
    
    
    // function parseLetterFilter(&$tpl, $vars, $manager) {
    function getLetterFilter($manager) {
            
        $tpl = new tplTemplatez($this->template_dir . 'form_filter_letter.html');
            
        $letters = array();
        $result =& $manager->getGlossaryLettersResult();
        while($row = $result->FetchRow()) {
            $letter = _strtoupper(_substr($row['phrase'], 0, 1));
            $letters[$letter] = $letter;
        }

        //SORT_LOCALE_STRING - compare items as strings, based on the current locale. 
        //Added in PHP 4.4.0 and 5.0.2. Before PHP 6, it uses the system locale, 
        // which can be changed using setlocale(). 
        //Since PHP 6, you must use the i18n_loc_set_default() function.
        sort($letters, SORT_LOCALE_STRING);
        @$vars = $_GET;

        foreach($letters as $letter) {
            $a['letter'] = $letter;
            $more = ['do_search' => 1, 'letter' => $letter];
            $a['letter_link'] = $this->getLink('full', 0, 0, 0, $more);
            $a['letter_class'] = (@$vars['letter'] == $letter) ? 'tdSubTitle' : '';
            
            $tpl->tplParse($a, 'letter');
        }
    
        $link = $this->getLink('full', 0, 0, 0, ['letter' => 0]);
        $tpl->tplAssign('all_letter_link', $link);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'id', 
            
            'title' => array(
                'type' => 'text_tooltip',
                'title' => 'phrase_msg',
                'params' => array(
                    'title' => 'title_title', 
                    'text' => 'title_entry')
            ),
            
            'definition' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'title' => 'definition_title', 
                    'text' => 'definition_entry')
            ),
            
            'highlight' => array(
                'type' => 'text',
                'title' => 'glossary_highlight_msg',
                'width' => 100,
                'params' => array( 
                    'text' => 'highlight')
            ),
            
            'case' => array(
                'type' => 'bullet',
                'title' => 'glossary_case_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path fill="#fff" d="M24 20v1h-4v-1h.835c.258 0 .405-.178.321-.422l-.473-1.371h-2.231l-.575-1.59h2.295l-1.362-4.077-1.154 3.451-.879-2.498.921-2.493h2.222l3.033 8.516c.111.315.244.484.578.484h.469zm-6-1h1v2h-7v-2h.532c.459 0 .782-.453.633-.887l-.816-2.113h-6.232l-.815 2.113c-.149.434.174.887.633.887h1.065v2h-7v-2h.43c.593 0 1.123-.375 1.32-.935l5.507-15.065h3.952l5.507 15.065c.197.56.69.935 1.284.935zm-10.886-6h4.238l-2.259-6.199-1.979 6.199z"/></svg>',
                'width' => 'min',
                'align' => 'center'
            ),
            
            'published'
        );
            
        return $options;
    } 

}
?>