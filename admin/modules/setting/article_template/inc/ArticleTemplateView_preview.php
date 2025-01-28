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


class ArticleTemplateView_preview extends AppView
{
    
    var $template = 'preview.html';
    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        if($this->controller->getMoreParam('popup') == 2) {
            $tpl->tplSetNeeded('/action_buttons');
        }
        
        /*
         * $tmpl_body = addslashes($row['body']);
            $tmpl_body = str_replace("\n",'', $tmpl_body);
            $tmpl_body = str_replace("\r", '', $tmpl_body);
         */
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>