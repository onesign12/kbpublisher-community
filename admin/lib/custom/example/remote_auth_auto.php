<?php
// for Remote Authentication (remoteDoAuth) see files
// remote_auth_db.php, remote_auth_ldap.php


// to enable auto auth
define('KB_AUTH_AUTO', 1);


// simple example with HTTP authentication
function remoteAutoAuth() {
    
    $user = false;
    
    if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $user = array();
        $user['username'] = $_SERVER['PHP_AUTH_USER'];
        $user['password'] = $_SERVER['PHP_AUTH_PW'];
    }
        
    return $user;
}



// example with your custom session
function remoteAutoAuth2() {
    
    $user = false;
    
    // user have session, logged already
    if(isset($_SESSION['my_authenticated_user_id'])) {
        
        $user_id = (int) $_SESSION['my_authenticated_user_id'];
        
        // conecting, getting user data
        // check at http://adodb.sourceforge.net/ for available drivers and documentation
        $conf = array();
        $conf['db_host']    = "localhost";
        $conf['db_base']    = "kbpublisher";
        $conf['db_user']    = "username";
        $conf['db_pass']    = "password";
        $conf['db_driver']  = "mysql";

        $db = &DBUtil::connect($conf);

        // request for user
        $sql = "SELECT username, password FROM ss_user WHERE id = '%d'";
        $sql = sprintf($sql, $user_id);
        $result = $db->Execute($sql) or die(db_error($sql, false, $db));
        
        // here we should have username and password
        // password should be not md5ing, it should be as user type it in HTML form
        $user = $db->FetchRow();
    }
        
    return $user;
}

?>