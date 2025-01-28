<?php

// for getSearchManager
require_once APP_CLIENT_DIR . 'client/inc/default/KBClientView_common.php';
require_once APP_CLIENT_DIR . 'client/inc/default/view/KBClientView_search.php';
require_once APP_CLIENT_DIR . 'client/inc/default/view/KBClientView_search_list.php';
//

	
	
	
class KBApiSearch
{
	
    // allowed in param in search 
    // static $search_in_range = array(
    //
    //     'kbp' => array(
    //         'all'
    //     ),
    //
    //     'a' => array(
    //         'article',
    //         'article_title',
    //         'article_keyword',
    //         // 'article_attach'
    //         'article_id',
    //         'article_author_id'
    //     ),
    //
    //     'f' => array(
    //         'fileall', // API v1.0
    //         'file',
    //         // 'file_title',
    //         'filename',
    //         'file_keyword',
    //         'file_id',
    //         'file_author_id'
    //     ),
    //
    //     'n' => array(
    //         'news',
    //         'news_title',
    //         'news_keyword',
    //         'news_id'
    //     )
    // );
	
	
	static $search_in_range = array(
		'all', 'article', 'file', 'news'
		);

	static $search_by_range = array(
		'all', 'title', 'keyword', 'id', 'author_id'
		);

	
    function validate($controller, $manager, $in_range = 'a') {
        
        $invalid = array();
        $params =& $this->rq->vars;
		$params = self::getMappedParams($params);
        
        $allowed_in = self::$search_in_range;
        // $allowed_in = $allowed_in[$in_range];
                
        if(!empty($params['in'])) {
            if(!in_array($params['in'], $allowed_in)) {
                $invalid[] = 'in';
            }
        }
        
		$allowed_by = self::$search_by_range;
        if(!empty($params['by'])) {
            if(!in_array($params['by'], $allowed_by)) {
                $invalid[] = 'by';
            }
        }
		
        if(isset($params['cid'])) {
            if(!KBApiValidator::isIds($params['cid'])) {
                $invalid[] = 'cid';
            }
        }
        
        foreach(array('min_date', 'max_date') as $v) {
            if(isset($params[$v])) {
                if(!KBApiValidator::isDateValid($params[$v])) {
                    $invalid[] = $v;
                }
            }
        }
        
        if($invalid) {
            KBApiError::errorInvalidArgs($invalid);
        }
    }
	
	
    // new in 6.0, changed "in" and "by" added 
    // trying to map old in values to new ones
	static function getMappedParams($params) {
        
		if(!isset($params['in'])) {
			$params['in'] = 'all';
		}
		
        $common_by_params = array('title', 'id', 'keyword', 'author_id');
        $common_by_search = sprintf('#_(%s)$#', implode('|', $common_by_params));
        if(preg_match($common_by_search, $params['in'], $match)) {
            $params['in'] = str_replace($match[0], '', $params['in']);
            $params['by'] = $match[1];
        }
        
        if($params['in'] == 'fileall') {
            $params['in'] = 'file';
        
        } elseif($params['in'] == 'filename') { // from 6.0 filename in title index 
            $params['in'] = 'file';
            $params['by'] = 'title';
        }
		
        // echo '<pre>', print_r($match, 1), '</pre>';
        // echo '<pre>', print_r($params, 1), '</pre>'
		
		return $params;
	}
	
	
	static function getSearchParams($params) {
	    
	    $sp = array(
	        'q','in', 'by', 'cp','c', 'et',
	        'period', 'pv', 'date_from', 'date_to', 'is_from', 'is_to',
            'custom'
	    );
        
		$params = self::getMappedParams($params);
        
        // category ids
        if(isset($params['cid'])) {
            $params['c'] = explode(',', $params['cid']);
            $params['c'] = array_filter($params['c'], 'is_numeric');

            // child, by default + all childs
            $params['cp'] = 1;
            if(isset($params['child'])) {
                $params['cp'] = (int) $params['child'];
            }
        }
        
        // limit by dates, posted/updated            
        // min date
        if(isset($params['min_date'])) {
            $params['period'] = 'custom';
            $params['is_from'] = 1;
            $params['date_from'] = self::getDateParams($params['min_date']);
        }
        
        // max date 
        if(isset($params['max_date'])) {
            $params['period'] = 'custom';
            $params['is_to'] = 1;
            $params['date_to'] = self::getDateParams($params['max_date']);
        }        
        
        if(isset($params['period'])) {
            $params['pv'] = 'u';
            if(isset($params['posted'])) {
                $params['pv'] = 'p';
            }    
        }
        
        // article type 
        if(isset($params['type'])) {
            $params['et'] = (int) $params['type'];
        }
		
		// custom
		if(isset($params['custom'])) {
		}
		
	    $params = array_intersect_key($params, array_flip($sp));
        
	    return $params;
	}
	
	
	static function getDateParams($date) {
	    if(is_numeric($date)) {
	        $date = date('Ymd', $date);
	    } else {
            $date = str_replace('-', '', $date);
	    }
        
	    return $date;
	}
    
    
    static function highlight($rows, $smanager, $q) {
        
        $keywords = $smanager->getKeywords();
        $summary_limit = 300;
        
        foreach(array_keys($rows) as $entry_id) {
            // $summary_limit = $this->getSummaryLimit($manager, $private, 300);
            
            $rows[$entry_id]['title_highlight'] 
                = $smanager->highlightTitle($rows[$entry_id]['title'], $q, $keywords);
            
            if(isset($rows[$entry_id]['body'])) {
                $rows[$entry_id]['body_highlight'] 
                    = $smanager->highlightBody($rows[$entry_id]['body'], $q, $keywords, $summary_limit);
            }
        
            if(isset($rows[$entry_id]['filename'])) {
                $rows[$entry_id]['filename_highlight'] 
                    = $smanager->highlightTitle($rows[$entry_id]['filename'], $q, $keywords);
            }
            
            if(isset($rows[$entry_id]['description'])) {
                $rows[$entry_id]['description_highlight'] 
                    = $smanager->highlightTitle($rows[$entry_id]['description'], $q, $keywords);
            }
        }
        
        return $rows;
    }
}
	
?>