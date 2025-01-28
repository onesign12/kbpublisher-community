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

// How files history works:
// When new file created no history records created
// on firts update 2 revisions added to history table 
// latest revision always link to live file, 
// 'archived' = null in file_history table, means live file 
// previos revision files moved to history/[entry_id] subdircetory 
// and in 'archived' = path_to_file in file_history table
// it is save to delete history file if 'archived' is not null


class FileEntryHistoryModel extends KBEntryHistoryModel
{

    var $tbl_pref_custom = 'file_';
    // var $tables = array('table'=>'entry_history', 'entry_history', 'entry');
    
    var $file_dir;
    var $arc_dir;

    // keep for history;
    var $fields_history = array(
        'directory', 'sub_directory', 'filename', 'filename_disk',
        'filesize', 'filetype', 'md5hash', 'addtype', 'filetext'
    );    
    
    var $fields_compare = array(
        'md5hash'
    );


    function setFileDirs($dir) {
        $this->file_dir = $dir;
        $this->arc_dir = $dir . 'history/';
    }
    

    function stripvars(&$data, $server = 'addslashes') {
        $rd = new RequestData($data);
        $rd->stripVars($server);

        return $data;
    }
        

    // FILE MANIPULATION // 
    
    function isRevisionFileLive($entry_id, $edata) {
        $ecompare = serialize($this->getHistoryFields($edata));
        
        $sql = "SELECT * FROM {$this->tbl->entry_history}
        WHERE archived is NULL AND entry_data = '%s'";
        $sql = sprintf($sql, $ecompare);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    
    function moveRevisionFile($rev_data, $manager) {
        $move = ['status' => true];// success
        
        foreach($rev_data as $k => $v) {
            $edata = unserialize($v['entry_data']);
            if($edata['addtype'] != 1) { // not local
                continue;
            }
            
            // for articles in $rev_data [new, old] or [new] 
            // for files we always have 2 values in $rev_data [new, old] or [new, file] 
            // key = old, first update, no revisions yet, move "old" file to history
            // key = file, next update, move "old" file to history, mark record as archived
            if($k != 'new') {
        
                $entry_id = $v['entry_id'];
                $fname = isset($edata['filename_disk']) ? $edata['filename_disk'] : $edata['filename'];
                $move_file = $edata['directory'] . $fname; 
                $arc_file = $this->arc_dir . sprintf('%d/%s', $entry_id, $fname);
        
                $move = $manager->moveFile($move_file, $arc_file);
                if($move['status']) {
                    $this->updateFilePath($entry_id, $v['revision_num'], $move['new_file']);
                } else {
                    $move['error_msg'] = "Revision for file id {$entry_id} created but exit with status: " . $move['error_msg'];
                    Logger::log($move['error_msg']);
                }
            }
        }
        
        return $move;
    }
    
    
    function deleteRevisionFile($entry_id, $rev_data) {
        $ret = true;
        if($rev_data['archived'] && $rev_data['addtype'] == 1) { // local and archived
            $file = $rev_data['archived'];
            if(file_exists($file)) {
                @$ret = unlink($file);
                if(!$ret) {
                    $error_msg = "Revision for file {$entry_id} deleted but exit with status: Unable to delete file {$file}";
                    Logger::log($error_msg);
                }
            }
        }
        
        return $ret;
    }
    
    
    // DELETE RELATED //     
    
    function removeExtraRevisions($entry_id, $allowed_rev) {
        $revisions = parent::removeExtraRevisions($entry_id, $allowed_rev);
        foreach($revisions as $k => $data) {
            $edata = unserialize($data['entry_data']);
            $this->deleteRevisionFile($entry_id, $edata);
        }
    }    
    
    
    function deleteRevision($entry_id, $revision_num) {
        $edata = $this->getVersionData($entry_id, $revision_num);
        parent::deleteRevision($entry_id, $revision_num);
        $this->deleteRevisionFile($entry_id, $edata);
    }
    
    
    function deleteRevisionAll($entry_id) {
        $ret = parent::deleteRevisionAll($entry_id);
        if($ret) {
            $arc_dir = $this->arc_dir . $entry_id;
            if(is_dir($arc_dir)) {
                foreach(glob("{$arc_dir}/*") as $file) {
                    @$ret = unlink($file);
                }
                
                if(!$ret) {
                    $error_msg = "All revisions for file {$entry_id} deleted but exit with status: Unable to delete some files in directory {$arc_dir}";
                    
                    if($this->error_die) { // in ui
                        Logger::log($error_msg);
                    } else { // in cron
                        trigger_error($error_msg);
                    }
                }
                
                @rmdir($arc_dir);
            }
        }
        
        return $ret;
    }
    
    
    function updateFilePath($entry_id, $revision_num, $file) {
        $sql = "UPDATE {$this->tbl->entry_history} SET archived = '%s'
        WHERE entry_id = %d AND revision_num = %d";
        $sql = sprintf($sql, $file, $entry_id, $revision_num);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // DOWNLOAD // -------------------------
    
    function sendFileDownload($left_rev, $right_rev) {

        $filename_archive = 'history_%d_rev_%d-%d.zip';
        $filename_archive = sprintf($filename_archive, $left_rev['entry_id'], $right_rev['revision_num'], $left_rev['revision_num']);

        $zip = new ZipArchive();

        $zip_file = APP_CACHE_DIR . $filename_archive;
        if ($zip->open($zip_file, ZIPARCHIVE::CREATE) !== true) {
            echo 'ERROR create zip archive!';
            exit;
        }
        
        $dir = new MyDir();
        
        $filename_str = '%s_rev%d.%s';
        foreach([$left_rev, $right_rev] as $k => $v) {
            $ext = $dir->getFileExtension($v['fdata']['filename']);
            $filename =  sprintf($filename_str, 
                basename($v['fdata']['filename'], '.'.$ext), $v['revision_num'], $ext);
            $zip->addFile($v['fdata']['file'], $filename);
        }

        $zip->close();

        $params['data'] = file_get_contents($zip_file);
        $params['gzip'] = false;
        $params['contenttype'] = 'application/zip';

        @unlink($zip_file);

        return WebUtil::sendFile($params, $filename_archive);
    }

}
?>