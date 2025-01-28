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

class KBEntryImport extends AppObj
{

	var $required = array('title', 'body');
	
    
    function validate($values, $manager) {
        
        $required = array('file');
        $values['file'] = (!empty($_FILES['file_1']['name'])) ? $_FILES['file_1']['name'] : '';
        
        $v = new Validator($values);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        } 
        
        // required table fields
        $required = $this->required;
        
        foreach($required as $v1) {
            if(!in_array($v1, $values['generated'])) {
                $msg = AppMsg::getMsg('random_msg.ini');
                $v->setError($msg['csv_required_error_msg'], 'generated', false, 'custom');
                $this->errors =& $v->getErrors();
                return true;
            }
        }        
    }
    
    
    function validateFile($values, $manager) {
				
        $upload = new Uploader; 
        $upload->setAllowedExtension('txt', 'csv');
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