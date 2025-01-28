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



class FileCategoryView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
            
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // private
        if($manager->bulk_manager->isActionAllowed('private')) {
            $tpl->tplSetNeeded('/private');
            $tpl->tplAssign('block_private_tmpl', 
                PrivatePlugin::getPrivateBulkBlock($obj, $manager, 'file', 'file_category'));
            $xajax->registerFunction(array('loadRoles', $this, 'ajaxLoadRoles'));
        }
        
        // supervisors
        $link = $this->getLink('users', 'user');
        CommonBulkView::parseAdminUsers($tpl, $manager, $link);
        
        // attachable
        $extra_range = array();
        $msg = AppMsg::getMsg('bulk_msg.ini', false, 'bulk_allow_disallow');
        $range = array(1 => $msg['allow'], 0 => $msg['disallow']);
        $select->setRange($range, $extra_range);
        $sel = $select->select();
        $tpl->tplAssign('attachable_select', $sel);
        
        
        // status
        $extra_range = array();
        $msg = AppMsg::getMsg('setting_msg.ini', false, 'list_category_status');
        $range = array(1 => $msg['published'], 0 => $msg['not_published']);
        $select->setRange($range, $extra_range);
        $tpl->tplAssign('status_select', $select->select());
        
        
        $options = array('key' => 'bulk_kbcategory');
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxLoadRoles($ids) {
        return PrivatePlugin::ajaxLoadRoles($ids, $this->manager);
    }
}
?>