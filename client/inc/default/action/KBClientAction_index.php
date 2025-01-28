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

class KBClientAction_index extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        if($this->category_id) {
        
            // faq
            if($manager->getCategoryType($this->category_id) == 'faq') {
                
                $view = &$controller->getView('index_faq');
                return $view;                
                
            // faq2
            } elseif($manager->getCategoryType($this->category_id) == 'faq2') {
                
                
                $view = &$controller->getView('index_faq2');
                return $view;                
            
            // book
            } elseif($manager->getCategoryType($this->category_id) == 'book') {
                
                $view = &$controller->getView('index_book');
                return $view;
            }
        }
        
        
        $view = &$controller->getView('index');        
        return $view;
    }
}
?>