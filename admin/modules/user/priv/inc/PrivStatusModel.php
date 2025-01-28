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


class PrivStatusModel extends AppModel
{

    static function &factory($class, $path) {
        
        $class = 'PrivStatusModel_' . $class;
        $file = $path . 'inc/' . $class . '.php';
        
        if(!file_exists($file)) {
            $class = 'PrivStatusModel';
            $file = $path . 'inc/' . $class . '.php';
        }
        
        require_once $file;
        $class = new $class;
        
        return $class;
    }
    
    
    function getStatusSelectRange() {
        
    }
}
?>