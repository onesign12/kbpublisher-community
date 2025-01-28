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

namespace LdapSetting;

use Validator;
use AuthRemote;
use AppMsg;



class SettingValidator
{
     
    function validate($values) {        
        
        $required = array(
            'ldap_host', 'ldap_port', 'ldap_base_dn',
            'remote_auth_map_fname', 'remote_auth_map_lname',
            'remote_auth_map_email', 'remote_auth_map_ruid');
        
        $v = new Validator($values, true);
                
        // required
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        // scripts
        $rules = array('remote_auth_auto_script_path', 'remoteAutoAuth');
        if(!empty($values['remote_auth_auto'])) {
            $error = $this->testScript($v, $rules, $values);
            if($error) {
                return $error;
            }
        }
        
        
        // regexps
        $f = array('remote_auth_map_fname', 'remote_auth_map_lname');
        foreach ($f as $field) {
            $r = explode('|', $values[$field]);
            if (!empty($r[1])) { // there is a regexp, check if it is valid
                if (@preg_match($r[1], '') === false) {
                    $v->setError('regexp_valid_msg', $field);
                    return $v->getErrors();
                }
            }
        }
        
        // $error = $this->testLdap($values);
        // if($error) {
        //     $v->setError($error, 'test_ldap', 'test_ldap', 'custom');
        // }
        
        return $v->getErrors();
    }
    
    
    function testScript(&$v, $rules, $values) {

        $file = AuthRemote::getScriptPath($values, $rules[0]);
        if(!is_file($file) || !is_readable($file)) {        
            $v->setError('file_not_exist_msg', $rules[0]);
        } else {
            require_once $file;
            if(!function_exists($rules[1])) {
                $error_msg = AppMsg::getErrorMsgs();
                $error_msg = $error_msg['func_name_not_exists_msg'];
                $error_msg = str_replace('{filename}', $file, $error_msg);
                $error_msg = str_replace('{funcname}', $rules[1], $error_msg);
              
                $v->setError($error_msg, $rules[0], false, 'custom');
            }
        }
        
        return $v->getErrors();
    }
    
    
    // return false is ok string with error otherwise
    function testLdap($values) {
        try {
            $ldap = new AuthLdap($values);
            $ldap->validateCompability();
            
            $ldap->connect();
            
            $password = $values['ldap_connect_password'];
            $password = \EncryptedPassword::encode($password);
            $ldap->bind($values['ldap_connect_dn'], $password);
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return false;
    }
    
}
?>