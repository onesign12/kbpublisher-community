<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
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

class TreeHelper
{
    
    var $raw_ar = array();
    var $tree_ar = array();
    
    
    function setItem($id, $parent_id, $more_items) {
        $this->setRawItem($id, $parent_id, $more_items);
        $this->setTreeItem($id, $parent_id);
    }
    
    
    function setRawItem($id, $parent_id, $more_items) {
        $this->raw_ar[$id]['parent_id'] = $parent_id;
        foreach($more_items as $k => $v) {
            $this->raw_ar[$id][$k] = $v;
        }
        
    }
    
    function setTreeItem($id, $parent_id, $more_items = false) {
        if($more_items) {
            $this->tree_ar[$parent_id][$id] = $more_items;
        } else {
            $this->tree_ar[$parent_id][$id] = $id;
        }
    }
    
    
    function &getRawArray() {
        return $this->raw_ar;
    }
    
    
    function &getTreeArray() {
        return $this->tree_ar;
    }
 
 
    // return array (one dimensional) to use to generate anything
    function &getTreeHelper($parent_id = 0, $level = 0, $ar = array()) {
        return TreeHelperUtil::getTreeHelper($this->tree_ar, $parent_id, $level, $ar);
    }

    
    // return array with all parent to id
    function &getParentsById($id, $label = 'id', $parent_key = 'parent_id', $ar = false) {
        return TreeHelperUtil::getParentsById($this->raw_ar, $id, $label, $parent_key, $ar );
    }
    
    
    // return array with all child to id
    function &getChildsById($id, $label = 'id', $ar = array()) {
        if(is_array($id)) {
            return TreeHelperUtil::getChildsByIds($this->tree_ar, $id, $label, $ar);
        } else {
            return TreeHelperUtil::getChildsById($this->tree_ar, $id, $label, $ar);            
        }    
    }
}


//
// $row[1] = array('id'=>1,'parent_id'=>0,'url'=>'index.php','name'=>'0.1');
// $row[2] = array('id'=>2,'parent_id'=>0,'url'=>'any.php','name'=>'0.2');
// $row[3] = array('id'=>3,'parent_id'=>0,'url'=>'contact.php','name'=>'0.3');
// $row[4] = array('id'=>4,'parent_id'=>1,'url'=>'doctor.php','name'=>'1.1');
// $row[5] = array('id'=>5,'parent_id'=>1,'url'=>'doctor1.php','name'=>'1.2');
// $row[6] = array('id'=>6,'parent_id'=>4,'url'=>'doctor2.php','name'=>'1.1.1');
// $row[7] = array('id'=>7,'parent_id'=>6,'url'=>'doctor2.php','name'=>'1.1.1.1');
// $row[8] = array('id'=>8,'parent_id'=>3,'url'=>'doctor2.php','name'=>'1.1.1.1');
//
//
// $tree = new TreeHelper;
// foreach($row as $k => $v) {
//     $tree->setTreeItem($v['id'], $v['parent_id']);
//     //$tree->setTreeItem($v['id'], $v['parent_id'], array('name'=>$v['name']));
// }
//
// // $childs = &$tree->getChildsById(array(1,3));
// $helper = &$tree->getTreeHelper(1);
//
// // echo "<pre>"; print_r($tree); echo "</pre>";
// // echo '<pre>', print_r($childs, 1), '</pre>';
// echo '<pre>', print_r($helper, 1), '</pre>';


//echo "<pre>"; print_r($row); echo "</pre>";
//echo "<pre>"; print_r(TreeHelperUtil::getTopParent($row, 7)); echo "</pre>";



/*
function createSelect($array, $pref, $parent = 0, $level = 0) {

    foreach($array[$parent] as $k => $v) { 
        if ($k == "0"){
            @$html .= "<option value=\"".$k."\" id=\"".$v['id']."\">".strtoupper($v['option'])."</option>\n";
        } else {
            $pre = str_repeat($pref, $level);
            @$html .= "<option value=\"".$k."\" id=\"".$v['id']."\">".$pre.$v['option']."</option>\n";
        }

        if(isset($array[$k])){
            $html .= createSelect($array, $pref, $k, $level+1);
        }
    }
    return $html;
}
*/
?>