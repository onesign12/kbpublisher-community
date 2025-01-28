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

class OneWaySort extends SortOrder
{
    
    function getImage($order) {
        $img = '';
        if($order) {
            $img = ($order == 1 || $order == 'asc') ? $this->img_asc : $this->img_desc;
            $img = '<img src="'.$this->img_path . $img.'" alt="sort" />';
        } else {
            $img = '<div style="padding: 4px"></div>';
        }
        
        return $img;
    }
    
    
    // get order return num
    function getNumOrder($order, $get_param) {
        if($order === false) {
            $order = (!empty($this->custom_default_order[$get_param])) ? $this->custom_default_order[$get_param]
                                                                        : $this->default_order;
        } else { 
            if(is_numeric($order)) {
                $order = ($order == 2) ? 1 : 2;
            } else {
                $order = ($order == 'desc') ? 1 : 2;
            }
        }
        
        return $order;
    }
    
    
    function &getVars() {
    
        foreach($this->sort_ar AS $tpl_var => $v) {
        
            $order = $this->getOrder($v['order'], $v['get_param']);
            $num_order = $this->getNumOrder($order, $v['get_param']);
            
            // short message
            $title = $this->title_msg[$num_order];
            if(is_array($v['field_name'])) {
                $to_shorten = (isset($v['field_name'][2])) ? $v['field_name'][2] : $v['field_name'][0];
                if(_strlen($to_shorten) > $v['field_name'][1]) {
                    $title = $v['field_name'][0] . ' - ' . $title;
                    $v['field_name'] = _substr($to_shorten, 0, $v['field_name'][1]);

                } else {
                    $v['field_name'] = $v['field_name'][0];
                }
            }
            
            $ar[$tpl_var]['link']  = $this->getLink($v['get_param'], $num_order);
            $ar[$tpl_var]['img']   = $this->getImage($order);
            $ar[$tpl_var]['field'] = $v['field_name'];
            $ar[$tpl_var]['title'] = $title;
        }

        return $ar;
    }


    function toHtml() {
        
        $arr = array();
        
        $str = '
            <div style="white-space: nowrap;">
                <div style="float: right; padding-left: 5px;">%s</div>
                <div><a href="%s" title="%s" class="%s">%s</a></div>
            </div>';
        // 
        // $str =
        //     '<table class="sTable tableCp0"><tr>
        //         <td><a href="%s" title="%s" class="%s">%s</a></td>
        //         <td style="padding-left: 5px; text-align: right;">%s</td>
        //     </tr></table>';

        foreach($this->getVars() as $k => $v) {
            $arr[$k] = sprintf($str, $v['img'], $v['link'], $v['title'], $this->a_class, $v['field']);
            // $arr[$k] = sprintf($str, $v['link'], $v['title'], $this->a_class, $v['field'], $v['img']);
        }
        
        return $arr;
    }
}
?>