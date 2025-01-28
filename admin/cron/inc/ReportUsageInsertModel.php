<?php

class ReportUsageInsertModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('kb_entry', 'file_entry', 'news', 
                        'user', 'kb_comment', 'feedback', 'entry_hits',
                        'report_summary');
                        
    var $ru_model; // ReportUsageModel


    function setReportUsageModel() {
        $this->ru_model = new ReportUsageModel();
    }


    function GetEntryHits($entry_type) {
        $table = $this->record_type_to_table[$entry_type];
        $field = ($table == 'file_entry' ? 'downloads' : 'hits');

        $sql = "SELECT e.id AS entry_id, e.{$field} AS hits,
                h.entry_id AS h_entry_id, h.hits AS h_hits
            FROM {$this->tbl->$table} e, {$this->tbl->entry_hits} h
            WHERE e.id = h.entry_id
                AND h.entry_type = {$entry_type} 
                AND e.{$field} != h.hits";

        $result = $this->db->Execute($sql);
        if ($result === false) {
            trigger_error('Cannot get entries. ' . $this->db->ErrorMsg());
        }

        return $result;
    }


    function UpdateHits($entry_type, $entry_id, $hits) {
        $table = $this->record_type_to_table[$entry_type];
        $field = ($table == 'file_entry' ? 'downloads' : 'hits');

        $sql = "UPDATE {$this->tbl->$table}
            SET $field = %d, date_updated = date_updated
            WHERE id = %d LIMIT 1";
        $sql = sprintf($sql, $hits, $entry_id);

        $result = $this->db->Execute($sql);
        if ($result === false) {
            trigger_error('Cannot update hits. ' . $this->db->ErrorMsg());
        }

        return $result;
    }


    /**
     * @param int $report_id WARNING: If NULL - get ANY report on this day!
     */
    function getReportRecord($report_id, $day /* Y-m-d */) {
        $report_w = '';
        if (!is_null($report_id)) {
            $report_w = "report_id = ". intval($report_id) ." AND";
        }
        $sql = "SELECT * FROM {$this->tbl->report_summary} WHERE %s date_day = %s";
        $sql = sprintf($sql, $report_w, $this->db->Quote($day));
        $result =& $this->db->GetRow($sql); // do not die!
        return $result;
    }


    function insertReportRecord($report_id, $timestamp, $value, $prev) {
        $sql = "INSERT INTO {$this->tbl->report_summary} (report_id, date_day, date_year, date_month, value_int, prev_int)
            VALUES (%d, '%s', %u, %u, %d, %d)";
        $sql = sprintf($sql, $report_id,
            date('Y-m-d', $timestamp),
            date('Y', $timestamp),
            date('Ym', $timestamp),
            $value, $prev);
        if (!$this->db->Execute($sql)) {
            trigger_error('Cannot insert report record. ' . $this->db->ErrorMsg());
            return false;
        }
        return true;
    }


    /**
     * return int ID of previous report of this type (report_id)
     */
    function getReportPrev($report_id) {
        $sql = "SELECT prev_int
            FROM {$this->tbl->report_summary}
            WHERE report_id = %u
            ORDER BY date_day DESC";
        $sql = sprintf($sql, $report_id);
        $result =& $this->db->GetOne($sql); // do not die!
        return $result;    // false if there is no record
    }


    function _insertReportByDate($timestamp, $report_type, $table, $date_field) {
        $res = true;
        $report_id = array_search($report_type, $this->ru_model->report_type);
        $extra_params = 1;

        $sql = "SELECT 
            COUNT(*) AS cnt FROM {$table} 
            WHERE $date_field BETWEEN '%s' AND '%s'";
        
        $from = date('Y-m-d 00:00:00', $timestamp);
        $to = date('Y-m-d 23:59:59', $timestamp);
        $sql = sprintf($sql, $from, $to);
        
        $result =& $this->db->GetOne($sql);
        if ($result === false) {
            trigger_error("Cannot get data for $report_type report.");
            $res = false;
        } else {
            $res = $this->insertReportRecord($report_id, $timestamp, $result, 0);
        }

        return $res;
    }


    function _insertReportByDelta($timestamp, $report_type, $entry_type) {
        $res = true;
        $report_id = array_search($report_type, $this->ru_model->report_type);

        $cur = 0;
        $sql = "SELECT SUM(hits) AS total
            FROM {$this->tbl->entry_hits}
            WHERE entry_type = '{$entry_type}'";

        $result =& $this->db->GetOne($sql);
        if ($result !== false) {
            $cur = $result;
        }

        $delta = 0;    // set 0 for the very first report record
        $prev = $this->getReportPrev($report_id);

        if ($prev !== false) {
            $delta = $cur - $prev;
            if ($delta < 0) {
                // in 8.0 added an option to reset views so previos value $delta could be lower 
                // then current one, now we do not show error.
                // trigger_error("$report_type report is not valid today (values are corrupted).");
                // $res = false;
                $delta = 0;    // let's continue reporting :)
            }
        }

        $res = ($this->insertReportRecord($report_id, $timestamp, $delta, $cur) && $res);

        return $res;
    }


    function insertArticleHitReport($timestamp) {
        return $this->_insertReportByDelta($timestamp, 'article_hit', 1);
    }


    function insertFileHitReport($timestamp) {
        return $this->_insertReportByDelta($timestamp, 'file_hit', 2);
    }


    function insertNewsHitReport($timestamp) {
        return $this->_insertReportByDelta($timestamp, 'news_hit', 3);
    }


    function insertLoginReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'login', $this->tbl->user, 'FROM_UNIXTIME(lastauth)');
    }


    function insertRegistrationReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'registration', $this->tbl->user, 'date_registered');
    }


    function insertCommentReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'comment', $this->tbl->kb_comment, 'date_posted');
    }


    function insertFeedbackReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'feedback', $this->tbl->feedback, 'date_posted');
    }


    function insertArticleNewReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'article_new', $this->tbl->kb_entry, 'date_posted');
    }


    function insertFileNewReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'file_new', $this->tbl->file_entry, 'date_posted');
    }


    function insertArticleUpdatedReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'article_updated', $this->tbl->kb_entry, 'date_updated');
    }


    function insertFileUpdatedReport($timestamp) {
        return $this->_insertReportByDate($timestamp, 'file_updated', $this->tbl->file_entry, 'date_updated');
    }
}

?>