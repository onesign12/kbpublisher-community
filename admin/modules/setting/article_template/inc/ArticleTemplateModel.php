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


class ArticleTemplateModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'article_template', 'article_template');
    
    var $entry_type;
    var $entry_types = array('knowledgebase' => 1);
    
    
    function setEntryType($module) {
        if (isset($this->entry_types[$module])) {
            $this->entry_type = $this->entry_types[$module];
        }
    }
    

    function getRecordsSql() {
        $sql = "SELECT e.*, {$this->sql_params_select}
        FROM {$this->tbl->table} e
        WHERE 1
        AND entry_type = '{$this->entry_type}'
        AND {$this->sql_params}
        {$this->sql_params_order}";
        return $sql;
    }


    function getArticleTemplateActiveList() {
        $sql = "SELECT e.id, e.title, e.description, e.body, e.is_widget
        FROM {$this->tbl->article_template} e
        WHERE e.active = 1
        AND entry_type = '{$this->entry_type}'
        ORDER BY e.title";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();                
    }
    

    function isTmplKeyExists($tmpl_key, $id = false) {
        $cond = ($id) ? "id != '$id'" : "1=1";
        
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE tmpl_key = '$tmpl_key' AND $cond";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }  
    
}
?>