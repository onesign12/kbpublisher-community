<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class ImportModel
{

    var $model;
    var $index = array('pri' => 'PRIMARY', 'uni' => 'UNIQUE', 'mul' => 'INDEX');

    function __construct($model) {
        $this->model = $model;
    }


    function getFields() {
        $sql = "DESCRIBE {$this->model->tbl->table}";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getIndex() {
        $sql = "SHOW INDEX FROM {$this->model->tbl->table}";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        $data = $result->GetArray();

        $_data = array();
        foreach($data as $k => $v) {
            $_data[$v['Column_name']] = $v;
        }

        return $_data;
    }


    function getMySQLVersion($num = 3) {
        $sql = "show variables like 'version'";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        $row = $result->FetchRow();
        $version = $row['Value'];
        $version = preg_replace("#[^\d]#", '', $version);
        $version = (int) substr($version, 0, $num);

        return $version;
    }


    // // As of MySQL 5.0.3, the column list can contain either column names or user variables.
    function isMoreFieldCompatible() {
        // $version = $this->getMySQLVersion();
        // return ($version >= 503);

        // always true if php implementation
        return true;
    }


    function getMoreFields($fields, $data, $filename) {
        return array();
    }


    // php implementation, to avoid load data in file
    // isMoreFieldCompatible should return true
    function getImportSql($fields, $data, $filename, $options) {

        $fields_terminated = stripslashes($options['fields_terminated']);
        $optionally_enclosed = stripslashes($options['optionally_enclosed']);
        $lines_terminated = stripslashes($options['lines_terminated']);
        $lines_terminated = str_replace(array('\n','\r','\t'), array("\n","\r","\t"), $lines_terminated); // to double quoted

        $mfields = array();
        $mvalues = array();
        if($this->isMoreFieldCompatible()) {

            $more_fields = $this->getMoreFields($fields, $data, $filename);
            if($more_fields) {
                foreach($more_fields AS $v) {
                    $v2 = explode('=', $v);
                    $mfields[] = trim($v2[0]);
                    $mvalues[] = trim($v2[1]);
                }
            }
        }
        

        $fdata = array();
        if($fp = @fopen($filename, "rb")) {
            while (($fdata[] = fgetcsv($fp, 0, $fields_terminated, $optionally_enclosed)) !== FALSE);
            fclose($fp);
        } else {
            return AppMsg::afterActionBox('inaccessible_file');
        }
        
        $ins = new MultiInsert;
        $ins->setFields($fields, $mfields);

        $fdata = array_filter($fdata);
        $fdata = array_chunk($fdata, 20);
        
        foreach(array_keys($fdata) as $k) {
        
            $fdata[$k] = RequestDataUtil::stripVars($fdata[$k], array(), 'stripslashes');
            $fdata[$k] = RequestDataUtil::stripVars($fdata[$k], array(), 'addslashes');
            $ins->setValues($fdata[$k], $mvalues);
            $sql = $ins->getSql($this->model->tbl->table, 'INSERT IGNORE');

            $result = $this->model->db->Execute($sql);// or die(db_error($sql, true));
            if(!$result) {
                $sql_error = (_strlen($sql) > 400) ? _substr($sql, 0, 400) . ' ...' : $sql;
                $sql_error = str_replace('),(', "),<br>(", $sql_error);
                return DbUtil::getError($this->model->db->ErrorNo(), $this->model->db->ErrorMsg(), $sql_error);
            }
        }
        
        return true;
    }


    function import($fields, $data, $filename, $options) {
        return $this->getImportSql($fields, $data, $filename, $options);
    }


    // $filesize in KB, 1020kb = 10mb
    function upload($filesize = 10240) {

        $upload = new Uploader;
        $upload->store_in_db = false; // we move file
        //$upload->setAllowedType('text/plain');
        $upload->setAllowedExtension('txt', 'csv');
        //$upload->setDeniedExtension();

        $size_allowed = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
        $size_max = ($size_allowed < $filesize) ? $size_allowed : $filesize;
        $upload->setMaxSize($size_max);

        $upload->setUploadedDir(APP_CACHE_DIR);

        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        } else{
            $f['filename'] = APP_CACHE_DIR . $f['good'][1]['name'];
        }

        return $f;
    }
}


/*
LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
    [REPLACE | IGNORE]
    INTO TABLE tbl_name
    [CHARACTER SET charset_name]
    [FIELDS
        [TERMINATED BY 'string']
        [[OPTIONALLY] ENCLOSED BY 'char']
        [ESCAPED BY 'char']
    ]
    [LINES
        [STARTING BY 'string']
        [TERMINATED BY 'string']
    ]
    [IGNORE number LINES]
    [(col_name_or_user_var,...)]
    [SET col_name = expr,...]
*/
?>