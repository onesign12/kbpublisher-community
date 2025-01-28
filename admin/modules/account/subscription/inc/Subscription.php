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

class Subscription extends AppObj
{
    
    var $properties = array('entry_id'          => 0,
                            'entry_type'        => 0,
                            'user_id'           => 0,
                            'date_subscribed'   => '',
                            'date_lastsent'     => ''
                            );
    
    
    var $hidden = array();
    
    
    function validate($values, $required = array()) {
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('no_selected_item_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
}
?>