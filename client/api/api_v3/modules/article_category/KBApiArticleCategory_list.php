<?php
class KBApiArticleCategory_list extends KBApiArticleCategory
{
	
	var $allowed_sort_order = array(
        'title-asc'     => 'e.title ASC',
        'title-desc'    => 'e.title DESC',
        'order-asc'     => 'e.sort_order ASC',
        'order-desc'    => 'e.sort_order DESC'
        );
	
	var $default_sort_order = 'order-asc';
	
	var $remove_fields_more = array();
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
        return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        
        // sort order
        // if(!empty($this->rq->sort)) {            
        //     $sort = $this->getSortOrderValue($this->rq->sort);
        //     if(!$sort) {
        //         KBApiError::error(25);
        //     }
        // }
    }
		
	
	function getData($manager, $values, $count = false) {

        // sort order
        $sort = (!empty($values['sort'])) ? $values['sort'] : $this->default_sort_order;
        $sort_order = $this->getSortOrderValue($sort);
        if($sort_order) {
            // change setting as it is used in getCategoryList
            $manager->setttig['category_sort_order'] = $sort_order;
        }
		
        if(isset($values['cid'])) {
            $parent_id = (int) $values['cid'];
            $rows = $manager->getCategoryList($parent_id);
        } else {
            $rows = $manager->getCategories();
        }
		
		// root attr
        if($count) {
    		$ra = $this->getResultAttributes(1, 1, count($rows), count($rows));
    		$this->setRootAttributes($ra);
        }

		return $rows;
	}
	
	
    function getSortOrderValue($value) {
        $ret = false;
        $value = strtolower($value);
        if(isset($this->allowed_sort_order[$value])) {
            $ret = $this->allowed_sort_order[$value];
        }
        return $ret;    
    }
    
}
?>