<?php
// There are 2 login screens, if set to 1, remote authentication 
// will never be applied on Admin Area login screen (admin/login.php)
// 1 - Enabled for Public area only
// 2 - Enabled for Public and Admin areas 
define('KB_AUTH_AREA', 2);


// 0 - never trying to authenticate by KBPublisher's built in authentication
// 1 - Trying to authenticate by KBPublisher's built in authentication before Remote Authentication
// 2 - Trying to authenticate by KBPublisher's built in authentication after Remote Authentication, if it failed
define('KB_AUTH_LOCAL', 0);


// If you have a lot of user and allow KBPublisher's built in authentication it could happen 
// that username and password can match with one of admin users and user will have access to Admin Area
// to avoid this you can set IP range, only users with these IP will trying to be authenticated
// by KBPublisher's built in authentication, it only does matter when KB_AUTH_LOCAL = 1 or 2
// you can set concrete IP or range of IP devided by "-", all IP(s) should be devided by ";"
// example: 127.0.0.1;210.234.12.15;192.168.1.1-192.168.255.255
define('KB_AUTH_LOCAL_IP', '127.0.0.1-127.0.0.3;208.34.45.1');


// remote auth type
// 1 - on success remoteDoAuth should return associative array with keys
//     (first_name, last_name, email, username, password, remote_user_id)
//     where "remote_user_id" is an unique id for user in your system (integer)
//     for example: array('first_name'=>'John', 'last_name'=>'Doe', ....);
//     
// 2 - on success remoteDoAuth should return user_id  that presents as id field in kb user table or 
//     associative array with keys (user_id, username)
//     for example: array('user_id'=>7, 'username'=>'Test');
//     On failure it should return false.
define('KB_AUTH_TYPE', 1);


// time in seconds to rewrite user data, (3600*24*30 = 30 days), works if KB_AUTH_TYPE = 1
// 0 - never, it means once user created, data in kb will never be updated by script
// 1 - on every authentication request user data in kb will be updated with data provided by script
define('KB_AUTH_REFRESH_TIME', 1); //3600*24*30


// here you may provide a link where your remote user can restore password
// it will be used on login screen
// set to false not to display restore password link at all
// KBPublisher will determine to set your link or built-in one
define('KB_AUTH_RESTORE_PASSWORD_LINK', '');


// usually with remote authentication there is no need for user to update his/her 
// account data because the data refreshed from remote source
// 0 = OFF, user can't update his account data in KBP
// 1 = ON, user can update his account data in KBP 
// 2 - automatic, it will be set depending on other remote settings 
define('KB_AUTH_UPDATE_ACCOUNT', 2);



// you should rename this function to remoteDoAuth
// on success should return apropriate value (depends on KB_AUTH_TYPE), false otherwise
function remoteDoAuth($username, $password) {
    
    $user = false;
    if(empty($username) || empty($password)) {
        return $user;
    }
    
    $user = [
        'remote_user_id' => 100,
        'email'      => 'remote@user.com',
        'username'   => 'remote_user',
        'password'   => 'Storage12#',
        'first_name' => 'John',
        'last_name'  => 'Dow',
        'priv_id'    => 2,
        'role_id'    => 'off'
    ];
    
    return $user;
}



// AUTO AUTHENTICATION // --------------------

// auto authentication
// 0 - not allowed
// 1 - allowed
// 2 = allowed [DEBUG], skip set session wrong auth try, 
//     use when setting it up, do not gorget to set to 1 on production
define('KB_AUTH_AUTO', 0);


// you should rename this function to remoteAutoAuth
// on success it should return array with keys username, password, false otherwise
// for example: array('username'=>'johndoe', 'password'=>'qew54ew')
// this function will be called where authentication required instead of login form
// here you should cacth user data and based on it get username, password and return it
// remoteAutoAuth() -> remoteDoAuth() -> authencate user to KB
function remoteAutoAuth() {
    
    $user = false;
        
    if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $user = array();
        $user['username'] = $_SERVER['PHP_AUTH_USER'];
        $user['password'] = $_SERVER['PHP_AUTH_PW'];
    }
        
    return $user;
}
?>