<?php
/**
 * SortOrder is a class to update sort values in table.
 *
 * @version 1.0
 * @since 06/15/2004
 * @author Evgeny Leontev 
 * @access public
 *
 * EXAMPLE
 * 
 *
 * CHANGELOG
 * 06/15/2004 - release
 */

class TableSortOrder
{

    var $db;
    var $table;
    var $id_field = 'id';
    var $name_field;
    var $sort_field = 'sort_order';
    
    var $extra_range = array(
        'sort_default'  =>'__', 
        'sort_begin'    =>'AT THE BEGINNING',
        'sort_end'      =>'AT THE END'
        );
    
    var $after_word = 'AFTER';
    var $no_matter_world = 'NO MATTER'; 
    var $min_sort_val = 1;
    var $no_matter_value;
    var $no_matter_sort_field = 'date_posted';
    
    //var $id
    var $range_more_sql = '1=1';
    var $manipulate_more_sql = '1=1';
    var $update_limit;
    var $more_update_sql;                // for timestaps fields not allow them to be updated on sort
                                        // example ", date_updated = dateu_pdated"
    
    
    
    var $new_after_item_addon = 0; // it is a hack, it will be added to sort order 
                                   // when adding new item and sort it after some item
    
    
    function getRange() {
        $sql = "SELECT {$this->sort_field}, CONCAT('{$this->after_word} - ', {$this->name_field}) AS sort_name 
        FROM {$this->table} WHERE {$this->range_more_sql} ORDER BY {$this->sort_field}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $data[$row[0]] = $row[1];
        }
        
