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

namespace RAuthSetting;

use Validator;
use AuthRemote;
use AppMsg;



class SettingValidator
{
     
    function validate($values) {        
        
        if (!empty($values['remote_auth_script'])) {
            $required = array('remote_auth_script_path');
        
            $v = new Validator($values, true);
            
            // required
            $v->required('required_msg', $required);
            if($v->getErrors()) {
                return $v->getErrors();
            }
            
            
            // defined in script
            $rules = array('remote_auth_script_path', 'remoteDoAuth');
            $error = $this->testScript($v, $rules, $values);
            if($error) {
                return $error;
            }
            
            return $v->getErrors();
        }
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
    
}
?>