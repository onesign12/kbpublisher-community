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

class KBAutosaveModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'entry_autosave');
    var $record_entry_type;


    function __construct() {
        parent::__construct();
        $this->user_id = AuthPriv::getUserId();
    }


    function getByIdKey($id_key) {
        $sql = "SELECT * FROM {$this->tbl->table} WHERE id_key = '%s'";
        $sql = sprintf($sql, $id_key);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    function getRecordsSql() {
        $sql = "
        SELECT
            *
        FROM
            {$this->tbl->table}
        WHERE entry_id = 0
        AND entry_type = '{$this->record_entry_type}'
        AND user_id = '{$this->user_id}'
        {$this->sql_params_order}";

        return $sql;
    }


    function delete($id_key) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id_key = '{$id_key}'";
        return $this->db->_Execute($sql) or die(db_error($sql));
    }

}
?>