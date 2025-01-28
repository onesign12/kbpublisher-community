<?php
class KBApiUser extends KBApiCommon
{		

    var $map_fields = array();

    var $remove_fields = array(
        'id_',
        'imported_user_id',
        'grantor_id',
        'password',
        'phone_ext',
        'editable',
        'import_data',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'country'
        );

    var $html_fields = array();
	
	// parse data with articles
	function &parse($rows, $manager) {

		// entry_type
		$types = ListValueModel::getListRange('article_type', false);
        
		//coments
		$entry_ids = $manager->getValuesString($rows);
		$comments = ($entry_ids) ? $manager->getCommentsNumForEntry($entry_ids) : array();
        
		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {
		    
			$row = $rows[$k];

			$entry_id = $this->cc->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
			$row['link'] = $this->cc->getLink('entry', $row['category_id'], $entry_id);			
			$row['print_link'] = $this->cc->getLink('print', $row['category_id'], $row['id']);			
			$row['pdf_link'] = $this->cc->getLink('pdf', $row['category_id'], $row['id']);
			
			$row['prefix'] = $this->cv->getEntryPrefix($row['id'], $row['entry_type'], $types, $manager);
			$row['summary'] = DocumentParser::getSummary($row['body'], $manager->getSetting('preview_article_limit'));
            
            $row['type'] = (isset($types[$row['entry_type']])) ? $types[$row['entry_type']] : '';

            if(isset($row['rating'])) {
                $rating = $this->cv->_getRating($row['rating']);
                $row['rating'] = round($row['rating']);
            }
            
			
		    $data[$row['id']] = $this->getReturnFields($row);
		}

		return $data;
	}    

}
?>