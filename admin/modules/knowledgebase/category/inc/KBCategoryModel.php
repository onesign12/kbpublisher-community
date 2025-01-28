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


class KBCategoryModel extends CommonCategoryModel
{

    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'category', 'category', 'entry', 'entry_to_category');
    var $custom_tables = array('data_to_user_value', 'user', 
                               'role'=>'user_role', 'user_subscription');
    
    var $role_read_rule = 'kb_category_to_role_read';
    var $role_read_id = 1;
    
    var $role_write_rule = 'kb_category_to_role_write';
    var $role_write_id = 5;
    
    var $admin_user_rule = 'kb_category_to_user_admin';
    var $admin_user_id = 3;    
    
    var $category_type = array(
        'default'   => 1, 
        'faq'       => 2,
        'faq2'      => 4,
        'book'      => 3
        //'doc'     => 4
    );
    
    var $entry_type = 11; // means article category, article type = 1    
    
    
    function __construct($user = array()) {
        parent::__construct();
        $this->dv_manager = new DataToValueModel();
        
        $this->user_id = (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $this->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $this->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();
        
        $this->role_manager = new RoleModel();            
    }
    
    
    function getCategoryTypeSelectRange() {
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'kb_category_type');
        foreach($this->category_type as $k => $v) {
            $range[$v] = $msg[$k];
        }
        
        return $range;
    }
    
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $popup = false, $bulk_action = false) {

        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('category', 'select');
        $priv->setCustomAction('clone_tree', 'insert');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new KBCategoryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
        
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        
        // check for actions and not correct user if private category
        // insert if add as "Add new child category"
        $actions = array(
            'insert', 'clone', 'status', 'update', 'delete'
        );
        
        if(in_array($action, $actions) && $record_id) {
            $private = $this->getCategoriesNotInUserRole('write');
            if(in_array($record_id, $private)) {
                echo $priv->errorMsg();
                exit;
            }
        
        // March 24, 2021 no record_id,  nothing to do
        } elseif(in_array($action, $actions) && $action != 'insert' && !$record_id) {
            $reg = &Registry::instance();
            $controller = &$reg->getEntry('controller');
            $controller->go('record_not_exists', true);
        }
        
        $priv->check($action);
    }
    
}
?>