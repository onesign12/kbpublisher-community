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


class ListValueView_form_fbs extends ListValueView_form
{
    
    var $tmpl = 'form_value_fbs.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('setting_msg.ini');
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $this->setTitle($obj);
        
        // colors
        $color = ($obj->get('custom_1')) ? $obj->get('custom_1') : '#000000';
        $tpl->tplAssign('color', $color);
        
        // default entry
        $tpl->tplAssign('custom_4_checked', $this->getChecked($obj->get('custom_4')));
        $tpl->tplAssign('custom_4_readonly', ($obj->get('custom_4')) ? 'onclick="return false"' : '');
        
        //admin user
        $user_popup_link = $this->getLink('users', 'user', false, false, array('filter[priv]'=>'any'));
        $tpl->tplAssign('user_popup_link', $user_popup_link);
        foreach($obj->getAdminUser() as $id => $name) {
            $data = array('user_id'=>$id, 'name'=>$name);
            $data['delete_msg'] = $this->msg['delete_msg'];
            $tpl->tplParse($data, 'admin_user_row');
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
}
?>