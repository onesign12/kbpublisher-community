<?php

class TreeHelperUtil
{
    
    // return array with all parent to id, $arr should be in simple format
    static function &getParentsById($arr, $id, $label = 'id', $parent_key = 'parent_id', $ar = array()) {
        
        if(isset($arr[$id])) {
            $parent_id = $arr[$id][$parent_key];
            $ar[$id] = ($label == 'id') ? $id : $arr[$id][$label];
            $ar = TreeHelperUtil::getParentsById($arr, $parent_id, $label, $parent_key, $ar);
        } else {
            $ar = array_reverse($ar, true);
        }
        
        //ksort($ar);
        return $ar;
    }

    // return array with top parent id, $arr should be in simple format
    static function getTopParent($arr, $id, $top_parent_id = 0, $label = 'id', $parent_key = 'parent_id') {

        if(isset($arr[$id])) {
            $parent_id = $arr[$id][$parent_key];
            if($parent_id == $top_parent_id) {
                return $id;
            } else {
                $ar[$id] = ($label == 'id') ? $id : $arr[$id][$label];
                $val = TreeHelperUtil::getTopParent($arr, $parent_id, $top_parent_id, $label, $parent_key);
            }
            
        } else {
            $val = $id;
        }
        
        //ksort($ar);
        return $val;        
    }
    
    
    // return array with all childs to id in format id => id, 
    // $arr should be in tree format
    static function &getChildsById($arr, $id, $label = 'id', $ar = array()) {
        
        if(!isset($arr[$id])) { $a = array(); return $a; }
        
        foreach($arr[$id] as $_child_id => $_id) {
            $ar[] = ($label == 'id') ? $_id : $arr[$id][$_child_id][$label];
            
            if(isset($arr[$_child_id])) {
                $ar = &TreeHelperUtil::getChildsById($arr, $_child_id, $label, $ar);
            }
        }
        
        return $ar;
    }
    
    
    // return array with all childs to id in format id => id, 
    // $arr should be in tree format
    static function &getChildsByIds($arr, $id_arr, $label = 'id') {
        
        if(!$id_arr) { $a = array(); return $a; }
        
        foreach($id_arr as $id) {
            $ar2[$id] = &TreeHelperUtil::getChildsById($arr, $id, $label);
        }
        
        //$ar = array_unique($ar);
        return $ar2;
    }
    
    
    //$arr should be in tree format
    static function &getSelectRange($arr, $parent_id = 0, $level = 0, $label = 'id', $ar = array()) {
        
        if(!isset($arr[$parent_id])) { $a = array(); return $a; }
        
        foreach($arr[$parent_id] as $_parent_id => $_id) {
            $ar[$level][] = ($label == 'id') ? $_id : $arr[$parent_id][$_parent_id][$label];

            if(isset($arr[$_parent_id])) {
                $ar = &TreeHelperUtil::getSelectRange($arr, $_parent_id, $level+1, $label, $ar);
            }
        }
        
        return $ar;
    }
    
    
    // return array (one dimensional) to use to generate anithing
    // in format id => level, $arr should be in tree format
    static function &getTreeHelper($arr, $parent_id = 0, $level = 0, $ar = array()) {
        
        if(!isset($arr[$parent_id])) { $a = array(); return $a; }
        
        foreach($arr[$parent_id] as $_parent_id => $_id) {
            $ar[$_id] = $level;

            if(isset($arr[$_parent_id])) {
                $ar = &TreeHelperUtil::getTreeHelper($arr, $_parent_id, $level+1, $ar);
            }
        }
        
        return $ar;
    }
    
    
    static function getIdByString($arr, $string, $delim = '->', $field_name = 'name') {
        
        $nodes = explode($delim, $string);
        $nodes = array_map('trim', $nodes);
        
        $tree = array();
        foreach($arr as $id => $v) {
            $tree[$v['parent_id']][$id] = $id;
        }
        
        $level = 0;
        $parent_id = 0;
        $id = TreeHelperUtil::_findNodeByName($arr, $tree, $nodes, $parent_id, $level, $field_name);
        
        return $id;
    }
    
    
    static function _findNodeByName($arr, $tree, $nodes, $parent_id, &$level, $field_name = 'name') {
        
        foreach ($tree[$parent_id] as $id) {
            if ($arr[$id][$field_name] == $nodes[$level]) { // found a match
                
                if ($level + 1 == count($nodes)) { // found them all
                    return $id;
                    
                } else {
                    $level ++;
                    return TreeHelperUtil::_findNodeByName($arr, $tree, $nodes, $id, $level, $field_name);
                }
                
            }
        }
        
    }
}

?>