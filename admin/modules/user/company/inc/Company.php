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

class Company extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'title'         => '',
                            'phone'         => '',
                            'phone2'        => '',
                            'fax'           => '',
                            'email'         => '',
                            'address'       => '',
                            'address2'      => '',
                            'city'          => '',
                            'state'         => '',
                            'zip'           => '',
                            'country'       => 0,
                            'url'           => '',
                            'description'   => '',
                            'custom'        => '',
                            'active'        => 1
                            );
    
    
    var $hidden = array('id', 'active');
    
    
    function validate($values) {
        
        $required[] = 'title';
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
}
?>