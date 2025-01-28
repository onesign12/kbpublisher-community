<?php

use Aws\S3\S3Client;  
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\CredentialProvider;

class KBS3Client
{

    var $s3;
    var $options = array();
    static $s3_url_tmpl = 'https://%s.s3.%s.amazonaws.com/%s'; 


    function __construct($options = array()) {
        if($options) {
            $this->setOptions($options);
        }
    }

    
    static function instance($options) {
        static $kbs3;
        static $region;
        
        $oreqion = (!empty($options['region'])) ? $options['region'] : false; // reqion on options
        
        if(empty($kbs3) || $region != $oreqion) {
            $region = $oreqion;
            $kbs3 = new KBS3Client();
            $kbs3->setOptions($options); // set reqion to connect
            $kbs3->connect();
        }
        
        $kbs3->setOptions($options); // reset bucket and keyname
    
        return $kbs3;
    }
    
    
    // static function instance2($options) {
    //     static $kbs3;
    //
    //     if(!$kbs3) {
    //         $kbs3 = new KBS3Client();
    //         $kbs3->setOptions($options);
    //         $kbs3->connect();
    //     }
    //
    //     return $kbs3;
    // }


    function connect($values = array()) {
        $credentials = $this->getCredentilalsObject($values);
        $options = $this->getS3ClientConnectOptions($credentials, $this->options['region']);
        $this->s3 = new Aws\S3\S3Client($options);

        // $this->s3 = new Aws\S3\S3Client([
        //     // 'version'     => 'latest',
        //     'version'     => '2006-03-01',
        //     'region'      => $this->options['region'],
        //     'credentials' => $credentials,
        //     // 'debug'   => true
        // ]);
    }


    function getS3ClientConnectOptions($credentials, $reqion) {
        return array(
            // 'version'     => 'latest',
            'version'     => '2006-03-01',
            'region'      => $reqion,
            'credentials' => $credentials,
            // 'debug'   => true
        );
    }


    function getCredentilalsObject($settings = array()) {
        if(empty($settings)) {
            $settings = SettingModel::getQuick(12);
        }
        
        $aws_secret_key = \EncryptedPassword::decode($settings['aws_secret_key']);
        return new Aws\Credentials\Credentials($settings['aws_access_key'], $aws_secret_key);
    }


    function doesObjectExist() {
        return $this->s3->doesObjectExist($this->options['bucket'], $this->options['keyname']);
    }

    
    function doesBucketExist() {
        return $this->s3->doesBucketExist($this->options['bucket']);
    }
    

    // Display the object in the browser.
    function getInline($data) {
        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->options['bucket'],
            'Key'    => $this->options['keyname'],
            // 'ContentTypeDisposition' => 'inline',
            // 'ContentType' => $data['filetype']
        ]);
        $result = $this->s3->execute($cmd);
        
        header("Content-Type: {$result['ContentType']}");
        echo $result['Body'];
        die();
    }


    // Download object 
    function getAttachment($data) {        
        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->options['bucket'],
            'Key'    => $this->options['keyname'],
            'ResponseContentDisposition' => 'attachment; filename=' . $data['filename']
        ]);
        
        $request = $this->s3->createPresignedRequest($cmd, '+20 minutes');
        $signedUrl = (string) $request->getUri();
        
        header('Location: ' . $signedUrl);
        die();
    }
    
    
    function getListBucketOptions($options) {
        
        $params = array(
            'Bucket' => $options['bucket'],
            'Prefix' => (!empty($options['keyname'])) ? rtrim($options['keyname'], '/') . '/' : ''
        );
        
        if(!empty($options['maxkeys'])) {
            $params['MaxKeys'] = $options['maxkeys'];
        }
                
        if(!empty($options['delimiter'])) {
            $params['Delimiter'] = $options['delimiter'];
        }
        
        return $params;
    }
    
    
    function listObjects($options) {
        $options = $this->getListBucketOptions($options);
        return $this->s3->ListObjectsV2($options);
    }
    
    
    function getIterator($options) {
        $options = $this->getListBucketOptions($options);
        return $this->s3->getIterator('ListObjectsV2', $options);
    }
    
    
    function setOptions($values) {
        if(empty($values['region'])) {
            $values['region'] = SettingModel::getQuick(12, 'aws_s3_region');
        }
        $this->options['region'] = $values['region'];
        $this->options['bucket'] = $values['bucket'];
        $this->options['keyname'] = $values['keyname'];
    }
    
    
    function setRegion($value = false) {
        if($value === false) {
            $value = SettingModel::getQuick(12, 'aws_s3_region');
        }
        
        $this->options['region'] = $value;
    }
    
    
    static function getFileUrl($options) {
        return sprintf(self::$s3_url_tmpl, $options['region'], $options['bucket'], $options['keyname']); 
    }


    // files could be added but then disabled aws_s3_allow
    static function isAllowedToRead() {
        $ret = false;
        $s = SettingModel::getQuick(12);
        // if(!empty($s['aws_access_key']) && !empty($s['aws_secret_key']) && !empty($s['aws_s3_region'])) {
        if(!empty($s['aws_access_key']) && !empty($s['aws_secret_key'])) {
            $ret = true;
        }
    
        return $ret;
    }

}

?>