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

class KBClientAction_search extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        if(isset($this->rq->s)) {
            $view = &$controller->getView('search_list');
            
        } else {
                        
            $view = &$controller->getView('search_form');            
        }
                
        return $view;
    }
}
?>