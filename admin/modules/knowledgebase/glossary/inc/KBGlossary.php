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

class KBGlossary extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'phrase'         => '',
                            'definition'     => '',
                            //'date_updated' => '',
                            'display_once'   => 0,
                            'active'         => 1
                            );
    
    
    var $hidden = array('id');
    
    
    
    function validate($values, $manager) {
        
        $required = array('phrase', 'definition');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        $id = (isset($values['id'])) ? intval($values['id']) : false;
        $phrase = addslashes(stripslashes($values['phrase']));
        if($manager->isPhraseExisting($phrase, $id)) {
            $v->setError('phrase_exists_msg', 'phrase');
        }
        
        //$this->js     = &$v->getJscript();
        $this->errors = &$v->getErrors();
        return $this->errors;
    }
    
}
?>