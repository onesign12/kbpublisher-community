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


class StuffCategoryView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav));
        
        // sort generate
        $sort = $this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());        
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // list records
        foreach($rows as $row) {

            $obj->set($row);            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active')));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        //$sort->setDefaultOrder(1);    
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);    
        
        $sort->setSortItem('id_msg', 'id', 'id',  $this->msg['id_msg']);
        $sort->setSortItem('title_msg',  'title', 'title',   $this->msg['title_msg'], 1);
        $sort->setSortItem('description_msg',  'desc', 'description',   $this->msg['description_msg']);
        
        return $sort;
    }
}
?>