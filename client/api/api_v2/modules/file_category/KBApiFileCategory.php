<?php
class KBApiFileCategory extends KBApiCommon
{		

    var $map_fields = array(
        'name' => 'title'
        );

    var $remove_fields = array();

    var $html_fields = array();
	
	
	// parse data with articles
	function &parse($rows, $manager) {
   		
		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {
		    
			$row = $rows[$k];

			$row['link'] = $this->cc->getLink('files', $row['id']);
			
		    $data[$row['id']] = $this->getReturnFields($row);
		}
		
		return $data;
	}

}
?>