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


class KBCategoryView_category extends AppView 
{
    
    var $template = 'form_category.html';
    
    
    function execute($obj, $manager) {
    
        $options = array(
            'all' => false,
            'limit' => 1,
            'sortable' => false,
            'non_active_state' => 'disabled',
            'creation' => false,
            'secondary_block' => false,
            'status_icon' => true,
            'cancel_button' => true,
            'mode' => 'category',
            'popup_title' => $this->msg['assign_parent_category_msg'],
            'main_title' => $this->msg['parent_category_msg'],
            'non_active_state' => 'visible'
        );
        
        //private
        $manager->setSqlParams($manager->getPrivateParams());
        $categories = $manager->getSelectRecords();
        
        $view = new KBEntryView_category;
        return $view->parseCategoryPopup($manager, $categories, $options);
    }
    
}
?>