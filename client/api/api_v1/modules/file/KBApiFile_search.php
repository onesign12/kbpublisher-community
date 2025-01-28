<?php

class KBApiFile_search extends KBApiFile_list
{

    var $html_fields = array(
        'titleHighlight', 'filenameHighlight', 'descriptionHighlight'
        );


	function &execute($controller, $manager) {
	    
        // if(empty($this->rq->vars['in'])) {
            $this->rq->vars['in'] = 'file';
        // }
        
	    $params = KBApiSearch::getSearchParams($this->rq->vars);
	    
        $rows = $this->getData($manager, $params);
		return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiSearch();
        $a->rq->vars =& $this->rq->vars;
        $a->validate($controller, $manager, 'f');
    }
		
	
	function getData($manager, $values, $count = false) {
	    
        $view = new KBClientView_search_list();
        $view->engine_name = $view->getSearchEngineName($manager, $values['q']);
        
        $sengine = $view->getSearchEngine($manager);
        $smanager = $sengine->getManager($manager, $values, 'file');
        
        $bp = $this->pageByPage($this->limit, 1);
        
        list($count, $rows) = $smanager->getFileSearchData($bp->limit, $bp->offset, $manager);
        $bp->countAll($count);
        
        $rows = KBApiSearch::highlight($rows, $smanager, $values['q']);
        
        if(empty($this->rq->skip_log)) {
            $exitcode = ($count > 10) ? 11 : $count;
            $smanager->logUserSearch($values, 2, $exitcode, $manager->user_id);
        }
        
		$ra = $this->getResultAttributesFromBP($bp);
		$this->setRootAttributes($ra);
        
        return $rows;
	}
	
}


/*
    'f' => array(
        'file', 
        'file_title',
        'file_keyword',
        'filename',
        'file_id',
        'file_author_id'
    ),
*/

?>