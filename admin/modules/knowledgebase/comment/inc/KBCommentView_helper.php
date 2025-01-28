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

class KBCommentView_helper
{
        
    static function getBBCodeObj() {
        
        //Basic,Extended,Links,Images,Lists,Email
        require_once 'HTML/BBCodeParser2.php';
        
        $options = array('filters' => 'Basic,Extended,Links,Lists,Email');
        return new HTML_BBCodeParser2($options);
    }
    
}
?>