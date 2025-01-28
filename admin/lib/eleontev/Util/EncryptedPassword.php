<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2013-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+


class EncryptedPassword
{
    protected static $method = 'AES-128-CTR';
    private static $key = 'salt';
    
  
    public static function encode($password) {
        if (function_exists('openssl_encrypt')) {
            return self::encodeOpenSsl($password);
            
        } else {
            return self::encodeRot13($password);
        }
    }
    
    
    private static function encodeOpenSsl($password) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$method));
        
        if(ctype_print(self::$key)) {
            $key = openssl_digest(self::$key, 'SHA256', true);
        }
        
        $encrypted_string = bin2hex($iv) . openssl_encrypt($password, self::$method, $key, 0, $iv);
        
        return $encrypted_string;
    }
    
    
    private static function getSimpleEncodePrefix() {
        return '<YqwRw21:-';
    }
    
    
    private static function encodeRot13($password) {
        return self::getSimpleEncodePrefix() . str_rot13($password);
    }

    
    public static function decode($password) {
        if (strpos($password, self::getSimpleEncodePrefix()) === 0) {
            return self::decodeRot13($password);
            
        } else {
            return self::decodeOpenSsl($password);
        }
    }
    
    
    private static function decodeRot13($password) {
        return str_rot13(substr($password, strlen(self::getSimpleEncodePrefix())));
    }
    
    
    private static function decodeOpenSsl($password) {
        if (!function_exists('openssl_encrypt')) {
            return false;
        }
        
        $iv_strlen = 2  * openssl_cipher_iv_length(self::$method);
        if(preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $password, $regs)) {
            list(, $iv, $crypted_string) = $regs;
            
            if(ctype_print(self::$key)) {
                $key = openssl_digest(self::$key, 'SHA256', true);
            }
            
            $decrypted_string = openssl_decrypt($crypted_string, self::$method, $key, 0, hex2bin($iv));
            return $decrypted_string;
          
        } else {
          return false;
        }
    }

}

?>