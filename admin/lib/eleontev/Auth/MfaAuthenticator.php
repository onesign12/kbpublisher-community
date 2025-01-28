<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;


class MfaAuthenticator
{
    static $mfa_type_map = [
        1 => 'app',
        // 2 => 'sms'
    ];
    
    static $extra_rule_id = 2;
    
    static function factory($type) {
        $class = 'MfaAuthenticator_' . $type;
        return new $class;
    }


    static function getManager() {
        static $manager;
        
        if(!$manager) {
            $manager = new UserModelExtra();
        }
    
        return $manager;
    }


    static function setSession($values) {
        $set = ['user_id', 'username', 'remember', 'msg_id', 'log', 'auth_type', 'entry_id'];
        foreach($set as $v) {
            if(isset($values[$v])) {
                $_SESSION['mfa_'][$v] = $values[$v];
            }
        }
        
        $_SESSION['mfa_']['time'] = time();
    }
    
    
    static function getSession() {
        return $_SESSION['mfa_'];
    }
    
        
    static function destroySession() {
        $_SESSION['mfa_'] = array();
        unset($_SESSION['mfa_']);
    }
    
    
    // parse after submit to table
    function parseUserMfaData($secret, $scratch, $active = 1) {
        $data = [self::$extra_rule_id => [
            'value1' => $this->mfa_type, // mfa type
            'value2' => $secret, 
            'value3' => $scratch, 
            'active' => $active  
        ]];
            
        return $data;
    }
    
    static function getMfaData($user_id) {
        $manager = self::getManager();
        $mfau = $manager->getExtraRuleById($user_id, self::$extra_rule_id);
        $ret['type'] =  !empty($mfau['value1']) ? self::$mfa_type_map[$mfau['value1']] : 1;
        $ret['secret'] =  !empty($mfau['value2']) ? $mfau['value2'] : '';
        $ret['scratch'] = !empty($mfau['value3']) ? $mfau['value3'] : '';
    
        return $ret;
    }
    
    
    static function getUserMfaData($user_id) {
        $manager = self::getManager();
        $mfau = $manager->getExtraRuleById($user_id, self::$extra_rule_id);
        $ret['type'] =  !empty($mfau['value1']) ? self::$mfa_type_map[$mfau['value1']] : 1;
        $ret['secret'] =  !empty($mfau['value2']) ? $mfau['value2'] : '';
        $ret['scratch'] = !empty($mfau['value3']) ? $mfau['value3'] : '';
    
        return $ret;
    }
    
    
    static function generateScratchCode($code = false) {
        if(!$code) {
            $code = strtoupper(WebUtil::generatePassword(6,4));
            $code = implode('-', str_split($code, 4));
        }
        
        $data = [
            'code' => $code,
            'hash' => HashPassword::getHash($code)
        ];
        
        return $data;
    }
    
    
    function save($user_id, $secret, $active = 1) {
        $data = $this->parseUserMfaData($secret, $active);
        $manager = self::getManager();
        return $manager->saveExtra($data, $user_id);
    }
    

    static function getMfaSetting() {
        return SettingModel::getQuick(1, 'mfa_policy');
    }
    
    
    // if set off on confif file
    static function isTemporaryDisabled() {
        $ret = false;
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        if(isset($conf['auth_mfa']) && empty($conf['auth_mfa'])) {
            $ret = true;
        }
        
        return $ret;
    }
    
    
    static function isPermamentDisabled() {
        return (self::getMfaSetting()) ? false : true;
    }
    
    
    // return false if not set, token otherwise 
    // true if required but not set
    static function isRequired($user_id, $priv_id, $area = false) {
        $ret = false;
        
        if(self::isTemporaryDisabled()) {
            return $ret;
        }
        
        // disabled for saml
        if($area == 'saml' && SettingModel::getQuick(162, 'saml_mfa')) {
            return $ret;
        }
        
        if($mfa = self::getMfaSetting()) {
            $manager = self::getManager();
            $mfau = $manager->getExtraRuleById($user_id, self::$extra_rule_id);
        }
        
        if($mfa == 1) { // allowed for all
            $ret = !empty($mfau['value2']) ? $mfau['value2'] : false;
        
        } elseif ($mfa == 2 && !$priv_id) { // allowed for others (not staff)
            $ret = !empty($mfau['value2']) ? $mfau['value2'] : false;
    
        } elseif ($mfa == 2 && $priv_id) { // required for staff
            $ret = !empty($mfau['value2']) ? $mfau['value2'] : true;
        
        } elseif ($mfa == 3) { // required for all
            $ret = !empty($mfau['value2']) ? $mfau['value2'] : true;
        }
    
        return $ret;
    }
    
    
    static function validateTimeSync() {
        $error = false;
        $mfa = new RobThree\Auth\TwoFactorAuth();
        try {
            $mfa->ensureCorrectTime();
        } catch (RobThree\Auth\TwoFactorAuthException $e) {
            $error = 'Your hosts time seems to be off: ' . $e->getMessage();
        }
    
        return $error;
    }
    
    
    static function emptyScratch($user_id) {
        $manager = self::getManager();
        $sql = "UPDATE {$manager->tbl->user_extra} SET value3 = NULL
        where rule_id = %d AND user_id = %d";
        $sql = sprintf($sql, self::$extra_rule_id, $user_id);
        $manager->db->Execute($sql) or die(db_error($sql));
    }
    
}


class MfaAuthenticator_app extends MfaAuthenticator
{
    
    var $mfa_type = 1;
    
    function getSetupVars($user_email) {
        $reg = &Registry::instance();
        $setting = &$reg->getEntry('setting');
        $conf = &$reg->getEntry('conf');
        $mfa_name = $setting['header_title'] ?: $conf['product_name'];
        
        // $qrCodeProvider = new EndroidQrCodeProvider();
        $qrCodeProvider = new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg');
        
        $mfa = new RobThree\Auth\TwoFactorAuth($mfa_name, 6, 30, 'sha1', $qrCodeProvider);

        $data = [];
        $data['secret'] = $mfa->createSecret();
        $data['qrcode'] = $mfa->getQRCodeImageAsDataUri($user_email, $data['secret']);
        $data['extra_rule_id'] = self::$extra_rule_id;
        $data['mfa_type'] = $this->mfa_type;
        $data['mfa_type_str'] = self::$mfa_type_map[$this->mfa_type];
        
        return $data;
    }
    
    
    function validate($code, $secret) {
        $mfa = new RobThree\Auth\TwoFactorAuth();
        $result = $mfa->verifyCode($secret, $code);
        return ($result === true) ? true : false;
    }
}
?>