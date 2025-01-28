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


class KBClientAjax_category_filter extends KBClientAjax
{
    
    function ajaxGetChildCategories($id, $callback) {
        
        $categories =& $this->manager->categories;
        //if(!$categories) {
        //    $manager = &KBClientLoader::getManager($this->manager->setting, $this->controller, 'index');
        //    $categories = &$manager->getCategories();            
        //}

        $objResponse = new xajaxResponse();
                                      
        $range_ = $this->manager->getCategorySelectRange($categories, (int) $id);
        
        $range = array();
        if(isset($categories[$id]['name'])) {
            $range[$id] = $categories[$id]['name'];
        }

        if (!empty($range_)) {
            foreach (array_keys($range_) as $cat_id) {
                $range[$cat_id] = '-- '. $range_[$cat_id];
            }
        }
        
        $objResponse->call($callback, $range, count($range_));

        return $objResponse;    
    }
    
    
    function ajaxSetCategoryView($view) {

        $objResponse = new xajaxResponse();
        
        // $_SESSION['kb_category_view_'] = $view;
        $_COOKIE['kb_category_view_'] = $view;
        setcookie('kb_category_view_', $view, time() + (86400*365)); // 1 year
        
        $objResponse->script('location.reload(false);'); 

        return $objResponse;    
    }
}
?>