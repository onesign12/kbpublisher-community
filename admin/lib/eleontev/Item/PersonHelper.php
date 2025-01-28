<?php
class PersonHelper
{
    

    // Dow John Haert
    static function getFullName($first_name, $middle_name = false, $last_name = false) {
        
        if(is_array($first_name)) {
            extract($first_name);
        }
        
        if($middle_name) {
            @$str = ucwords($last_name.', '.$first_name.' '.$middle_name); 
        } elseif ($first_name || $last_name) {
            @$str = ucwords($last_name.' '.$first_name); 
        } else {
            $str = '';
        }
        return $str;
    }
    
    
    // Doe E.D.
    static function getShortName($first_name, $middle_name = false, $last_name = false) {
        
        if(is_array($first_name)) {
            extract($first_name);
        }        
        
        if($first_name || $last_name) {
            $str = ucwords($last_name.' '._substr($first_name, 0, 1).'.');
            if($middle_name) { $str .= ucwords(_substr($middle_name, 0, 1)).'.'; }
        } else {
            $str = '';
        }
        return $str;
    }
    
    
    // John Dow
    static function getEasyName($first_name, $last_name = false) {        
    
        if(is_array($first_name)) {
            extract($first_name);
        }
        
        @$str = sprintf('%s %s', ucwords($first_name), ucwords($last_name));
        return $str;
    }


    // John Dow
    static function getEmailName($first_name, $last_name = false) {        
    
        if(is_array($first_name)) {
            extract($first_name);
        }
        
        @$str = sprintf('%s %s', ucwords($first_name), ucwords($last_name));
        return $str;
    }

}
?>