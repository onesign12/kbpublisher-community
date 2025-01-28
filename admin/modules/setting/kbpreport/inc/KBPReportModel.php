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


class KBPReportModel extends AppModel
{

    var $tables = array('table' => 'stuff_data', 'log_cron');
    var $setting = array();
        
    var $code = array('error', 'passed', 'disabled', 'skipped');
    var $icon = array('error_red', 'check_green', 'minus', false);
    
    var $items = array(
        'dir' => array(
            'file_dir',
            'cache_dir',
            'html_editor_upload_dir'
        ),
        'extract' => array(
            'xpdf',
            'catdoc',
            'antiword',
            'zip'
        ),
        'export' => array(
            'wkhtmltopdf',
            // 'htmldoc'
        ),
        'search' => array(
            'spell',
            'sphinx'
        ),
        'other' => array(
            'cron',
            'curl'
        )
    );
    
    var $data_key = 'setup_report';
    
    
    function __construct() {
        parent::__construct();
        
        $this->sm = new SettingModel;
        $this->setting = $this->sm->getSettings('1, 2, 134, 140, 141');
        
        $pluginable = AppPlugin::getPluginsFiltered('setup', true);
        foreach ($pluginable as $sgroup => $plugin) {
            if(!AppPlugin::isPlugin($plugin)) {
                unset($this->items[$sgroup]);
            }
        }
    }
        

    function isCronExecuted($minutes, $magic) {
        $sql = "SELECT date_finished FROM {$this->tbl->log_cron} 
        WHERE magic = %d AND date_finished > SUBDATE(NOW(), INTERVAL %d MINUTE)";
        $sql = sprintf($sql, $magic, $minutes);
        $result = $this->db->SelectLimit($sql, 1);
        
        if(!$result) {            
            return $this->db_error2($sql);
        }
        
        return $result->Fields('date_finished');
    }


    function getFirstCronExecution() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_started)) AS 'min' FROM {$this->tbl->log_cron}";
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }        

        return $result->Fields('min');
    }
    
    
    function getReport() {
        $sql = "SELECT * FROM {$this->tbl->table} WHERE data_key = '%s'";
        $sql = sprintf($sql, $this->data_key);
        $result = $this->db->Execute($sql);        
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        // ! ATTENTION FetchRow RETURNS FALSE IF NO RECORDS FOUND 
        return ($ret = $result->FetchRow()) ? $ret : array();
    }


    function isReport() {
        $sql = "SELECT id FROM {$this->tbl->table} WHERE data_key = '%s'";
        $sql = sprintf($sql, $this->data_key);
        $result = $this->db->Execute($sql);        
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result->Fields('id');
    }    
    
    
    function saveReport($data_string) {
        $is_report = $this->isReport();
        if($is_report === false) {
            return false;
        }        
        
        if($is_report) {
            $ret = $this->updateReport($data_string);
        } else {
            $ret = $this->addReport($data_string);
        }
        
        return $ret;
    }
    
    
    function updateReport($data_string) {
        $sql = "UPDATE {$this->tbl->table}
            SET data_string = '%s', date_posted = NOW()
            WHERE data_key = '%s'";
        $sql = sprintf($sql, $data_string, $this->data_key);
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
    
    function addReport($data_string) {
        $sql = "INSERT {$this->tbl->table} VALUES (NULL, '%s', NOW(), '%s')";
        $sql = sprintf($sql, $this->data_key, $data_string);
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
}
?>