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


class AuthProvider
{

    // user)to_sso_map
    static $providers = array(
        1 => 'remote',
        2 => 'ldap',
        3 => 'saml',
        4 => 'google',
        5 => 'facebook',
        6 => 'twitter',
        7 => 'vk',
        8 => 'yandex'
    );
    
    
    static function getProviderId($provider) {
        return array_search($provider, self::$providers);
    }

    
    static function getRemoteAuthVars($admin_area = false) {
    
        $auth_remote = false;
        $auth_auto = false;
        $load_remote_error = false;
        
        if (self::isRemoteAuth()) {
            $load_remote_error = AuthRemote::loadEnviroment();
            
            if(defined('KB_AUTH_AREA')) {
                
                $auth_remote = true;
                if($admin_area) {
                    $auth_remote = (KB_AUTH_AREA == 2);
                }
                
                if($auth_remote) {
                    $auth_auto = (defined('KB_AUTH_AUTO') && KB_AUTH_AUTO);
                }
            }  
        }
    
        $ret = array(
            'auth_remote' => $auth_remote,
            'auth_auto' => $auth_auto,
            'load_remote_error' => $load_remote_error
        );
        
        return $ret;
    } 
    
    
    static function getSettings() {
        static $setting;
        
        if(!$setting) {
            $setting = KBClientBaseModel::getSettings(array(160,162,163));
        }
        
        return $setting;
    }
    
    
    static function getAuthProvider() {
        
        static $ret;
        
        if($ret) {
            return $ret;
        }

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
    
        $ret = 'local';
        if(empty($conf['auth_remote']) || !AppPlugin::isPlugin('auth')) {
            return $ret;
        }

        $setting_map = array(
            'saml_auth' => 'saml',  
            'remote_auth' => 'ldap',
            'remote_auth_script' => 'remote',
        );
    
        $setting = self::getSettings();
        // $setting = array_intersect_key($setting, $setting_map);
        
        foreach($setting_map as $k => $v) {
            if($setting[$k]) {
                $ret = $setting_map[$k];
                break;
            }
        }
        
        return $ret;
    }
    
    
    static function getAuthType() {
        return AuthProvider::getAuthProvider();
    }
    

    static function isRemoteAuth() {
        $provider = self::getAuthProvider();
        return (in_array($provider, array('ldap', 'remote')));
    }
    
    
    static function isSamlAuth() {
        $provider = self::getAuthProvider();
        return ($provider == 'saml');
    }


    static function isSamlOnly() {
        $setting = self::getSettings();
        return (in_array($setting['saml_mode'], array(2,3)));
    }
    
    
    static function isSamlAuto() {
        $setting = self::getSettings();
        return ($setting['saml_mode'] == 3);
    }
    
    
    static function loadSaml() {
        require_once 'php-saml/_toolkit_loader.php';
    }
}
    
?>
