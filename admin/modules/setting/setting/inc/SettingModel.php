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


class SettingModel extends AppModel
{

    var $tables = array('table'=>'setting', 'setting', 
                        'setting_to_value',
                        'setting_to_value_user',
                        'priv', 'priv_module');
    
    var $module_id;
    var $module_name;
    var $wizard_group_id;
    var $array_delim = ',';
    var $parser;
    var $user_id = 0;
    var $separate_form = false;
    
    var $setting_input = array(
        0 => false,
        1 => 'select',
        2 => 'text',
        3 => 'textarea',
        4 => 'checkbox',
        5 => 'password',
        6 => 'text_btn',
        7 => 'hidden_btn',
        8 => 'checkbox_btn',
        9 => 'info',
        10 => 'double_checkbox',
        11 => 'button'
    );
    
    var $module_names = array(
        1 => 'admin_setting',
        2 => 'public_setting',
        // 3 => 'plugin_setting',
        140 => 'export_setting',
        141 => 'sphinx_setting'
    );
    
    var $wizard_groups = array(
        1 => 'admin',
        2 => 'public',
        3 => 'email',
        4 => 'view',
        5 => 'test'
    );
    
    // var $wizard_groups_module_names = array(
    //     1 => 'admin',
    //     2 => 'public',
    //     3 => 'email',
    //     4 => 'view',
    //     5 => 'test'
    // );
    
    static $key_to_module = array(
        'kbc_setting'    => 'public_setting',
        'kba_setting'    => 'knowledgebase',
        'kbf_setting'    => 'file',
        'export_setting' => 'export_setting',
        'sphinx_setting' => 'sphinx_setting',
        'ldap_setting'   => 'ldap_setting',
        'saml_setting'   => 'saml_setting',
        'rauth_setting'  => 'rauth_setting',
        'sauth_setting'  => 'sauth_setting',
        // 'plugin_setting' => 'plugin_setting',
    );
    
    
    static function getQuick($module_id, $setting_key = false, $ignore_parser = false) {
        static $m;
        if(!$m) {
            $m = new SettingModel();
        }
        
        return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }
    
    
    static function getQuickCron($module_id, $setting_key = false, $ignore_parser = false) {
        static $m;
        if(!$m) {
            $m = new SettingModel();
        }
        
        $m->error_die = false;
        return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }
    
    
    static function &getQuickUser($user_id, $module_id, $setting_key = false, $ignore_parser = false, $options = array()) {
        static $m;
        if(!$m) {
            $m = new SettingModel();
        }
        
        // when we have different module_id and user_module_id
        $user_module_id = $module_id;
        if(isset($options['user_module_id'])) {
            $user_module_id = $options['user_module_id'];
        }
            
        $setting = &$m->getSettings($module_id, $setting_key, $ignore_parser);
        $user_setting = &$m->getSettingsUser($user_id, $user_module_id, $setting_key, $ignore_parser);
        
        if($setting_key) {
            if($user_setting) {
                $setting = $user_setting;
            }
        } else {
            $setting = array_merge($setting, $user_setting);
        }
        
        return $setting;
    }        


