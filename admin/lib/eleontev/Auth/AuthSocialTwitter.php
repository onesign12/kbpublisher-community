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

require_once 'twitteroauth/Config.php';
require_once 'twitteroauth/Response.php';
require_once 'twitteroauth/Request.php';
require_once 'twitteroauth/Util.php';
require_once 'twitteroauth/Util/JsonDecoder.php';
require_once 'twitteroauth/SignatureMethod.php';
require_once 'twitteroauth/Consumer.php';
require_once 'twitteroauth/HmacSha1.php';
require_once 'twitteroauth/Token.php';
require_once 'twitteroauth/TwitterOAuth.php';
require_once 'twitteroauth/TwitterOAuthException.php';


class AuthSocialTwitter extends AuthSocial
{
    
    public function getLoginLink($test = false) {
        $connection = new Abraham\TwitterOAuth\TwitterOAuth($this->client_id, $this->client_secret);
        
        $callback = AppController::getAjaxLinkToFile('twitter');
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $callback));
        
        $_SESSION['twitter_oauth_token'] = $request_token['oauth_token'];
        $_SESSION['twitter_oauth_token_secret'] = $request_token['oauth_token_secret'];
        
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        
        return $url;
    }
    
    
    public function getAccessToken() {
        if (empty($_GET['code'])) {
            throw new Exception('Error: Failed to recieve response code');
        }
        
        $oauth_token = $_SESSION['twitter_oauth_token'];
        $oauth_token_secret = $_SESSION['twitter_oauth_token_secret'];
        
        if (isset($_REQUEST['oauth_token']) && $oauth_token !== $_REQUEST['oauth_token']) {
            throw new Exception('Error: Failed to recieve response token');
        }
        
        $connection = new Abraham\TwitterOAuth\TwitterOAuth(
            $this->client_id,
            $this->client_secret,
            $oauth_token,
            $oauth_token_secret
        );
        $access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $_REQUEST['oauth_verifier']]);
        
        return $access_token;
    }
    

    public function getUserInfo($access_token) {
        $connection = new Abraham\TwitterOAuth\TwitterOAuth(
           $this->client_id,
           $this->client_secret,
           $access_token['oauth_token'],
           $access_token['oauth_token_secret']
        );
        
        $user = $connection->get('account/verify_credentials', ['tweet_mode' => 'extended', 'include_entities' => 'true']);
        $user = (array) $user;
        
        if(!$user) {
            throw new Exception('Error: Failed to get user information');
        }
        
        return $user;
    }


    static function isTest() {
        if (!empty($_SESSION['twitter_debug'])) {
            unset($_SESSION['twitter_debug']);
            return true;            
        }
    }
    
    
    public function getUserMapped($sso_user) {
        
        $user = array();
        $user['remote_provider'] = 'twitter';
        $user['remote_user_id'] = $sso_user['id'];
        
        $user['first_name'] = $sso_user['first_name'];
        $user['last_name'] = $sso_user['last_name'];
        $user['email'] = $sso_user['email'];
        
        return $user;
    }

}

?>