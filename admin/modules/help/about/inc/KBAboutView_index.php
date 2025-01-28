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


class KBAboutView_index extends AppView
{
    
    var $tmpl = 'index.html';

    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $tpl->tplAssign('name', $this->conf['product_name']);
        $tpl->tplAssign('version', $this->conf['product_version']);
        $tpl->tplAssign('current_year', date('Y'));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>