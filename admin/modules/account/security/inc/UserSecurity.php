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


class UserSecurity extends User
{

    function validateMfa($values) {
        
        $v = new Validator($values);
        $v->csrf();
        $v->required('required_msg', ['code']);

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        $mfa = MfaAuthenticator::factory('app');
        $result = $mfa->validate($values['code'], $values['secret']);
        if($result !== true) {
            $v->setError('confirm_text_msg', 'code');
        }
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    

    function validateDisableMfa($values) {
        
        $v = new Validator($values);
        $v->csrf();
                
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    

    function getValidateMfa($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateMfa');
        $ret['options'] = array($values);
        return $ret;
    }
    
    
    function getValidateDisableMfa($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateDisableMfa');
        $ret['options'] = array($values);
        return $ret;
    }
}
?>