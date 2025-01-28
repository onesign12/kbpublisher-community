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

class ListValue extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'list_id'       => '',
                            'list_key'      => '',
                            'list_value'    => 0,
                            'title'         => '',
                            'description'   => '',
                            'sort_order'    => 1,
                            'predifined'    => 0, // 1=yes, 2=yes and locked
                            'active'        => 1,
                            'custom_1'      => '',
                            'custom_2'      => '',
                            'custom_3'      => 0,
                            'custom_4'      => 0
                            );
    
    
    var $hidden = array('id', 'list_key', 'list_value', 'predifined');
    
    var $keys = array(
        'article_status'    => 'as',
        'file_status'       => 'fs',
        'article_type'      => 'at',
        'user_status'       => 'us',
        'feedback_subj'     => 'fbs',
        'rate_status'       => 'rs'
    );
    
    var $group_list = array();
    var $html_field = false;
    var $admin_user = array();
    
    
    function getAdminUser() {
        return array_unique($this->admin_user);
    }
    
    function setAdminUser($user) {
        $this->admin_user = $user;
    }
    
    function setGroupList($data) {
        $this->group_list = $data;
    }
    
    function getGroupList() {
        return $this->group_list;
    }
    
    function setHtmlField($data) {
        $this->html_field = $data;
    }
    
    function getHtmlField() {
        return $this->html_field;
    }    
    
    static function factory($list_key, $controller) {
    
        $keys = array(
            'article_status'    => 'as',
            'file_status'       => 'fs',
            'article_type'      => 'at',
            'user_status'       => 'us',
            'feedback_subj'     => 'fbs',
            'rate_status'       => 'rs'
        );      
    
        $class = 'ListValue_' . $keys[$list_key];
        if($controller->isClass($class)) {
            
        } else {
            $class = 'ListValue';
            
        }
        
        return new $class;
    }
    
    
    function getManager($list_key, $controller) {
        $list_key = $this->keys[$list_key];
        $class = 'ListValueModel_' . $list_key;
        
        if($controller->isClass($class)) {
            
        } else {
            $class = 'ListValueModel';
            
        }
        
        return new $class;
    }    
    
    
    function getListView($list_key, $controller) {
        $list_key = $this->keys[$list_key];
        $class = 'ListValueView_list_' . $list_key;
        
        if(!$controller->isClass($class)) {
            $class = 'ListValueView_list';
        }
            
        return $class;
    }
    
    
    function getFormView($list_key, $controller) {
        $list_key = $this->keys[$list_key];
        $class = 'ListValueView_form_' . $list_key;
        
        if(!$controller->isClass($class)) {
            $class = 'ListValueView_form';
        }
            
        return $class;    
    }
    
    
    function _callBack($property, $val) {
        if($property == 'custom_1') {
            $pattern = '/^#[0-9A-Fa-f]+$/';
            if (!preg_match($pattern, $val)) { // convert to white
                $val = '#ffffff';
            }
        }
        
        return $val;
    }    
    
    
    function validate($values) {
        
        $required = array('title');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        
        $this->errors = &$v->getErrors();
        return $this->errors;
    }
    
}
?>