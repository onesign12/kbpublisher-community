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


// used when updating custom in public area
class KBEntryView_custom_field extends AppView 
{
    
    var $tmpl = 'form_custom_field.html';
    

    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('client_msg.ini', 'public');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax(); 
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidate'));
        
              
        $cat_records = $this->stripVars($manager->getCategoryRecords());  
        $custom_fields = $manager->cf_manager->getCustomField($cat_records, $obj->getCategory());
        $rows = $obj->getCustom();
        
        $rows_by_group = array();
        foreach ($custom_fields as $field_id => $v) {
            $rows_by_group[$v['display']][$field_id] = $v;
        }
        ksort($rows_by_group);
        
        
        $msg = AppMsg::getMsgs('custom_field_msg.ini', false, 'display_options');
        $display = array(1 => 'top', 2 => 'bottom', 3 => 'block', 4 => 'hidden');
        
        foreach($rows_by_group as $display_id => $group) {
            
            $values = array();
            foreach ($group as $field_id => $v) {
                if (!empty($rows[$field_id])) {
                    $values[$field_id] = $rows[$field_id];
                }
            }
            
            $v['block'] = CommonCustomFieldView::getFieldBlock($group, $values, $manager->cf_manager, false);            
            $v['title'] = $msg[$display[$display_id]];
            
            $tpl->tplParse($v, 'group');
        }

        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidate($custom) {
        $objResponse = new xajaxResponse();
        
        $values = array();
        $values['custom'] = $custom;
        
        $fields = $this->manager->cf_manager->getCustomField($this->manager->getCategoryRecords(), array());
        $error = $this->manager->cf_manager->validateUserDefined($fields, $values);;
        if($error) {
            $errors = array(
                $error[3] => array(
                    array(
                        'msg' => $error[0],
                        'rule' => 'required'
                    )
                )
            );
            
            $this->setErrors($errors);
            $objResponse->assign('custom_error_msg', 'innerHTML', $this->getErrors());
            
        } else {
            $objResponse->call('saveValidated');
        }
        
        return $objResponse;
    }
}
?>