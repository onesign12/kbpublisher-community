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


class SettingViewAuthDebug_popup extends SettingView_form
{
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $tpl = new tplTemplatez($this->template_dir . 'auth_debug.html');
        
        $tpl->tplAssign($this->msg);
        
        try {
            
            $ldap = new AuthLdap($obj->get(), true);
            
            $row['action'] = 'Checking PHP Support...';
            $ldap->validateCompability();
            $tpl->tplSetNeeded('step/status_good');
            $tpl->tplParse($row, 'step');
                       
            $row['action'] = 'Trying to connect to host...';
            $ldap->connect();
            $tpl->tplSetNeeded('step/status_good');
            $tpl->tplParse($row, 'step');
            
            $row['action'] = 'Trying to bind user...';
            
            $password = @$obj->get('ldap_connect_password'); // from form
            
            // password field could be empty, trying get from db
            // if(!$password) {
            //     if($password = $manager->getSettings('160', 'ldap_connect_password')) {
            //         $password = EncryptedPassword::decode($password); // from db
            //     }
            // }
            
            $ldap->bind(@$obj->get('ldap_connect_dn'), $password);
            
            $tpl->tplSetNeeded('step/status_good');
            $tpl->tplParse($row, 'step');
            
            if (@$obj->get('ldap_debug_username')) {
                $row['action'] = "Trying to find user {$obj->get('ldap_debug_username')}...";
                $ldap_user = $ldap->searchUser($obj->get('ldap_debug_username'), true);
                $tpl->tplSetNeeded('step/user');
                $tpl->tplAssign('user', print_r($ldap_user, 1));
                $tpl->tplAssign('user_message', 'Found LDAP user\'s entry');
                $tpl->tplSetNeeded('step/status_good');
                $tpl->tplParse($row, 'step');
                
                $row['action'] = 'Verifying user\'s password...';
                $ldap->bind($ldap_user['dn'], @$obj->get('ldap_debug_password'));
                $tpl->tplSetNeeded('step/status_good');
                $tpl->tplParse($row, 'step');
                
                $row['action'] = 'Converting the LDAP user to the KBP user...';
                $user = $ldap->getUserMapped($ldap_user);
                $tpl->tplSetNeeded('step/user');
                
                if (!empty($user['priv_id']) && $user['priv_id'] != 'off') { // showing names for privileges
                    $manager2 = new UserModel;
                    $priv_range = $manager2->getPrivSelectRange();
                    
                    $privilege = (empty($priv_range[$user['priv_id']])) ? '--' : $priv_range[$user['priv_id']];
                    $user['priv_id'] .= sprintf(' (%s)', $privilege);
                }
                
                if (!empty($user['role_id']) && $user['role_id'] != 'off') { // showing names for roles
                    $manager2 = new UserModel;
                    $roles_range = $manager2->getRoleSelectRangeFolow();
                    
                    $roles_ids = explode(',', $user['role_id']);
                    $roles = array();
                    foreach ($roles_ids as $role_id) {
                        $roles[] = (empty($roles_range[$role_id])) ? '--' : $roles_range[$role_id];
                    }
                    
                    $user['role_id'] .= sprintf(' (%s)', implode(', ', $roles));
                }
                
                $tpl->tplAssign('user', print_r($user, 1));
                $tpl->tplAssign('user_message', 'KBPublisher user\'s data');
                $tpl->tplSetNeeded('step/status_good');
                $tpl->tplParse($row, 'step');
            }
            
        } catch (Exception $e) {
            $row['error_msg'] = $e->getMessage();
            $tpl->tplSetNeeded('step/status_bad');
            $tpl->tplParse($row, 'step');
        
            $tpl->tplParse();
            return $tpl->tplPrint(1);
        }
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
}
?>