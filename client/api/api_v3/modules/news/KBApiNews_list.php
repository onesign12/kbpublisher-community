<?php
class KBApiNews_list extends KBApiNews
{
	
	var $allowed_sort_order = array(); // means not allowed 
	
	var $remove_fields_more = array(
	    'body'
	    );	
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
		return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        
    }
		
	
	function getData($manager, $values, $count = false) {

        $range = $this->getDateRange($values);
        
		// root attr
		$offset = 0;
        if($count) {
            $bp = $this->pageByPage($this->limit, $manager->getNewsCount($range['min'], $range['max']));
            $offset = $bp->offset;
    		
    		$ra = $this->getResultAttributesFromBP($bp);
    		$this->setRootAttributes($ra);
        }

        return $manager->getNewsList($this->limit, $offset, $range['min'], $range['max']);
	}
    
    
    function getDateRange($values) {
        
        $date_from = false;
        $date_to = false;

        // year
        if(!empty($values['year'])) {
            $date_from = (int) $values['year'];
        } 
        
        if(!empty($values['month'])) {
            $month = (int) $values['month'];
            $day_end = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $date_from = sprintf('%s-%02s-%02s', $year, $month, 1);
            $date_to = sprintf('%s-%02s-%02s', $year, $month, $day);
        }
        
        if(!empty($values['day'])) {
            $day = (int) $values['day'];
            $date_from = sprintf('%s-%02s-%02s', $year, $month, $day);
            $date_to = sprintf('%s-%02s-%02s', $year, $month, $day);
        }
        
        return array('min' => $date_from, 'max' => $date_to);
    }
}
?>