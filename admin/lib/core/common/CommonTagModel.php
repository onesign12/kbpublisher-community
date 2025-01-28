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


class CommonTagModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'tag', 'tag', 'tag_to_entry',
        'tag_to_entry_update', 'entry_task');
    
    var $entry_type = 22;
    
    static $keyword_delimeter = ','; // for meta_keywords field


    static function getKeywordDelimeter() {
        return self::$keyword_delimeter;
    }
    
    
    function getSuggestList($limit = false, $offset = 0) {
        $sql = "SELECT t.*, COUNT(te.tag_id) as num
                FROM {$this->tbl->table} t
                
                LEFT JOIN {$this->tbl->tag_to_entry} te
                ON t.id = te.tag_id
                
                GROUP BY t.id
                ORDER BY num DESC, t.title";
                
        if ($limit) {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));           
        } else {
            $result = $this->db->Execute($sql) or die(db_error($sql));        
        }
        
        return $result->GetArray();
    }

 
    // ENTRY TO TAG // ------------------    
    
    static function parseTagOnAdding($tag) {
        // $tag = _strtolower($tag);
        $tag = trim(preg_replace("#['\",]#u", " ", $tag));
        return $tag;
    }
        
    
    function parseTagString($str) {
        
        $tags = array();

        $pattern = '#"(.*?)"#';
        preg_match_all($pattern, $str, $match);

        if($match) {
            $tags = $match[1];
            $str = str_replace($match[0], '', $str);
        }

        $pattern = '#[\s+]#';
        $match = preg_split($pattern, $str, -1, PREG_SPLIT_NO_EMPTY);
        if($match) {
            $tags = array_merge($tags, $match);
        }
        
        foreach($tags as $k => $tag) {
            $tags[$k] = self::parseTagOnAdding($tag);
        }        
        
        return $tags;
    }
    
    
    function getTagByIds($ids) {
        $sql = "SELECT id, title FROM {$this->tbl->tag} WHERE id IN ({$ids})";
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }

        return $result->GetAssoc();
    }


    function getKeywordsStringByIds($ids) {
        $tags = $this->getTagByIds($ids);
        return implode($this->getKeywordDelimeter(), $tags);
    }


    function getTagByEntryId($record_id) {
        
        $sql = "
        SELECT 
            t.id, 
            t.title 
        FROM 
            {$this->tbl->tag} t, 
            {$this->tbl->tag_to_entry} te
        WHERE 1
            AND te.entry_id IN ({$record_id}) 
            AND te.tag_id = t.id
            AND te.entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getTagToEntry($record_id) {
        
        $sql = "
        SELECT 
            te.entry_id,
            t.id,
            t.title 
        FROM 
            {$this->tbl->tag} t, 
            {$this->tbl->tag_to_entry} te
        WHERE 1
            AND te.entry_id IN ({$record_id}) 
            AND te.tag_id = t.id
            AND te.entry_type = '{$this->entry_type}'";
            
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }
     
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']][$row['id']] = $row['title'];
        }
        
        return $data;        
    }    

        
    function &getTagByTitleResult($tags) {
        
        $tags = (is_array($tags)) ? $tags : array($tags);
        $tags = array_map('trim', $tags);        
        $tags = implode("','", $tags);
        
        $sql = "SELECT * FROM {$this->tbl->tag} WHERE title IN ('{$tags}')";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
    

    function getTagIds($tags) {
        $result =& $this->getTagByTitleResult($tags);
        return array_keys($result->GetAssoc());
    }


    function getTagArray($tags) {
        $result =& $this->getTagByTitleResult($tags);
        return $result->GetArray();
    }
    
    
    function isTagExists($title, $id = false) {
        $m = Singleton('TagModel');
        return $m->isTagExists($title, $id);
    }
    
    
    function saveTag($tags) {
                
        $tags = (is_array($tags)) ? $tags : array($tags);
        $data = array();
        foreach($tags as $title) {
            if(!$this->isTagExists($title)) {
                $data[] = array($title);
            }
        }
        
        if($data) {
            $sql = MultiInsert::get("INSERT {$this->tbl->tag} (title, date_posted) VALUES ?", $data, 'NOW()');
            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    function saveTagToEntry($values, $entry_id, $entry_type = false) {
                
        if(empty($values)) {
            return;
        }
        
        $entry_id = (is_array($entry_id)) ? $entry_id : array($entry_id);
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $tags = array();
        foreach($entry_id as $_entry_id) {
            foreach($values as $tag_id) {
                $tags[] = array($_entry_id, $entry_type, $tag_id);
            } 
        }
        
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->tag_to_entry} (entry_id, entry_type, tag_id) VALUES ?", $tags);
                                        
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return true;
    }
    
    
    function deleteTagToEntry($entry_id, $entry_type = false, $tag_id = false) {
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;  
        $entry_id = (is_array($entry_id)) ? implode(',', $entry_id) : $entry_id;
        $tag_id = (is_array($tag_id)) ? implode(',', $tag_id) : $tag_id;
        
        $sql = "DELETE FROM {$this->tbl->tag_to_entry}
            WHERE entry_id IN ({$entry_id})
            AND entry_type = '{$entry_type}'";
            
        if ($tag_id) {
            $sql .= sprintf(" AND tag_id IN (%s)", $tag_id);
        }
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return true;
    }
}
?>