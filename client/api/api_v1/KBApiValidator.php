<?php
class KBApiValidator 
{
        
    
    static function validateArguments($values, $required = array()) {
        
        $missed = array();
        foreach($required as $v) {
            if(!isset($values[$v])) {
                $missed[] = $v; 
            }
        }
        
        if($missed) {
            $missed = (is_array($missed)) ? implode(',', $missed) : $missed;
            $msg = KBApiError::parseMsg('required', $missed);
            return KBApiError::error(25, $msg);
        }
    }


    static function validateGetArguments($required) {
        self::validateArguments($_GET, $required);
    }
    
    
    static function validateRequest($request, $allowed_requests) {
        if(!in_array($request, $allowed_requests)) {
            $error = KBApiError::getError(array(22, $request));
            KBApiError::error($error);
        }
    }
    
    
    static function validateCall($call, $allowed_calls) {
        if(!isset($allowed_calls[$call])) {
            KBApiError::error(23);
        }
    }
    
    
    static function validateMethod($method, $allowed_methods) {
        if(!isset($allowed_methods[$method])) {
            KBApiError::error(24);
        }
    }
    
    
    static function validateSignature($private_api_key, $api_cc) {
        
        $auth = false;
        
        $host = $api_cc->host;
        $request_method = strtoupper($api_cc->request_method);
        parse_str($_SERVER['QUERY_STRING'], $params);
        
        $time = false;
        if(!empty($params['timestamp'])) {
            $gap = 5*60; // 5 minutes
            // $gap = 1; // test 1 sec
            $request_ts = (int) $params['timestamp'];
            $max_ts = $request_ts + $gap;
            $min_ts = $request_ts - (5*60); // just in case, request valid with 5 mins in the past
            $current_ts = time();
            
            if($current_ts > $min_ts && $current_ts < $max_ts) {
                $time = true;
            }
        }
                
        if($time && !empty($params['signature'])) {
            
            $url_signature = rawurlencode($params['signature']);
            
            $string_to_sign = "$request_method\n";
            $string_to_sign .= "$host\n";
            $string_to_sign .= "/\n";
            
            unset($params['signature']);
            ksort($params);
            $string_to_sign .= http_build_query($params, false, '&');
            $signature = rawurlencode(base64_encode(hash_hmac("sha1", $string_to_sign, $private_api_key, true))); 
            
            if($url_signature == $signature) {
                $auth = true;
            }
        }

        // echo '<pre>request_timestamp: ', print_r($request_ts, 1), '</pre>';
        // echo '<pre>max_timestamp: ', print_r($max_ts, 1), '</pre>';
        // echo '<pre>timestamp: ', print_r($ts, 1), '</pre>';
        // echo '<pre>string_to_sign: ', print_r($string_to_sign, 1), '</pre>';
        // echo '<pre>', print_r($url_signature, 1), '</pre>';
        // echo '<pre>', print_r($signature, 1), '</pre>';
        // echo '<pre>', print_r($_SERVER, 1), '</pre>';
 
        if(!$auth) {
            KBApiError::error(4);
        }
    }
    
    
    static function isDateValid($date) {
        if(is_numeric($date)) {
            $date = date('Y-m-d');
        }    
        
        $date = explode('-', $date);
        $date = array_filter($date, 'is_numeric'); // remove non numeric
        
        @$ret = checkdate((int) $date[1], (int) $date[2], $date[0]);
        return $ret;
    }
    
    
    static function isNumeric($val) {
        $ret = false;
        if(is_numeric($val)) {
            $ret = true;
        }
        
        return $ret;
    }
    
    // string like that: 1,23,45 ...
    static function isIds($val) {
        $ret = false;
        if(is_numeric($val)) {
            $ret = true;
        } else {
            $ret = (preg_replace("#[\s\d,]#", '', $val)) ? false : true;
        }
        
        return $ret;
    }
       
}
?>