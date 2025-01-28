<?php
class CommonBulkView
{
    
    static function parseTags($tpl, $xajax, $obj, $manager, $view, $link) {
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $items = array('add', 'set', 'remove');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_tag');
        $select->setRange($range);
        $tpl->tplAssign('tag_action_select', $select->select());
        
        $options = array('width' => 300);
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link, $options));
        
        $xajax->registerFunction(array('addTag', self::class, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', self::class, 'ajaxGetTags'));
    }
    
    
    static function parseAdminUsers($tpl, $manager, $link) {
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $items = array('set', 'remove');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_admin_user');
        $select->setRange($range);
        $tpl->tplAssign('admin_action_select', $select->select());
        
        $tpl->tplAssign('user_popup_link', $link);
    }
    
    
    // static function parseBulkBlock($manager, $bulk_content, $confirm_type = 'jqueryui') {
    static function parseBulkBlock($manager, $bulk_content, $options = []) {
        
        @$confirm_type = ($ct = $options['confirm_type']) ? $ct : 'jqueryui';
        
            
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'common_bulk_form.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // action
        $msg = AppMsg::getMsg('common_msg.ini');    
        $range = $manager->bulk_manager->getActionsMsg();
        $select->setRange($range, array('none' => $msg['with_checked_msg']));
        
        $selected_arange = array();
        foreach ($range as $k => $v) {
            if (in_array($k, $manager->bulk_manager->actions_immidiate)) {
                $select->setOptionParam($k, 'data-ready="true"');
            }
        }
        
        $tpl->tplAssign('action_select', $select->select());
        $tpl->tplAssign('bulk_content', $bulk_content);
        
        $reg =& Registry::instance();
        $reg->setEntry('bulk_actions', $range);
        
        $tpl->tplAssign('confirm_type', $confirm_type);

        $hidden = array('atoken' => Auth::getCsrfToken());
        $tpl->tplAssign('hidden_fields', http_build_hidden($hidden));

        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
       
}  
?>