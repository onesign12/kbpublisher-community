<?php

class SearchSuggestModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('log_search', 'report_search');

    
    function isEmptyReport() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->report_search}";
        $result = $this->db->Execute($sql);
        
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
                         
        return $result->Fields('num');
    }
    
    
    function populateReport() {
        $sql = "INSERT {$this->tbl->report_search}
            SELECT search_string, COUNT(*) AS num
            FROM {$this->tbl->log_search}
            WHERE exitcode != 0
            GROUP BY search_string";
            
        $result = $this->db->Execute($sql);
        
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }
        
    
    function updateReport($search_string, $search_num) {
        $sql = "UPDATE {$this->tbl->report_search}
            SET search_num = search_num + %d
            WHERE search_string = %s";
            
        $sql = sprintf($sql, $search_num, $this->db->Quote($search_string));
        $result = $this->db->Execute($sql);

        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }

        return $this->db->Affected_Rows();
    }


    function addReport($search_string, $search_num) {
        $sql = "INSERT {$this->tbl->report_search}
            SET search_num = %d, search_string = %s";
            
        $sql = sprintf($sql, $search_num, $this->db->Quote($search_string));
        $result = $this->db->Execute($sql);

        if (!$result) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }

        return $result;
    }


    function &getSearchLogsResult($timestamp) {
        $sql = "SELECT search_string, COUNT(*) AS num
            FROM {$this->tbl->log_search}
            WHERE exitcode != 0
            AND date_search BETWEEN '%s' AND '%s'
            GROUP BY search_string
            ORDER BY num";
            
        $sql = sprintf($sql, date('Y-m-d 00:00:00', $timestamp), date('Y-m-d 23:59:59', $timestamp));
        $result = $this->db->Execute($sql);

        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }



    // PUBLIC // ----------

    function getSearchSuggestions($term, $limit, $min = 1) {
        $sql = "SELECT * 
            FROM {$this->tbl->report_search}
            WHERE search_string LIKE '{$term}%'
                AND search_num >= '{$min}'
            ORDER BY search_num DESC";

        $result = $this->db->SelectLimit($sql, $limit) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            if (preg_match('#\w+\*($|\s)#', $row['search_string'])) {
                continue;
            }
            
            if (preg_match('#(^|\s)[\+\-~]\w+#', $row['search_string'])) {
                continue;
            }
            
            $data[_strtolower($row['search_string'])] = $row['search_num'];
        }
        
        return $data;
    }
    
    
    function getEntryTypeSelectRange() {
        $entry_type = array('article', 'file', 'news', 'user', 'feedback', 'article_draft', 'file_draft');
        
        $data = array();
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        foreach ($entry_type as $type) {
            $k = array_search($type, $this->record_type);
            $data[$k] = $msg[$type];            
        }
        
        return $data;
    }

}
?>