        $data =  $this->extra_range + $data;
        return $data;
    }
    
    
    function setNoMatterValue($num) {
        $this->no_matter_value = $num;
    }
    
    function getNoMatterExtraRange() {
        return array($this->no_matter_value => $this->no_matter_world);
    }
    
    function setNoMatterExtraRange($num) {
        $this->extra_range['sort_no_matter'] = 'NO MATTER';
        $this->no_matter_value = $num;
    }
    
    
    function setMoreSql($range_more_sql, $manipulate_more_sql = false) {
        $this->range_more_sql = $range_more_sql;
        $this->manipulate_more_sql = ($manipulate_more_sql) ? $manipulate_more_sql : $range_more_sql;
    }
    
    
    function setMoreUpdateSql($sql) {
        $this->more_update_sql = ', ' . $sql;
    }
    

    function getDoSort($sort_value, $record_id = false, $current_value = false) {
        
        // update
        if($record_id) {
            
            if($current_value != 'none') {
                if(!$current_value) {
                    $current_value = $this->getCurrentValue($record_id);
                }            
            }
            
            // process as new 
            // in some we can not have $current_value, for example in kb_entry_to_category 
            // when when changing category and update article
            if(!$current_value || $current_value == 'none') {
                return $this->getDoSort($sort_value, false);
            }
            
            $submit_value = $sort_value;
            
            if($sort_value == 'sort_begin') {
                $sort_value = $this->min_sort_val;
            
            } elseif($sort_value == 'sort_end') {
                $sort_value = $this->getMaxValue();
            
            } elseif($sort_value == 'sort_default') {
                $sort_value = $current_value;
            }

            $is_more = ($sort_value > $current_value);
            $is_less = ($sort_value < $current_value);
            $is_equal = ($sort_value == $current_value);
            
            //echo "<pre>Current:"; print_r($current_value); echo "</pre>";
            //echo "<pre>Choosed:"; print_r($sort_value); echo "</pre>";
            //echo "<pre>is_more:"; print_r($is_more); echo "</pre>";
            //echo "<pre>is_less:"; print_r($is_less); echo "</pre>";
            //echo "<pre>is_equal:"; print_r($is_equal); echo "</pre>";
            //exit;    
            
            if($is_equal) {
                $sort = $sort_value;
            
            // current > choosed (choosed is less) 
            } elseif($is_less) {
                $sort = ($submit_value == 'sort_begin') ? $sort_value : $sort_value+1;
                if($sort !=  $current_value) {    
                    $this->updateLess($sort, $current_value);
                }
            
            // current < choosed (choosed is more)
            } elseif($is_more) {
                $sort = $sort_value;
                $this->updateMore($sort, $current_value);
                if($sort_value == $this->no_matter_value) {
                    $this->updateOneValueFromNoMatter();
                }                 
            }        
        
        // new
        } else {
        
            if($sort_value == 'sort_begin') {
                $sort = $this->min_sort_val;
                $this->update('+', '>=', $sort); //all upper and choosed: +1
            
            } elseif($sort_value == 'sort_end' || $sort_value == 'sort_default') {
                $sort = $this->getMaxValue()+1;
            
            } elseif($sort_value == $this->no_matter_value) {
                $sort = $this->no_matter_value;
            
            } else {
                $sort = ($sort_value == 0) ? $sort_value+1 : $sort_value + $this->new_after_item_addon;
                $this->update('+', '>=', $sort); //all upper and choosed: +1
            }
        }
        
        return $sort;
    }
    

    
    // SQL // ----------------------------------
    
    function getMaxValue() {
        $sql = "SELECT MAX({$this->sort_field}) AS 'num' FROM {$this->table}    
        WHERE {$this->manipulate_more_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $val = $result->Fields('num');
        
        return ($val) ? $val : $this->min_sort_val-1;
    }
    
    
    function getCurrentValue($record_id) {
        $sql = "SELECT {$this->sort_field} FROM {$this->table}    
        WHERE {$this->id_field} = '$record_id'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->sort_field);    
    }

    
    function getIdFromNoMatter() {
        $sql = "SELECT {$this->id_field}
        FROM {$this->table}    
        WHERE {$this->sort_field} = '{$this->no_matter_value}'
        ORDER BY {$this->no_matter_sort_field}";
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }
    
    
    function updateOneValueFromNoMatter() {
        
        $id = $this->getIdFromNoMatter();
        $val = $this->no_matter_value -1;
        
        $sql = "UPDATE {$this->table}
        SET {$this->sort_field} = {$val}
        WHERE {$this->id_field} = '{$id}'";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // all upper: -1
    function updateOnDelete($sort_value) {
        
        if($this->no_matter_value) {
            if($sort_value < $this->no_matter_value) {
                $this->update('-', '>', $sort_value);
                $this->updateOneValueFromNoMatter();     
            }
        } else {
            $this->update('-', '>', $sort_value);
        }
    }    

    
    function update($assign_sign, $compare_sign, $sort_value) {
            
            $limit = $this->getLimitSql();
            
            $sql = "UPDATE {$this->table} 
            SET {$this->sort_field} = {$this->sort_field} {$assign_sign} 1 
                {$this->more_update_sql}
            WHERE {$this->manipulate_more_sql} 
            AND {$this->sort_field} {$compare_sign} {$sort_value} {$limit}";

            //echo "<pre>"; print_r($sql); echo "</pre>";
            //exit();
            
            return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function updateMore($sort_value, $current_value) {
            
            $limit = $this->getLimitSql();
            
            $sql = "UPDATE {$this->table} 
            SET {$this->sort_field} = {$this->sort_field} -1 
                {$this->more_update_sql}
            WHERE {$this->manipulate_more_sql} 
            AND {$this->sort_field} <= {$sort_value}
            AND {$this->sort_field} > {$current_value} {$limit}";
        
            //echo "<pre>"; print_r($sql); echo "</pre>";
            //exit();
            
            return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function updateLess($sort_value, $current_value) {
                
            $limit = $this->getLimitSql();
            
            $sql = "UPDATE {$this->table} 
            SET {$this->sort_field} = {$this->sort_field} +1 
                {$this->more_update_sql}
            WHERE {$this->manipulate_more_sql} 
            AND {$this->sort_field} >= {$sort_value}
            AND {$this->sort_field} < {$current_value} {$limit}";
        
            //echo "<pre>"; print_r($sql); echo "</pre>";
            //exit();
            
            return $this->db->Execute($sql) or die(db_error($sql));
    }    
    
    
    // if we store it in other table
    function delete($record_id) {
        $sql = "DELETE {$this->table} WHERE {$this->id_field} = '$record_id'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    

    function getLimitSql() {
        return ($this->update_limit) ? 'LIMIT ' . $this->update_limit : '';
    }
}
?>