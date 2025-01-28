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


class KBClientAction_news extends KBClientAction_common
{

    function &execute($controller, $manager) {        
        
        $action = $controller->msg_id;
        
        if($controller->entry_id) {
            
            if($manager->isUserViewed($this->entry_id) === false) {
                $manager->addView($this->entry_id, 3);
                $manager->setUserViewed($this->entry_id);
            }            
            
            UserActivityLog::add('news', 'view', $this->entry_id);
            
            $view = &$controller->getView('news_entry');
        
        } else {
            $view = &$controller->getView('news_list');
        }
        
        return $view;
    }
}
?>