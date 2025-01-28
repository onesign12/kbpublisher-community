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


class KBClientView_search extends KBClientView_common
{
    
    static $search_in_range = array(
        'all',
        'article',
        'file',
        'news'
    );
    
    static $search_by_range = array(
        'all',
        'title',
        'keyword',
        'id'
    );

    static $search_by_range_extra = array(
		'file' => array(
            'attachment'
		)
    );
    
    
    static function getSearchInRange($manager, $skip_disabled = false) {
        
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_in_range');
        
        $range = array();
        foreach(self::$search_in_range as $v) {
            
            if($skip_disabled) {
                if($v == 'news' && !$manager->isModule('news')) {
                    continue;
                } elseif($v == 'file' && !$manager->isModule('file')) {
                    continue;
                }
            }
            
            $range[$v] = $range_msg[$v];
        }
        
        return $range;
    }

    
    function getSearchInSelect($range, $current) {
        
        $select = new FormSelect();
        $select->select_name = 'in';
        $select->select_tag = false;

        $select->setRange($range);
        return $select->select($current);
    }
    
    
    // SERACH BY // ------------------------
    
    static function getSearchByRange($current_in = false) {
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
        
        $range = array();
        foreach(self::$search_by_range as $k => $v) {
            if($v == 'keyword') {
                continue;
            }
            
            $range[$v] = $range_msg[$v];
        }
        
        if ($current_in) {
            $extra_range = self::getSearchByExtraRange($current_in);
            $range = array_merge($range, $extra_range);
        }
		
        return $range;
    }
    
    
    static function getSearchByExtraRange($current_in) {
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
    
        $_in = (is_array($current_in)) ? $current_in : array($current_in);
        $range = array();
        foreach($_in as $in) {
            if(isset(self::$search_by_range_extra[$in])) {
                foreach(self::$search_by_range_extra[$in] as $k => $v) {
                    $range[$v] = $range_msg[$v];
                }
            }
        }
    
        return $range;
    }
    
    
    function parseSearchByBlock($tpl, $current_by, $current_in) {
        $range = self::getSearchByRange();
        $range = $this->stripVars($range);
        
        $extra_range = self::getSearchByExtraRange($current_in);
        $extra_range = $this->stripVars($extra_range);
        
        $range = array_merge($range, $extra_range);
        
        foreach ($range as $k => $v) {
            $v1['name'] = $v;
            $v1['value'] = $k;
            $v1['checked'] = ($current_by == $k) ? 'checked' : '';
            $v1['class'] = (!empty($extra_range[$k])) ? 'search_item search_extra_item' : 'search_item';
            
            $tpl->tplParse($v1, 'by_row');
        }
    }
    
    
    // SPECIAL SEARCH // --------------------
    
    // if some special search used, 
    static function isSpecialSearch($str) {
        
        $search = array();
        $search[] = array(
            'in' => false,
            'by' => false,
            'search' => SphinxModel::getSphinxSearchRegex() // just to know and skip suggest
        );
        
        $search[] = array(
            'in' => 'article',
            'by' => 'id',
            'search' => "#^(id:|article_id:)(\d+)$#"
        );
        
        $search[] = array(
            'in' => 'file',
            'by' => 'id',
            'search' => "#^(file_id:)(\d+)$#"
        );
        
        $search[] = array(
            'in' => false,
            'by' => 'title',
            'search' => "#^(title:)(.*?)$#"
        );
        
        $search[] = array(
            'in' => false,
            'by' => 'keyword',
            'search' => "#^(tag:|keyword:)(.*?)$#"
        );
        
        $search[] = array(
            'in' => false,
            'by' => 'keyword',
            'search' => "#^(\[(.*?)\])$#"
        );
        
        return self::parseSpecialSearchStr($str, $search);
    }
        
    
    static function parseSpecialSearchStr($str, $preg_arr) {
        
        $str = trim(urldecode($str));
        
        foreach ($preg_arr as $k => $v) {
            preg_match($v['search'], $str, $match);
            
            if(!empty($match[0])) {
                $v['sk'] = (isset($match[1])) ? $match[1] : false; // special_search_key
                $v['sq'] = (isset($match[2])) ? $match[2] : false; // specail_search_str
                $v['in'] = ($v['in']) ? array($v['in']) : $v['in'];
                
                return $v;
            }
        }
        
        return false;        
    }
    
}
?>