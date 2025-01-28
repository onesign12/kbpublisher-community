<?php

class SearchSuggestAdminModel extends AppSphinxModel
{
    
    
    function getSearchSuggestions($term, $limit) {
        $this->setSqlParamsFrom($this->idx->admin);
        
        $this->setSqlParamsSelect('source_id as entry_type, title');
        
        $this->setSqlParamsMatch($term . '*');
        $rows = $this->getRecords($limit, 0);
        return $rows;
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