    function _getSettingKeyParams($setting_key = false) {
        $key_param = 1;
        if($setting_key) {
            $setting_key = (!is_array($setting_key)) ? array($setting_key) : $setting_key;
            $keys = implode("','", $setting_key);
            $key_param = "s.setting_key IN('{$keys}')";
        }
        
        return $key_param;
    }
    
    
    function &getSettingsUser($user_id, $module_id, $setting_key, $ignore_parser = false) {
        
        $key_param = $this->_getSettingKeyParams($setting_key);
        
        $sql = "
        SELECT 
            s.setting_key,
            s.input_id,
            sv.setting_value AS value
        FROM 
            ({$this->tbl->setting} s,
            {$this->tbl->setting_to_value_user} sv)
            
        WHERE 1
            AND $key_param
            AND s.id = sv.setting_id 
            AND sv.user_id = %d
            AND s.active = 1 
            AND s.user_module_id IN(%s)";
            
        $data = array();
        $module_id = ($module_id !== false) ? $module_id : $this->module_id;
        $module_id = (is_array($module_id)) ? implode(',', $module_id) : $module_id;
        
        $sql = sprintf($sql, $user_id, $module_id);
        // echo '<pre>', print_r($this->getExplainQuery($this->db, $sql), 1), '</pre>';
        
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        $rows = $result->GetArray();
        $parser = $this->getParser($ignore_parser);

        foreach($rows as $k => $v) {
            $value = $parser->parseSettingOut($v['setting_key'], $v['value']);
            $data[$v['setting_key']] = $parser->parseReplacements($value);
            unset($rows[$k]);
        }    
        
        $data = ($setting_key && !is_array($setting_key)) ? @$data[$setting_key] : $data;

        return $data;
    }
    
    
    function getSettingsSql($setting_key) {

        $key_param = $this->_getSettingKeyParams($setting_key);
        
        $sql = "
        SELECT 
            s.setting_key,
            s.input_id,
            IFNULL(sv.setting_value, s.default_value) AS value
        FROM 
            {$this->tbl->setting} s
        LEFT JOIN 
            {$this->tbl->setting_to_value} sv ON s.id = sv.setting_id
            
        WHERE 1
            AND $key_param
            AND s.active = 1 
            AND s.module_id IN(%s)";
            
        return $sql;
    }
    
    
    function &getSettings($module_id = false, $setting_key = false, $ignore_parser = false) {
        
        $data = array();
        $module_id = ($module_id !== false) ? $module_id : $this->module_id;
        $module_id = (is_array($module_id)) ? implode(',', $module_id) : $module_id;
        
        $sql = $this->getSettingsSql($setting_key);
        $sql = sprintf($sql, $module_id);
        // echo '<pre>', print_r($this->getExplainQuery($this->db, $sql), 1), '</pre>';
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        $rows = $result->GetArray();
        $parser = $this->getParser($ignore_parser);

        foreach($rows as $k => $v) {
            $value = $parser->parseSettingOut($v['setting_key'], $v['value']);
            $data[$v['setting_key']] = $parser->parseReplacements($value);
            unset($rows[$k]);
        }    

        $data = ($setting_key && !is_array($setting_key)) ? @$data[$setting_key] : $data;
        
        return $data;
    }


    function getCommonGroupModules($wizard_group_id) {
        
        $sql = "SELECT DISTINCT(module_id) FROM {$this->tbl->setting} WHERE common_group_id = '%d'";
        $sql = sprintf($sql, $wizard_group_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['module_id'];
        }
        
        return $data;
    }
    
    
    function getRecordsSql() {
        
        $sql = "
        SELECT 
            s.id AS name,
            s.setting_key,
            s.module_id,
            s.messure,
            s.options,
            s.range,
            s.group_id,
            s.required,
            s.default_value AS value,
            s.input_id
        FROM 
            {$this->tbl->setting} s
                
        WHERE s.active = 1 
        AND s.module_id = '%d'
        AND {$this->sql_params}
        ORDER BY s.module_id, s.group_id, s.sort_order";
        
        $sql = sprintf($sql, $this->module_id);
        //echo "<pre>"; print_r($sql); echo "</pre>";
        
        return $sql;
    }
    
    
    function getCommonGroupRecordsSql($wizard_group_id) {
        
        $sql = "
        SELECT 
            s.id AS name,
            s.module_id,
            s.setting_key,    
            s.messure,
            s.options,
            s.range,
            s.group_id,
            s.required,
            s.default_value AS value,
            s.input_id
        FROM 
            {$this->tbl->setting} s
                
        WHERE s.active = 1 
        AND s.common_group_id = '%d'
        ORDER BY s.module_id, s.group_id, s.sort_order";
        
        $sql = sprintf($sql, $wizard_group_id);
        //echo "<pre>"; print_r($sql); echo "</pre>";
        
        return $sql;
    }
    
    
    function &getRecords($limit = -1, $offset = -1) {
        
        $data = array();
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $parser = $this->getParser();
        
        while($row = $result->FetchRow()){
            
            // here we can miss some fields
            if($parser->skipSettingDisplay($row['setting_key'])) {
                continue;
            }
            
            // cloud and plugins
            if($parser->skipSettingDisplayCommon($row['setting_key'])) {
                continue;
            }
            
            $row['input'] = $this->setting_input[$row['input_id']];
            
            if($row['input'] == 'select') {
                $row['value'] = $this->_valueToArray($row['value']);
            }
                        
            if($row['range'] !== '' && $row['range'] != 'dinamic') {
                $row['range'] = $this->_valueToArray($row['range']);
            }
            
            $group_id = $parser->parseGroupId($row['group_id']);
            $data[$group_id][$row['setting_key']] = $row;
        }
        
        ksort($data);
        return $data;
    }


