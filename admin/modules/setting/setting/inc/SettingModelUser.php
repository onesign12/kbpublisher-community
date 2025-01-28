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



class SettingModelUser extends SettingModel
{

    function __construct($user_id) {
        parent::__construct();
        $this->user_id = $user_id;
    }


    function getRecordsSql() {

        $sql = "
        SELECT
            s.id AS name,
            s.setting_key,
            s.module_id,
            s.user_module_id,
            s.messure,
            s.options,
            s.range,
            s.group_id,
            s.required,
            s.input_id
        FROM
            {$this->tbl->setting} s

        WHERE s.active = 1
        AND s.user_module_id != 0
        ORDER BY s.module_id, s.group_id, s.sort_order";

        $sql = sprintf($sql, $this->user_id);
        //echo "<pre>"; print_r($sql); echo "</pre>";

        return $sql;
    }


    // this only used in setting module
    function getSettingsSql($setting_key) {

        $sql = "
        SELECT
            s.setting_key,
            s.input_id,
            IFNULL(svu.setting_value, IFNULL(sv.setting_value, s.default_value)) AS value
        FROM
            {$this->tbl->setting} s
        LEFT JOIN {$this->tbl->setting_to_value} sv ON s.id = sv.setting_id
        LEFT JOIN {$this->tbl->setting_to_value_user} svu ON s.id = svu.setting_id
                                                         AND svu.user_id = %d

        WHERE 1
            AND s.active = 1
            AND s.user_module_id != 0";

        $sql = sprintf($sql, $this->user_id);
        //echo "<pre>"; print_r($sql); echo "</pre>";

        return $sql;
    }


    function getSettingInputTypes() {
        $sql = "SELECT s.id, s.input_id
        FROM {$this->tbl->setting} s
        WHERE s.active = 1
        AND s.user_module_id != 0";

        $data = array();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            $data[$row['id']] = $this->setting_input[$row['input_id']];
        }

        return  $data;
    }


    function getSettingKeys() {
        $sql = "SELECT s.id, s.setting_key
        FROM {$this->tbl->setting} s
        WHERE s.active = 1
        AND s.user_module_id != 0";

        $sql = sprintf($sql);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return  $result->GetAssoc();
    }


    // if no setting_id for users we just delete all not hidden settings from setting_to_value_user
    // $check_skip is not required, defined here for compability
    function setDefaultValues($setting_id = false, $check_skip = true) {
        $sql = "DELETE svu
        FROM {$this->tbl->setting} s, {$this->tbl->setting_to_value_user} svu
        WHERE s.id = svu.setting_id
        AND svu.user_id = '%d' 
        AND %s
        AND s.active = 1";
        
        // no setting id reset all not hidden (in user account)
        $setting_sql = ($setting_id) ? sprintf('s.id = %d', $setting_id) 
                                     : 's.user_module_id != 0';        
        $sql = sprintf($sql, $this->user_id, $setting_sql);
                
        $this->db->Execute($sql) or die(db_error($sql));
    }


    function saveQuery($data) {
        $ins = new MultiInsert;
        $ins->setFields(array('setting_id', 'setting_value'), 'user_id');
        $ins->setValues($data, $this->user_id);
        $sql = $ins->getSql($this->tbl->setting_to_value_user, 'REPLACE');

        //echo '<pre>', print_r($data, 1), '</pre>';
        //echo '<pre>', print_r($sql, 1), '</pre>';
        //exit;

        $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>