<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class FileToHtmlWebService
{
    var $api_url = '';
    var $ssl = false;
    
    
    function getExtensionsList() {
        $response = $this->request('getConvertibleExtensions');
        
        $xml = new SimpleXMLElement($response);
        
        $list = array();
        foreach ($xml->entry as $extension) {
            $list[] = (string) $extension;
        }
          
        return $list;
    }
    
    
    function isFileConvertible($file_info) {
        $response = $this->request('validate', $file_info);
        
        $xml = new SimpleXMLElement($response);
        
        if (!empty($xml->error)) { // we got an error
            $error_msg = (empty($xml->error->errorMessage)) ? 'Bad response' : (string) $xml->error->errorMessage;
            return array('error' => $error_msg);
        }
        
        return true;
    }
    
    
    function sendFile($path, $name) {
        $file_str = sprintf('@%s;filename=%s', $path, $name);
        //$post_data = array('file' => $file_str);
        
        $post_data['file'] = new CurlFile($path, '', $name);
        
        $response = $this->request('convert', $post_data);
        
        return $response;
    }
    
    
    function request($api_method, $post_data = array()) {
        
        // $keys = KBValidateLicense::loadLicenseKeys();
        // $public_key = $keys['user_id'];
        // $private_key = KBValidateLicense::getLicenseKey();
        // $private_key = substr($private_key, 0, 10);
        
        $public_key = 0;
        $private_key = 'asdasdasdasd';
        $private_key = substr($private_key, 0, 10);
        
        set_time_limit(0);
        
        $http_method = (empty($post_data)) ? 'GET' : 'POST';
        $version = 1;
        $format = 'xml';
        
        $call = array(
            'call' => 'file_to_html',
            'method' => $api_method
            );
        
        $params = $call;
        $params['accessKey'] = $public_key;
        $params['accessKey2'] = $private_key;
        $params['timestamp'] = time();
        $params['version'] = $version;
        $params['format'] = $format;
        
        ksort($params);
        $string_params = http_build_query($params, false, '&');
        
        $string_to_sign = "$http_method\n";
        $string_to_sign .= $this->api_url . "\n";
        $string_to_sign .= "/\n";
        $string_to_sign .= $string_params;
        
        $signature = rawurlencode(base64_encode(hash_hmac('sha1', $string_to_sign, $private_key, true)));
        
        $string_params .= '&signature=' . $signature;
        $http = ($this->ssl) ? 'https://' : 'http://';
        $url = $http . $this->api_url . '?' . $string_params;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        
        if($http_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if($errno) { // do we need this?
            echo sprintf('Curl Error: %s - %s', $errno, $error), "\n";
            die();
        }
        
        return $response;
    }
    
    
    function parseResponse($response) {
        
        libxml_use_internal_errors(true);
        
        try {
            $xml = new SimpleXMLElement($response);
            
        } catch (Exception $e) {
            return array('error' => 'Bad response');
        }
        
        if (empty($xml->article)) { // we got an error
            $error_msg = (empty($xml->error)) ? 'Bad response' : (string) $xml->error;
            return array('error' => $error_msg);
        }
        
        return array('content' => (string) $xml->article[0]);
        
        $content = '';
        foreach($xml->article->children() as $element) {
            $content .= $element->asXML();
        }
        
        return array('content' => $content);
    }
}

?>