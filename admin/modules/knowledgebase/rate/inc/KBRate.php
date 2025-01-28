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

class KBRate extends AppObj
{


    var $properties = array('id'             => NULL,
                            'user_id'        => NULL,
                            'entry_id'       => '',
                            //'email'        => '',
                            'comment'        => '',
                            'date_posted'    => '',
                            'active'         => 1
                            );
    
    var $hidden = array('id','user_id','entry_id','date_posted');
    var $title;
    var $username;
    var $r_email;
    
    
    function _callBack($property, $val) {
        if($property == 'date_posted' && !$val) {
            $val = date('Y-m-d H:i:s');
        }
        
        if($property == 'user_id' && !$val) {
            $val = NULL;
        }
        
        return $val;
    }
    
    
    function validate($values) {
        
        $required = array('comment');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        /*$v->regex('email_msg', 'email', 'email', false);
                if($v->getErrors()) {
                    $this->errors =& $v->getErrors();
                    return true;
                }*/
    }
    
}
?>