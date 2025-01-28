<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class KBClientFileModel extends KBClientModel_common
{
    var $tbl_pref_custom = 'file_';
    
    var $custom_tables = array('feedback',
                               'list_value',
                               'file_entry',
                               'file_entry_to_category',
                               'data_to_value'=>'data_to_user_value',
                               'data_to_value_string'=>'data_to_user_value_string',
                               'glossary'=>'kb_glossary',
                               'user_subscription',
                               'entry_hits',
                               'custom_field',
                               'custom_field_to_category',
                               'custom_field_range_value',
                               'tag',
                               'tag_to_entry',
                               'user',
                               'user_company'
                               );
    
    
    // rules id in data to user rule
    var $role_entry_read_id = 102;
    var $role_entry_write_id = 106;    
    var $role_category_read_id = 2;
    var $role_category_write_id = 6;
    
    var $entry_list_id = 2; // id in list statuses 
    var $entry_type = 2; // entry type in entry_hits, entry_schedule  
    var $entry_type_cat = 12; // entry type for category
    
    var $session_view_name = 'kb_view_file_';
    
    // CATEGORIES
    
    function getCategoriesSql($sort, $type_param = 'all') {
        $sql = "SELECT id, parent_id, name, description, sort_order, sort_public, private
        FROM {$this->tbl->category} c FORCE INDEX ( sort_order )
        WHERE c.active = 1
        ORDER BY {$sort}";
        
        return $sql;
    }
    
    
    function getCategoryType($category_id) {
        return 1;
    }    
    
    
    // FILES // ------------------------    
    
    function _getEntriesSqlSelect() {
        $select = "e.*,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            e_to_cat.sort_order AS real_sort_order";
            
        return $select;
    }
    
    
    function _getEntriesIdsSqlSelect() {
        $select = "
            e.id AS 'id_',
            e.id AS 'id',
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private";
            
        return $select;
    }
    
    
    function getEntryDataByIds($ids) {
        $sql = "SELECT id, title, description, filename, filesize, private, downloads
        FROM {$this->tbl->entry} WHERE id IN ({$ids})
        -- ORDER BY FIELD(id, {$ids})
        ";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    static function getSortOrderArray() {
        
        $sort_arr = array(
            'name'         => 'e.title',
            'filename'     => 'e.filename',
            'sort_order'   => 'e_to_cat.sort_order',
            'added_desc'   => 'e.date_posted DESC',
            'added_asc'    => 'e.date_posted ASC',
            'updated_desc' => 'e.date_updated DESC',
            'updated_asc'  => 'e.date_updated ASC',
            'hits_desc'    => 'e.downloads DESC',
            'hits_asc'     => 'e.downloads ASC'
        );
        
        return $sort_arr;
    }     
}
?>