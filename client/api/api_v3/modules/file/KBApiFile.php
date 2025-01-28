<?php

// 


class KBApiFile extends KBApiCommon
{		

    var $map_fields = array(
        'meta_keywords' => 'tags'
        );

    var $remove_fields = array(
        'id_',
        'sub_directory', 
        'filename_index', 
        'description_full', 
        'comment',
        'body',
        'sort_order',
        'ts_posted',
        'ts_updated'
    );

	
	function &parse($rows, $manager) {

		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {

			$row = $rows[$k];

            $row['link'] = $this->cc->getLink('file', false, $row['id']); // download link
            
            $more = array('f' => 1);
            $row['inline_link'] = $this->cc->getLink('file', false, $row['id'], false, $more);
            

		    $data[$row['id']] = $this->getReturnFields($row);
		}

		return $data;
	}

}
?>