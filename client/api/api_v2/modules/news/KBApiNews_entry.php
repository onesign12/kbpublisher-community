<?php
class KBApiNews_entry extends KBApiNews
{
	
	
	function &execute($controller, $manager) {

        $row = $manager->getNewsById($this->entry_id);

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
        
        // parse images
        if(empty($this->rq->img_rpath)) {
            $row['body'] = $this->parseImages($row['body'], $controller->baseUrl);
        }
        		
        $data['entry'] =& $row;
		return $this->parse($data, $manager);
	}
	
}
?>