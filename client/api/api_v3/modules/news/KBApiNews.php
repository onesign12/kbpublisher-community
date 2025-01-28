<?php
class KBApiNews extends KBApiCommon
{		

    var $map_fields = array(
        'meta_keywords' => 'tags'
        );

    var $remove_fields = array(
        'id_',
        'body_index', 
        'place_top_date',
        'ts_posted',
        'ts_updated'
        );
    
    var $html_fields = array(
        'body', 'titleHighlight', 'bodyHighlight'
        );    

	
	// parse data with articles
	function &parse($rows, $manager) {     
		
		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {
		    
			$row = $rows[$k];
			
			$row['link'] = $this->cc->getLink('news', 0, $row['id']);			
			$row['print_link'] = $this->cc->getLink('print-news', 0, $row['id']);
			$row['summary'] = DocumentParser::getSummary($row['body'], $manager->getSetting('preview_article_limit'));
			
		    $data[$row['id']] = $this->getReturnFields($row);
		}

		return $data;
	}

}
?>