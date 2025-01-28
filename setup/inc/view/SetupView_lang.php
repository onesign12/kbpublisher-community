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


class SetupView_lang extends SetupView
{

    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'lang.html');
        
        // languages
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->select_tag = false;
        $select->setSelectName('lang');
        
        $range = &$manager->getLangSelectRange();
        $select->setRange($range);
        
        $tpl->tplAssign('lang_select', $select->select($this->lang));
    
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());    
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>