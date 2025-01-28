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



class FileCategoryView_form extends KBCategoryView_form
{
    var $tmpl = 'form.html';
    
    
    function parseFields($tpl, $obj, $manager) {

        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->setSelectWidth(250);
        $select->select_tag = false;

        // public sort 
        $select->resetOptionParam();
        $select->setSelectName('sort_public_select');
        $extra_range = array('' => $this->msg['sort_public_default_msg']);
        $select->setRange($manager->getCategorySortPublicSelectRange(), $extra_range);
        $tpl->tplAssign('sort_public_select', $select->select($obj->get('sort_public')));

        // other
    	$tpl->tplAssign('attachable_options', $this->getChecked($obj->get('attachable')));
    }
    
    
    function getCategoryPopupBlock() {
        return CommonCategoryView::getCategoryPopupBlock($this->controller, $this->msg, 'file', 'file_category');
    }
    
    
    // private
    function parsePrivateStuff(&$tpl, &$xajax, $obj, $manager) {
        if(AppPlugin::isPlugin('private')) {
            $tpl->tplSetNeeded('/block_private');
            $tpl->tplAssign('block_private_tmpl', 
            PrivatePlugin::getPrivateCategoryBlock($xajax, $obj, $manager, $this, 'file', 'file_category'));
        
            // get private info on load and reload ? (now it works with ajax)
            $info = PrivatePlugin::getPrivateCategoryMsg($manager, $obj->get('parent_id'), true, $this->msg);
            $tpl->tplAssign('category_info', $info);
        }
    }
}
?>