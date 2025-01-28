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

class ListValueView_form extends AppView
{
    
    var $tmpl = 'form_value.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('setting_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $this->setTitle($obj);
        
        // colors
        $color = ($obj->get('custom_1')) ? $obj->get('custom_1') : '#000000';
        $tpl->tplAssign('color', $color);        
                
        // entry published
        $tpl->tplAssign('custom_3_checked', $this->getChecked($obj->get('custom_3')));
        
        // default entry
        $tpl->tplAssign('custom_4_checked', $this->getChecked($obj->get('custom_4')));
        $tpl->tplAssign('custom_4_readonly', ($obj->get('custom_4')) ? 'onclick="return false"' : '');
        
        // user, 
        if($obj->get('list_id') == 4) {
            $obj->hidden[] = 'custom_3'; // item active
            $this->msg['list_entry_status_msg'] = $this->msg['list_user_status_msg'];
            
            $tpl->tplSetNeeded('/status_default');
            
            // not allowed to change publish status for "approve" 
            // active removed from users for now, it is not checked in code
            // it is hard coded to = 1
            // if($obj->get('list_key') != 'approve') {
                // $tpl->tplSetNeeded('/status_active');
            // }
        
        // rating comment
        } elseif($obj->get('list_id') == 6) {
        
        // all other 
        } else {
            $tpl->tplSetNeeded('/status_active');
            $tpl->tplSetNeeded('/status_default');
        }
        
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
    
    
    function getColors() {
        
        return $safe_color;
    }
    
    //&& !empty($obj->get('list_key'))
    function setTitle(&$obj) {
        $msg = ParseListMsg::getValueMsg($obj->group_key);        
        if(!$obj->get('title') && $obj->get('list_key')) {
            $obj->set('title', $msg[$obj->get('list_key')]);
        }
    }
}
?>