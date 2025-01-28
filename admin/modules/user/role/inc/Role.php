<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class Role extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'parent_id'     => 0,
                            'title'         => '',
                            'description'   => '',
                            // 'sort_order'    => 0,
                            'sort_order'    => 'sort_default',
                            'active'        => 1
                            );
    
    
    var $hidden = array('id');
    
    function validate($values) {
        
        $required[] = 'title';
        
        // when user select wrong category
        if(!isset($values['parent_id'])) {
            $required[] = 'parent_id';
        
        // not to generate error if top category selected 
        } elseif($values['parent_id'] !== '0') {
            $required[] = 'parent_id';
        }
        
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