<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2013-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+

class HashPassword
{
   
    static function getHash($password, $force = false) {
        return HashPassword_crypt::getHash($password, $force);
    }

    
    static function validate($password, $hashed_password) {
        return HashPassword_crypt::validate($password, $hashed_password);
    }


    static function validateQuick($password, $hashed_password) {
        return HashPassword_crypt::validateQuick($password, $hashed_password);
    }

}



class HashPassword_common
{
    
     static function factory($type) {

        $class = 'HashPassword_' . $type;
        $file = $class . '.php';

        $obj = new $class;
        return $obj;
    }

    
    static function getRandom() {
       
        $r = false;
          
        if(function_exists('mcrypt_create_iv')) {
        
            // less than 5.3
            if(version_compare(PHP_VERSION, '5.3', '<')) {
                srand((double) microtime() * 1000000);
            
                // and windows
                if(substr(PHP_OS, 0, 3) == 'WIN') {
                    $random_source = MCRYPT_RAND;
                } else {
                    $random_source = MCRYPT_DEV_RANDOM; // for compability with default value
                }
            
            } else {
                $random_source = MCRYPT_DEV_URANDOM;
            }
            
            $r = mcrypt_create_iv(30, $random_source);
        }
        
        if(!$r) {
            
            $r = pack('N5', mt_rand(1111,99999), mt_rand(1111,99999), 
                            mt_rand(1111,99999), mt_rand(1111,99999), 
                            mt_rand(1111,99999));
        }
        
        return str_replace('+', '', base64_encode($r));
    }
    
    
    // Compares two strings $a and $b in length-constant time.
    static function slowEquals($a, $b) {
        $diff = strlen($a) ^ strlen($b);
        for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
        {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }
}    


class HashPassword_crypt
{

    static function getSalt($password, $force = false) {

        $salt = NULL;
        $str = HashPassword_common::getRandom();
        
        $enc = array(
            'CRYPT_BLOWFISH' => CRYPT_BLOWFISH, 
            'CRYPT_SHA512' => CRYPT_SHA512, 
            'CRYPT_SHA256' => CRYPT_SHA256, 
            'CRYPT_MD5' => CRYPT_MD5
            );
        
        $crypt = false;
        foreach($enc AS $k => $v) {
            if($v == 1) {
                $crypt = $k;
                break;
            }
        }
        
        if(isset($enc[$force]) && $enc[$force] == 1) {
            $crypt = $force;
        }
        
        if($crypt == 'CRYPT_BLOWFISH') {
            
            // $2a$07$usesomesillystringforsalt$
            // PHP 5.3.7 and later should use "$2y$" in preference to "$2a$".
            $key = '$2a$'; 
            if(version_compare(PHP_VERSION, '5.3.7', '>=')) {
                $key = '$2y$';
            } 

            $cost = '07';
            $salt = substr($str, 0, 22);
            $salt = $key . $cost . '$'  . $salt . '$';

        } elseif($crypt == 'CRYPT_SHA512') {
            
            // $6$rounds=5000$usesomesillystringforsalt$
            $key = '$6$'; 
            $cost = '5000';
            $salt = substr($str, 0, 22);
            $salt = $key . 'rounds=' . $cost . '$'  . $salt . '$';

        } elseif($crypt == 'CRYPT_SHA256') {
            
            // $5$rounds=5000$usesomesillystringforsalt$
            $key = '$5$'; 
            $cost = '5000';
            $salt = substr($str, 0, 22);
            $salt = $key . 'rounds=' . $cost . '$'  . $salt . '$';

        } elseif($crypt == 'CRYPT_MD5') {
            
            // $1$rasmusle$rISCgZzpwk3UhDidwXvin0
            $key = '$1$'; 
            $salt = substr($str, 0, 12);
            $salt = $key . $salt . '$';        
        }

        return $salt;
    }

    
    // Returns the hashed string or a string that is shorter than 13 characters 
    // and is guaranteed to differ from the salt on failure.
    static function getHash($password, $force = false) {
        $salt = self::getSalt($password, $force);
        $ret = crypt($password, $salt);
        return $ret;
    }
    
    
    static function validate($password, $hashed_password) {
        $ret = crypt($password, $hashed_password);
        return HashPassword_common::slowEquals($ret, $hashed_password);
    }

    
    static function validateQuick($password, $hashed_password) {
        $ret = crypt($password, $hashed_password);
        return ($ret == $hashed_password);
    }
    
}

?>