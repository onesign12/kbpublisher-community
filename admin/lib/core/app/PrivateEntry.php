<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class PrivateEntry
{


    // ENTRY FORM // ------------------

    // to set article, files, news private and assign roles
    static function getPrivateEntryBlock(&$xajax, $obj, $manager, $view, $module = 'knowledgebase', $page = 'kb_entry') {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_private_entry.html');
        $tpl->tplSetNeeded('/private_list');
        
        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->select_tag = false;

        $roles = $manager->role_manager->getSelectRangeFolow(false, 0, ' :: ');

        // read
        $range = array();
        foreach($obj->getRoleRead() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_select', $select->select());

        // write
        $range = array();
        foreach($obj->getRoleWrite() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_write_select', $select->select());

        $private = $obj->get('private');
        $tpl->tplAssign('private_options', $view->getChecked(self::isPrivateRead($private)));
        $tpl->tplAssign('private_write_options', $view->getChecked(self::isPrivateWrite($private)));
        $tpl->tplAssign('private_list_options', $view->getChecked(self::isPrivateUnlisted($private)));


        if($xajax) {
            $xajax->registerFunction(array('getCategoryPrivateInfo', $view, 'ajaxGetCategoryPrivateInfo'));
        }

        $link = $view->controller->getFullLink($module, $page, false, 'role');
        $tpl->tplAssign('popup_link', $link);
        
        $tpl->tplAssign('confirm', ($obj && $obj->get('id')) ? 'true' : 'false');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // ENTRY/CATEGORY FORM

    // get private category roles read msg on entry form
    static function getCategoryPrivateInfo($category_id, $category_title, $manager, $detail_view = false) {

        $roles = false;
        $html = '';
        $is_private = $manager->isPrivate($category_id);
        $msg = AppMsg::getMsg('user_msg.ini');

        if($is_private) {

            $roles_ret = '';
            $str = '<div>%s</div><div>%s</div>';
            $roles = $manager->getRoleById($category_id, false);

            if($roles) {
                $roles_range = $manager->getRoleRangeFolow();
                foreach($roles AS $rule => $v) {
                    if(!empty($v)) {
                        $roles_msg = array();
                        foreach($v as $id) {
                            $roles_msg[] = $roles_range[$id];
                        }

                        $mkey = "private2_{$rule}_msg";
                        $roles_msg = ' - ' . implode('<br> - ', $roles_msg);
                        $roles_ret .= sprintf($str, $msg[$mkey], $roles_msg);
                    }
                }
            }

            $private_msg = BaseView::getPrivateTypeMsg($is_private, $msg);
            $category_msg = $msg['private_msg'];

            $html = array();
            $html[] = '<div class="privateCategoryDiv">';

            if($detail_view) {
                $title = '%s (%s): %s';
                $title = sprintf($title, $category_msg, $private_msg, $category_title);

                $html[] = '<div style="padding-bottom: 2px;">' . $title . '</div>';
                $html[] = '<div style="margin: 5px;padding-bottom: 6px;">';

            } else {
                $html[] = '<div style="padding-bottom: 2px;"><b>'. $category_title .':</b></div>';
                $html[] = '<div style="padding-bottom: 6px; padding-left: 15px;">';
                $str = '<span style="color:#cc0000;">%s (%s)</span>';
                $html[] = sprintf($str, $category_msg, $private_msg);
            }

            $html[] = '<div>'. $roles_ret .'</div>';
            $html[] = '</div>';
            $html[] = '</div>';

            $html = implode("\n\t", $html);
        }

        return $html;
    }
    
    
    // on entry form page
    static function ajaxGetCategoryPrivateInfo($category_id, $category_title, $manager) {

        $objResponse = new xajaxResponse();

        $manager = (isset($manager->cat_manager)) ? $manager->cat_manager : $manager;
        $html = self::getCategoryPrivateInfo($category_id, $category_title, $manager);

        if (strlen($html) > 0) {
            $objResponse->script('$("#category_private_content").show();');
            $objResponse->script('$("#category_toggle_title").show();');
            $objResponse->script('$("#category_private_content2").show();');

            $objResponse->addAppend('writeroot_private', 'innerHTML', $html);
            $objResponse->script('populateCategoryPrivateContent();');
        }

        return $objResponse;
    }
}
?>