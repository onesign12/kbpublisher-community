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


class ListValueView_form_at extends ListValueView_form
{
    
    var $tmpl = 'form_value_at.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('setting_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $this->setTitle($obj);
        
        $tpl->tplAssign('ckeditor', $this->getEditor($obj->get('custom_1'), 'article', 'custom_1'));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        $tpl->tplAssign('group_title', $obj->group_title);        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        
        $num_active = $manager->countActiveItems($obj->get('list_id'));
        $disabled = ($obj->get('active') && $num_active <= 1) ? true : false;
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active'), true, $disabled));
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>