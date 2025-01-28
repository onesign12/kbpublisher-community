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

class Feedback extends AppObj
{
    
    var $properties = array('id'                => NULL,
                            'user_id'           => '',
                            'admin_id'          => '',
                            'subject_id'        => '',
                            'name'              => '',
                            'email'             => '',
                            'title'             => '',
                            'question'          => '',
                            'attachment'        => '',
                            'answer'            => '',
                            'answer_attachment' => '',
                            'date_posted'       => '',
                            'date_answered'     => NULL,
                            'answered'          => 0,
                            'placed'            => 0
                            );
    
    var $hidden = array('id', 'admin_id', 'user_id', 'date_posted', 
                        'subject_id', 'name', 'email', 'title', 'question', 'attachment');
    
    var $custom = array();
    
    
    function setCustom($val) {
        $this->custom = &$val;
    }  

    function getCustom() {
        return $this->custom;
    }    
    
    
    function _callBack($property, $val) {
        if($property == 'date_answered' && !$val) {
            $val = date('Y-m-d H:i:s');
        
        } elseif($property == 'admin_id' && !$val) {
            $val = AuthPriv::getUserId();
        }
        
        return $val;
    }
    
    
    function validate($values) {
        
        $required = array('answer');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function validateFile($values, $manager) {
        
        $upload = new Uploader;
        $upload->setMaxSize(WebUtil::getIniSize('upload_max_filesize')/1024);
        
        $errors = $upload->validate($values);
        if(!empty($errors)) {
            $v = new Validator($values, true);
            $error_msg = Uploader::getErrorText($errors);
            foreach($error_msg as $msg) {
                $v->setError($msg, 'file', 'file', 'custom');
            }

            $this->errors = $v->getErrors();
            return true;
        }
    }
    
    
    function getValidateFile($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateFile');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }
    
}
?>