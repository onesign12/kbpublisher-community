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


class TrashEntryViewIncomplete extends AppView 
{
    
    var $template = 'form_incomplete.html';
 
 
    static function factory($type) {
        $class = 'TrashEntryViewIncomplete_' . $type;
        $file = 'TrashEntryViewIncomplete_' . $type . '.php';
        return new $class;
    }
 

    function execute(&$obj, &$manager, $eobj) {

        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $class_name = $manager->record_type[$obj->get('entry_type')];
        $view = self::factory($class_name);
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($eobj->errors));
        $tpl->tplAssign('incomplete_block_tmpl', $view->execute($eobj, $manager));
        
        $entry_type = $manager->getEntryTypeSelectRange();
        $tpl->tplAssign('type', $entry_type[$obj->get('entry_type')]);
        $tpl->tplAssign('date_formatted', $this->getFormatedDate($obj->get('date_deleted'), 'datetime'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>