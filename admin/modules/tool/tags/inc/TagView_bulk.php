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



class TagView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // status
        $status_range = array();
        if($manager->bulk_manager->isActionAllowed('status')) {
            $range = array(
                1 => $this->msg['status_visible_msg'],
                0 => $this->msg['status_hidden_msg']
            );
            $status_range = &$range;
            $select->setRange($range);
            $tpl->tplAssign('status_select', $select->select());
        }
        
        
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>