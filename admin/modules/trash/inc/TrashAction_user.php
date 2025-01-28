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


class TrashAction_user extends TrashAction
{
    
    function __construct() {        
        $this->emanager = new UserModel;
    }


    function validate($entry_obj, $values) {

        $v = new Validator($values);
        $v->display_all = false;
        $v->required_set = true;
        
        $v->csrf();
        $v->required('required_msg', array('email', 'username'));
        $v->regex('email_msg', 'email', 'email', false);
     
        if($v->getErrors()) {
            $entry_obj->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function validateObj($entry_obj) {
        
        $manager =& $this->emanager;
        $v = new Validator($values);
        
        // check username or email exists 
        $username = addslashes($entry_obj->get('username'));
        $email = addslashes($entry_obj->get('email'));
        
        if($manager->isUsernameExists($username) || $manager->isEmailExists($email)) {            
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_record_incomplete'];
            $msg['body'] = $msgs['note_user_incomplete'];
            
            $v->setError(BoxMsg::factory('error', $msg, array()), 'username', 'username', 'formatted');
        }
        
        if($v->getErrors()) {
            $entry_obj->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function restore($entry_obj) {
        
        $id = $entry_obj->get('id');
        
        $manager =& $this->emanager;
        
        // license, reset priv if exceeded
        $au = KBValidateLicense::getAllowedUserRest($manager);
        if($au !== true && $au < 0) {
            $entry_obj->setPriv(array());
        } 
        
        // checking roles
        $roles = $entry_obj->getRole();
        
        if (!empty($roles)) {
            $role_ids = implode(',', array_keys($roles));
            
            $manager->role_manager->setSqlParams(sprintf('AND r.id IN (%s)', $role_ids));
            $roles_actual = $manager->role_manager->getRecords();
            
            foreach (array_keys($roles) as $k) {
                $role_id = $roles[$k];
                if (empty($roles_actual[$role_id])) {
                     unset($roles[$k]);
                }
            }
            
            $entry_obj->setRole($roles);
        }
        
        $lastauth = ($la = $entry_obj->get('lastauth')) ? $la : NULL;
        $entry_obj->set('lastauth', $lastauth);
        
        $password = WebUtil::generatePassword(5, 2); // 12 length
        $entry_obj->set('password', HashPassword::getHash($password));
        
        $manager->add($entry_obj);
        $manager->addPriv($entry_obj->getPriv(), $id);
        $manager->addRole($entry_obj->getRole(), $id);
        // $this->subscribe($entry_obj->getSubscription(), $id); // we did not delete subscription
        $manager->addExtra($entry_obj->getExtra(), $id);
        $manager->addSso($entry_obj->getSso(), $id);
        
        return true;
    }
    
    
    function getPreview($entry_obj, $controller) {
        $entry_obj = unserialize($entry_obj);
        
        $view = new UserView_detail;
        $view->trash_view = true;
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/'; 
        $view = $view->execute($entry_obj, $this->emanager);
        
        return $view;
    }


    static function getTitleStr($obj_str) {
        $search = '#s:10:"first_name";s:\d+:"(.*?)";s:11:"middle_name";s:\d+:"(.*?)";s:9:"last_name";s:\d+:"(.*?)";s:#';
        preg_match($search, $obj_str, $matches);
        return (!empty($matches[1])) ? $matches[1] . ' ' . $matches[3] : '';
    }
}