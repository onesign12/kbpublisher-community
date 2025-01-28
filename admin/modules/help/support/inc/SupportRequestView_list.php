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

class SupportRequestView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // filter sql
        $manager->setSqlParams($this->getFilterSql());        
        

        // header generate
        $bp = &$this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // sort generate
        $sort = $this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // subjects
        $subject = $manager->getSubjectSelectRange();
        
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
        $sort->default_order = 1;
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('subject_msg', 'subj', 'subject_id', $this->msg['subject_msg']);
        $sort->setSortItem('email_msg', 'email', 'email', $this->msg['email_msg']);
        $sort->setSortItem('username_msg', 'username', 'user_id', $this->msg['username_msg']);
        $sort->setSortItem('attachment_num_msg', 'attachment', 'attachment', array($this->msg['attachment_num_msg'], 6));
        
        $sort->setSortItem('answered_status_msg','status', 'answered', $this->msg['answered_status_msg']);
        $sort->setSortItem('date_posted_msg', 'date', 'date_posted', $this->msg['date_posted_msg'], 2);
        $sort->setSortItem('placed_status_msg', 'placed', 'placed', $this->msg['placed_status_msg']);
        
        //$sort->getSql();
        //$sort->toHtml()
        return $sort;
    }
    
}
?>