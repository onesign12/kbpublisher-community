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

class PageDesignCustomBlock extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'data_key'      => 'design_block',
                            //'date_posted'   => NULL,
                            'data_string'   => ''
                            );
    
    
    var $hidden = array('id', 'data_key');
    
    
    function validate($values) {
        $required = array('body');
        
        $v = new Validator($values);
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