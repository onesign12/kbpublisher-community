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


class ArticleTemplate extends AppObj
{
    
    var $properties = array('id'            => NULL,
                            'entry_type'    => 0,    
                            'tmpl_key'      => '',
                            'title'         => '',
                            'description'   => '',
                            'body'          => '',
                            'is_widget'     => 0,
                            'sort_order'    => 1,
                            'active'        => 1
                            );
    
    
    var $hidden = array('id', 'entry_type');
    var $reset_on_clone = array('id', 'title');
    
    
    function validate($values, $manager) {
        
        $required = array('title', 'body');        
        
        $v = new Validator($values, false);
        $v->csrf();
        
        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        // template id
        $tmpl_id = (isset($values['id'])) ? intval($values['id']) : false;
        
        // we have such tmpl_key
        if(!empty($values['tmpl_key'])) {
            $tmpl_key = addslashes(stripslashes($values['tmpl_key']));
            if($manager->isTmplKeyExists($tmpl_key, $tmpl_id)) {
                $v->setError('key_exists_msg', 'tmpl_key');
            }
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
}
?>