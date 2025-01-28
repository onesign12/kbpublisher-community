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


class KBEntryImportView_form extends AppView
{
    
    var $tmpl = 'form.html';

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        
        $au = KBValidateLicense::getAllowedEntryRest($manager->model);
        if($au !== true) {
            if($au <= 0) {
                $tpl = new tplTemplatez($this->template_dir . 'empty.html');
                $key = ($au == 0) ? 'license_limit_entry' : 'license_exceed_entry';
                $tpl->tplAssign('msg', AppMsg::licenseBox($key));
                $tpl->tplParse();
                return $tpl->tplPrint(1);
            }        
        }            
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        $skip = array(
            'category_id', 'url_title', 'sort_order', 
            'history_comment', 'date_commented', 'private',
            'body_index', 'date_edited');
        $required = array('title', 'body'); 
        $recommended = array('date_posted');
        
        $tpl->tplAssign('import_block_tmpl', 
            CommonImportView::getImportFormBlock($manager, $skip, $required, $recommended));
        
        
        // check 
        if($au !== true) {
            $msg = AppMsg::licenseBox('license_limit_import_entry', array('num_entry' => $au));
            $tpl->tplAssign('msg', $msg);            
        }
        
        if($manager->isMoreFieldCompatible()) {
            // $tpl->tplSetNeeded('/more_fields');
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