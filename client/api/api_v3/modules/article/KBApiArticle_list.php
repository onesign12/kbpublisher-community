<?php
class KBApiArticle_list extends KBApiArticle
{
	
	var $allowed_sort_order = array(
        'title-asc'     => 'e.title ASC',
        'title-desc'    => 'e.title DESC',
        'order-asc'     => 'e_to_cat.sort_order ASC',
        'order-desc'    => 'e_to_cat.sort_order DESC',
        'date-posted-asc'    => 'e.date_posted ASC',
        'date-posted-desc'   => 'e.date_posted DESC',
        'date-updated-asc'   => 'e.date_updated ASC',
        'date-updated-desc'  => 'e.date_updated DESC',
        'hits-asc'      => 'e.hits ASC',
        'hits-desc'     => 'e.hits DESC',
        'rating-asc'    => 'rating ASC',
        'rating-desc'   => 'rating DESC',
        );
	
	var $default_sort_order = 'updated-desc';
	
	var $remove_fields_more = array(
	    'body', 'externalLink'
	    );
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
        return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        
        // sort order
        if(!empty($this->rq->sort)) {            
            $sort = $this->getSortOrderValue($this->rq->sort);
            if(!$sort) {
                KBApiError::error(25);
            }
            
            // not allowed if no category
            if(($sort == 'order-asc' || $sort == 'order-desc') && !empty($this->rq->cid)) {
                KBApiError::error(25);
            }
        }
    }
		
	
	function getData($manager, $values, $count = false) {
		
		$manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
		$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        // category
        $sql_type = 'index';
        if(!empty($values['cid'])) {
            $cid = (int) $values['cid'];
            $manager->setSqlParams("AND cat.id = '{$cid}'");
            $sql_type = 'category';
        }

        // sort order
        $sort = (!empty($values['sort'])) ? $values['sort'] : $this->default_sort_order;
        $sort_order = $this->getSortOrderValue($sort);
        if($sort_order) {
            $manager->setSqlParamsOrder('ORDER BY ' . $sort_order);
        }
		
		// root attr
		$offset = 0;
        if($count) {
            $bp = $this->pageByPage($this->limit, $manager->getEntryCount());
            $offset = $bp->offset;
    		
    		$ra = $this->getResultAttributesFromBP($bp);
    		$this->setRootAttributes($ra);
        }

		return $manager->getEntryList($this->limit, $offset, $sql_type);
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