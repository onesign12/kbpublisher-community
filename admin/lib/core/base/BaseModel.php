<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class BaseModel extends BaseApp
{
    
    var $tbl_pref_custom;
    var $tbl_pref;    
    var $tables = array();
    var $custom_tables = array();
    var $db;
    var $limit;
    var $tbl;
    var $extra;
    var $priv;
    
    var $sql_params = '1';                       // will be added to select rows
    var $sql_params_ar = array();
    var $sql_params_default = '1';
    
    var $sql_params_select = '1';        
    var $sql_params_select_ar = array();
    
    var $sql_params_from = '';               
    var $sql_params_from_ar = array();
    
    var $sql_params_join = '';               
    var $sql_params_join_ar = array();
    
    var $sql_params_order = ''; 
    var $sql_params_group = ''; 
    
    var $record_type = array(
        1 => 'article', 2 => 'file', 3 => 'news',
        5 => 'attachment', // special type just to use for sphinx
        7 => 'article_draft', 8 => 'file_draft', 
        10 => 'user', 20 => 'feedback', 21 => 'glossary',
        22 => 'tag', 30 => 'email', 31 => 'comment',
        32 => 'rating_feedback');

    var $record_type_to_table = array(
        1 => 'kb_entry', 2 => 'file_entry', 3 => 'news', 
        10 => 'user', 20 => 'feedback');

    var $record_type_to_category_type = array(
        1 => 11, 2 => 12);

    var $category_type_to_table = array(
        11 => 'kb_category', 12 => 'file_category');
    
    var $category_type_to_etoc_table = array(
        11 => 'kb_entry_to_category', 12 => 'file_entry_to_category');

    var $no_private_priv = array(1); // for this priv private entry does not matter

    // nums in private field in tables
    // 1 = read, 2 = write, 4 = unlisted
    var $private_rule = array();
    
    static $private_rule_st = array(
        // 'read'  => array(1,3), 
        // 'write' => array(1,2)
        // 'read'  => array(1,3,7,9), 
        // 'write' => array(1,2,6,9),
        // 'list'  => array(4,6,7,9)
        'read'  => 1, 
        'write' => 2,
        'list'  => 4
    );

    var $entry_type_to_url = array(
        1 => array('knowledgebase', 'kb_entry'), 
        2 => array('file', 'file_entry'), 
        3 => array('news', 'news_entry'), 
        7 => array('knowledgebase', 'kb_draft'),
        8 => array('file', 'file_draft'), 
        10 => array('users', 'user'), 
        20 => array('feedback', 'feedback')
    );
    
    // view in public 
    // var $entry_type_to_view = array(
    static $entry_type_to_view = array(
        1 => 'entry', 
        2 => 'file', 
        3 => 'news'
    );
    
    var $entry_task_rules = array(
        1 => 'update_index_body',
        2 => 'update_meta_keywords', // on upgrade to 5.0, import 
        3 => 'sync_meta_keywords',    // when updated in tags, sync in meta_keywords
        4 => 'sphinx_restart',
        5 => 'sphinx_index',
        6 => 'sphinx_stop',
        7 => 'sphinx_files',
        // 8 => 's3_file_entry' // added links to amazon files, need to parse data ? not used now
    );
    
    // here is list of taken rule it for upgrade purposes
    // 101 => 'history_upgrade'  // taken by history upgrade to v7.0
    

    function __construct() {

        $reg =& Registry::instance();
        $this->db         =& $reg->getEntry('db');
        $this->tbl_pref   =& $reg->getEntry('tbl_pref');
        $this->limit      =& $reg->getEntry('limit');
        $this->extra      =& $reg->getEntry('extra');
        $this->priv       =& $reg->getEntry('priv');
        
        $this->tbl = $this->setTableNames();
        $this->private_rule = self::$private_rule_st;
    }
    
    
    function setTableNames() {
        $t = array();
        $t1 = $this->_setTableNames($this->tables, $this->tbl_pref, $this->tbl_pref_custom);
        $t2 = $this->_setTableNames($this->custom_tables, $this->tbl_pref);
        
        return (object) array_merge($t1, $t2);
    }
    
    
    static function _setTableNames($tables, $tbl_pref = '', $tbl_pref_custom = '') {
        $tbl =  array(); 
        foreach($tables as $k => $v) {
            $t = (!is_int($k)) ? $k : $v;
            $tbl[$t] = $tbl_pref . $tbl_pref_custom . $v;
        }
        
        return $tbl;
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParams($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_ar = array(); }
        
        $this->sql_params_ar['default'] = $this->sql_params_default;
        $this->sql_params_ar[$key] = $params;
        $this->sql_params = implode(' ', $this->sql_params_ar);
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParamsSelect($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_select_ar = array(); }
        
        $this->sql_params_select_ar['default'] = 1;
        if($params) {
            $this->sql_params_select_ar[$key] = $params;
        }
        
        $this->sql_params_select = implode(', ', $this->sql_params_select_ar);
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParamsFrom($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_from_ar = array(); }
        
        //$this->sql_params_from_ar['default'] = '';
        $this->sql_params_from_ar[$key] = $params;
        $this->sql_params_from = implode(', ', $this->sql_params_from_ar);
    }    
    
    
    // set diffrent sql params used when list records
    function setSqlParamsJoin($params, $key = null) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        
        //$this->sql_params_from_ar['default'] = '';
        $this->sql_params_join_ar[$key] = $params;
        $this->sql_params_join = implode("\n", $this->sql_params_join_ar);
    }    
    
    
    function setSqlParamsOrder($val) {
        $this->sql_params_order = $val;
    }
    
        
    function setSqlParamsGroup($val) {
        $this->sql_params_group = $val;
    }
    
        
    function getValuesArray($data, $id_field = 'id', $unique = false) {
        $ids = array();
        foreach($data as $k => $v) {
            $ids[] = $v[$id_field];
        }
        
        return ($unique) ? array_unique($ids) : $ids;
    }
    
    
    function getValuesString($data, $id_field = 'id', $unique = false) {
        return implode(',', $this->getValuesArray($data, $id_field, $unique));
    }

    
    function isExtra($module) {
        return (!empty($this->extra[$module]));
    }
    
    
    function getExplainQuery($db, $sql) {
        require_once 'vendor/adodb/adodb-php/tohtml.inc.php';
        $sql = 'EXPLAIN ' . $sql;
        $result = $db->Execute($sql);
        
        $ret = "<pre>" . print_r($sql, 1) . "</pre>";    
        $ret .= rs2html($result,'border=2 cellpadding=3'); 
        
        return $ret;
    }
    
    
    function getNow() {
        return date('Y-m-d H:i:s');
    }
    
    
    function getCurdate() {
        return date('Y-m-d');
    }
    
    
    function getCurtime() {
        return date('H:i:s');
    }
    
    
    // EXPORT // --------------------------
    
    static function getExportTool($tool = null) {
        $ret = false;
    
        // 06-06-2024 htmldoc was disabled but we keep code, need to test if enable
        // $tools = ['wkhtmltopdf', 'htmldoc'];
        // $tools = ($tool === null) ? $tools : [$tool];
        $tools = ['wkhtmltopdf'];
        
        foreach($tools as $v) {
            $key = ($tool == 'wkhtmltopdf') ? 'plugin_wkhtmltopdf_path' : 'plugin_htmldoc_path';
            $tool = SettingModel::getQuick(140, $key);
            $ret = (strtolower($tool) != 'off') ? $tool : false;
        }
        // var_dump($ret);
        // var_dump(strtolower($tool));
        return $ret;             
    }
    
    
    // CLOUD // --------------------------
    
    static function isCloud() {
        $ret = false;
        if(defined('KBP_CLOUD')) {
            $ret = (KBP_CLOUD);
        }
        
        // $ret = true;
        return $ret;
    }
    
    
    static function getCloudHideTabs() {
        $hide_tabs_cloud = array(
            'licence_setting',
            'file_bulk',
            'file_rule',
            'rauth_setting',
            'sphinx_setting', // in 6.0 only
            'sphinx_log'      // in 6.0 only
        );
        
        return $hide_tabs_cloud;
    }
    
    
    static function getCloudHideSettings() {
        $keys = array(
            'html_editor_upload_dir', 'cache_dir', 'file_dir', 
            'file_extract_pdf', 'file_extract_doc', 'file_extract_doc2',
            'directory_missed_file_policy',
            'cron_mail_critical',
            'mass_mail_send_per_hour',
            // 'remote_auth_script', 'remote_auth_script_path',
            'remote_auth_auto', 'remote_auth_auto_script_path',
            'plugin_htmldoc_path', 'plugin_wkhtmltopdf_path',
            'sphinx_host', 'sphinx_port', 'sphinx_bin_path', 'sphinx_data_path'
            );
            
        return $keys;
    }
    
    
    static function getCloudPluginKeys() {
        $keys = array(
            'license_key4' // copyright 
            );
            
        return $keys;
    }
    
    
    static function getCloudSkipDeafaults() {
        $keys = BaseModel::getCloudHideSettings();
        $keys = array_merge($keys, BaseModel::getCloudPluginKeys());
        return $keys;
    }
}
?>