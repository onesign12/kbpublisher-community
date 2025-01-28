<?php

class KBApiFile_method extends KBApiFile_list
{
	
    var $allowed_methods = array(
        'recent' => 'getRecentlyUpdated', 
        'popular' => 'getMostViewed', 
        'file' => 'getFileData'
    );


	function &execute($controller, $manager) {
        $func = $this->allowed_methods[$controller->method];
        $rows = call_user_func_array(array($this, $func), array($manager, $this->rq->vars));
        
        if($controller->method != 'file') {
    		$ra = $this->getResultAttributes(1, 1, count($rows), count($rows));
    		$this->setRootAttributes($ra);
        }
        
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
	function getFileData($manager, $values) {
	    
        
        
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

        $row2 = array();
        $row2['id'] = $row['id'];

        // file base64 encoded
        $file_dir = $manager->getSetting('file_dir');
        if($file = FileEntryUtil::getFileDir($row, $file_dir)) {
            $content = file_get_contents($file);
            $row2['file_data'] = base64_encode($content);
        } else {
            KBApiError::error404();
        }

        $data['entry'] = $row2;
		$ret = $this->parse($data, $manager);        
        
        // remove items defined in for all record set
        $remove = array('link', 'inlineLink', 'tags');
        foreach($remove as $v) {
            unset($ret[$row['id']][$v]);
        }
        
        return $ret;
        
	}
}
?>