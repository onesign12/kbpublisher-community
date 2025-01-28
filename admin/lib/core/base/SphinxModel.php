<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class SphinxModel extends BaseModel
{
          
    var $sphinx;
    
    static $indexes = array(
        'all' => 'kbpIndexAll',
        'client' => 'kbpIndexClient',
        'admin' => 'kbpIndexAdmin',
        'kbpArticleIndex_main', 'kbpArticleIndex_delta',
        'kbpFileIndex_main', 'kbpFileIndex_delta',
        'kbpNewsIndex_main', 'kbpNewsIndex_delta',
        'kbpFeedbackIndex_main', 'kbpFeedbackIndex_delta',
        'kbpGlossaryIndex_main', 'kbpGlossaryIndex_delta',
        'kbpTagIndex_main', 'kbpTagIndex_delta',
        'kbpUserIndex_main', 'kbpUserIndex_delta',
        'kbpArticleDraftIndex_main', 'kbpArticleDraftIndex_delta',
        'kbpFileDraftIndex_main', 'kbpFileDraftIndex_delta',
        'kbpCommentIndex_main', 'kbpCommentIndex_delta',
        'kbpRatingFeedbackIndex_main', 'kbpRatingFeedbackIndex_delta',
        'kbpAttachmentIndex_main', 'kbpAttachmentIndex_delta'
    );
    
    var $idx;
    
    var $sql_params_default = 'is_deleted = 0';
    
    var $sql_params_match = '';
    var $sql_params_match_ar = array();
    
    var $sql_params_group2 = '';
    
    var $match_check = true;
    
    var $quorum_percentage = '0.5';
    
    
    // need to modify adodb drivers
    //var $sql_params_option = "OPTION ranker = expr('sum(lcs) * 1000 + bm25 + sum(user_weight)'), field_weights = (title = 2)";
    
    
    function __construct() {
        $this->sphinx = &self::connect();
        $this->setSqlParamsSelect('entry_id, WEIGHT() as score');
        $this->idx = self::setIndexNames();
    }
    
    
    static function setIndexNames() {
        $prefix = self::getSphinxPrefix();
        
        $tbl =  array(); 
        foreach(self::$indexes as $k => $v) {
            $t = (!is_int($k)) ? $k : $v;
            $tbl[$t] = $prefix . $v;
        }
        
        return (object) $tbl;
    }
    
    
    static function &connect($error_die = true, $settings = array()) {
        
        if(!isset($settings['sphinx_host'])) {
            $settings = self::getSphinxSetting();
        }
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $sphinx_conf = array(
            'db_driver' => $conf['db_driver'],
            'db_host' => sprintf('%s:%s', $settings['sphinx_host'], $settings['sphinx_port']),
            'db_user' => '',
            'db_pass' => '',
            'db_base' => '',
            'debug_db_sql' => (!empty($conf['debug_sphinx_sql'])),
            'sphinx' => true
        );
        
        return DBUtil::connect($sphinx_conf, $error_die);
    }
    
    
    function setIndexParams($entry_type, $empty = false) {
        if (!is_array($entry_type)) {
            $entry_type = array($entry_type);
        }
        
        foreach ($entry_type as $v) {
            $main_key = sprintf('kbp%sIndex_main', ucwords($v));
            $this->setSqlParamsFrom($this->idx->$main_key, null, $empty);
            
            $delta_key = sprintf('kbp%sIndex_delta', ucwords($v));
            $this->setSqlParamsFrom($this->idx->$delta_key);
        }
    }
    
    
    function setSqlParamsMatch($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_match_ar = array(); }
        
        $this->sql_params_match_ar[$key] = $params;
        $this->sql_params_match = implode(' ', $this->sql_params_match_ar);
        $this->sql_params_match = SphinxModel::getSphinxString($this->sql_params_match);
        // echo $where = sprintf("AND MATCH('%s')", addslashes($this->sql_params_match));
        $where = sprintf("AND MATCH('%s')", $this->sql_params_match);
        $this->setSqlParams($where, 'match');
        // echo '<pre>' . print_r($this->sql_params, 1) . '</pre>';
    }
    
    
    function getRecordsIds($limit, $offset) {
        $result =& $this->getRecordsResult($limit, $offset);
        $rows = $result->GetArray();
        return $this->getValuesArray($rows, 'entry_id');
    }
    
    
    function getRecords() {
        
        // php 5.4 fix, Strict Standards
        $args = func_get_args();
        $limit = (isset($args[0])) ? $args[0] : -1;
        $offset = (isset($args[1])) ? $args[1] : -1;
        
        $result =& $this->getRecordsResult($limit, $offset);
        return $result->GetArray();
    }
    
    
    function &getRecordsResult($limit = -1, $offset = -1) {
        
        if ($this->sql_params_match && $this->match_check) { // checking
			 // quorum
            if ($this->quorum_percentage && $this->sql_params_match[0] != '"') {
				//if(str_word_count($this->sql_params_match) >= 3) {
				$words_num = count(preg_split("/[\s,]+/", $this->sql_params_match));
                
                $quorum = false;
                
                if ($words_num == 1) { // no spaces, checking for CJK
                    $pattern = '/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u';
                    $is_cjk = preg_match($pattern, $this->sql_params_match);
                    
                    if ($is_cjk && mb_strlen($this->sql_params_match) >= 3) {
                        $quorum = true;
                    }
                    
                } elseif ($words_num >= 3) {
                    $quorum = true;
                }
                
				if ($quorum) {
	                $this->sql_params_match = sprintf('"%s"/%s', $this->sql_params_match, $this->quorum_percentage);
                
	                // $where = sprintf("AND MATCH('%s')", addslashes($this->sql_params_match));
	                $where = sprintf("AND MATCH('%s')", $this->sql_params_match);
	                $this->setSqlParams($where, 'match');
				}
            }
        
            // $match = addslashes($this->sql_params_match);
            $match = $this->sql_params_match;
            
            $sql = "SELECT * FROM {$this->sql_params_from} WHERE MATCH('{$match}')";
            $result = $this->sphinx->SelectLimit($sql, 0, 0);
            if (!$result) {
                $match = $this->_escapeMatch($this->sql_params_match);
                $this->sql_params_match = $match;
                //$where = sprintf("AND MATCH(%s)", $this->sphinx->Quote($match));
                // $where = sprintf("AND MATCH('%s')", addslashes($match));
                $where = sprintf("AND MATCH('%s')", $match);
                $this->setSqlParams($where, 'match');
            }
            
            // $this->sql_params_match = addslashes($this->sql_params_match);
            $this->sql_params_match = $this->sql_params_match;
        }
        
        $sql = $this->getRecordsSql();
        
        if($limit == -1) {
            $result = $this->sphinx->Execute($sql) 
                        or die(DBUtil::error($sql, false, $this->sphinx));
        } else {
            $sql = sprintf('%s LIMIT %s, %s OPTION max_matches = %d', $sql, $offset, $limit, $offset + $limit);
            
            $result = $this->sphinx->Execute($sql) 
                        or die(DBUtil::error($sql, false, $this->sphinx));
        }
        
        return $result;
    }
    
    
    function _escapeMatch($match) {
		// $from = array('\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', '<');
		// $to = array('\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=', '\<');
        // return str_replace($from, $to, $match);

		$from = array('"',  '(',    ')',    '|',  '-',  '!',  '@',   '~',  '&',  '/',  '^',  '$',  '=',  '<');
		$to =   array('\"', '\\\(', '\\\)', '\|', '\-', '\!', '\\\@', '',  '\&', '\\\/', '\^', '\$', '\=', '\<');

		$replace = array_combine($from, $to);
        return strtr($match, $replace);
	}
    
    
    function getRecordsSql() {
        $sql = "SELECT {$this->sql_params_select}
            FROM {$this->sql_params_from}
            WHERE {$this->sql_params}
            {$this->sql_params_group2}
            {$this->sql_params_order}";
        
        // debug($sql);
        // debug_trace('SphinxModel::getRecordsSql');
        return $sql;
    }
    

    function getCountRecords() {
        $sql = 'SHOW META';
        $result = $this->sphinx->Execute($sql) or die(db_error($sql));
        $meta = $result->getAssoc();
        
        return $meta['total_found'];
    }
    
    
    function setSourceParams($source_id) {
        if (!is_array($source_id)) {
            $source_id = array($source_id);
        }
        
        $where = sprintf("AND source_id IN (%s)", implode(',', $source_id));
        $this->setSqlParams($where);
    }
    
	
	static function getSphinxSetting($key = false) {
		static $setting;
		
		if(!$setting) {
			$setting = SettingModel::getQuick(141);
		}
		
		return ($key) ? $setting[$key] : $setting;
	}
	
    
    // sphinx exists, 1 or 2 (2 = is starting)
    static function isSphinxExists() {
        $setting = self::getSphinxSetting('sphinx_enabled');
        return ($setting);
    }
    
    
    // sphinx is on and no test mode
    static function isSphinxOn() {
        $settings = self::getSphinxSetting();
        return (($settings['sphinx_enabled'] == 1) && !$settings['sphinx_test_mode']);
    }
    
    
    static function isSphinxOnSearch($search_str, $settings = array()) {
        
        $on = false;
        
        if(!empty($search_str)) {

            if(empty($settings)) {
                $settings = self::getSphinxSetting();
            }
            
            $on = ($settings['sphinx_enabled'] == '1') ? true : false;
        
            if($settings['sphinx_test_mode'] && $on) {
                $search = SphinxModel::getSphinxSearchRegex();
                $on = (preg_match($search, $search_str));
            }
        }
        
        return $on;
    }
    
    
    static function getSphinxSearchRegex() {
        return "#^(sphinx:)\s?((?!\s*$).+)$#";
    }
    
    
    static function getSphinxString($str) {
        $str = preg_replace('#^sphinx:#', '', $str);
        return $str;
    }
    
	
	static function getSphinxPrefix() {
		$prefix = self::getSphinxSetting('sphinx_prefix');
        $prefix = ($prefix) ? $prefix . '_' : false;
        return $prefix;
	}
	
	
    static function isSphinxSingleInstance() {
        return (BaseModel::isCloud()) ? true : false;
    }

    
    static function getSphinxVersion() {
        return self::getSphinxSetting('sphinx_version');
    }
    

    static function isSphinxVersion3() {
        return (self::getSphinxVersion() == 3);
    }
    
}
?>