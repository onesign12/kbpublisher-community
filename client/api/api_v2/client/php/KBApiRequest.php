<?php
class KBApiRequest
{
    
    var $public_key;
    var $private_key;
    
    // var $method = 'GET';
    var $host;
    var $version = 1;
    var $format = 'json';
    var $fields = array();
    
    var $ssl = 0;
    var $timeout = 4;
    
    
    static function factory($module) {
        
        $class = sprintf('KBApiQuery_%s', $module);
        $file = $class . '.php';
        
        require_once $file;
        return new $class;
    }
    
    
    function setPublicKey($key) {
        $this->public_key = $key;
    }
    
    
    function setPrivateKey($key) {
        $this->private_key = $key;
    }
    
    
    function generateRequest($call, $method = 'GET') {
        
        $params = $call;
        $params['accessKey'] = $this->public_key;
        $params['timestamp'] = time();
        $params['version'] = $this->version;
        $params['format'] = $this->format;
        $params['fields'] = $this->fields;

        // params to string
        ksort($params);
        $string_params = http_build_query($params, false, '&');

        // craete the signature
        $string_to_sign = "$method\n";
        $string_to_sign .= "$this->host\n";
        $string_to_sign .= "/\n";
        $string_to_sign .= $string_params;

        $signature = rawurlencode(base64_encode(hash_hmac("sha1", $string_to_sign, $this->private_key, true)));

        $string_params .= '&signature=' . $signature;
        $http = ($this->ssl) ? 'https://' : 'http://';
        $request = $http . $host . '?' . $string_params;
        
        return $request;
    }
    
    
    function sendRequest($request, $post = array()) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        // post data
        if($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $res = array();
        $res['body']     = curl_exec($ch);
        $res['code']     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $res['headers']  = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $res['errno']    = curl_errno($ch);
        $res['error']    = curl_error($ch);

        curl_close($ch);

        return $res;
    }
    
    
    function request($call, $post = array()) {
        $method = ($post) ? 'POST' : 'GET';
        $this->generateRequest($call, $method);
        return $this->sendRequest($request, $post);
    }
    
}



?>