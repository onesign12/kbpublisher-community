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

        // author,updater
        $row['author'] = $this->parseEntryUser($manager->getUserInfo($row['author_id']));
        $row['updater'] = $this->parseEntryUser($manager->getUserInfo($row['updater_id']));

        // file base64 encoded
        // $file_dir = $manager->getSetting('file_dir');
        // if($file = FileEntryUtil::getFileDir($row, $file_dir)) {
        //     $content = file_get_contents($file);
        //     $row['file_data'] = base64_encode($content);
        // }

		$data['entry'] =& $row;
		return $this->parse($data, $manager);
	}
	
}
?>