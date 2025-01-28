<?php

class KBApiNews_method extends KBApiNews_list
{
	
    var $allowed_methods = array(
        'recent' => 'getRecentlyPosted',
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
	
	
	function getRecentlyPosted($manager, $values) {
	    $values['sort'] = 'date-posted-desc'; 
		$rows = $this->getData($manager, $values);
		return $this->parse($rows, $manager);
	}
	
}
?>