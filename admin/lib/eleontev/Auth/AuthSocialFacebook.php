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


class AuthSocialFacebook extends AuthSocial
{

    public $auth_url = 'https://www.facebook.com/v10.0/dialog/oauth';
    public $token_url = 'https://graph.facebook.com/v10.0/oauth/access_token?';
    public $user_url = 'https://graph.facebook.com/v10.0/me?fields=email,first_name,middle_name,last_name,address';

    public $scope = 'public_profile, email';


    public function getAccessToken() {
        if (empty($_GET['code'])) {
            throw new Exception('Error: Failed to recieve response code');
        }

        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => AppController::getAjaxLinkToFile('facebook'),
            'client_secret' => $this->client_secret,
            'code' => $_GET['code']
        );

        $url = $this->token_url . http_build_query($params);
        $url = AppController::_replaceArgSeparator($url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200) {
            throw new Exception('Error: Failed to recieve access token');
        }

        return $data['access_token'];
    }


    public function getUserMapped($sso_user) {
        
        $user = array();
        $user['remote_provider'] = 'facebook';
        $user['remote_user_id'] = $sso_user['id'];

        $user['first_name'] = $sso_user['first_name'];
        $user['last_name'] = $sso_user['last_name'];
        $user['email'] = $sso_user['email'];
        
        return $user;
    }


    public function getSharesCount($article_url) {
        $url = 'http://graph.facebook.com/?id=' . $article_url;
        $url = AppController::_replaceArgSeparator($url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $data['share']['share_count'];
    }

}

?>