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



class UserView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    var $account_view = false;
    
    
    function execute(&$obj, &$manager, $data = array()) {
        
        $this->addMsg('user_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('menu_block', UserView_common::getEntryMenu($obj, $manager, $this));
        $tpl->tplAssign('detail_link', $this->getActionLink('detail', $obj->get('id')));
        $tpl->tplAssign('date_formatted_full', $this->getFormatedDate($obj->get('date_registered'), 'datetime'));
        $tpl->tplAssign('date_full_interval', $this->getTimeInterval($obj->get('date_registered')));
        
        if($data['activities']) {
            $tpl->tplSetNeededGlobal('activities');
            
            $eview = new UserView_detail;
            $eview->critical_keys = array_keys($data['critical_activities']);
            $eview->msg = $this->msg;
            $block_rows = $eview->getUserActivityArray($data['activities'], $obj->get('id'), 'red');
            
            foreach ($block_rows as $section_key => $section_str) {
                $row = array();
                $row['section_str'] = $section_str;
                
                if ($section_key == 'supervisor' && empty($data['critical_activities'])) {
                    $tpl->tplSetNeeded('row/user_popup');
                    $more = array('limit' => 1, 'close' => 1);
                    $link = $this->getLink('users', 'user', false, false, $more);
                    
                    $tpl->tplAssign('user_popup_link', $link);
                }
                    
                $tpl->tplParse(array_merge($row, $this->msg), 'row');
            }
        }
        
        if($data['activities'] || $data['critical_activities']) {            
            $error_key = ($data['critical_activities']) ? 'nondeletable_user' : 'note_user_delete';
            $tpl->tplAssign('error_msg', AppMsg::afterActionBox($error_key));        
        }
        
        $tpl->tplSetNeeded(($data['critical_activities']) ? '/back_button' : '/delete_button');
        
        // ajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormDelete'));


        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('form_title', $this->msg['delete_account_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormDelete($values, $options = array()) {
        $options['func'] = 'getValidateDelete';
        return $this->ajaxValidateForm($values, $options);
    }
    
}
?>