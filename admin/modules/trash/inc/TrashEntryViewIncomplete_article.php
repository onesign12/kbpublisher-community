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


class TrashEntryViewIncomplete_article extends AppView 
{
    
    var $template = 'form_incomplete_article.html';
    var $msg_key = 'note_entry_incomplete'; 
 

    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $b_options = array(
            'no_button' => true, 
            'default_button' => false, 
            'hide_private' => true
            );
            
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock(false, $manager, false,'knowledgebase', 'kb_entry', $b_options));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>