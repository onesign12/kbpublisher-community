<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

namespace AdminSetting;

use BaseModel;
use Validator;
use FileTextExctractor;
use AppMsg;
use MfaAuthenticator;


class SettingValidator
{
     
    function validate($values) {
        $required = array('app_width');
        
        if(!BaseModel::isCloud()) {
            $required[] = 'html_editor_upload_dir';
            $required[] = 'file_dir';
        }
        
        if(!empty($values['aws_s3_allow'])) {
            $required[] = 'aws_access_key';
            $required[] = 'aws_secret_key';
            $required[] = 'aws_s3_region';
        }
        
        $v = new Validator($values, true);
        
        $v->setRequiredMethod(true); // all required should be set
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
                
        $dirs = array('html_editor_upload_dir', 'file_dir');
        foreach($dirs as $dir) {
            if(isset($values[$dir])) {
                if(!is_dir($values[$dir]) || !is_writeable($values[$dir])) {
                    $v->setError('file_dir_not_exists_msg', $dir);
                }
            }
        }      
        
        if($v->getErrors()) {
            return $v->getErrors();
        }        
        
        // Amazon s3
        if(!empty($values['aws_s3_allow'])) {
            
            try {
                
                $credentials = new \Aws\Credentials\Credentials($values['aws_access_key'], $values['aws_secret_key']);

                $kbs3 = new \KBS3Client();
                $options = $kbs3->getS3ClientConnectOptions($credentials, $values['aws_s3_region']);
                
                $s3 = new \Aws\S3\S3Client($options);
                $buckets = $s3->listBuckets(); // just to catch connection error
                
            } catch (\Aws\S3\Exception\S3Exception $e) {
                
                $error = ($b = $e->getAwsErrorMessage()) ? $b : $e->getMessage();
                $v->setError($error, 'aws_access_key', 'aws_access_key', 'custom');
            }
            
            if($v->getErrors()) {
                return $v->getErrors();
            }  
        }
        

        if(isset($values['file_extract'])) {
    
            // xpdf
            if(isset($values['file_extract_pdf'])) {
                if(strtolower($values['file_extract_pdf']) != 'off') {
                    $ret = $this->validateXPDF($values['file_extract_pdf']);
                    if($ret !== true) {
                        $msg = AppMsg::getMsgs('error_msg.ini', 'file', 'extract_xpdf');
                        $body = AppMsg::replaceParse($msg['body'], $ret);
                        $v->setError($body, 'file_extract_pdf', 'file_extract_pdf', 'custom');
                    }
                }
            }
            
            // catdoc
            if(isset($values['file_extract_doc'])) {
                if(strtolower($values['file_extract_doc']) != 'off') {
                    $ret = $this->validateDoc($values['file_extract_doc'], 'doc');
                    if($ret !== true) {
                        $msg = AppMsg::getMsgs('error_msg.ini', 'file', 'extract_catdoc');
                        $body = AppMsg::replaceParse($msg['body'], $ret);
                        $v->setError($body, 'file_extract_doc', 'file_extract_doc', 'custom');
                    }    
                }
            }    

            // antiword
            if(isset($values['file_extract_doc2'])) {
                if(strtolower($values['file_extract_doc2']) != 'off') {
                    $ret = $this->validateDoc($values['file_extract_doc2'], 'doc2');
                    if($ret !== true) {
                        $msg = AppMsg::getMsgs('error_msg.ini', 'file', 'extract_antiword');
                        $body = AppMsg::replaceParse($msg['body'], $ret);
                        $v->setError($body, 'file_extract_doc2', 'file_extract_doc2', 'custom');
                    }    
                }
            }

        }        
        
        // mfa
        // **Note:** the `NTPTimeProvider` requires your PHP to have the ability to create sockets. If you do not have that ability and wish to use this function, you should pass an array with only an instance of `HttpTimeProvider`.
        
        // if(!empty($values['mfa_policy'])) {
        //     
        //     if($error = MfaAuthenticator::validateTimeSync()) {
        //         $msg = 'Your hosts time seems to be off: ' . $error;
        //         $v->setError($body, 'mfa_policy', 'mfa_policy', 'custom');
        //     }
        // }
    
        return $v->getErrors();
    }
    
    
    static function validateXPDF($tool_path) {
        
        $test_file = APP_ADMIN_DIR . 'tests/extract_test.pdf';
        
        $extractor = new FileTextExctractor('pdf');
        $extractor->setTool($tool_path);
        $extractor->setExtractDir(APP_CACHE_DIR);
        $ret = $extractor->getText($test_file, true); // true for testing
        
        if($ret === 0) {
            return true;
        } else {
            $msg = (isset($extractor->ex->codes[$ret])) ? $extractor->ex->codes[$ret] : 'Unknown error';
            return array('code' => $ret, 'code_message' => $msg);
        }
    }
    
    
    static function validateDoc($tool_path, $extension) {
        
        $test_file = APP_ADMIN_DIR . 'tests/extract_test.doc';
        
        $extractor = new FileTextExctractor($extension);
        $extractor->setTool($tool_path);
        $extractor->setExtractDir(APP_CACHE_DIR);
        $ret = $extractor->getText($test_file, true); // true for testing

        if($ret === 0) {
            return true;
        } else {
            $msg = (isset($extractor->ex->codes[$ret])) ? $extractor->ex->codes[$ret] : 'Unknown error';
            return array('code' => $ret, 'code_message' => $msg);
        }
    }
    
}
?>