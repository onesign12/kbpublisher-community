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



class KBGlossaryView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager) {
            
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;

        // highlight
        $range = $manager->getHighlightRange();
        $select->setRange($range);
        $tpl->tplAssign('display_select', $select->select());        
        
        // case
        $bit = $manager::CASE_BIT;
        $range = array(0 => $this->msg['no_msg'],
                      $bit => $this->msg['yes_msg']);
        $select->setRange($range);
        $tpl->tplAssign('case_select', $select->select());
                
        // status
        $range = array(1 => $this->msg['status_published_msg'],
                       0 => $this->msg['status_not_published_msg']);
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select());        
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>