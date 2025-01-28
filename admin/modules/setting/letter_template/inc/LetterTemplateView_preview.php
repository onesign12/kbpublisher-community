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


class LetterTemplateView_preview extends AppView
{
    
    var $template = 'preview.html';
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('common_msg.ini', 'email_setting');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        $skip_block = array('from', 'to', 'to_cc', 'to_bcc');
        $skip = explode(',', $obj->get('skip_field'));
        foreach($skip_block as $k => $v) {
            if(!in_array(trim($v), $skip)) {
                $tpl->tplSetNeeded('/' . $v);
            } else {
                $obj->hidden[] = $v;
            }
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        
        $param = array('id' => $obj->get('id'));
        $tpl->tplAssign('cancel_link', $this->controller->getLink('setting', 'letter_template', '', 'update', $param));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>