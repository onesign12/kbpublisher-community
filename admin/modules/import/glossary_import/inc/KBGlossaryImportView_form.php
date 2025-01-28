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


class KBGlossaryImportView_form extends AppView
{
    
    var $tmpl = 'form.html';

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');        
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $skip = array();
        $required = array('phrase', 'definition');
        $recommended = array(); 

        $tpl->tplAssign('import_block_tmpl', 
            CommonImportView::getImportFormBlock($manager, $skip, $required, $recommended));

        
        if($manager->isMoreFieldCompatible()) {
            // $tpl->tplSetNeeded('/more_fields');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormImport'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign('action_link', $this->getLink('this','this'));
        // $tpl->tplAssign($this->setStatusFormVars(1));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormImport($values, $options = array()) {
        
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if (!$this->obj->errors) {
            $options['func'] = 'getValidateFile';
            $objResponse = $this->ajaxValidateForm($values['_files'], $options);            
        }
        
        return $objResponse;
    }
}
?>