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


class AuthSocial extends AuthPriv
{

    static public $providers_color = array(
        'google'   => '#4285f4',
        'facebook' => '#3c5a99',
        'twitter'  => '#4AB3F4',
        'vk'       => '#5181b8',
        'yandex'   => '#ffd633' // #fb0d1b red
    );

    public $client_id;
    public $client_secret;
    public $log;
    public $log_prefix = 'Social';


    static function factory($provider, $settings = array()) {

        $class = 'AuthSocial' . ucfirst($provider);
        $file = sprintf('%seleontev/Auth/%s.php', APP_LIB_DIR, $class);

        if (file_exists($file)) {
            return new $class($provider, $settings);
        } else {
            die('Wrong social provider');
        }
    }


    public function __construct($provider, $settings = array()) {
        $this->umanager = new AuthRemoteModel;

        if(!$settings) {
            $settings = SettingModel::getQuick(164);
        }

        $this->client_id = $settings[$provider . '_client_id'];
        $this->client_secret = $settings[$provider . '_client_secret'];

        parent::__construct();
    }


    static function getProviderList() {
        return self::$providers_color;
    }


    public function getLoginLink($test = false) {
        $state = ($test) ? 'test' : 1;

        $classname = get_class($this);
        $key = strtolower(substr($classname, 10));

        $params = array(
            'scope' => $this->scope,
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'access_type' => 'online',
            'redirect_uri' => AppController::getAjaxLinkToFile($key),
            'state' => $state
        );

        $link = sprintf('%s?%s', $this->auth_url, http_build_query($params));
        $link = AppController::_replaceArgSeparator($link);

        return $link;
    }


    public function getUserInfo($access_token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->user_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200) {
            throw new Exception('Error: Failed to get user information');
        }

        return $data;
    }


    function parseUserMapped($sso_user) {

        // $parts = explode("@", $sso_user['email']);
        // $username = $parts[0];

        @$fname = $sso_user['first_name'];
        @$lname = $sso_user['last_name'];

        // if(!$first_name) {
        //     $delimiters = array(".", "-", "_");
        //     foreach ($delimiters as $delimiter) {
        //         if(strpos($username, $delimiter)) {
        //           $names = preg_replace("/\d+$/","", $username);
        //           $names = explode( $delimiter, $parts_name);
        //           break; // If we've found a delimiter we can move on
        //         }
        //     }
        //
        //     if($names ) {
        //         $fname = ucfirst(strtolower($namea[0]));
        //         $lname = ucfirst(strtolower($names[1]));
        //     }
        // }

        if(empty($fname)) {
            $sso_user['first_name'] = $sso_user['remote_provider'];
        }

        if(empty($lname)) {
            $sso_user['last_name'] = 'sso';
        }

        return $sso_user;
    }


    static function isTest() {
        return (isset($_GET['state']) && $_GET['state'] == 'test');
    }


    function isIncompleteRemoteUser($sso_user) {
        $error = false;
        if (empty($sso_user['remote_user_id'])) {
            $error = 'There is no remote_user_id in responce';
        }

        return $error;
    }


    public function doAuthSocial($sso_user) {

        // remote_user_id is missing for some reason
        if ($this->isIncompleteRemoteUser($sso_user)) {
            $this->writeIncompleteRemoteUserLog($sso_user, 'social');
            throw new Exception('Error: Wrong remote params error', self::WRONG_REMOTE_PARAMS_ERROR);
        }

        $sso_user = $this->parseUserMapped($sso_user);

        // we have this in AuthRemote::doAuth so decided add here
        $sso_user = &RequestDataUtil::stripslashes($sso_user, array('username'));
        $sso_user = &RequestDataUtil::addslashes($sso_user, array('username'));


        $remote_provider_id = AuthProvider::getProviderId($sso_user['remote_provider']);
        $kb_user = $this->umanager->isUserLinkedSso($sso_user['remote_user_id'], $remote_provider_id);

        $msg = 'User details - remote_provider: %s, remote_user_id: %s';
        $this->putLog(sprintf($msg, $sso_user['remote_provider'], $sso_user['remote_user_id']));

        $is_current_user = ($kb_user) ? true : false;

        // linked user, login
        if($is_current_user) {
            $msg = 'User is linked with KBPublisher account, User ID: %d, authenticating ...';
            $this->putLog(sprintf($msg, $kb_user['id']));

            // if($this->umanager->isUserHasPriv($kb_user['id'])) {
            //     $msg = 'It is not allowed to login linked users as Staff user, exit...';
            //     $this->putLog(sprintf($msg, $kb_user['id']));
            //     throw new Exception('Error: Empty email', self::NO_EMAIL_ERROR);
            //
            // // get kb user and auth
            // } else {
                $user = $this->umanager->getUserById($kb_user['id']);
                $auth = AuthPriv::doAuth($user['username'], $user['password'], false);
            // }
        }


        // new user, save
        if(!$is_current_user) {

            $msg = 'User is not linked with KBPublisher account';
            $this->putLog($msg);

            $user['social'] = 1;

             // email is missing for some reason
            if (empty($sso_user['email'])) {
                $this->putLog('User account does not have email');

                $log =& $this->log;
                $log->saveLoginLogData();

				$sso_user['remote_provider_id'] = $remote_provider_id;
                AuthPriv::saveUserData($sso_user);

                throw new Exception('Error: Empty email', self::NO_EMAIL_ERROR);
            }

             // email exist in database
            if($kb_user = $this->umanager->isUserByEmail($sso_user['email'])) {
                $msg = 'Email exists in KBPublisher database, User ID: %d, Email:  %s';
                $this->putLog(sprintf($msg, $kb_user['id'], $sso_user['email']));

                $log =& $this->log;
                $log->saveLoginLogData();

				$sso_user['remote_provider_id'] = $remote_provider_id;
                AuthPriv::saveUserData($sso_user);

                throw new Exception('Error: Email exists', self::EMAIL_EXISTS_ERROR);
            }

            // continue creating user
            $user = $sso_user;
            $user['id'] = NULL;
            $user['username'] = $sso_user['email'];
            $user['password'] = WebUtil::generatePassword(4,3);
            $user['remote_provider_id'] = $remote_provider_id;

            $msg = 'Creating new user...';
            $this->putLog($msg);

            $this->umanager->saveUser($user);

            $msg = 'User was successfully added';
            $this->putLog($msg);

            $auth = AuthPriv::doAuth($user['username'], $user['password']);
        }

        return $auth;
    }

}

?>