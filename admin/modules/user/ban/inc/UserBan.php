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

class UserBan extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'ban_type'       => 0,
                            'ban_rule'       => 0,
                            'ban_reason'     => 0,
                            'ban_value'      => '',
                            'date_start'     => '',
                            'date_end'       => '',
                            'admin_reason'   => '',
                            'user_reason'    => '',
                            'active'         => 1
                            );
    
    
    var $hidden = array('id', 'date_start', 'date_end');
    var $reset_on_clone = array('id');
    
    
    function _callBack($property, $val) {
        
        if($property == 'date_start' && !$val) {
            $val = date('Y-m-d H:i:s');
        }
        
        return $val;
    }
    
    
    function validate($values) {
        
        $required = array('ban_type', 'ban_rule', 'ban_reason', 'ban_value', 'user_reason');
        
        /*if($values['date_end'] != 'perm') {
            $required[] = 'date_end_num';
        }*/
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        /*if ($values['ban_rule'] == 1) {
            if (!is_numeric($values['ban_value'])) {
                $v->setError('user_id_msg', 'ban_value');
                $this->errors =& $v->getErrors();
                return true; 
            }
        }
        
        if ($values['ban_rule'] == 3) {
            $v->regex('ip_msg', 'ip', 'ban_value');   
        }*/
        
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }

}
?>