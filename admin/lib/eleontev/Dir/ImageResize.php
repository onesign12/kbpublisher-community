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


class ImageResize
{    
    
    function stripSizeData(&$str) {
        $dom = new DOMDocument();
        @$dom->loadHTML($str);
        $dom->preserveWhiteSpace = false;

        $images = $dom->getElementsByTagName('img');
        
        foreach ($images as $image) {
            
            $attrs = array('width', 'height', 'style');
            foreach($attrs as $attr) {
                if($image->hasAttribute($attr)) {
                    $image->removeAttribute($attr);
                }
            }
        }
        
        $str = $dom->saveHTML();
    }
        
}

?>