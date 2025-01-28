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


class LetterTemplate extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'group_id'       => 0,
                            'title'          => '',
                            'subject'        => '',
                            'body'           => NULL,
                            'description'    => '',
                            'from_email'     => '',
                            'from_name'      => '',
                            'to_email'       => '',
                            'to_name'        => '',
                            'to_cc_email'    => '',
                            'to_cc_name'     => '',
                            'to_bcc_email'   => '',
                            'to_bcc_name'    => '',
                            'to_special'     => '',
                            'letter_key'     => '',
                            'skip_field'     => '',
                            'extra_tags'     => '',
                            'skip_tags'      => '',
                            'is_html'        => 0,
                            'in_out'         => 1,
                            'predifined'     => 0,
                            'active'         => 1,
                            'sort_order'     => 100
                            );
    
    
    var $hidden = array('id', 'predifined', 'letter_key', 'is_html', 'group_id',
                        'skip_field', 'in_out', 'sort_order', 'extra_tags', 'skip_tags');
    
    
    function validate($values) {
        
        $required = array('subject', 'body');
        if(!$values['predifined']) {
            $required[] = 'title';
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