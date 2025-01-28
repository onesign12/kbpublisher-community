<?php

class KBApiFile_method extends KBApiFile_list
{
	
    var $allowed_methods = array(
        'recent' => 'getRecentlyUpdated', 
        'popular' => 'getMostViewed', 
        // 'file' => 'getFile', 
    );


	function &execute($controller, $manager) {
        $func = $this->allowed_methods[$controller->method];
        $rows = call_user_func_array(array($this, $func), array($manager, $this->rq->vars));
        
		$ra = $this->getResultAttributes(1, 1, count($rows), count($rows));
		$this->setRootAttributes($ra);
        
        return $rows;
	}
	
	
	function validate($controller, $manager) {
        parent::validate($controller, $manager);
        KBApiValidator::validateMethod($controller->method, $this->allowed_methods);
	}
		
	
	function getMostViewed($manager, $values) {
	    $values['sort'] = 'hits-desc'; 
		$rows = $this->getData($manager, $values);
		return $this->parse($rows, $manager);
	}

    
    function getRecentlyUpdated($manager, $values) {
	    $values['sort'] = 'date-updated-desc'; 
		$rows = $this->getData($manager, $values);
		return $this->parse($rows, $manager);
	}
	
	
    // maybe need a function to send file ???
	function getFile($manager, $values) {
	    
	    $data = $manager->getEntryById($this->entry_id, $this->category_id);

        // does not matter why no article, deleted, or inactive or private
        // always send 404
        if(!$data) { 
            KBApiError::error404();
        }	    
	    
        $file_dir = $manager->getSetting('file_dir');
        
        if(!FileEntryUtil::getFileDir($data, $file_dir)) {
            exit(123);
        }
                
        FileEntryUtil::sendFileDownload($data, $file_dir);
        // $manager->addDownload($file_id);

        exit();
	}
}
?>