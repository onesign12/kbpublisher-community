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

class ListGroup extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'list_key'      => '',
                            'title'         => '',
                            'description'   => '',
                            'sort_order'    => 255,
                            'predifined'    => 0,
                            'active'        => 1
                            );
    
    
    var $hidden = array('id', 'list_key', 'predifined', 'active');
    
    
    
    function validate($values) {
        
        $required = array('title');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        
        $this->errors = &$v->getErrors();
        return $this->errors;
    }
    
}
?>