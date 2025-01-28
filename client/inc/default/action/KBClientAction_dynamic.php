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

class KBClientAction_dynamic extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        switch ($controller->view_id) {
        case 'featured':
        case 'popular':
        case 'recent':
            
            $view = &$controller->getView('dynamic');
            $view->dynamic_type = $controller->view_id;
            break;
            
        case 'files':
            if(in_array($controller->page_id, array('popular', 'recent'))) {
                
                $view = &$controller->getView('dynamic_file');
                $view->dynamic_type = $controller->page_id;
            }
            break;               
        }
        
        if(!$view->dynamic_type) {
            $controller->goStatusHeader('404');
        }
        
        return $view;
    }
}
?>