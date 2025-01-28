<?php

/*
    Identity - who is making an API request?
    Authentication - are they really are who they say they are?
    Authorization – are they allowed to do what they are trying to do?
*/

class KBApiError
{
    
    
    static $codes = array(
        
        // 1 => array('Authentication required', 401),
        // 2 => array('Authentication expiered', 401),
        3 => array('Authentication failed', 401), // Unknown user, not found by api key
        4 => array('Authorization falied', 401), // Bad signature
        5 => array('You are not allowed access this resource using (%s) request', 401),
        
        11 => array('Database error', 500),
        14 => array('JSON encoding error', 500),
        
        21 => array('API is available via SSL only', 400),
        22 => array('You cannot access this resource using (%s) request', 400),
        23 => array('Sorry, that page does not exist', 400), // for wrong calls
        24 => array('Sorry, that method does not exist', 400), // for wrong methods
        25 => array('Missing or invalid argument(s)', 400),
        
        28 => array('API is not available', 503),
        29 => array('API is temporarily unavailable', 503),
        
        31 => array('Not found', 404), // when no entry or category, could be private or inactive
    );
    
    
    static $http_codes = array(
        301 => 'Moved Permanently',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',  
        503 => 'Service Unavailable',
    );
    
    
    static $error_messages = array(
        'required' => 'Required argument(s): %s',
        'invalid'  => 'Invalid argument(s): %s'
    );
    
    
    static function error($error_code, $error_info = false) {
        
        if(is_array($error_code)) {
           $error['error'] =  $error_code;
           $error_code = $error_code['errorCode'];
        } else {
            $error['error'] = self::getError($error_code, $error_info);
        }
        
	    $format = self::getResponceFormat();
        $responce = KBApiResponce::factory($format);
        
        $error = KBApiUtil::camelize($error);
        
        echo $responce->parse($error, 'errors');
        
        self::sendHeader(self::$codes[$error_code][1]);
        exit;
    }
    
    
    static function getError($error_code, $error_info = false) {

        if(is_array($error_code)) {
            $code = $error_code[0];
            $msg = sprintf(self::$codes[$code][0], $error_code[1]);
        } else {
            $code = $error_code;
            $msg = self::$codes[$code][0];
        }

        $error = array(
            'errorCode' => $code,
            'errorMessage' => $msg
        );

        if($error_info) {
            $error['errorInfo'] = $error_info;
        }
        
        return $error;
    }
    
    
    static function error404() {
        return self::error(31);
    }
    
    
    static function errorInvalidArgs($args) {
        $args = (is_array($args)) ? implode(',', $args) : $args;
        $msg = self::parseMsg('invalid', $args);
        return self::error(25, $msg);
    }
    
    
    static function sendHeader($http_code) {
        if(isset(self::$http_codes[$http_code])) {
            $header = sprintf('HTTP/1.1 %d %s', $http_code, self::$http_codes[$http_code]);
            header($header);
        }
    }
    
    
	// will be exuted on die(db_error($sql))
	static function shutdownDbError($data) {
	    $info = sprintf('%s: %s', $data['num'], $data['msg']);
	    self::error(11, $info);
    }    
    
    
    static function getResponceFormat() {
	    $reg =& Registry::instance();
        $conf = &$reg->getEntry('conf');
        return $conf['api_format'];
    }
    
    
    static function parseMsg($msg_key, $info) {
        $msg = self::$error_messages[$msg_key];
        return sprintf($msg, $info);
    }
    
}
?>