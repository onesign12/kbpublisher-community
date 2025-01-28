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

class KBCategory extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'parent_id'      => 0,
                            'name'           => '',
                            'description'    => '',
                            'sort_order'     => 'sort_end',
                            'sort_public'    => 'default',
                            //‘num_entry'      => 0,
                            'category_type'  => 1,
                            'commentable'    => 1,
                            'ratingable'     => 1,
                            'private'        => 0,
                            // 'active_real' => 1,
                            'active'         => 1
                            );
    
    
    var $hidden = array('id');
    var $reset_on_clone = array('id', 'name');
    
    var $admin_user = array();
    var $role_read = array();
    var $role_write = array();
    
    
    function getAdminUser() {
        if(is_array($this->admin_user)) {
            return array_unique($this->admin_user);
        }
        
        return $this->admin_user;
    }
    
    function setAdminUser($user) {
        $this->admin_user = $user;
    }
    
    function getRoleRead() {
        return $this->role_read;
    }
    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
    
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
    }
    
    function _callBack($property, $val) {
        if($property == 'private') {
            $val = $this->getPrivateValue($val);
        }   
        
        return $val;
    }    
    
    function validate($values) {
        
        $required[] = 'name';
        
        // when user select wrong category
        if(!isset($values['parent_id'])) {
            $required[] = 'parent_id';
        
        // not to generate error if top category selected 
        } elseif($values['parent_id'] !== '0') {
            $required[] = 'parent_id';
        }
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
}
?>