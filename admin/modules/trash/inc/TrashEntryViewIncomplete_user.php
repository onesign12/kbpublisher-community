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


class TrashEntryViewIncomplete_user extends AppView 
{
    
    var $template = 'form_incomplete_user.html';
    var $msg_key = 'note_user_incomplete';
 

    function execute(&$eobj, &$manager) {

        $this->addMsg('user_msg.ini');

        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign($this->setCommonFormVars($eobj));
        $tpl->tplAssign($eobj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>