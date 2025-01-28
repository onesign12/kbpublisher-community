<?php
class KBApiArticle_method extends KBApiArticle_list
{
	
    var $allowed_methods = array(
        'recent' => 'getRecentlyUpdated',
        'popular' => 'getMostViewed', 
        'rated' => 'getMostRated', 
        'featured' => 'getFeatured'
    );


	function &execute($controller, $manager) {
        $func = $this->allowed_methods[$controller->method];
        $rows = call_user_func_array(array($this, $func), array($manager, $this->rq->vars));
        
		$ra = $this->getResultAttributes(1, 1, count($rows), count($rows));
		$this->setRootAttributes($ra);
        
        return $rows;
	}
	
	
	function validate($controller, $manager) {
        parent::validate($controller, $manager);
        KBApiValidator::validateMethod($controller->method, $this->allowed_methods);
	}
		
	
	function getMostViewed($manager, $values) {
	    $values['sort'] = 'hits-desc'; 
		$rows = $this->getData($manager, $values);
		return $this->parse($rows, $manager);
	}
	
	
	function getMostRated($manager, $values) {
	    $values['sort'] = 'rating-desc'; 
		$rows = $this->getData($manager, $values);
		return $this->parse($rows, $manager);
	}

    
    function getRecentlyUpdated($manager, $values) {
        $values['sort'] = 'date-updated-desc'; 
        $rows = $this->getData($manager, $values);
        return $this->parse($rows, $manager);
    }


    function getFeatured($manager, $values) {
        
        $from = sprintf(', %s ef', $manager->tbl->entry_featured);
        $manager->setSqlParamsFrom($from, null, true);
        
        $manager->setSqlParams('AND e.id = ef.entry_id');
        $manager->setSqlParams('AND ef.entry_type = 1');
        $manager->setSqlParamsOrder('ORDER BY ef.sort_order');
        
        $rows = $this->getData($manager, $values);
        return $this->parse($rows, $manager);
    }
}
?>