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


class UserImportView_form extends AppView
{
    
    var $tmpl = 'form.html';

    
    function execute(&$obj, &$manager, $eobj) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        

        $skip = array('grantor_id', 'lastauth', 'user_comment', 'user_comment', 'password', 
                      'company_id', 'editable', 'admin_comment', 'import_data',
                      'phone_ext', 'address', 'address2', 'city', 'state', 'zip', 'country');
        $required = array('first_name', 'last_name', 'email', 'username');
        $recommended = array();
        
        $tpl->tplAssign('import_block_tmpl', 
            CommonImportView::getImportFormBlock($manager, $skip, $required, $recommended));        
        
        
        if($manager->isMoreFieldCompatible()) {

            $tpl->tplSetNeeded('/more_fields');
            
            $select = new FormSelect();
            $select->setSelectWidth(250);            
            $select->select_tag = true;
            
            // company
            $select->setSelectName('company_id');
            $select->setRange($manager->model->getCompanySelectRange(), array(0=>'__'));
            $tpl->tplAssign('company_select', $select->select($eobj->get('company_id')));
            
            $link = $this->controller->getLink('users', 'company', false, 'insert');
            $tpl->tplAssign('company_link', $link);
            
            
            $select->setSelectName('role');
            $select->setMultiple(5);
            $select->setRange($manager->model->getRoleSelectRange());
            $tpl->tplAssign('role_select', $select->select($eobj->getRole()));
        }
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormImport'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign('action_link', $this->getLink('this','this'));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
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