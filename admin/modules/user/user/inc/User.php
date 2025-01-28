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


class User extends Person
{

    var $properties = array(
        'id'                   =>NULL,
        'grantor_id'           => 0,
        'company_id'           => 0,
        'first_name'           => '',
        'middle_name'          => '',
        'last_name'            => '',
        'email'                => '',
        'phone'                => '',
        'username'             => '',
        'password'             => '',
        'date_registered'      => '',
        'active'               => 1,

        'lastauth'             =>NULL,
        'user_comment'         => '',
        'admin_comment'        => '',

        'phone_ext'            => '',
        'address'              => '',
        'address2'             => '',
        'city'                 => '',
        'state'                => '',
        'zip'                  => '',
        'country'              => 0
    );

    var $hidden = array(
        'id', 'date_registered', 'lastauth', 'grantor_id' 
    );

    var $priv = array();
    var $role = array();
    var $subscription = array(3); // news by default
    var $extra_data = array();
    var $sso = array();

    // map, data fields to extra table and default values
    var $extra_data_default = array(
        1 => array(
            'value1' => array('api_access', 0),
            'value2' => array('api_public_key', false),
            'value3' => array('api_private_key', false),
            ),
        2 => array(
            'value1' => array('mfa_type', 1),
            'value2' => array('mfa_secret', false),
            'value3' => array('mfa_scratch', false),
            'active' => array('mfa_active', 0)
            )
        );

    var $more_info = false;


    // remove password from obj, not to replace in db
    // or set hashed password to keep in db
    function setPassword($skip_password = false) {
        if($skip_password) {
            unset($this->properties['password']);// mean not insert in db
        } else {
            // password escaped here, with slashes
            $password = $this->get('password');
            $this->set('password', HashPassword::getHash($password));
        }
    }

    
    function setUsername($force = false) {
        if(empty($this->get('username')) || $force ) {
            if(SettingModel::getQuick(1, 'username_force_email')) {
                $this->set('username', $this->get('email'));
            }
        }
    }

    function setPriv($values) {
        $this->priv = $values;
    }

    function &getPriv() {
        return $this->priv;
    }

    function setRole($values) {
        $this->role = $values;
    }

    function &getRole() {
        return $this->role;
    }

    function setSubscription($values) {
        $this->subscription = $values;
    }

    function getSubscription() {
        return $this->subscription;
    }

    function setExtra($values) {
        $this->extra_data = $values;
    }

    function getExtra() {
        return $this->extra_data;
    }

    function setSso($sso_provider_id, $sso_user_id) {
        $this->sso[$sso_provider_id] = array(
            'sso_provider_id' => $sso_provider_id,
            'sso_user_id' => $sso_user_id
        );
    }

    function getSso() {
        return $this->sso;
    }

    // used in form, to populate fields
    function getExtraValues($rule_id) {
        $values = array();
        $data = $this->getExtra();
        foreach($this->extra_data_default[$rule_id] as $k => $v) {
            $values[$v[0]] = isset($data[$rule_id][$k]) ? $data[$rule_id][$k] :  $v[1];
        }

        return $values;
    }

    // some formating goes here
    function _callBack($property, $val) {

        if($property == 'first_name' || $property == 'last_name' || $property == 'middle_name') {
            $val = ucfirst($val);

        } elseif($property == 'date_registered' && !$val) {
            $val = date('Y-m-d H:i:s');

        } elseif($property == 'lastauth' && !$val) {
            $val = NULL;

        } elseif($property == 'grantor_id') {
            $val = (int) $val;
        }

        return $val;
    }


    function setGrantor() {
        if($this->get('grantor_id') == 0) {
            $this->set('grantor_id', AuthPriv::getUserId());
        }
    }

    
    function collect($id, $data, $manager, $action = 'save') {
        
        $this->set($data, false, $action);
        
        $this->setPriv($manager->getPrivById($id));
        $this->setRole($manager->getRoleById($id));
        $this->setExtra($manager->getExtraById($id));

        foreach($manager->getSso($id) as $v) {
            $this->setSso($v['sso_provider_id'], $v['sso_user_id']);
        }
        
        // when we saved serialized $obj, make $obj the same as we save in db
        if($action == 'save' && $data) {
            // $this->setPriv(current($this->getPriv()));
            $this->setSubscription(array()); // not keep subscription
            $this->setPassword(true); // not keep password
        }

        return $this;
    }


