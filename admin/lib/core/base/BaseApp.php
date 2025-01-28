<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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


class BaseApp
{
    var $BaseAppCheck = true;


}




class KBValidateLicense
{

    function __construct() {

    }


    // VALIDATE // --------------------

    static function validate($action = false) {

        $keys_str = KBValidateLicense::loadLicenseKeysString();
        if($keys_str == 'license_file_not_found') {
            return 'license_file_not_found';
        }

        $keys = KBValidateLicense::loadLicenseKeys($keys_str);
        if($keys_str == 'license_file_not_found') {
            return 'license_file_not_found';
        }
     
        // $trial = array('trial', 'evaluation');
        // if(in_array($keys['license'], $trial)) {
        //     return KBValidateLicense::validateEvaluation($keys, $keys_str);
        // }

        return true;
    }


    static function validateCommon($keys, $keys_str) {

        $num_days = 45;
        $license_key = KBValidateLicense::getLicenseKey('license_key');

        // no license key
        if(!$license_key) {
            $valid_date = $keys['date_purchased'] + (3600*24*$num_days); //valid until
            $current_date = time();

            // no more days
            if($current_date > $valid_date) {
                return 'license_no_key_period_expire';

            // have some days
            } else {
                $rest_days = ceil(($valid_date - $current_date) / (3600*24));
                $valid_date = date('j M, Y', $valid_date);
                return array('license_no_key_period_notice', 'days' => $rest_days, 'valid_date' => $valid_date);
            }
        }

        // we have license key
        $license_key_compare = KBValidateLicense::getLicenseKeyCompare($keys_str);
        // echo '<pre>'; print_r($license_key); echo '</pre>';
        // echo '<pre>'; print_r($license_key_compare); echo '</pre>';


        // check for copyright removal licence, remove it from db if incorrect
        // to be able to check it for concrete license only
        // KBValidateLicense::validateCopyright($license_key);

        if($license_key != $license_key_compare) {
            return 'license_keys_not_match';
        }

        return true;
    }

    
    static function  getAllowedEntryRest() {
        return true;
    }
    
    static function  getAllowedUserRest() {
        return true;
    }

    static function  validateUser() {
        return true;
    }


    // static function validateEvaluation($keys, $message = false, $param = false) {
    // 
    //     $num_days = 45;
    //     $valid_date = $keys['date_purchased'] + (3600*24*$num_days); //valid until
    //     $current_date = time();
    // 
    //     // no more days
    //     if($current_date > $valid_date) {
    //         return 'license_trial_period_expired';
    //     }
    // 
    //     $rest_days = ceil(($valid_date - $current_date) / (3600*24));
    //     $valid_date = date('j M, Y', $valid_date);
    //     return array('license_trial_period_notice', 'days' => $rest_days, 'valid_date' => $valid_date);
    // }


    static function isLicenseKeysMatch($license_key = false) {
        $license_key = ($license_key) ? $license_key : KBValidateLicense::getLicenseKey();
        $keys_str = KBValidateLicense::loadLicenseKeysString();
        $license_key_compare = KBValidateLicense::getLicenseKeyCompare($keys_str);

        //echo '<pre>License: ', print_r($license_key_compare, 1), '</pre>';
        return ($license_key == $license_key_compare);
    }


    // KEYS // -----------------

    static function getLicenseKey($key = 'license_key', $setting_module_id = 150) {
        $license_key = false;
        $reg =& Registry::instance();

        if($reg->isEntry('setting')) {
            $setting = $reg->getEntry('setting');
            if(!empty($setting[$key])) {
                $license_key = $setting[$key];
            }
        } else {
            $license_key = SettingModel::getQuick($setting_module_id, $key);
        }

        return $license_key;
    }


    static function getLicenseKeyCompare($string_keys) {
        // not sure in this ... ? no in old implemantation and error if ' in user name
        // keys generated wrongs on server and here it is ok with addslashes
        // $string_keys = addslashes($string_keys);
        $salt = 'asd4%32#esd*JBGtg679';
        $key = md5($string_keys . $salt);
        return $key;
    }


    static function loadLicenseKeys($keys = false) {

        if(!$keys) {
            $keys = KBValidateLicense::loadLicenseKeysString();
        }

        if($keys == 'license_file_not_found') {
            return 'license_file_not_found';
        }

        list($ls['user_id'],
             $ls['licensed'],
             $ls['date_purchased'],
             $ls['date_license'],
             $ls['license'],
             $ls['num_user'],
             $ls['num_article'],
             $ls['num_file'],
             $ls['cloud']) = explode('|', $keys);

        // echo '<pre>'; print_r($ls); echo '</pre>';
        if(count($ls) != 9) {
            return 'license_file_not_found';
        }

        if(!defined('KBP_CLOUD')) {
            define('KBP_CLOUD', $ls['cloud']);
        }

        return $ls;
    }


    static function loadLicenseKeysString() {

        $file = APP_CLIENT_DIR . 'key.php';
        if(defined('APP_USER_CLOUD_KEY')) {
            $file = APP_USER_CLOUD_KEY;
        }

        if(!file_exists($file)) {
            return 'license_file_not_found';
        }

        $c = file_get_contents($file);
        if(strpos($c, 'ioncube') !== false) {
            include $file;
            return (!empty($license)) ? $license : 'license_file_not_found';

        } else {
            preg_match('#\/\*(.*?)\*\/#', $c, $match);
            return (!empty($match[1])) ? KBValidateLicense::parseKey($match[1]) : 'license_file_not_found';
        }
    }


    // deccode key encoded by our encoder
    static function parseKey($hex){
        $string = '';
        $hex = base64_decode(strrev($hex));
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }


    // REQUEST // -------------------------

    static function sendLicenseInfo() {
        $ret = KBValidateLicense::sendRequest('license_info');
    }


    static function sendRequest($reason) {

        require_once 'PEAR/HTTP/Request2.php';

        $page = 'https://www.kbpublisher.com/rpc/license.php';

        $options = array(
            'follow_redirects' => true,
            'timeout' => 5);

        $req = new HTTP_Request2($page, HTTP_Request2::METHOD_POST, $options);
        // $req->setMethod(HTTP_Request2::METHOD_POST);

        $req->addPostParameter('a', 'check_license');
        $req->addPostParameter('r', $reason);
        foreach(KBValidateLicense::getProductData() as $k => $v) {
            $req->addPostParameter($k, $v);
        }
        
        
        $ret = -1;
        try {
            $response = $req->send();
            
            if ($response->getStatus() == 200) {
                $ret = $response->getBody();
            }
            
        } catch (HTTP_Request2_Exception $e) {
            
        }

        return $ret;
    }


    static function getProductData() {

        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');

        $data = array(
            'version'         => $conf['product_version'],
            'license_str'     => KBValidateLicense::loadLicenseKeysString(),
            'license_key'     => KBValidateLicense::getLicenseKey('license_key'),
            'url'              => $conf['client_path']
        );

        return $data;
    }


    function validateShutDown() {
        echo 123;
    }


    static function getDateBack($date) {
        if($date == 1) {
            $date = 30;
        } else {
            $date = $date - 1;
        }

        return $date;
    }



}


define('KBP_LICENSE_LOADED', true);
define('KBP_LICENSE_LOADED_PRIVATE', true);

//register_shutdown_function(array('KBValidateLicense', 'validateShutDown'));
?>