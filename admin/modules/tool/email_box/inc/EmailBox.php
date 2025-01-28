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

class EmailBox extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'data_key'      => 'iemail',
                            //'date_posted'   => NULL,
                            'data_string'   => ''
                            );
    
    
    var $hidden = array('id', 'data_key');
    
    
    function validate($values) {
        
        
        
        $required = array('host', 'port', 'mailbox', 'user', 'password');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }        
        
		// only if no ajax 
		if(!AppController::isAjaxCall()) {
			
	        $setting = $values;
	        $setting['imap_user'] = $values['user'];
	        $setting['imap_pass'] = $values['password'];
        
	        $imap = new ImapParser($setting);
	        $ret = $imap->open();
	        if ($ret === false) {
			
				$err_msg = AppMsg::getMsgs('error_msg.ini');
				$err_msg = sprintf('%s - %s', $err_msg['imap_open_msg'], $imap->getLastError());
				$v->setError($err_msg, 'host', false, 'custom');
			
	            $this->errors =& $v->getErrors();
	            return true;
	        }
		}
    }
}
?>