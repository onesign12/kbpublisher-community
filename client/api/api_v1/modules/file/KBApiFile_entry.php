<?php



class KBApiFile_entry extends KBApiFile
{
	
	
	function &execute($controller, $manager) {

        $row = $manager->getEntryById($this->entry_id, $this->category_id);

        // does not matter why no article, deleted, or inactive or private
        // always send 404
        if(!$row) { 
            KBApiError::error404();
        }
				
        // views
        if(empty($this->rq->skip_hit)) {
            $manager->addView($this->entry_id);
        }
                
        // custom
        $custom = $manager->getCustomDataByEntryId($this->entry_id);
        $row['custom']['item'] = $this->getCustomDataApi($custom);

        // file 
        $file_dir = $manager->getSetting('file_dir');
        if(!FileEntryUtil::getFileDir($row, $file_dir)) {
            exit(123);
        }

        $content = FileEntryUtil::getFileDir($row, $file_dir);
        $row['file_base64_encoded'] = base64_encode($content);


		$data['entry'] =& $row;
		return $this->parse($data, $manager);
	}
	
}
?>