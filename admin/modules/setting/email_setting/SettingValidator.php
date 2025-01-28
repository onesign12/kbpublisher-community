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

namespace EmailSetting;

use Validator;
use Validate;
use AppController;
use AppMailSender;

class SettingValidator
{
     
    function validate($values) {
        
        $required = array('from_email', 'noreply_email', 'admin_email');
        
        $v = new Validator($values, true);
        
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        // from/support email
        $from_email = explode(',', $values['from_email']);
        foreach($from_email as $email) {
            $email = trim($email);
            if(!$ret = Validate::email($email)) {
                $v->setError('email_msg', 'from_email', 'email');
                break;
            }
        }
        
        // admin email
        if($values['admin_email']) {
            $from_email = explode(',', $values['admin_email']);
            foreach($from_email as $email) {
                $email = trim($email);
                if(!$ret = Validate::email($email)) {
                    $v->setError('email_msg', 'admin_email', 'email');
                    break;
                }
            }        
        }
        
        // noreplay email
        $email = trim($email);
        if(!$ret = Validate::email(trim($values['noreply_email']))) {
            $v->setError('email_msg', 'noreply_email', 'email');
        }
         
        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
		// execute only without ajax
        if(!AppController::isAjaxCall()) {
			$output = $this->testEmail($values);            
            
        	if(!empty($output['error'])) {
                $error = array('title' => '', 'body' => $output['error']);
                
                if(!empty($output['debug'])) {
                    $error = array(
                        'title' => $output['error'],
                        'body'  => '<br/>' . $output['debug']
                    );
                }
                
            	$v->setError($error, 'test_email', 'test_email', 'parsed');
        	}
        }
		
        return $v->getErrors();
    }
    
    
    // return false if ok string with error otherwise
    function testEmail($values) {
        
        $values['smtp_auth'] = (isset($values['smtp_auth'])) ? $values['smtp_auth'] : 0;
        
        
        $mail = new AppMailSender($values);
        return $mail->testMail();
    }
}
?>