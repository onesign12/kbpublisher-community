<?php

class KBApiSearch_list extends KBApiCommon
{

    var $html_fields = array(
        'body', 'bodyHighlight', 'titleHighlight', 'filenameHighlight', 'descriptionHighlight'
        );

	var $remove_fields_more = array(
	    'body', 'externalLink'
	    );


	function &execute($controller, $manager) {
        
        if(empty($this->rq->vars['in'])) {
            $this->rq->vars['in'] = 'all';
        }
                
        if(empty($this->rq->vars['by'])) {
            $this->rq->vars['by'] = 'all';
        }
        
	    $params = KBApiSearch::getSearchParams($this->rq->vars);
	    
		$rows = $this->getData($manager, $params);
		return $rows;
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiSearch();
        $a->rq = new stdClass();
        $a->rq->vars =& $this->rq->vars;
        $a->validate($controller, $manager, 'a');
    }

	
	function getData($manager, $values, $count = false) {
        
		$view = new KBClientView_search_list();
		$view->engine_name = $view->getSearchEngineName($manager, $values['q']);
		
        $trows = array();
        
        $sengine = $view->getSearchEngine($manager);
        $smanager = $sengine->getManager($manager, $values, 'all');
        
        $bp = $this->pageByPage($this->limit, 1);
        $limits = $view->getLimitVars($bp);
        
        list($count, $rows, $managers) = $sengine->getSearchData($manager, $this->cc, 
                                                            $values, $limits['limit'], $limits['offset']);
        
        $bp->countAll(array_sum($count));
        
		$controller = new KBApiController();
		$controller->setUrlVars();
        
        // articles
        if(!empty($rows['article'])) {
			$p = new KBApiArticle_list();
			$p->setVars($controller);

            foreach(array_keys($rows['article']) as $k) {
                @$score = $rows['article'][$k]['score'];
				$entry_id = $rows['article'][$k]['id'];
                $trows[] = array($score, 'article', $entry_id, 1);
            }

            $rows['article'] = KBApiSearch::highlight($rows['article'], $smanager, $values['q']);
			$rows['article'] = $p->parse($rows['article'], $manager);
		}
		
        // file
        if(!empty($rows['file'])) {
            $p = new KBApiFile_list();
            $p->setVars($controller);
            
            foreach(array_keys($rows['file']) as $k) {
                @$score = $rows['file'][$k]['score'];
                $entry_id = $rows['file'][$k]['id'];
                $trows[] = array($score, 'file', $entry_id, 1);
            }
        
            $rows['file'] = KBApiSearch::highlight($rows['file'], $smanager, $values['q']);
            $rows['file'] = $p->parse($rows['file'], $manager);
        }
        
        // attachment
        if(!empty($rows['attachment'])) {
            $p = new KBApiArticle_list();
            $p->setVars($controller);

            foreach(array_keys($rows['attachment']) as $k) {
                
                @$score = $rows['attachment'][$k]['score'];
                $entry_id = $rows['attachment'][$k]['id'];
                $trows[] = array($score, 'attachment', $entry_id, 5);
                
                $file_id = $rows['attachment'][$k]['file_id'];
                $more = array('AttachID' => $file_id);
                $link = $p->cc->getLink('afile', false, $entry_id, false, $more, 1);
                $rows['attachment'][$k]['attachment_link'] = $link;

                $more['f'] = 1;
                $link = $p->cc->getLink('afile', false, $entry_id, false, $more, 1);
                $rows['attachment'][$k]['attachment_inline_link'] = $link;
            }
        
            $rows['attachment'] = KBApiSearch::highlight($rows['attachment'], $smanager, $values['q']);
            $rows['attachment'] = $p->parse($rows['attachment'], $manager);
        }
        
        // news
        if(!empty($rows['news'])) {
			$p = new KBApiNews_list();
			$p->setVars($controller);

            foreach(array_keys($rows['news']) as $k) {
                @$score = $rows['news'][$k]['score'];
				$entry_id = $rows['news'][$k]['id'];
                $trows[] = array($score, 'news', $entry_id, 1);
            }
			
            $rows['news'] = KBApiSearch::highlight($rows['news'], $smanager, $values['q']);
            $rows['news'] = $p->parse($rows['news'], $manager);
        }
		
        uasort($trows, array($this, 'kbpSortByScore'));
        $trows_count = array_slice($trows, $limits['slice_offset'], 11, true);
        $trows = array_slice($trows, $limits['slice_offset'], $this->limit, true);
		
        
		// log
        if(empty($this->rq->skip_log)) {
            $search_type = ($values['in'] == 'all') ? 0 : array_search($values['in'], $smanager->record_type);
            $exitcode = (count($trows_count) > 10) ? 11 : count($trows);
            $smanager->logUserSearch($values, $search_type, $exitcode, $manager->user_id);
        }
        
        
		$ra = $this->getResultAttributesFromBP($bp);
		$this->setRootAttributes($ra);

		$trows2 = array();
        foreach(array_keys($trows) as $k) {
			$record_type = $trows[$k][1];
			$entry_id = $trows[$k][2];
			
            $trows2[] = $rows[$record_type][$entry_id] + array('recordType' => $record_type);
		}

		return $trows2;
	}
	
	
	function kbpSortByScore($a, $b) {
        return ($a[0] < $b[0]) ? 1 : 0;
	}	
}
?>