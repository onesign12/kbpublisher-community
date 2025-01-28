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


class SphinxLogView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);

        $type = $manager->getActionTypeSelectRange($this->msg);
        $tpl->tplAssign('action_type_message', $type[$obj->get('action_type')]);
        
        $tpl->tplAssign('is_error', ($obj->get('exitcode') == 0) ? $this->msg['yes_msg'] : $this->msg['no_msg']);
        
        $tpl->tplAssign('date_executed_formatted', $this->getFormatedDate($obj->get('date_executed'), 'datetime'));
        $tpl->tplAssign('date_executed_interval', $this->getTimeInterval($obj->get('date_executed')));

        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>