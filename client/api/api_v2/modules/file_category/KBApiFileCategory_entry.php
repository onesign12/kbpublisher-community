<?php
class KBApiFileCategory_entry extends KBApiFileCategory
{
    
	
	function &execute($controller, $manager) {
	    
        $row = KBApiEntryModel::getCategory($manager, $this->entry_id);
        
        // does not matter why no article, deleted, or inactive or private
        // always send 404
        if(!$row) { 
            KBApiError::error404();
        }
        
        $row = array('entry' => $row);
		
		return $this->parse($row, $manager);
	}	
	
}
?>