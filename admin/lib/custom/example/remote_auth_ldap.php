<?php
define('KB_AUTH_LOCAL', 1);
define('KB_AUTH_TYPE', 1);
define('KB_AUTH_REFRESH_TIME', 3600*24*30);

/*
FOR ADVANCED USERS ONLY

Requirements
You will need to get and compile LDAP client libraries from either » OpenLDAP or » Bind9.net 
in order to compile PHP with LDAP support.

Installation
LDAP support in PHP is not enabled by default. You will need to use 
the --with-ldap[=DIR] configuration option when compiling PHP to enable LDAP support. 
DIR is the LDAP base install directory. 
To enable SASL support, be sure --with-ldap-sasl[=DIR] is used, and that sasl.h exists on the system. 
*/

// Documentations for PHP ldap functions http://php.net/ldap

function remoteDoAuth($username, $password) {
    
    $user = false;
    if(empty($username) || empty($password)) {
        return $user;
    }
    
    // conecting
    $ldapconfig['host'] = 'localhost';
    $ldapconfig['port'] = NULL;
    $ldapconfig['basedn'] = 'dc=localhost,dc=com';
    
    $username = addslashes($username);
    $password = addslashes($password);
    $ldap_user = array();
    
    $ds = @ldap_connect($ldapconfig['host'], $ldapconfig['port']);
    $r = @ldap_search( $ds, $ldapconfig['basedn'], 'uid=' . $username);
    if ($r) {
        $result = @ldap_get_entries($ds, $r);
        if($result[0]) {
            if(@ldap_bind($ds, $result[0]['dn'], $password)) {
                $ldap_user = $result[0];
            }
        }
    }
    
    // if found
    if($ldap_user) {
    
        // here you have to create asociative array from $ldap_user with keys
        // first_name, last_name, email, username, password, remote_user_id
        // remote_user_id is a unique id for user in your system (integer)
        $user['first_name'] = $ldap_user['firstname'];
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


// Example of using adLDAP - LDAP Authentication with PHP for Active Directory 
# Download the Active Directory/PHP Helper library from http://adldap.sourceforge.net/
# Unpack the download and place the adLDAP.php file into the /admin/lib/custom folder of your KBPublisher installation.
# Open adLDAP.php in a text editor. Around line 67 there starts a few variables 
# you'll need to modify so that an AD connection can be made to your AD server. 
# You'll likely need to modify at least _account_suffix, _base_dn, and _domain_controllers. 
# When finished save the file.

define('KB_AUTH_LOCAL', 2); // returning exiting kb user

function remoteDoAuth($username, $password) {
    
    require_once 'custom/adLDAP.php';
    
    $auth = false;
    if(empty($username) || empty($password)) {
        return $auth;
    }
    
    $username = addslashes($username);
    $password = addslashes($password);
    
    //create the AD LDAP connection
    $adldap = new adLDAP();
    
    // if found
    if($adldap->authenticate($username, $password)){
        $user = 1; // assign a user id, this user id should exists in kb user table
    }
    
    return $user;
}
?>