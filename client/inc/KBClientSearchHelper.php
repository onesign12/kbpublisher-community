<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class KBClientSearchHelper
{
    
    
    static function getInValue($values, $manager) {
              
        $q = (isset($values['q'])) ? trim($values['q']) : '';
        
        // get in value
        if(empty($values['in'])) {
            $in = $manager->getSetting('search_default');
            if($in == 'file' && !$manager->isModule('file')) {
                $in = 'all';
            }
        } else {
            $in = $values['in'];
        }
        
        // validate in, reset to all if not known $in
        $range = KBClientView_search::getSearchInRange($manager);
        $range = array_filter(array_keys($range), 'is_string');
        
        $in = (is_array($in)) ? $in : array($in);
        if(!array_intersect($in, $range)) {
            $in[] = 'all';
            $_GET['in'] = $in; 
        }
        
        // validate by, reset to all if not known $by
        $by = (!empty($values['by'])) ? $values['by'] : 'all';
        $range = KBClientView_search::getSearchByRange($in);
        $range['author_id'] = ''; // add to range for api
        $range = array_filter(array_keys($range), 'is_string');
        if(!in_array($by, $range)) {
            $by = 'all';
            $_GET['by'] = $by;
        }
        
        // special search
        $sk = false;
        if($ret = KBClientView_search::isSpecialSearch($q)) {
            
            if($ret['in'] !== false) {
                $in = $ret['in'];
            }
            
            if($ret['by'] !== false) {
                $by = $ret['by'];
            }            
            
            $q = $ret['sq'];
            $sk = $ret['sk'];
        }
        
        // trying to change search if searched by id but not number
        // it could be possible if redirected programatically to id params
        if($by == 'id' && !is_numeric($q)) {
            $by = 'all';
            $_GET['by'] = $by;
        }
        
        $ret = array('qs'=>$q, 'in'=>$in, 'by'=>$by, 'sk'=>$sk);
        return $ret;
    }
        
    
    // this will be used to ser correct ORDER BY
    static function isOrderByScore($values) {
        $val = false;
        if(!empty($values['q'])) {
            if($values['by'] != 'id' &&
               $values['by'] != 'keyword' && 
               $values['by'] != 'author_id') {
                
                $val = true;
            }
        }
        
        return $val;        
    }
    
    
    static function isUseFilter($in, $type, $values, $manager) {
        $ret = false;
        if($manager->getSetting('search_filter')) { // could be disabled
            $run = false;
            
            // display default filter if search in all
            if($search_filter_default = $manager->getSetting('search_filter_default')) {
                $_in = ($search_filter_default == 1) ? 'article' : 'file';
                if($type == $_in && count($in) == 1 && in_array('all', $in)) {
                    $type = 'all';
                } 
            }
            
            if(count($in) == 1 && in_array($type, $in)) { // if search in one type
                $check_values = ['q', 'period', 'c', 'et']; // if some serach params not empty
                $check_values = array_intersect_key($values, array_flip($check_values));
                $check_values['q'] = trim(SphinxModel::getSphinxString($check_values['q']));
                if(@$check_values['period'] == 'all') {
                    unset($check_values['period']);
                }
                
                $ret = (array_filter($check_values)) ? true : false;
            }
        }
        
        return $ret;
    }
    
    
    static function getSearchParams($strip = true) {
        
        $arr = $_GET;
        $r = ['q', 'in', 'by', 'c', 'cp', 'et', 'period', 'pv', 
            'date_from', 'date_to', 'is_from', 'is_to', 'custom', 'sort'];
            
        foreach(array_keys($arr) as $k) {
            if(!in_array($k, $r)) {
               unset($arr[$k]);
            }
        }

        if(isset($arr['q'])) {
            $arr['q'] = trim($arr['q']);
        }

        if(isset($arr['in'])) {
            $arr['in'] = (is_array($arr['in'])) ? $arr['in'] : array($arr['in']);
        }
                
        if($strip) {
            $arr = RequestDataUtil::stripVars($arr, array(), 'qweqweqe'); // 3 param for stripslashes
        }

        return $arr;
    }
}
?>