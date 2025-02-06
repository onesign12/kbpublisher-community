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


class AuthSocialVk extends AuthSocial
{

    public $auth_url = 'https://oauth.vk.com/authorize';
    public $token_url = 'https://oauth.vk.com/access_token?';
    public $user_url = 'https://api.vk.com/method/users.get?v=5.87&';

// fields=uid,first_name,last_name,nickname,sex,bdate,city,country,photo.

    public $scope = 'email';

    public function getAccessToken() {
        if (empty($_GET['code'])) {
            throw new Exception('Error: Failed to recieve response code');
        }

        $redirect_uri = AppController::getAjaxLinkToFile('vk');

        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $redirect_uri,
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

        //return $data['access_token'];
    	return $data; // we have user email in this responce
    }


    public function getUserInfo($access_token_arr) {

    	// here $access_token is array
    	$access_token = $access_token_arr['access_token'];
		$email = (!empty($access_token_arr['email'])) ?  $access_token_arr['email'] : '';

        $url = $this->user_url . 'access_token=' . $access_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(!empty($data['error']) || $http_code != 200) {
            throw new Exception('Error: Failed to get user information');
        }

		$data = $data['response'][0];
		$data['email'] = $email;

        return $data;
    }


    public function getUserMapped($sso_user) {
        
        $user = array();
        $user['remote_provider'] = 'vk';
        $user['remote_user_id'] = $sso_user['id'];

        $user['first_name'] = $sso_user['first_name'];
        $user['last_name'] = $sso_user['last_name'];
        $user['email'] = $sso_user['email']; // could be empty

        return $user;
    }

}

?>