    function validate($values, $manager, $more_required = array(), $csrf_session = true) {

        $required = array_merge(array('first_name', 'last_name', 'email'), $more_required);

        if(!SettingModel::getQuick(1, 'username_force_email')) {
            $required[] = 'username';
        }

        $pass = false;
        if(empty($values['not_change_pass'])) {
            $pass = true;
            $required[] = 'password';
        }

        $v = new Validator($values);
        
        $func = ($csrf_session) ? 'csrf' : 'csrfCookie'; // for registration use cookie
        $v->$func();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }


        // user id
        $user_id = (isset($values['id'])) ? intval($values['id']) : false;

        // we have such username
        if(in_array('username', $required)) { // could be empty if username_force_email
            $username = addslashes(stripslashes($values['username']));
            if($manager->isUsernameExists($username,  $user_id)) {
                $v->setError('username_exists_msg', 'username');
            }
        }

        // we have such email
        $email = addslashes(stripslashes($values['email']));
        if($manager->isEmailExists($email, $user_id)) {
            $v->setError('email_exists_msg', 'email');
        }


        $v->regex('email_msg', 'email', 'email');

        // password
        if($pass) {
            if(PasswordUtil::isWeakPassword($values['password'])) {
                $emsg = PasswordUtil::getWeakPasswordError();
                $v->setError($emsg, 'password', 'password', 'custom');
            }
            
            if(PasswordUtil::isNotAllowedCharacters($values['password'])) { // disabled
                $emsg = PasswordUtil::getNotAllowedCharactersPasswordError();
                $v->setError($emsg, 'password', 'password', 'custom');
            }

            $v->compare('pass_diff_msg', 'password_2', 'password');
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }


    function validatePassword($values, $manager) {

        $required = array('password', 'password_2');

        if($manager->use_old_pass) {
            $required[] = 'password_old';
        }

        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }

        // password
        if(PasswordUtil::isWeakPassword($values['password'])) {
            $emsg = PasswordUtil::getWeakPasswordError();
            $v->setError($emsg, 'password', 'password', 'custom');
        }
        
        if(PasswordUtil::isNotAllowedCharacters($values['password'])) { // disabled
            $emsg = PasswordUtil::getNotAllowedCharactersPasswordError();
            $v->setError($emsg, 'password', 'password', 'custom');
        }

        $v->compare('pass_diff_msg', 'password', 'password_2');

        $hashed_stored_password = $manager->getPassword($values['id']);

        if($manager->use_old_pass) {
            $old_password = addslashes($values['password_old']);
            $ret = HashPassword::validate($old_password, $hashed_stored_password);
            if(!$ret) {
                $v->setError('current_pass_failed_msg', 'password_old');
            }
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }

        // if password changed, new one is diffrent than prev
        $new_password = addslashes($values['password']);
        $this->pass_changed = !HashPassword::validate($new_password, $hashed_stored_password);
        
        // check expiered password 
        if (AuthPriv::getPassExpired() && (AuthPriv::getUserId() == $values['id'])) {
            
            $expiered_pass_error = false;
            if(!$this->pass_changed) {
                $expiered_pass_error = true;
            
            // compare stored passwords
            } elseif($num = SettingModel::getQuick(1, 'password_rotation_useold')) {
                
                $pass = new PasswordUtil($manager);
                $prev_passwords = $pass->getSavedPasswords(AuthPriv::getUserId(), $num);
        
                foreach($prev_passwords as $hashed_prev_password) {
                    if(HashPassword::validateQuick($new_password, $hashed_prev_password)) {
                        $expiered_pass_error = true;
                        break;
                    }
                }
            }
            
            if($expiered_pass_error) {
                $v->setError('pass_match_old_msg', 'password', 'password');
            }
        }
        

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }


    function getValidatePassword($values) {
        $ret = array();
        $ret['func'] = array($this, 'validatePassword');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }


    function validateApiKeys($values) {

        $required = array('api_public_key', 'api_private_key'); //

        $v = new Validator($values);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }

        // public key
        if(_strlen($values['api_public_key']) < 32) {
            $v->setError('wrong_format_msg', 'api_public_key');
        }

        // private key
        if(_strlen($values['api_private_key']) < 32) {
            $v->setError('wrong_format_msg', 'api_private_key');
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }


    function getValidateApiKeys($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateApiKeys');

        $this->setExtra($values['extra']);
        $api_data = $this->getExtraValues(1);
        $api_data['atoken'] = $values['atoken'];
        
        $ret['options'] = array($api_data);
        return $ret;
    }
    
    
    function validateDelete($values) {

        $v = new Validator($values);
        $v->csrf();

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }


    function getValidateDelete($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateDelete');
        $ret['options'] = array($values);
        return $ret;
    }
}
?>