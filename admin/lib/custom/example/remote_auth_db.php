<?php
define('KB_AUTH_AREA', 1);
define('KB_AUTH_LOCAL', 1);
define('KB_AUTH_TYPE', 1);
define('KB_AUTH_REFRESH_TIME', 3600*24*30);
define('KB_AUTH_LOCAL_IP', false);
define('KB_AUTH_RESTORE_PASSWORD_LINK', false);


// quering  remote user table and return user array to be saved in kb
function remoteDoAuth($username, $password) {
    
    $user = false;
    if(empty($username) || empty($password)) {
        return $user;
    }
    
    // conecting
    // check at http://adodb.sourceforge.net/ for available drivers and documentation
    $conf = array();
    $conf['db_host']    = "localhost";
    $conf['db_base']    = "kbpublisher";
    $conf['db_user']    = "username";
    $conf['db_pass']    = "password";
    $conf['db_driver']  = "mysql";
    
    $db = &DBUtil::connect($conf);
    
    // request for user
    $username = addslashes($username);
    $md5_password = MD5($password);
    
    $sql = "
    SELECT 
        id AS 'remote_user_id', 
        email AS 'email',
        username AS 'username',
        first_name AS 'first_name',
        last_name AS 'last_name'
    FROM ss_user
    WHERE username = '%s' AND password = '%s'";
    $sql = sprintf($sql, $username, $md5_password);
    $result = $db->Execute($sql) or die(db_error($sql, false, $db));
    
    
    // if found
    if($result->RecordCount() == 1) { 
        $user = $result->FetchRow();
        $user['password'] = $password; // here you have provide not md5ing password
        
        // assign a priv to user (optional)
        // it is fully up to you how to determine who is authenticated and what priv to assign
        $user['priv_id'] = 3;
        
        // assign a role to user (optional)
        // it is fully up to you how to determine who is authenticated and what role to assign
        $user['role_id'] = 1;
    }
    
    return $user;
}


// quering  remote table
function _remoteDoAuth($username, $password) {
    
    $user = false;
    if(empty($username) || empty($password)) {
        return $user;
    }
    
    // conecting
    // check at http://adodb.sourceforge.net/ for available drivers and documentation
    $conf = array();
    $conf['db_host']    = "localhost";
    $conf['db_base']    = "kbpublisher";
    $conf['db_user']    = "username";
    $conf['db_pass']    = "password";
    $conf['db_driver']  = "mysql";
    
    $db = &DBUtil::connect($conf);
    
    // request for user
    $username = addslashes($username);
    $md5_password = MD5($password);
    
    $sql = "SELECT 1 FROM ss_user WHERE username = '%s' AND password = '%s'";
    $sql = sprintf($sql, $username, $md5_password);
    $result = $db->Execute($sql) or die(db_error($sql, false, $db));
    
    // if found
    if($result->RecordCount() == 1) {
        $user = 1; // assign a user id, this user id should exists in kb user table
    }
    
    return $user;
}



define('KB_AUTH_AUTO', 1);


// you should rename this function to remoteAutoAuth
// on success it should return array with keys username, password, false otherwise
// for example: array('username'=>'johndoe', 'password'=>'qew54ew')
// this function will be called where authentication required instead of login form
// here you should cacth user data and based on it get username, password and return it
// remoteAutoAuth() -> remoteDoAuth() -> authencate user to KB
function remoteAutoAuth() {
    
    $user = false;
        
    // if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    //     $user = array();
    //     $user['username'] = $_SERVER['PHP_AUTH_USER'];
    //     $user['password'] = $_SERVER['PHP_AUTH_PW'];
    // }
    
    $user = array();
    $user['username'] = 'onesign';
    $user['password'] = 'onesign';
        
    // $_SESSION ['auth_']['selector'] = $_COOKIE['kb_selector_'];
    // echo '<pre>', print_r($_COOKIE,1), '</pre>';
    // exit;
        
    return $user;
}
?>