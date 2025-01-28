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


class Priv extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'name'           => '',
                            'description'    => '',
                            'editable'       => 1,
                            'sort_order'     => 2,
                            'active'         => 1
                            );
    
    
    var $hidden = array('id', 'editable', 'sort_order');
    var $reset_on_clone = array('id', 'name');
    var $priv = array();
    
    
    function setPriv($priv) {
        $this->priv = $priv;
    }
    
    
    function getPriv() {
        return $this->priv;
    }
    
    
    function validate($values) {
        
        $required[] = 'name';
        if(isset($values['sort_order'])) {
            $required[] = 'sort_order';            
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