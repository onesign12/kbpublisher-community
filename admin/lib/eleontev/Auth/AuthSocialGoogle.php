<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                               |
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


class AuthSocialGoogle extends AuthSocial
{

    public $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
    public $token_url = 'https://accounts.google.com/o/oauth2/token';
    public $user_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    

    public $scope = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.me';


    public function getAccessToken() {
        if (empty($_GET['code'])) {
            throw new Exception('Error: Failed to recieve response code');
        }

        $redirect_uri = AppController::getAjaxLinkToFile('google');

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


    public function getUserMapped($sso_user) {

        $user = array();
        $user['remote_provider'] = 'google';
        $user['remote_user_id'] = $sso_user['sub'];

        $user['first_name'] = $sso_user['given_name'];
        $user['last_name'] = $sso_user['family_name'];
        $user['email'] = $sso_user['email'];

        return $user;
    }


    public function getSharesCount($article_url) {
        $url = 'https://clients6.google.com/rpc';

        $post_params = '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"%s","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]';
        $post_params = sprintf($post_params, $article_url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);

        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = json_decode(curl_exec($ch), true);
        return $data[0]['result']['metadata']['globalCounts']['count'];
    }

}
?>