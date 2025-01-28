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


class KBGlossaryModel extends AppModel
{
    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'glossary', 'glossary');
    
    var $entry_type = 21;
    
    const HIGHTLIGHT_BIT = 3; // 0,1,2
    const CASE_BIT = 4;


    function getRecordsSql() { 
        $hbit = KBGlossaryModel::HIGHTLIGHT_BIT;
        $cbit = KBGlossaryModel::CASE_BIT;
        $sql = "SELECT *, 
            display_once & {$hbit} as 'highlight', 
            display_once & {$cbit} as 'case'
            FROM {$this->tbl->table} e
            WHERE {$this->sql_params}
        {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
     
     
    function getByIdSql($record_id) {
        $hbit = KBGlossaryModel::HIGHTLIGHT_BIT;
        $cbit = KBGlossaryModel::CASE_BIT;
        $sql = "SELECT *, 
            display_once & {$hbit} as 'highlight', 
            display_once & {$cbit} as 'case'
            FROM {$this->tbl->table} 
            WHERE {$this->sql_params} AND {$this->id_field} = %d";
        $sql = sprintf($sql, $record_id);
        return $sql;
    } 
        
    
    function &getAltPhrases($ids) {
        $data = array();
        $sql = "SELECT * FROM {$this->tbl->table}_alt WHERE glossary_id IN ($ids)";        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        while($row = $result->FetchRow()){
            $data[$row['glossary_id']][] = $row['phrase'];
        }
        
        return $data;
    }
    
    
    function &getGlossaryLettersResult() {
        $sql = "SELECT phrase FROM {$this->tbl->glossary}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;        
    }
    
    
    // if check priv is different for model so reassign 
    function checkPriv(&$priv, $action, $record_id = false, $bulk_action = false) {
        
        $priv->setCustomAction('preview', 'select');
        
        // bulk will be first checked for update access
        // later we probably need to change it
        // for now it works ok as we do not allow bulk without full update access
        if($action == 'bulk') {
            $bulk_manager = new KBGlossaryModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
        
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }

        $priv->check($action);
    }
    
    
    function isPhraseExisting($phrase, $id = false) {
        $cond = ($id) ? "id != '$id'" : "1=1";
        
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE phrase = '$phrase' AND $cond";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }
    
    
    function getHighlightRange() {
        $range_map = array('all' => 0, 'once' => 1, 'skip' => 2);
        $range_msg = AppMsg::getMsg('ranges_msg.ini', false, 'glossary_highlight');        
        
        $range = array();
        foreach($range_map as $k => $v) {
            $range[$v] = $range_msg[$k];
        }
        
        return $range;
    }
    
    
    // ACTIONS // ---------------------
    

    
    // DELETE RELATED // ---------------------


}
?>