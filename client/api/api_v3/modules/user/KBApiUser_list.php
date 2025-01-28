<?php
class KBApiUser_list extends KBApiUser
{
	
	var $allowed_sort_order = array(
        'title-asc'     => 'e.title ASC',
        'title-desc'    => 'e.title DESC',
        'date-registered-asc'    => 'e.date_registered ASC',
        'date-registered-desc'   => 'e.date_registered DESC',
        'date-updated-asc'   => 'e.date_updated ASC',
        'date-updated-desc'  => 'e.date_updated DESC',        
        'date-lastauth-asc'   => 'e.lastauth ASC',
        'date-lastauth-desc'  => 'e.lastauth DESC'
        );
	
	var $default_sort_order = 'date-registered-desc';
	
	var $remove_fields_more = array();
	
	
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