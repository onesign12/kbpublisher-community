<?php
class KBApiFile_list extends KBApiFile
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
        'hits-asc'      => 'e.downloads ASC',
        'hits-desc'     => 'e.downloads DESC'
        );
	
	
	var $remove_fields_more = array();
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
		return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiArticle_list();
        $a->rq = $this->rq;
        $a->allowed_sort_order = $this->allowed_sort_order;
        $a->validate($controller, $manager);
    }
		
	
	function getData($manager, $values, $count = false) {
        $a = new KBApiArticle_list();
        $a->allowed_sort_order = $this->allowed_sort_order;
        $data = $a->getData($manager, $values, 1);
	    $this->root_attributes = $a->getRootAttributes();
	    return $data;
	}
    
}
?>