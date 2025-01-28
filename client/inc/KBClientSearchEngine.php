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


class KBClientSearchEngine
{
    
    var $manager;

    static function factory($engine_type) {
        
        $class = 'KBClientSearchEngine_' . $engine_type;
        $file = sprintf('%sclient/inc/%s.php', APP_CLIENT_DIR, $class);
        
        require_once $file;
        $se = new $class();
        
        return $se;
    }
    
}
?>