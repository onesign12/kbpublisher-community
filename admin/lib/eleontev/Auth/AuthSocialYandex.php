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


class AuthSocialYandex extends AuthSocial
{

    public $auth_url = 'https://oauth.yandex.ru/authorize';
    public $token_url = 'https://oauth.yandex.ru/token?';
    public $user_url = 'https://login.yandex.ru/info?format=json&';

    public $scope = 'login:email login:info';


    public function getAccessToken() {
        if (empty($_GET['code'])) {
            throw new Exception('Error: Failed to recieve response code');
        }

        $redirect_uri = AppController::getAjaxLinkToFile('yandex');

        $post_params = 'client_id=%s&redirect_uri=%s&client_secret=%s&code=%s&grant_type=authorization_code';
        $post_params = sprintf($post_params, $this->client_id, $redirect_uri, $this->client_secret, $_GET['code']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200) {
            throw new Exception('Error: Failed to recieve access token');
        }

        return $data['access_token'];
    }


    public function getUserInfo($access_token) {
        $url = $this->user_url . 'oauth_token=' . $access_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200) {
            throw new Exception('Error: Failed to get user information');
        }

        return $data;
    }


    public function getUserMapped($sso_user) {
        
        $user = array();
        $user['remote_provider'] = 'yandex';
        $user['remote_user_id'] = $sso_user['id'];

        $user['first_name'] = $sso_user['first_name'];
        $user['last_name'] = $sso_user['last_name'];
        $user['email'] = $sso_user['default_email'];

        return $user;
    }

}

?>