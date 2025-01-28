<?php
class KBApiArticleCategory extends KBApiCommon
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

			$row['link'] = $this->cc->getLink('index', $row['id']);
            $row['pdf_link'] = $this->cc->getLink('pdf-cat', $row['id']);
			
		    $data[$row['id']] = $this->getReturnFields($row);
		}
		
		return $data;
	}

}
?>