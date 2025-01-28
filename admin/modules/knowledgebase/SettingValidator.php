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

namespace Knowledgebase;

class SettingValidator
{
     
    function validate($values) {
        
        $required = array();

        $v = new \Validator($values, true);

        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        // catdoc
        if($values['toc_tags']) {
            
            $tag_allowed = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
            $tag_entered = array_map('strtolower', array_map('trim', explode(',', $values['toc_tags'])));
            $tags = array_intersect($tag_entered, $tag_allowed);
            
            if(count($tag_entered) > count($tags)) {
                $v->setError('wrong_format_msg', 'toc_tags', 'toc_tags');
            }
        }
        
        return $v->getErrors();
    }

}
?>