    function getCommonGroupRecords($wizard_group_id) {
        
        $data = array();
        $sql = $this->getCommonGroupRecordsSql($wizard_group_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $parser = $this->getParser();
        
        $i = 1;
        while($row = $result->FetchRow()){
            
            $row['input'] = $this->setting_input[$row['input_id']];
            
            if($row['input'] == 'select') {
                $row['value'] = $this->_valueToArray($row['value']);
            }
                        
            if($row['range'] !== '' && $row['range'] != 'dinamic') {
                $row['range'] = $this->_valueToArray($row['range']);
            }    
            
            $row['sort_order'] = $i;
            
            $data[] = $row;
            
            $i ++;
        }
        
        ksort($data);
        return $data;
    }
    
    
    function getSettingInputTypes() {

        $sql = "SELECT s.id, s.input_id
        FROM {$this->tbl->setting} s
        WHERE s.active = 1 
        AND s.module_id = '%d'";
                
        $data = array();
        $sql = sprintf($sql, $this->module_id);
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
        AND s.module_id = '%d'"; 
		
        $sql = sprintf($sql, $this->module_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return  $result->GetAssoc();
    }
    
    
    function getWizardGroupSettingKeys($wizard_group_id) {
        $sql = "SELECT s.id, s.setting_key, module_id
        FROM {$this->tbl->setting} s
        WHERE s.active = 1 
        AND s.common_group_id = '%d'"; 
        
        $sql = sprintf($sql, $wizard_group_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['module_id']][$row['id']] = $row['setting_key'];
        }
        
        return  $data;
    }
    
    
    function getSettingIdByKey($key) {
        $keys = (is_array($key)) ? $key : array($key);
        
        foreach (array_keys($keys) as $k) {
            $keys[$k] = sprintf('"%s"', $keys[$k]);
        }
        
        $sql = "SELECT setting_key, id 
        FROM {$this->tbl->setting} 
        WHERE setting_key IN (%s)";
        $sql = sprintf($sql, implode(',', $keys));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (is_array($key)) ? $result->GetAssoc() : $result->Fields('id');
    }
    
    
    // function getDefaultValueByKeys($keys) {
    //     $keys = is_array($keys) ? implode(',', $keys) : $keys;
    //     $sql = "SELECT setting_key, default_value
    //     FROM {$this->tbl->setting}
    //     WHERE setting_key IN ('%s')";
    //     $sql = sprintf($sql, $keys);
    //
    //     $result = $this->db->Execute($sql) or die(db_error($sql));
    //     return (is_array($key)) ? $result->GetAssoc() : $result->Fields('default_value');
    // }

    
    function getRangeById($id) {
        $sql = "SELECT `range` FROM {$this->tbl->setting} WHERE id = %d";
        $sql = sprintf($sql, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('range');
    }        
    
    
    function formDataToObj($values) {
        $values1 = array();
        $keys = $this->getSettingKeys();
        foreach($values as $k => $v) {
            $values1[$keys[$k]] = $v;
        }
        
        return $values1;
    }
    
    
    function saveQuery($data) {
        $ins = new MultiInsert;
        $ins->setFields(array('setting_id', 'setting_value'));
        $ins->setValues($data);
        $sql = $ins->getSql($this->tbl->setting_to_value, 'REPLACE');
        
        // echo '<pre>', print_r($data, 1), '</pre>';
        // echo '<pre>', print_r($sql, 1), '</pre>';
        // exit;
        
        $this->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function save($data) {
        
        $input = $this->getSettingInputTypes();
        $keys  = array_flip($this->getSettingKeys());
        
        // handle checkboxex, set 0 value for not checked (not in data)
        foreach($keys as $setting_key => $setting_id) {
            if($input[$setting_id] == 'checkbox' && !isset($data[$setting_key])) {
                $data[$setting_key] = 0;
            }
        }
        
        // handle data
        $data1 = array();
        foreach($data as $setting_id => $v) {
            if($input[$keys[$setting_id]] == 'select') {
                $v = $this->_valueToString($v);
            }
                       
            $data1[$keys[$setting_id]] = array($keys[$setting_id], $v);
        }         
                
        $this->saveQuery($data1);
    }
    
    
    function setSettings($data) {
        
        $data1 = array();
        foreach($data as $setting_id => $setting_value) {
            $data1[] = array($setting_id, $setting_value);
        }
        
        if($data1) {
            $this->saveQuery($data1);            
        }
    }
        
    
    function getDefaultValuesSql($check_skip = true) {
        $sql = "SELECT id AS setting_id, default_value AS setting_value 
        FROM {$this->tbl->setting} WHERE module_id = %d 
        AND active = 1";
        
        if ($check_skip) {
            $sql .= ' AND skip_default = 0';
        }
        
        return $sql;
    }
    
    
    // function setDefaultValues($setting_id = false, $check_skip = true) {
    //     $sql = $this->getDefaultValuesSql($check_skip);
    //     $sql = sprintf($sql, $this->module_id);
    // 
    //     if ($setting_id) {
    //         $sql .= sprintf(' AND id = %d', $setting_id);
    //     }
    // 
    //     $result = $this->db->Execute($sql) or die(db_error($sql));      
    // 
    //     // skip in cloud 
    //     $skip = array();
    //     if(BaseModel::isCloud()) {
    //         $keys = BaseModel::getCloudSkipDeafaults();
    //         $skip = $this->getSettingIdByKey($keys);
    //     }
    // 
    //     $parser = $this->getParser();
    //     $data = array();
    //     foreach($result->GetArray() as $k => $v) {
    // 
    //         // skip cloud values
    //         if(in_array($v['setting_id'], $skip)) {
    //             continue;
    //         }
    // 
    //         $data[$k]['setting_id'] = $v['setting_id'];
    //         $data[$k]['setting_value'] = $parser->parseReplacements($v['setting_value']);
    //     }
    // 
    //     $this->saveQuery($data);
    // }
    
    
    // 20-10-2022 eleontev 
    // changed to delete values so defaults goes from defualt_value
    function setDefaultValues($setting_id = false, $check_skip = true) {
        $sql = "DELETE sv FROM 
            ({$this->tbl->setting} s,
            {$this->tbl->setting_to_value} sv)
        WHERE s.id = sv.setting_id 
        AND s.module_id = %d
        AND %s %s";
        
        // ignore skip_default if for one setting by setting_id
        $setting_sql = ($setting_id) ? sprintf('s.id = %d', $setting_id) 
                                     : 's.skip_default = 0';
        
        // skip in cloud 
        $cloud_sql = '';
        if(BaseModel::isCloud()) {
            $keys = BaseModel::getCloudSkipDeafaults();
            $skip_ids = $this->getSettingIdByKey($keys);
            $cloud_sql = sprintf(' AND s.id NOT IN (%s)', implode(',', $skip_ids));
        }
        
        $sql = sprintf($sql, $this->module_id, $setting_sql, $cloud_sql);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function setModuleId($module_name) {
        $module_id = array_search($module_name, $this->module_names);
        
        if ($module_id) {
            $this->module_id = $module_id;
            
        } else {
            $sql = "SELECT id FROM {$this->tbl->priv_module} WHERE module_name = '%s'";
            $sql = sprintf($sql, $module_name);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
            $this->module_id = $result->Fields('id');
        }
        
        $this->module_name = $module_name;
    }
    
    
    function getModuleName($module_id) {
        if (!empty($this->module_names[$module_id])) {
            return $this->module_names[$module_id];
            
        } else {
            $sql = "SELECT module_name FROM {$this->tbl->priv_module} WHERE id = '%d'";
            $sql = sprintf($sql, $module_id);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
            return $result->Fields('module_name');
        }
    }
    
    
    // set default_value
    function updateDefaultValue($id, $value) {
        $sql = "UPDATE {$this->tbl->setting} 
        SET default_value = '%s' WHERE id = '%d'";
        $sql = sprintf($sql, $value, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // HELPERS // ----------------------------    
    
    function _valueToArray(&$val) {
        
        $val = explode($this->array_delim, $val);
        $new_ar = array();
        
        foreach($val as $v) {
            $new_ar[trim($v)] = trim($v);
        }
        return $new_ar;
    }
    
    
    function _valueToString($val) {
        if(is_array($val)) {
            $val = implode($this->array_delim, $val);
        }
        
        return $val; 
    }

    
    function &getParser($ignore_concrete = false) {
        if(!$this->parser) {
            $this->loadParser($ignore_concrete);
        }
        
        return $this->parser;
    }    
    
    
    function loadParser($ignore_concrete = false, $module_name = false) {
        
        $is_parser = false;
        $is_manager = false;
        
        if (!$module_name) {
            $module_name = $this->module_name;
        }
        
        if(!$ignore_concrete) {
        
            $file[] = APP_MODULE_DIR . $module_name . '/';
            $file[] = APP_PLUGIN_DIR . $module_name . '/';
            $file[] = APP_MODULE_DIR . 'setting/' . $module_name . '/';
            
            foreach($file as $v) {
                $f = $v . 'SettingParser.php';
                if(file_exists($f)) {
                    $is_parser = true;
                    require_once $f;
                    
                    $f = $v . 'SettingParserModel.php';
                    if(file_exists($f)) {
                        $is_manager = true;
                        require_once $f;
                    }
                    
                    break;
                }
            }
        }
        
        $namespace = str_replace('_', '', (string) $module_name);
        $class = ($is_parser) ? $namespace . '\SettingParser' : 'SettingParserCommon';
        
        $this->parser = new $class($this);
        
        $class = ($is_manager) ? $namespace . '\SettingParserModel' : 'SettingParserModelCommon';
        $this->parser->manager =  new $class();
    }
    
    
    function getUploader() {
        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->setUploadedDir(APP_CACHE_DIR);
        
        return $upload;
    }
    
    
    function upload($upload) {
        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
            
        } else{
            $f['filename'] = APP_CACHE_DIR . $f['good'][1]['name'];
        } 
                                   
        return $f;
    }
    
    
    function getSearchableSettings() {
        
        $skip_module_id = array(0, 11, 20); // hidden modules
        
        $sql = "SELECT * FROM {$this->tbl->setting} s
        WHERE s.active = 1 AND s.module_id NOT IN(%s)";
        $sql = sprintf($sql, implode(',', $skip_module_id));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetAssoc();
        
        $data = array();
        foreach($rows as $k => $v) {
            $data[$v['module_id']][$v['group_id']][$k] = $v['setting_key'];
        }    
        
        return $data;
    }
}
?>