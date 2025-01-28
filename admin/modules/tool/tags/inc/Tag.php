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


class Tag extends AppObj
{
    
    var $properties = array('id'          => NULL,
                            'date_posted' => '',
                            'title'       => '',
                            'description' => '',
                            'date_posted' => '',
                            'active'      => 1
                            );
    
    
    var $hidden = array('id', 'date_posted');
    
    
    function _callBack($property, $val) {
        
        if($property == 'title') {
            $val = CommonTagModel::parseTagOnAdding($val);
            
        } elseif($property == 'date_posted' && !$val) {
            $val = date('Y-m-d H:i:s');
        }
        
        return $val;
    }
    
    
    function validate($values, $manager) {
        
        $required = array('title');        
        
        $v = new Validator($values, false);
        $v->csrf();
        
        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        
        $id = (isset($values['id'])) ? intval($values['id']) : false;
        
        // we have such tag
        $title = addslashes(stripslashes($values['title']));
        if($manager->isTagExists($title, $id)) {
            $v->setError('tag_exists_msg', 'tmpl_key');
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
}
?>