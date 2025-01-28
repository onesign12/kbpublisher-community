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


class CommonReportChart
{    

    static function getChartBlock($type, $rows, $datasets, $view) {
        
        $chart_class = 'ReportChart_' . $type;
        $chart = new $chart_class;
        $chart->view = $view;
        
        return $chart->getChartBlock($rows, $datasets);
    }
    
    
    static function ajaxSetDefaultChartType($value) {
        
        $sm = new SettingModelUser(AuthPriv::getUserId());
        $sm->user_id = AuthPriv::getUserId();
        $sm->setSettings(array(213 => $value));

        $objResponse = new xajaxResponse();
    
        return $objResponse;    
    }

}


class ReportChart_canvas
{   
    
    var $view;
    var $chart_colors = array('777777', '35528F', '902131', '0e4a2b', 'eeaf19', 'cb01c9', '01cb27', '333333', '666666', '35528f'); 
    //var $chart_highlight_colors = array();
    
    
    function getChartBlock($rows, $datasets) {
        $tpl = new tplTemplatez(APP_PLUGIN_DIR . 'report/usage/template/block_chart_canvas.html');
            
        $line_chart_json = $this->getChartJson('line', $rows, $datasets);
        $tpl->tplAssign('line_chart_json', $line_chart_json);
        
        $bar_chart_json = $this->getChartJson('bar', $rows, $datasets);
        $tpl->tplAssign('bar_chart_json', $bar_chart_json);
        
        $pie_chart_json = $this->getChartPieJson($rows, $datasets);
        $tpl->tplAssign('pie_chart_json', json_encode($pie_chart_json));
        
        list($start_value, $max_value, $step_width) = $this->getChartSteps($rows, $datasets);
        
        $setting = SettingModel::getQuickUser(AuthPriv::getUserId(), 0, 'report_chart');
        $tpl->tplAssign('chart_type', $setting);
        $tpl->tplAssign('chart_' . $setting . '_checked', 'checked');
        
        $chunks = array_chunk(array_keys($pie_chart_json), 2);
        foreach ($chunks as $chunk) {
            $a = array();
            $a['pie1_id'] = $chunk[0];
            $a['pie1_title'] = (empty($this->view->chart_titles[$chunk[0]])) ? sprintf('-- (%s: %s)', $this->view->msg['id_msg'], $chunk[0]) : $this->view->chart_titles[$chunk[0]];
            
            if (!empty($chunk[1])) {
                $tpl->tplSetNeeded('pie_tr/pie_td2');
                $a['pie2_id'] = $chunk[1];
                $a['pie2_title'] = (empty($this->view->chart_titles[$chunk[1]])) ? sprintf('-- (%s: %s)', $this->view->msg['id_msg'], $chunk[1]) : $this->view->chart_titles[$chunk[1]];
            }
            
            $tpl->tplParse($a, 'pie_tr');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1); 
    }
    
    
    function getChartJson($chart_type, $rows, $entry_ids) {
        
        $range = $this->view->getRangeToGenerate();
        $period = $this->view->getPeriodToGenerate();
        
        $output_json = array();
        reset($this->chart_colors);
        
        
        /*$date_num = count($this->view->date_range);
        
        $date_labels = array_fill(0, $date_num, '');
        $date_labels[0] = $this->view->date_range[0]; 
        $date_labels[$date_num - 1] = $this->view->date_range[$date_num - 1];
        $s = round(($date_num - 2) / 3);
            
        if ($s == 0) {
            $s = 1;
        }
            
        $date_labels[$s] = @$this->view->date_range[$s];
        $date_labels[$s * 2] = @$this->view->date_range[$s * 2];*/
        
        $date_labels = $this->view->date_range;
        
        $output_json['labels'] = array();

        foreach ($date_labels as $date) {
            if ($range == 'daily') {
                $this->view->date_format = '%d %b, %Y';
            }

            if ($date) { 
                $date_formatted = $this->view->getFormatedDateReport($date, 'datetimesec');
                 
                // day of week for weekly range
                if ($period == 'this_week' ||$period == 'previous_week') {
                    $date_formatted .= ', ' . date('l', strtotime($date));
                }
                 
                $output_json['labels'][] =  $date_formatted; 
            } else {
                $output_json['labels'][] =  ''; 
            }
        }        
                                
        foreach ($this->view->date_range as $date) {
            foreach ($entry_ids as $v) {
                $val = (isset($rows[$date][$v])) ? $rows[$date][$v] : 0;
                $data[$v][] = $val;
            }
        }
        

        foreach ($entry_ids as $k => $v) {
            $dataset_labels[$v] = (empty($this->view->chart_titles[$v])) ? sprintf('-- (%s: %s)', $this->view->msg['id_msg'], $v) : $this->view->chart_titles[$v];
        }
        
        $output_json['datasets'] = array();
        
        switch ($chart_type) {
            
            case 'line':
                foreach ($data as $id => $elem) {
                    $line_data = array();
                    $line_data['label'] = $dataset_labels[$id];
                    $line_data['data'] = $elem;
                    
                    list($line_data['borderColor'], $line_data['hoverBackgroundColor']) = $this->getColors();
                    //$line_data['backgroundColor'] = '#eeeeee';
                    $line_data['borderWidth'] = '2';
                    $line_data['pointStyle'] = 'star';
                    $line_data['lineTension'] = 0.1;
                    //$line_data['pointColor'] = $line_data['strokeColor'];
                    
                    $output_json['datasets'][] = $line_data;
                }
                
                break;
                
                
            case 'bar':
                foreach ($data as $id => $elem) {
                    $line_data = array();
                    $line_data['label'] = $dataset_labels[$id];
                    $line_data['data'] = $elem;
                    
                    list($line_data['backgroundColor'], $line_data['hoverBackgroundColor']) = $this->getColors();
                    
                    /*$line_data['pointColor'] = $line_data['strokeColor'];
                    $line_data['backgroundColor'] = $line_data['strokeColor'];*/
                    
                    $output_json['datasets'][] = $line_data;
                }
                
                break;
            
        }

        return json_encode($output_json);
    }
    
    
    function getChartPieJson($rows, $entry_ids) {
        $output_json = array();
        
        foreach ($entry_ids as $pie_id) {
            reset($this->chart_colors);
            
            $title = (empty($this->view->chart_titles[$pie_id])) ? sprintf('-- (%s: %s)', $this->view->msg['id_msg'], $pie_id) : $this->view->chart_titles[$pie_id];
    
            $labels = array();
            $dataset = array();
            foreach ($this->view->date_range as $date) {
                if (empty($rows[$date][$pie_id])) {
                    continue;
                }
                
                $pie_data = array();
                
                $labels[] = $this->view->getFormatedDateReport($date);
                $dataset['data'][] = (int) $rows[$date][$pie_id];
                
                list($color, $highlight) = $this->getColors();
                
                $dataset['backgroundColor'][] = $color;
                $dataset['hoverBackgroundColor'][] = $highlight;
            }
            
            if (!empty($dataset['data']) && array_sum($dataset['data'])) { // no data - no pie
                $output_json[$pie_id] = array();
                $output_json[$pie_id]['labels'] = $labels;
                $output_json[$pie_id]['datasets'][0] = $dataset;
            }
        }
        
        return $output_json;
    }
    
    
    function getColors() {
        // $current_color = each($this->chart_colors); // deprecated
        $current_color = current($this->chart_colors);
        next($this->chart_colors);
        
        // echo '<pre>', print_r($current_color,1), '</pre>';
        // echo '<pre>', print_r($this->chart_colors,1), '</pre>';
        if (empty($current_color)) {
            // reset($this->chart_colors);
            // $current_color = each($this->chart_colors);
            $current_color = current($this->chart_colors);
            next($this->chart_colors);
        }
        
        $color = (string) $current_color;
        // $color = (string) $current_color['value'];
        //$highlight_color = $this->chart_highlight_colors[$current_color['key']];
        
        // calculating a highlight color
        $addend = 30;
        list($red, $green, $blue) = str_split($color, 2);
        
        $red = hexdec($red) + $addend;
        $green = hexdec($green) + $addend;
        $blue = hexdec($blue) + $addend;
        
        if ($red > 255) {
            $red = 255;
        }
        
        if ($green > 255) {
            $green = 255;
        }
        
        if ($blue > 255) {
            $blue = 255;
        }
        
        $highlight_color = sprintf('#%s%s%s', dechex($red), dechex($green), dechex($blue));
        $color = '#' . $color;
        
        return array($color, $highlight_color);
    }
    
    
    function getChartSteps($rows, $entry_ids) {
        foreach ($this->view->date_range as $row) {
            $max_vals[] = (isset($rows[$row])) ? max($rows[$row]) : 0;
            
            if (empty($rows[$row]) || count($entry_ids) > count($rows[$row])) {
                $min_vals[] = 0;
                
            } else {
                $min_vals[] = min($rows[$row]);
            }
        }
        
        $max_val = (empty($rows)) ? 1 : max($max_vals);    
        $min_val = (empty($rows)) ? 0 : min($min_vals);
                                     
        if ($max_val < 100) {
            $max_val = ($max_val % 2) ? $max_val + 1 : $max_val;
            $middle = ($max_val - $min_val) / 2;
            if ($middle % 2) {
                $middle ++;
            }
            
        } else {
            $dig_num = ceil(log10($max_val));
            $exp = pow(10, ($dig_num - 1));
            $max_val = ceil($max_val / $exp) * $exp;
            
            $middle = floor(($max_val - $min_val) / 2);
        }
        
        if ($min_val == 0 && $max_val == 0) {
            $max_val = 2;
            $middle = 1;
        }
        
        return array($min_val, $max_val, $middle);
    }
    
}

?>