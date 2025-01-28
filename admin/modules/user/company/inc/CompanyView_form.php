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


class CompanyView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        $select = new FormSelect();
        $select->setSelectWidth(250);
        
        // state
        $select->setSelectName('state');        
        $select->setRange($manager->getStateSelectRange(), array(''=>'___'));
        $tpl->tplAssign('state_select', $select->select(@$obj->get('state')));        
        
        // country
        $select->setSelectName('country');        
        $select->setRange($manager->getCountrySelectRange(), array(0=>'___'));
        $tpl->tplAssign('country_select', $select->select(@$obj->get('country')));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        // assign button
        if ($this->controller->getMoreParam('popup')) {
            $tpl->tplSetNeeded('/assign_company');
        }
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxAddCompany() {
        
    }
}
?>