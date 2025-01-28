<?php

class KBApiArticle_search extends KBApiArticle_list
{

	function &execute($controller, $manager) {

        // if(empty($this->rq->vars['in'])) { // comented June 4, 2020 eleontev search changed in lib 
            $this->rq->vars['in'] = 'article';
        // }
        
	    $params = KBApiSearch::getSearchParams($this->rq->vars);
	    
        $rows = $this->getData($manager, $params);
		return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiSearch();
        $a->rq->vars =& $this->rq->vars;
        $a->validate($controller, $manager, 'a');
    }
    
	
	function getData($manager, $values, $count = false) {
        
        $view = new KBClientView_search_list();
        $view->engine_name = $view->getSearchEngineName($manager, $values['q']);
        
        $sengine = $view->getSearchEngine($manager);
        $smanager = $sengine->getManager($manager, $values, 'article');
        
        $bp = $this->pageByPage($this->limit, 1);
        
        list($count, $rows) = $smanager->getArticleSearchData($bp->limit, $bp->offset, $manager);
        $bp->countAll($count);
        
        $rows = KBApiSearch::highlight($rows, $smanager, $values['q']);
        
        if(empty($this->rq->skip_log)) {
            $exitcode = ($count > 10) ? 11 : $count;
            $smanager->logUserSearch($values, 1, $exitcode, $manager->user_id);
        }
        
		$ra = $this->getResultAttributesFromBP($bp);
		$this->setRootAttributes($ra);
        
        return $rows;
	}
		
}
?>