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

class UserView_role extends AppView 
{
    
    var $template = 'form_category.html';
    var $select_id = 'role';
    
    function execute(&$obj, &$manager, $options = false) {
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        
        if (!$options) {            
            $options = array(
                'secondary_block' => false,
                'cancel_button' => true,
                'creation' => $this->priv->isPriv('insert', 'role'),
                'status_icon' => false,
                'sortable' => false,
                'mode' => 'entry',
                'popup_title' => $this->msg['assign_role_msg'],
                'main_title' => $this->msg['assigned_role_msg'],
                'select_id' => $this->getSelectId(),
                'handler_name' => htmlentities($_GET['field_id']),
                'msg' => array(
                    'enter_category_msg' => $this->msg['enter_role_msg'],
                    'enter_category2_msg' => $this->msg['enter_role2_msg'],
                    'enter_category3_msg' => $this->msg['enter_role3_msg'],
                    'type_category_msg' => $this->msg['type_role_msg'],
                    'add_new_category_msg' => $this->msg['add_new_role_msg']
                )
            );
            
            $mustread = (isset($_GET['field_id']) && $_GET['field_id'] == 'selMustreadRoleHandler');
            
            $options['delim'] = ' -> ';
            if ($this->controller->module != 'users' && !$mustread) { // var_dump($mustread);
                $options['related'] = 'parent';
                $options['delim'] = ' :: ';
            }
            
            $roles = RequestDataUtil::addslashes($manager->role_manager->getSelectRecords());
        }
        
        $view = new KBEntryView_category;
        $view->module = 'users';
        $view->entry_page = 'user';
        $view->category_page = 'role';
        $view->action = 'role';
        
        return $view->parseCategoryPopup($manager->role_manager, $roles, $options);
    }
    
    
    function getSelectId() {
        return $this->select_id;
    }
    
} 
?>