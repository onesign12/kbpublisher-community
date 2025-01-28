<?php
class KBApiFileCategory_list extends KBApiFileCategory
{
	
	var $remove_fields_more = array();
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
        return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiArticleCategory_list();
        $a->rq = $this->rq;
        $a->validate($controller, $manager);
    }
		
	
	function getData($manager, $values, $count = false) {
        $a = new KBApiArticleCategory_list();
        $data = $a->getData($manager, $values, $count);
        $this->root_attributes = $a->getRootAttributes();
	    return $data;
	}
    
}
?>