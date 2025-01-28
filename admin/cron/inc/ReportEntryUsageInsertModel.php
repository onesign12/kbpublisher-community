<?php

class ReportEntryUsageInsertModel extends AppModel
{
    var $tbl_pref_custom = '';
    var $tables = array('kb_entry', 'file_entry', 'news',
                        'user', 'kb_comment', 'feedback',
                        'entry_hits', 'report_entry');

    var $re_model; // ReportEntryModel

    var $chunk_size = 2;


    function __construct() {
        parent::__construct();
        $this->re_model = new ReportEntryModel;
    }


    function getReportRecord($day) {
        $sql = "SELECT * FROM %s WHERE date_day = %s";
        $sql = sprintf($sql, $this->tbl->report_entry, $this->db->Quote($day));
        $result =& $this->db->GetRow($sql);
        return $result;
    }


    function insertReportRecords($report_id, $timestamp, $week_start, $data) {
        $_sql = "INSERT IGNORE {$this->tbl->report_entry}
                 (entry_id, value_int, report_id, date_day, date_year, date_month, date_week) VALUES ?";

        $cal = new CalendarUtil;
        $cal->week_start = $week_start;
        $cal->setCalendar();
        $week_number = $cal->getWeekNumber($timestamp);

        $constant_values = array(
            $report_id,
            date('Y-m-d', $timestamp),
            date('Y', $timestamp),
            date('Ym', $timestamp),
            $week_number
        );

        $chunks = array_chunk($data, $this->chunk_size);

        foreach ($chunks as $chunk) {
            $sql = MultiInsert::get($_sql, $chunk, $constant_values);

            $result = $this->db->Execute($sql);
            if (!$result) {
                trigger_error($this->db->ErrorMsg());
                return false;
            }
        }

        return true;
    }


    function _insertReportByDelta($timestamp, $week_start, $report_type, $entry_type) {
        $res = true;

        foreach ($this->re_model->report_type as $k => $v) {
            if ($v['key'] == $report_type) {
                $report_id = $k;
                $table = $v['table'];
            }
        }

        $day = date('Y-m-d', $timestamp);
        $field = ($table == 'file_entry' ? 'downloads' : 'hits');
        $table = $this->tbl->$table;

        $sql = "SELECT h.entry_id, h.hits, e.{$field} AS prev_hits
                FROM {$this->tbl->entry_hits} h, {$table} e
                WHERE h.entry_id = e.id
                    AND h.entry_type = {$entry_type}
                    AND h.date_hit BETWEEN '{$day} 00:00:00' AND '{$day} 23:59:59'";

        $result =& $this->db->GetAssoc($sql);
        if ($result !== false) {
            $entries = $result;
        }

        $data = array();
        foreach ($entries as $entry_id => $v) {
            $delta = $v['hits'] - $v['prev_hits'];

            if ($delta == 0) { // no new hits, skip this
                continue;
            }

            if ($delta < 0) {
                trigger_error("$report_type report is not valid today (values are corrupted).");
                $res = false;
                continue;
            }

            $data[] = array($entry_id, $delta);
        }

        if (!empty($data)) {
            $res = ($this->insertReportRecords($report_id, $timestamp, $week_start, $data) && $res);
        }

        return $res;
    }


    function insertArticleHitReport($timestamp, $week_start) {
        return $this->_insertReportByDelta($timestamp, $week_start, 'article_hit', 1);
    }


    function insertFileHitReport($timestamp, $week_start) {
        return $this->_insertReportByDelta($timestamp, $week_start, 'file_hit', 2);
    }


    function insertNewsHitReport($timestamp, $week_start) {
        return $this->_insertReportByDelta($timestamp, $week_start, 'news_hit', 3);
    }

}

?>